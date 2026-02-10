<?php
namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use DateTime;
use DateTimeZone;
use App\Controllers\BaseController;


class OiController extends BaseController
{
    private const IDX_MY_CACHE_VER = 'v2'; // bump to v3, v4 when logic changes

    /**
     * Allowed symbols for this dashboard.
     * Uses ?symbol=... (default NIFTY).
     */
    private function getSymbol(): string
    {
        $sym = strtoupper(trim((string)($this->request->getGet('symbol') ?? 'NIFTY')));
        // Common aliases / cleanup
        $sym = str_replace(' ', '', $sym);
        if ($sym === 'BANKNIFTY' || $sym === 'BANKNIFTY50') return 'BANKNIFTY';
        if ($sym === 'NIFTY' || $sym === 'NIFTY50') return 'NIFTY';
        // Fallback
        return 'NIFTY';
    }
    private function getIndexEodLastUpdate(): array
    {
        $db = \Config\Database::connect();

        // last fetched row time (any symbol)
        $row = $db->table('oi_index_eod')
            ->select('MAX(fetched_at) AS max_fetched, MAX(trade_date) AS max_date')
            ->get()->getRowArray();

        $maxFetched = $row['max_fetched'] ?? null;
        $maxDate    = $row['max_date'] ?? null;

        $minsAgo = null;
        if ($maxFetched) {
            $minsAgo = (int) floor((time() - strtotime($maxFetched)) / 60);
        }

        // Health idea:
        // - OK if last trade_date is latest trading day (we’ll compute latest trading day)
        // - otherwise STALE
        $latestTradingDay = $this->latestTradingDayIST();

        $status = 'UNKNOWN';
        if ($maxDate && $latestTradingDay) {
            $status = ($maxDate >= $latestTradingDay) ? 'OK' : 'STALE';
        }

        return [
            'maxFetched' => $maxFetched,
            'minsAgo'    => $minsAgo,
            'maxDate'    => $maxDate,
            'latestTD'   => $latestTradingDay,
            'status'     => $status,
        ];
    }
    private function latestTradingDayIST(): ?string
    {
        $db = \Config\Database::connect();

        // Start from today (IST) and go backwards until we find a trading day
        $d = new \DateTime('now', new \DateTimeZone('Asia/Kolkata'));

        for ($i=0; $i<10; $i++) {
            $dateStr = $d->format('Y-m-d');
            $dow = (int)$d->format('N'); // 6=Sat,7=Sun

            $isWeekend = ($dow >= 6);

            // FO holidays (since NIFTY/BANKNIFTY are F&O relevant)
            $isHoliday = (bool)$db->table('nse_holidays')
                ->where('segment', 'FO')
                ->where('holiday_date', $dateStr)
                ->countAllResults();

            // If it's weekend/holiday, go back one day
            if ($isWeekend || $isHoliday) {
                $d->modify('-1 day');
                continue;
            }

            // If today is a trading day but market is not closed yet,
            // you may want latest trading day as "yesterday" until after ~15:45.
            // (Optional gate)
            $nowHM = (int)$d->format('Hi');
            if ($i === 0 && $nowHM < 1545) {
                $d->modify('-1 day');
                continue;
            }

            return $dateStr;
        }

        return null; // fallback
    }

    private function getIndexMoves(): array
    {
        $db = \Config\Database::connect();

        // Helper must be defined BEFORE using it
        $calc = function (?float $base, float $now): array {
            if ($base === null || $base == 0.0) {
                return ['pts' => null, 'pct' => null, 'dir' => 0];
            }
            $pts = $now - $base;
            $pct = ($pts / $base) * 100.0;
            $dir = ($pts > 0) ? 1 : (($pts < 0) ? -1 : 0);
            return ['pts' => $pts, 'pct' => $pct, 'dir' => $dir];
        };

        $symbols = ['NIFTY', 'BANKNIFTY'];
        $out = [];

        foreach ($symbols as $sym) {

            // latest row
            $latest = $db->table('oi_index_eod')
                ->where('symbol', $sym)
                ->orderBy('trade_date', 'DESC')
                ->limit(1)->get()->getRowArray();

            if (!$latest) {
                $out[$sym] = ['ok' => false];
                continue;
            }

            $latestDate  = $latest['trade_date'];
            $latestClose = (float)$latest['close'];

            // prev trading day close
            $prev = $db->table('oi_index_eod')
                ->select('close')
                ->where('symbol', $sym)
                ->where('trade_date <', $latestDate)
                ->orderBy('trade_date', 'DESC')
                ->limit(1)->get()->getRowArray();

            $prevClose = $prev ? (float)$prev['close'] : null;

            // previous week close
            $prevWeek = $db->query(
                "SELECT close FROM oi_index_eod
                WHERE symbol=? AND trade_date = (
                SELECT MAX(trade_date) FROM oi_index_eod
                WHERE symbol=? AND YEARWEEK(trade_date,1) < YEARWEEK(?,1)
                )",
                [$sym, $sym, $latestDate]
            )->getRowArray();

            $prevWeekClose = $prevWeek ? (float)$prevWeek['close'] : null;

            // previous month close
            $prevMonth = $db->query(
                "SELECT close FROM oi_index_eod
                WHERE symbol=? AND trade_date = (
                SELECT MAX(trade_date) FROM oi_index_eod
                WHERE symbol=? AND DATE_FORMAT(trade_date,'%Y-%m') < DATE_FORMAT(?, '%Y-%m')
                )",
                [$sym, $sym, $latestDate]
            )->getRowArray();

            $prevMonthClose = $prevMonth ? (float)$prevMonth['close'] : null;

            // ===== YTD (Year-to-Date) =====
            $yearStart = substr($latestDate, 0, 4) . '-01-01';

            // last close BEFORE year start
            $prevYearCloseRow = $db->table('oi_index_eod')
                ->select('close')
                ->where('symbol', $sym)
                ->where('trade_date <', $yearStart)
                ->orderBy('trade_date', 'DESC')
                ->limit(1)->get()->getRowArray();

            $ytdBase = $prevYearCloseRow ? (float)$prevYearCloseRow['close'] : null;

            // fallback: first close of the year
            if ($ytdBase === null) {
                $firstOfYear = $db->table('oi_index_eod')
                    ->select('close')
                    ->where('symbol', $sym)
                    ->where('trade_date >=', $yearStart)
                    ->orderBy('trade_date', 'ASC')
                    ->limit(1)->get()->getRowArray();

                $ytdBase = $firstOfYear ? (float)$firstOfYear['close'] : null;
            }

            // ===== 30D (rolling) =====
            $base30dRow = $db->query(
                "SELECT close FROM oi_index_eod
                WHERE symbol=? AND trade_date <= DATE_SUB(?, INTERVAL 30 DAY)
                ORDER BY trade_date DESC
                LIMIT 1",
                [$sym, $latestDate]
            )->getRowArray();

            $base30d = $base30dRow ? (float)$base30dRow['close'] : null;

            // fallback: oldest available close if very new DB
            if ($base30d === null) {
                $oldest = $db->table('oi_index_eod')
                    ->select('close')
                    ->where('symbol', $sym)
                    ->orderBy('trade_date', 'ASC')
                    ->limit(1)->get()->getRowArray();

                $base30d = $oldest ? (float)$oldest['close'] : null;
            }

            // ===== MTD (Month-to-Date) =====
            $monthStart = date('Y-m-01', strtotime($latestDate));

            $mtdBaseRow = $db->query(
                "SELECT close FROM oi_index_eod
                WHERE symbol=? AND trade_date >= ? AND trade_date <= ?
                ORDER BY trade_date ASC
                LIMIT 1",
                [$sym, $monthStart, $latestDate]
            )->getRowArray();

            $mtdBase = $mtdBaseRow ? (float)$mtdBaseRow['close'] : null;


            $out[$sym] = [
                'ok'    => true,
                'date'  => $latestDate,
                'close' => $latestClose,
                'day'   => $calc($prevClose, $latestClose),
                'week'  => $calc($prevWeekClose, $latestClose),
                'month' => $calc($prevMonthClose, $latestClose),
                'mom'   => $calc($prevMonthClose, $latestClose),

                 // ✅ NEW
                'd30'   => $calc($base30d, $latestClose),   // 30D rolling
                'mtd'   => $calc($mtdBase, $latestClose),   // Month-to-Date

                // keep YTD
                'year'  => $calc($ytdBase, $latestClose),
            ];
        }

        return $out;
    }
    public function purgeIndexCache()
    {
        // simple shared-secret style check (optional)
        // if (($this->request->getGet('k') ?? '') !== getenv('DASH_PURGE_KEY')) return $this->response->setStatusCode(403);

        $cache = \Config\Services::cache();
        $db = \Config\Database::connect();

        $syms = ['NIFTY','BANKNIFTY'];
        $deleted = [];

        foreach ($syms as $sym) {
            $latest = $db->table('oi_index_eod')->select('trade_date')
                ->where('symbol', $sym)->orderBy('trade_date','DESC')->limit(1)->get()->getRowArray();

            if (!$latest) continue;

            $latestDate = $latest['trade_date'];
            $key = 'idxmy_' . self::IDX_MY_CACHE_VER . '_' . $sym . '_' . str_replace('-', '', $latestDate);

            // Most cache drivers support delete(key). If not, no harm.
            $cache->delete($key);
            $deleted[] = $key;
        }

        // Redirect back with a small flag
        return redirect()->to(site_url('/oi?cache_purged=1'));
    }


    public function exportIndexEodYear(int $year)
    {
        $db = \Config\Database::connect();
        $from = "$year-01-01";
        $to   = "$year-12-31";

        $rows = $db->table('oi_index_eod')
            ->where('trade_date >=', $from)
            ->where('trade_date <=', $to)
            ->orderBy('symbol', 'ASC')
            ->orderBy('trade_date', 'ASC')
            ->get()->getResultArray();

        $filename = "index_eod_$year.csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['symbol','trade_date','open','high','low','close','prev_close','source','fetched_at']);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['symbol'], $r['trade_date'], $r['open'], $r['high'], $r['low'],
                $r['close'], $r['prev_close'], $r['source'], $r['fetched_at']
            ]);
        }

        fclose($out);
        exit;
    }
    private function getMonthYtdBlockAllSymbols(): array
    {
        $out = [];
        foreach (['NIFTY','BANKNIFTY'] as $sym) {
            $out[$sym] = $this->getMonthYtdMetricsCached($sym);
        }
        return $out;
    }
    private function getMonthYtdMetricsCached(string $symbol): array
    {
        $cache = \Config\Services::cache();
        $db = \Config\Database::connect();

        $latest = $db->table('oi_index_eod')->select('trade_date, close, fetched_at')
            ->where('symbol', $symbol)
            ->orderBy('trade_date', 'DESC')
            ->limit(1)->get()->getRowArray();

        if (!$latest) return ['ok' => false, 'symbol' => $symbol];

        $latestDate = $latest['trade_date'];
        $key = 'idxmy_' . self::IDX_MY_CACHE_VER . '_' . $symbol . '_' . str_replace('-', '', $latestDate);

        $t0 = microtime(true);
        $cached = $cache->get($key);

        if (is_array($cached)) {
            $cached['_cache'] = [
                'key'      => $key,
                'ver'      => self::IDX_MY_CACHE_VER,
                'hit'      => true,
                'saved_at' => $cached['_cache']['saved_at'] ?? null,
                'age_s'    => $cached['_cache']['saved_at'] ? (time() - (int)$cached['_cache']['saved_at']) : null,
                'ms'       => (int)round((microtime(true) - $t0) * 1000),
            ];
            return $cached;
        }

        $val = $this->computeMonthYtdMetrics($symbol, $latestDate, (float)$latest['close'], $latest['fetched_at'] ?? null);

        $val['_cache'] = [
            'key'      => $key,
            'ver'      => self::IDX_MY_CACHE_VER,
            'hit'      => false,
            'saved_at' => time(),
            'age_s'    => 0,
            'ms'       => (int)round((microtime(true) - $t0) * 1000),
        ];

        // 10 min cache
        $cache->save($key, $val, 600);

        return $val;
    }

    private function computeMonthYtdMetrics(string $symbol, string $latestDate, float $latestClose, ?string $latestFetchedAt): array
    {
        $db = \Config\Database::connect();

        // Period starts
        $monthStart = date('Y-m-01', strtotime($latestDate));
        $yearStart  = date('Y-01-01', strtotime($latestDate));
        $prevMonthStart = date('Y-m-01', strtotime("$monthStart -1 day"));
        $prevMonthEnd   = date('Y-m-t', strtotime($prevMonthStart));
        $prevYearStart  = date('Y-01-01', strtotime("$yearStart -1 day"));
        $prevYearEnd    = date('Y-12-31', strtotime("$yearStart -1 day"));

        // ===== helpers
        $calcMove = function(?float $base, float $now): array {
            if ($base === null || $base == 0.0) return ['pts'=>null,'pct'=>null,'dir'=>0];
            $pts = $now - $base;
            $pct = ($pts / $base) * 100.0;
            $dir = ($pts > 0) ? 1 : (($pts < 0) ? -1 : 0);
            return ['pts'=>$pts,'pct'=>$pct,'dir'=>$dir];
        };

        $rangePos = function(?float $lo, ?float $hi, float $now): ?float {
            if ($lo === null || $hi === null) return null;
            $den = $hi - $lo;
            if ($den == 0.0) return null;
            return (($now - $lo) / $den) * 100.0;
        };

        $fromHigh = fn(?float $hi, float $now): ?float => ($hi && $hi != 0.0) ? (($now - $hi) / $hi) * 100.0 : null;
        $fromLow  = fn(?float $lo, float $now): ?float => ($lo && $lo != 0.0) ? (($now - $lo) / $lo) * 100.0 : null;

        $trendHL = function(?float $curHi, ?float $curLo, ?float $prevHi, ?float $prevLo): string {
            if ($curHi === null || $curLo === null || $prevHi === null || $prevLo === null) return '—';
            $hh = ($curHi > $prevHi);
            $hl = ($curLo > $prevLo);
            $lh = ($curHi < $prevHi);
            $ll = ($curLo < $prevLo);

            if ($hh && $hl) return 'HH–HL';
            if ($lh && $ll) return 'LH–LL';
            return 'Mixed';
        };

        // ====== Period aggregates (Month + YTD) + previous period hi/lo + period open-close base
        // Month
        $mAgg = $db->query(
            "SELECT
            MIN(trade_date) AS first_d,
            MAX(high) AS hi,
            MIN(low)  AS lo
            FROM oi_index_eod
            WHERE symbol=? AND trade_date>=? AND trade_date<=?",
            [$symbol, $monthStart, $latestDate]
        )->getRowArray();

        $mFirstClose = $db->query(
            "SELECT close FROM oi_index_eod
            WHERE symbol=? AND trade_date>=?
            ORDER BY trade_date ASC LIMIT 1",
            [$symbol, $monthStart]
        )->getRowArray();
        $mBaseClose = $mFirstClose ? (float)$mFirstClose['close'] : null;

        $pmAgg = $db->query(
            "SELECT MAX(high) AS hi, MIN(low) AS lo
            FROM oi_index_eod
            WHERE symbol=? AND trade_date>=? AND trade_date<=?",
            [$symbol, $prevMonthStart, $prevMonthEnd]
        )->getRowArray();

        // YTD
        $yAgg = $db->query(
            "SELECT
            MIN(trade_date) AS first_d,
            MAX(high) AS hi,
            MIN(low)  AS lo
            FROM oi_index_eod
            WHERE symbol=? AND trade_date>=? AND trade_date<=?",
            [$symbol, $yearStart, $latestDate]
        )->getRowArray();

        // YTD base: last close before yearStart; fallback first close of year
        $prevYearCloseRow = $db->query(
            "SELECT close FROM oi_index_eod
            WHERE symbol=? AND trade_date < ?
            ORDER BY trade_date DESC LIMIT 1",
            [$symbol, $yearStart]
        )->getRowArray();
        $ytdBaseClose = $prevYearCloseRow ? (float)$prevYearCloseRow['close'] : null;

        if ($ytdBaseClose === null) {
            $yFirstClose = $db->query(
                "SELECT close FROM oi_index_eod
                WHERE symbol=? AND trade_date>=?
                ORDER BY trade_date ASC LIMIT 1",
                [$symbol, $yearStart]
            )->getRowArray();
            $ytdBaseClose = $yFirstClose ? (float)$yFirstClose['close'] : null;
        }

        $pyAgg = $db->query(
            "SELECT MAX(high) AS hi, MIN(low) AS lo
            FROM oi_index_eod
            WHERE symbol=? AND trade_date>=? AND trade_date<=?",
            [$symbol, $prevYearStart, $prevYearEnd]
        )->getRowArray();

        // ===== Average Daily Move (ADM) and Vol regime
        // Month ADM (from monthStart to latestDate)
        $mAdmRow = $db->query(
            "SELECT AVG(ABS((close - prev_close)/NULLIF(prev_close,0))*100) AS adm
            FROM oi_index_eod
            WHERE symbol=? AND trade_date>=? AND trade_date<=? AND prev_close IS NOT NULL",
            [$symbol, $monthStart, $latestDate]
        )->getRowArray();
        $mADM = isset($mAdmRow['adm']) ? (float)$mAdmRow['adm'] : null;

        // YTD ADM
        $yAdmRow = $db->query(
            "SELECT AVG(ABS((close - prev_close)/NULLIF(prev_close,0))*100) AS adm
            FROM oi_index_eod
            WHERE symbol=? AND trade_date>=? AND trade_date<=? AND prev_close IS NOT NULL",
            [$symbol, $yearStart, $latestDate]
        )->getRowArray();
        $yADM = isset($yAdmRow['adm']) ? (float)$yAdmRow['adm'] : null;

        $volClass = function(?float $adm): string {
            if ($adm === null) return '—';
            if ($adm < 0.35) return 'LOW';
            if ($adm <= 0.75) return 'NORMAL';
            return 'HIGH';
        };

        // ===== OI Context (optional)
        // Works only if you add oi_index_eod.total_oi column and populate it daily.
        $getOiAt = function(string $sym, string $d) use ($db): ?int {
            $r = $db->query("SELECT total_oi FROM oi_index_eod WHERE symbol=? AND trade_date=?", [$sym,$d])->getRowArray();
            return ($r && $r['total_oi'] !== null) ? (int)$r['total_oi'] : null;
        };
        $oiContext = function(?int $oiStart, ?int $oiEnd, int $priceDir) : string {
            if ($oiStart === null || $oiEnd === null) return '—';
            $oiDir = ($oiEnd > $oiStart) ? 1 : (($oiEnd < $oiStart) ? -1 : 0);
            if ($priceDir > 0 && $oiDir > 0) return 'Long Buildup';
            if ($priceDir > 0 && $oiDir < 0) return 'Short Covering';
            if ($priceDir < 0 && $oiDir > 0) return 'Short Buildup';
            if ($priceDir < 0 && $oiDir < 0) return 'Long Unwinding';
            return 'Neutral';
        };

        // Determine start dates actually used (first trading day in period)
        $mFirstD = $mAgg['first_d'] ?? null;
        $yFirstD = $yAgg['first_d'] ?? null;

        // Period OPENs (first trading day open)
        $mOpenRow = ($mFirstD) ? $db->query(
            "SELECT `open` FROM oi_index_eod WHERE symbol=? AND trade_date=? LIMIT 1",
            [$symbol, $mFirstD]
        )->getRowArray() : null;

        $yOpenRow = ($yFirstD) ? $db->query(
            "SELECT `open` FROM oi_index_eod WHERE symbol=? AND trade_date=? LIMIT 1",
            [$symbol, $yFirstD]
        )->getRowArray() : null;

        $mOpen = $mOpenRow ? (float)$mOpenRow['open'] : null;
        $yOpen = $yOpenRow ? (float)$yOpenRow['open'] : null;


        // Compute outputs
        $mHi = isset($mAgg['hi']) ? (float)$mAgg['hi'] : null;
        $mLo = isset($mAgg['lo']) ? (float)$mAgg['lo'] : null;
        $pmHi= isset($pmAgg['hi']) ? (float)$pmAgg['hi'] : null;
        $pmLo= isset($pmAgg['lo']) ? (float)$pmAgg['lo'] : null;

        $yHi = isset($yAgg['hi']) ? (float)$yAgg['hi'] : null;
        $yLo = isset($yAgg['lo']) ? (float)$yAgg['lo'] : null;
        $pyHi= isset($pyAgg['hi']) ? (float)$pyAgg['hi'] : null;
        $pyLo= isset($pyAgg['lo']) ? (float)$pyAgg['lo'] : null;

        $mMove = $calcMove($mBaseClose, $latestClose);
        $yMove = $calcMove($ytdBaseClose, $latestClose);

        // OI context start/end (optional)
        $mOiStart = ($mFirstD) ? $getOiAt($symbol, $mFirstD) : null;
        $mOiEnd   = $getOiAt($symbol, $latestDate);

        $yOiStart = ($yFirstD) ? $getOiAt($symbol, $yFirstD) : null;
        $yOiEnd   = $getOiAt($symbol, $latestDate);
        

        $month = [
            'o' => $mOpen,
            'h' => $mHi,
            'l' => $mLo,
            'c' => $latestClose,
            'start' => $mFirstD,
            'end'   => $latestDate,
            'move' => $mMove,
            'hi' => $mHi, 'lo' => $mLo,
            'pos' => $rangePos($mLo, $mHi, $latestClose),
            'fromHigh' => $fromHigh($mHi, $latestClose),
            'fromLow'  => $fromLow($mLo, $latestClose),
            'trend' => $trendHL($mHi, $mLo, $pmHi, $pmLo),
            'adm' => $mADM,
            'vol' => $volClass($mADM),
            'oi'  => $oiContext($mOiStart, $mOiEnd, $mMove['dir'] ?? 0),
        ];

        $ytd = [
            'o' => $yOpen,
            'h' => $yHi,
            'l' => $yLo,
            'c' => $latestClose,
            'start' => $yFirstD,
            'end'   => $latestDate,
            'move' => $yMove,
            'hi' => $yHi, 'lo' => $yLo,
            'pos' => $rangePos($yLo, $yHi, $latestClose),
            'fromHigh' => $fromHigh($yHi, $latestClose),
            'fromLow'  => $fromLow($yLo, $latestClose),
            'trend' => $trendHL($yHi, $yLo, $pyHi, $pyLo),
            'adm' => $yADM,
            'vol' => $volClass($yADM),
            'oi'  => $oiContext($yOiStart, $yOiEnd, $yMove['dir'] ?? 0),
        ];

        $mMovePts = $month['move']['pts'] ?? null;
        $yMovePts = $ytd['move']['pts'] ?? null;

        $mOcPts = (isset($month['o'], $month['c']) && $month['o'] !== null && $month['c'] !== null)
            ? ((float)$month['c'] - (float)$month['o'])
            : null;

        $yOcPts = (isset($ytd['o'], $ytd['c']) && $ytd['o'] !== null && $ytd['c'] !== null)
            ? ((float)$ytd['c'] - (float)$ytd['o'])
            : null;

        $this->logGapTrapIfExtreme($symbol, 'MTD', $mMovePts, $mOcPts, $latestClose, $latestDate);
        $this->logGapTrapIfExtreme($symbol, 'YTD', $yMovePts, $yOcPts, $latestClose, $latestDate);

        return [
            'ok' => true,
            'symbol' => $symbol,
            'date' => $latestDate,
            'close' => $latestClose,
            'fetched_at' => $latestFetchedAt,
            'month' => $month,
            'ytd'   => $ytd,
        ];


        /*
        return [
            'ok' => true,
            'symbol' => $symbol,
            'date' => $latestDate,
            'close' => $latestClose,
            'fetched_at' => $latestFetchedAt,

            'month' => [
                'o' => $mOpen,
                'h' => $mHi,
                'l' => $mLo,
                'c' => $latestClose,
                'start' => $mFirstD,          // first trading day in month
                'end'   => $latestDate,
                'move' => $mMove,
                'hi' => $mHi, 'lo' => $mLo,
                'pos' => $rangePos($mLo, $mHi, $latestClose),
                'fromHigh' => $fromHigh($mHi, $latestClose),
                'fromLow'  => $fromLow($mLo, $latestClose),
                'trend' => $trendHL($mHi, $mLo, $pmHi, $pmLo),
                'adm' => $mADM,
                'vol' => $volClass($mADM),
                'oi'  => $oiContext($mOiStart, $mOiEnd, $mMove['dir'] ?? 0),
            ],

            'ytd' => [
                'o' => $yOpen,
                'h' => $yHi,
                'l' => $yLo,
                'c' => $latestClose,
                'start' => $yFirstD,          // first trading day in year
                'end'   => $latestDate,
                'move' => $yMove,
                'hi' => $yHi, 'lo' => $yLo,
                'pos' => $rangePos($yLo, $yHi, $latestClose),
                'fromHigh' => $fromHigh($yHi, $latestClose),
                'fromLow'  => $fromLow($yLo, $latestClose),
                'trend' => $trendHL($yHi, $yLo, $pyHi, $pyLo),
                'adm' => $yADM,
                'vol' => $volClass($yADM),
                'oi'  => $oiContext($yOiStart, $yOiEnd, $yMove['dir'] ?? 0),
            ],
        ];
        */
    }
    private function logGapTrapIfExtreme(string $symbol, string $periodKey, ?float $movePts, ?float $ocPts, ?float $latestClose, string $asOfDate): void
    {
        if ($movePts === null || $ocPts === null || $latestClose === null || $latestClose == 0.0) return;

        $diffPts = abs($ocPts - $movePts);
        $diffPct = ($diffPts / $latestClose) * 100.0;

        // Thresholds (tune if needed)
        // NIFTY: 60 pts is meaningful; BANKNIFTY: 180 pts is meaningful
        $ptsThresh = ($symbol === 'BANKNIFTY') ? 180.0 : 60.0;
        $pctThresh = 0.25; // 0.25% of index

        if ($diffPts >= $ptsThresh || $diffPct >= $pctThresh) {
            log_message('warning', sprintf(
                'GAP_TRAP? sym=%s period=%s date=%s movePts=%.2f ocPts=%.2f diffPts=%.2f diffPct=%.3f%% close=%.2f',
                $symbol, $periodKey, $asOfDate, $movePts, $ocPts, $diffPts, $diffPct, $latestClose
            ));
        }
    }


    public function index()
    {
        $t0 = microtime(true);
        $db = \Config\Database::connect();

        $this->dbg('[OI_DASH] index() hit');

        // Latest DB insert (oi_snapshots.ts is UTC)
        $row = $db->table('oi_snapshots')->selectMax('ts')->get()->getRowArray();
        $lastFetched = $row['ts'] ?? null;

        $this->dbg('[OI_DASH] lastFetched from DB', [
            'lastFetched_utc' => $lastFetched,
            'minsAgo'         => $this->minsSince($lastFetched),
        ]);

        // Logs to infer cron heartbeat
        $fetchLog  = WRITEPATH . 'logs/oi_fetch.log';
        $enrichLog = WRITEPATH . 'logs/oi_enrich.log';
        //dd(WRITEPATH, is_writable(WRITEPATH.'logs'));exit;
        
        //log_message('info', "OIController::index - fetchLog: $fetchLog, enrichLog: $enrichLog");
        
        $health = [
            'db' => [
                'ist'     => $this->toIST($lastFetched),
                'minsAgo' => $this->minsSince($lastFetched),
            ],
            'fetch' => [
                'ist'     => $this->fileTimeIST($fetchLog),
                'minsAgo' => $this->fileMinsAgo($fetchLog),
            ],
            'enrich' => [
                'ist'     => $this->fileTimeIST($enrichLog),
                'minsAgo' => $this->fileMinsAgo($enrichLog),
            ],
        ];

        $this->dbg(
            sprintf('[OI_TRACK] response ready | elapsed_ms=%d', 
                (int) round((microtime(true) - $t0) * 1000)
            )
        );

        $holidayDateSetFO = $this->getHolidayDateSet('FO');
        $nextHolidayFO    = $this->nextHolidayInfo($holidayDateSetFO);
        $holidayRowsFO    = $this->getHolidayRows('FO');


        $indexMoves         = $this->getIndexMoves();          // Day/Week/Month + YTD
        $indexLastUpdate    = $this->getIndexEodLastUpdate();  // badge + health
        $indexMY            = $this->getMonthYtdBlockAllSymbols();

        // ------------------------------------------------------
        // Cache health badge (use NIFTY as reference)
        // ------------------------------------------------------
        $cacheBadge = [
            'hit' => null,
            'age' => null,
            'ms'  => null,
        ];

        if (!empty($indexMY ['NIFTY']['_cache'])) {
            $c = $indexMY ['NIFTY']['_cache'];

            $cacheBadge = [
                'hit' => $c['hit'] ?? null,
                'age' => $c['age_s'] ?? null,
                'ms'  => $c['ms'] ?? null,
                'ver' => $c['ver'] ?? null,
            ];
        }

        $indexMY_cache = $cacheBadge;
        $cache_purged = (int) ($this->request->getGet('cache_purged') ?? 0);


        return view('oi_dashboard', [
            'health'        => $health,
            'lastFetched'   => $lastFetched,

            // Holidays (F&O) - used for banner + bottom list + filtering daywise metrics
            'holidayDateSetFO' => $holidayDateSetFO,
            'nextHolidayFO'    => $nextHolidayFO,   // ['date' => 'YYYY-MM-DD', 'days' => int]
            'holidayRowsFO'    => $holidayRowsFO,
            'indexMoves'       => $indexMoves,
            'indexLastUpdate'  => $indexLastUpdate,
            'indexMY'          => $indexMY,
            'indexMY_cache'    => $indexMY_cache,
            'cache_purged'     => $cache_purged,
        ]);
    }

    public function track()
    {
        $t0 = microtime(true);
        // Symbol can be NIFTY or BANKNIFTY via ?symbol=
        // Keep the GET param in place for future expansion, but ignore it for now.
        $symbol = $this->getSymbol();
        $expiry = $this->request->getGet('expiry'); // optional
        //$lookbacks = [5,10,15,30]; // minutes
        // read custom minutes from ?lookbacks=5,10,15,30
        $def = [5,10,15,30];
        $lookbacksParam = $this->request->getGet('lookbacks');
        if ($lookbacksParam) {
            $lookbacks = array_values(array_unique(array_filter(array_map(function($x){
                $v = (int)trim($x);
                return ($v >= 1 && $v <= 120) ? $v : null; // guardrails (1–120m)
            }, explode(',', $lookbacksParam)))));
            if (!$lookbacks) { $lookbacks = $def; }
        } else {
            $lookbacks = $def;
        }
        sort($lookbacks); // ascending for consistent keys like oi_5m, oi_10m, ...
        // How many strikes around ATM to return (client dropdown): ?strikes=3|5|10|all
        $strikesRaw = strtolower(trim((string)($this->request->getGet('strikes') ?? '5')));
        if ($strikesRaw === 'all' || $strikesRaw === '999') {
            $limitStrikes = 99999;
        } else {
            $limitStrikes = (int)$strikesRaw;
            if ($limitStrikes < 1) $limitStrikes = 5;
            if ($limitStrikes > 50) $limitStrikes = 50;
        }

        $db = \Config\Database::connect();

        // Latest expiry + timestamp
        $latest = $db->query("
            SELECT expiry, MAX(ts) as ts
            FROM oi_snapshots
            WHERE symbol=?
            GROUP BY expiry
            ORDER BY ts DESC
            LIMIT 1
        ", [$symbol])->getRowArray();
        if (!$latest) {
            return $this->response->setJSON(['ok'=>false,'msg'=>'No data']);
        }
        $expiry = $expiry ?: $latest['expiry'];
        $latestTs = $latest['ts'];

        // Current OI snapshot
        $rows = $db->query("
            SELECT strike,opt,oi,chg_oi,vol,underlying
            FROM oi_snapshots
            WHERE symbol=? AND expiry=? AND ts=?
        ", [$symbol,$expiry,$latestTs])->getResultArray();

        $currCE=[]; $currPE=[]; $underlying=0;
        $ceTot = 0.0; $peTot = 0.0;
        foreach ($rows as $r){
            $underlying=(float)$r['underlying'];
            $oiVal = (float)($r['oi'] ?? 0);
            if ($r['opt']==='CE') { $currCE[$r['strike']]=$r; $ceTot += $oiVal; }
            if ($r['opt']==='PE') { $currPE[$r['strike']]=$r; $peTot += $oiVal; }
        }

        // ATM and strikes note:
        // - If strikes=all => return all strikes
        // - Else strikes=N => return symmetric window ATM ± N (2N+1 strikes)
        $step=50;
        $atm=(int)round($underlying/$step)*$step;

        $strikes = array_unique(array_merge(array_keys($currCE),array_keys($currPE)));
        sort($strikes);

        // Find nearest available strike to spot-rounded ATM (ATM strike may not exist in snapshots)
        $atmStrike = $atm;
        if ($strikes) {
            $best = $strikes[0];
            $bestD = abs($best - $atm);
            foreach ($strikes as $s) {
                $d = abs($s - $atm);
                if ($d < $bestD) { $bestD = $d; $best = $s; }
            }
            $atmStrike = $best;
        }

        if ($limitStrikes !== 99999 && $limitStrikes < 99999 && $strikes) {
            $idx = array_search($atmStrike, $strikes, true);
            if ($idx === false) { $idx = 0; }
            $from = max(0, $idx - $limitStrikes);
            $to   = min(count($strikes) - 1, $idx + $limitStrikes);
            $strikes = array_slice($strikes, $from, $to - $from + 1);
        }

        // Fetch historical OI at each lookback
        $data=[];
        foreach ($strikes as $st){
            $rowCE = $currCE[$st] ?? null;
            $rowPE = $currPE[$st] ?? null;
            $entry=['strike'=>$st];
            foreach(['CE'=>$rowCE,'PE'=>$rowPE] as $opt=>$cur){
                if(!$cur){ continue; }
                $snap=['cur_oi'=>(int)$cur['oi'],'cur_chg'=>(int)$cur['chg_oi']];
                // Volume (from oi_snapshots.vol)
                $curVol = array_key_exists('vol',$cur) ? (is_null($cur['vol']) ? null : (int)$cur['vol']) : null;
                $snap['cur_vol'] = $curVol;

                // Today's change in Volume: current vol - first vol of the same IST date (based on latestTs)
                $snap['cur_vol_chg'] = null;
                if ($curVol !== null) {
                    $baseVolRow = $db->query("
                        SELECT vol
                        FROM oi_snapshots
                        WHERE symbol=? AND expiry=? AND strike=? AND opt=?
                          AND DATE(CONVERT_TZ(ts,'+00:00','+05:30')) = DATE(CONVERT_TZ(?,'+00:00','+05:30'))
                        ORDER BY ts ASC
                        LIMIT 1
                    ", [$symbol,$expiry,$st,$opt,$latestTs])->getRowArray();
                    $baseVol = ($baseVolRow && isset($baseVolRow['vol'])) ? (int)$baseVolRow['vol'] : null;
                    if ($baseVol !== null) {
                        $snap['cur_vol_chg'] = $curVol - $baseVol;
                    }
                }

                foreach($lookbacks as $m){
                    $cutoff = (new \DateTime($latestTs,new \DateTimeZone('UTC')))
                                ->modify("-{$m} minutes")->format('Y-m-d H:i:s');
                    $prev = $db->query("
                        SELECT oi,chg_oi
                        FROM oi_snapshots
                        WHERE symbol=? AND expiry=? AND strike=? AND opt=? AND ts<=?
                        ORDER BY ts DESC LIMIT 1
                    ", [$symbol,$expiry,$st,$opt,$cutoff])->getRowArray();
                    $snap["oi_{$m}m"]  = $prev['oi']  ?? null;
                    $snap["chg_{$m}m"] = $prev['chg_oi'] ?? null;
                }
                $entry[$opt]=$snap;
            }
            $data[]=$entry;
        }

        $this->dbg(
            sprintf('[OI_TRACK] response ready | elapsed_ms=%d', 
                (int) round((microtime(true) - $t0) * 1000)
            )
        );

        return $this->response->setJSON([
            'ok'       => true,
            'symbol'   => $symbol,
            'expiry'   => $expiry,
            'latestTs' => $latestTs,
            'atm'      => $atm,
            'lookbacks'=> $lookbacks,
            'atm_strike' => $atmStrike ?? $atm,
            'strikes_param' => $strikesRaw,
            'expiry_totals' => [
                'now' => [
                    'ce_oi' => $ceTot,
                    'pe_oi' => $peTot,
                    'pcr'   => ($ceTot > 0 ? round($peTot / $ceTot, 2) : null),
                ],
            ],
            'rows'     => $data
        ]);
    }


    // ----------------- JSON: snapshot panel (expiry-aware) -----------------
    // GET /oi/json?symbol=NIFTY&window=10&expiry=YYYY-MM-DD (expiry optional)
    public function json()
    {
        $symbol = $this->getSymbol();
        $window = max(1, (int)($this->request->getGet('window') ?? 10)); // minutes
        $expiry = $this->request->getGet('expiry'); // optional YYYY-MM-DD

        $db = \Config\Database::connect();

        // --- latest snapshot timestamp per expiry for this symbol ---
        $latestRows = $db->query("
            SELECT s.expiry, MAX(s.ts) AS ts
            FROM oi_snapshots s
            WHERE s.symbol = ?
            GROUP BY s.expiry
            ORDER BY ts DESC
        ", [$symbol])->getResultArray();
        if (!$latestRows) {
            return $this->response->setJSON(['ok'=>false,'msg'=>'No data found']);
        }

        // pick expiry (nearest active if not provided)
        $chosenExpiry = $expiry ?: $latestRows[0]['expiry'];
        $latestTs = null;
        foreach ($latestRows as $r) {
            if ($r['expiry'] === $chosenExpiry) { $latestTs = $r['ts']; break; }
        }
        if (!$latestTs) { $chosenExpiry = $latestRows[0]['expiry']; $latestTs = $latestRows[0]['ts']; }

        // --- CURRENT snapshot rows for chosen expiry & ts (pull chg_oi too) ---
        $cur = $db->query("
            SELECT t.strike, t.opt, t.oi, t.chg_oi, t.underlying
            FROM oi_snapshots t
            WHERE t.symbol = ? AND t.expiry = ? AND t.ts = ?
        ", [$symbol, $chosenExpiry, $latestTs])->getResultArray();

        if (!$cur) {
            return $this->response->setJSON(['ok'=>false,'msg'=>'No rows at latest ts']);
        }

        $currCE = []; $currPE = [];
        $callNseDelta = []; $putNseDelta = []; // NSE official
        $underlying = 0.0;

        foreach ($cur as $r) {
            $underlying = (float)$r['underlying'];
            $st = (int)$r['strike'];
            $oi = (int)$r['oi'];
            $nse = (int)($r['chg_oi'] ?? 0); // NSE “CHNG IN OI”

            if ($r['opt'] === 'CE') {
                $currCE[$st]      = $oi;
                $callNseDelta[$st]= $nse;
            } elseif ($r['opt'] === 'PE') {
                $currPE[$st]      = $oi;
                $putNseDelta[$st] = $nse;
            }
        }

        // strike step & ATM
        $strikes = array_keys($currCE + $currPE);
        sort($strikes);
        $step = 50;
        for ($i=1; $i<count($strikes); $i++) { $d=$strikes[$i]-$strikes[$i-1]; if ($d>0){ $step=$d; break; } }
        $atm = (int)round($underlying/$step)*$step;

        // --- INTRA ΔOI (vs window start) ---
        $windowStartUtc = (new DateTime($latestTs, new DateTimeZone('UTC')))
                            ->modify("-{$window} minutes")->format('Y-m-d H:i:s');

        $winRows = $db->query("
            SELECT s.strike, s.opt, s.oi, s.ts
            FROM oi_snapshots s
            JOIN (
                SELECT strike, opt, MAX(ts) AS ts
                FROM oi_snapshots
                WHERE symbol = ? AND expiry = ? AND ts <= ?
                GROUP BY strike, opt
            ) x ON s.strike=x.strike AND s.opt=x.opt AND s.ts=x.ts
            WHERE s.symbol = ? AND s.expiry = ?
        ", [$symbol, $chosenExpiry, $windowStartUtc, $symbol, $chosenExpiry])->getResultArray();

        $winCE=[]; $winPE=[];
        foreach ($winRows as $r){
            $k=(int)$r['strike'];
            if ($r['opt']==='CE') $winCE[$k]=(int)$r['oi'];
            if ($r['opt']==='PE') $winPE[$k]=(int)$r['oi'];
        }

        $callDelta = []; $putDelta = [];
        foreach ($currCE as $k=>$v) { $callDelta[$k] = $v - ($winCE[$k] ?? 0); }
        foreach ($currPE as $k=>$v) { $putDelta[$k]  = $v - ($winPE[$k] ?? 0); }

        // --- DAY ΔOI (TRUE market-open baseline in IST) ---
        // Baseline = earliest snapshot at/after 09:15 IST on the trading day of latestTs (per strike+opt).
        // If baseline not found (holiday / before open / missing), we fall back to previous-day-last-snapshot logic.

        $tzIST = new DateTimeZone('Asia/Kolkata');
        $dtLatestUtc = new DateTime($latestTs, new DateTimeZone('UTC'));
        $dtLatestIst = clone $dtLatestUtc; $dtLatestIst->setTimezone($tzIST);

        // Market open for that trading day in IST (09:15)
        $openIst = new DateTime($dtLatestIst->format('Y-m-d') . ' 09:15:00', $tzIST);
        $openUtc = clone $openIst; $openUtc->setTimezone(new DateTimeZone('UTC'));
        $openUtcS = $openUtc->format('Y-m-d H:i:s');

        // Baseline at/after market open but not after latestTs (so dayΔ is stable)
        $oRows = $db->query("
            SELECT s.strike, s.opt, s.oi
            FROM oi_snapshots s
            JOIN (
                SELECT strike, opt, MIN(ts) AS ts
                FROM oi_snapshots
                WHERE symbol = ? AND expiry = ? AND ts >= ? AND ts <= ?
                GROUP BY strike, opt
            ) x ON s.strike=x.strike AND s.opt=x.opt AND s.ts=x.ts
            WHERE s.symbol = ? AND s.expiry = ?
        ", [$symbol, $chosenExpiry, $openUtcS, $latestTs, $symbol, $chosenExpiry])->getResultArray();

        $baseDayCE=[]; $baseDayPE=[];
        foreach ($oRows as $r){
            $k=(int)$r['strike'];
            if ($r['opt']==='CE') $baseDayCE[$k]=(int)$r['oi'];
            if ($r['opt']==='PE') $baseDayPE[$k]=(int)$r['oi'];
        }

        // Determine actual baseline timestamp used (it should be identical across strikes in a batch; we take the earliest).
        $baselineUsedUtc = null;
        if ($oRows) {
            $minTs = null;
            foreach ($oRows as $r) {
                if (!empty($r['ts'])) {
                    $t = $r['ts'];
                    if ($minTs === null || $t < $minTs) $minTs = $t;
                }
            }
            if ($minTs !== null) $baselineUsedUtc = $minTs;
        }

        // Default label = planned market open, but we'll override with actual baselineUsedUtc if available.
        $dayBaselineIst = $openIst->format('Y-m-d H:i:s') . ' IST';
        if ($baselineUsedUtc) {
            $dtBaseUtc = new DateTime($baselineUsedUtc, new DateTimeZone('UTC'));
            $dtBaseIst = clone $dtBaseUtc; $dtBaseIst->setTimezone($tzIST);
            $dayBaselineIst = $dtBaseIst->format('Y-m-d H:i:s') . ' IST';
        }

        // Fallback: previous-day last available snapshot (old behavior)
        if (!$oRows) {
            $todayIst = new DateTime($dtLatestIst->format('Y-m-d') . ' 00:00:00', $tzIST);
            $todayUtc = clone $todayIst; $todayUtc->setTimezone(new DateTimeZone('UTC'));
            $todayUtcS = $todayUtc->format('Y-m-d H:i:s');

            $yRows = $db->query("
                SELECT s.strike, s.opt, s.oi
                FROM oi_snapshots s
                JOIN (
                    SELECT strike, opt, MAX(ts) AS ts
                    FROM oi_snapshots
                    WHERE symbol = ? AND expiry = ? AND ts < ?
                    GROUP BY strike, opt
                ) x ON s.strike=x.strike AND s.opt=x.opt AND s.ts=x.ts
                WHERE s.symbol = ? AND s.expiry = ?
            ", [$symbol, $chosenExpiry, $todayUtcS, $symbol, $chosenExpiry])->getResultArray();

            foreach ($yRows as $r){
                $k=(int)$r['strike'];
                if ($r['opt']==='CE') $baseDayCE[$k]=(int)$r['oi'];
                if ($r['opt']==='PE') $baseDayPE[$k]=(int)$r['oi'];
            }
        }

        $callDayDelta = []; $putDayDelta = [];
        foreach ($currCE as $k=>$v) { $callDayDelta[$k] = $v - ($baseDayCE[$k] ?? 0); }
        foreach ($currPE as $k=>$v) { $putDayDelta[$k]  = $v - ($baseDayPE[$k] ?? 0); }
// --- Tops, PCR, Bias (unchanged) ---
        arsort($currCE); $topCalls = array_slice($currCE, 0, 6, true);
        arsort($currPE); $topPuts  = array_slice($currPE,  0, 6, true);

        $totCall = array_sum($currCE);
        $totPut  = array_sum($currPE);
        $pcr     = $totCall>0 ? round($totPut/$totCall, 2) : null;
        $bias    = $pcr===null ? null : ($pcr>1.05 ? 'Bullish' : ($pcr<0.95 ? 'Bearish' : 'Range-bound'));

        return $this->response->setJSON([
            'ok'        => true,
            'symbol'    => $symbol,
            'expiry'    => $chosenExpiry,
            'ts'        => $latestTs,        // UTC
            'price'     => $underlying,
            'window'    => $window,
            'atm'       => $atm,
            'dayBaselineIst' => $dayBaselineIst,
            'pcr'       => $pcr,
            // Full-expiry totals (all strikes) - used for NSE OC footer + PCR
            'expiry_totals' => [
                'now' => [
                    'ce_oi' => (float)($totCall ?? 0),
                    'pe_oi' => (float)($totPut ?? 0),
                    'ce_vol' => 0,
                    'pe_vol' => 0,
                    'pcr'   => $pcr,
                ],
                'pcr' => $pcr,
            ],
            'bias'      => $bias,

            'topCalls'  => $topCalls,
            'topPuts'   => $topPuts,

            // INTRA
            'callDelta'    => $callDelta,
            'putDelta'     => $putDelta,

            // DAY (computed)
            'callDayDelta' => $callDayDelta,
            'putDayDelta'  => $putDayDelta,

            // DAY (NSE official)
            'callNseDelta' => $callNseDelta,
            'putNseDelta'  => $putNseDelta,
        ], ResponseInterface::HTTP_OK);
    }

    // ----------------- JSON: PCR trend & classifications -----------------
    // GET /oi/metrics?symbol=NIFTY&limit=12
    public function metrics()
    {
        $symbol = $this->getSymbol();
        $want   = (int)($this->request->getGet('limit') ?? 12);
        $want   = max(1, min(60, $want));

        # Fetch extra rows so that after removing holidays we can still return $want rows
        $fetch = min(200, max($want * 4, $want));

        $m = new \App\Models\OiMetricsModel();
        $rows = $m->lastN($symbol, $fetch);

        # Filter out NSE holidays (Cash Market by default)
        $holidaySet = $this->getHolidayDateSet('FO');
        if ($holidaySet) {
            $rows = array_values(array_filter($rows, function ($r) use ($holidaySet) {
                $ts = $r['ts'] ?? null;
                if (!$ts) return true;
                $d = $this->dateISTFromTs($ts);
                return !isset($holidaySet[$d]);
            }));
        }

        usort($rows, function ($a, $b) {
            return strcmp((string)($b['ts'] ?? ''), (string)($a['ts'] ?? ''));
        });
        $rows = array_slice($rows, 0, $want);

        return $this->response->setJSON([
            'ok' => true, 'symbol' => $symbol, 'rows' => $rows]);
    }

    // ----------------- JSON: Breakout + Swing High/Low (15m) -----------------
    // GET /oi/daywise?symbol=NIFTY&days=7
     
    // GET /oi/signals?symbol=NIFTY
    public function signals()
    {
        $symbol = $this->getSymbol();
        $db = \Config\Database::connect();

        // Today's IST range -> query UTC
        $tzIST = new \DateTimeZone('Asia/Kolkata');
        $tzUTC = new \DateTimeZone('UTC');
        $startIST = new \DateTime('today', $tzIST);
        $endIST   = (clone $startIST)->modify('+1 day');

        $startUTC = (clone $startIST)->setTimezone($tzUTC)->format('Y-m-d H:i:s');
        $endUTC   = (clone $endIST )->setTimezone($tzUTC)->format('Y-m-d H:i:s');

        $rows = $db->query(
            "SELECT ts, underlying FROM oi_snapshots
             WHERE symbol=? AND ts>=? AND ts<? ORDER BY ts ASC",
            [$symbol, $startUTC, $endUTC]
        )->getResultArray();

        if (!$rows) {
            return $this->response->setJSON(['ok'=>true,'symbol'=>$symbol,'signals'=>[],'note'=>'no data today']);
        }

        // Build 15-min candles
        $candles = [];
        foreach ($rows as $r) {
            $t = strtotime($r['ts']); // UTC epoch
            $b = (int) (floor($t / 900) * 900); // 15-min bucket
            $p = (float)$r['underlying'];

            if (!isset($candles[$b])) {
                $candles[$b] = ['t'=>$b,'o'=>$p,'h'=>$p,'l'=>$p,'c'=>$p,'first_ts'=>$t,'last_ts'=>$t];
            } else {
                $c = &$candles[$b];
                if ($t < $c['first_ts']) { $c['o'] = $p; $c['first_ts'] = $t; }
                if ($t > $c['last_ts'])  { $c['c'] = $p; $c['last_ts']  = $t; }
                if ($p > $c['h']) $c['h'] = $p;
                if ($p < $c['l']) $c['l'] = $p;
                unset($c);
            }
        }
        ksort($candles);
        $candles = array_values($candles);

        // Breakout detection
        $N = 10; $breakouts = [];
        for ($i=$N; $i<count($candles); $i++) {
            $win = array_slice($candles, $i-$N, $N);
            $maxHigh = max(array_column($win, 'h'));
            $body    = abs($candles[$i]['c'] - $candles[$i]['o']);
            $avgBody = array_sum(array_map(fn($k)=>abs($k['c']-$k['o']), $win)) / $N;

            if ($candles[$i]['c'] > $maxHigh && $body >= 1.5 * $avgBody) {
                $dt = new \DateTime('@'.$candles[$i]['t']); // UTC
                $dt->setTimezone($tzIST);
                $breakouts[] = [
                    'type'     => 'Bullish Breakout',
                    'time_ist' => $dt->format('Y-m-d H:i') . ' IST',
                    'close'    => round($candles[$i]['c'], 2),
                    'index'    => $i,
                ];
            }
        }
        $lastBreakout = $breakouts ? end($breakouts) : null;

        // Swing High/Low (5-bar fractals)
        $swHigh = $swLow = null;
        $n = count($candles);
        for ($i = 2; $i <= $n-3; $i++) {
            $h = $candles[$i]['h']; $l = $candles[$i]['l'];
            $isHigh = ($h > $candles[$i-1]['h'] && $h > $candles[$i-2]['h'] &&
                       $h >= $candles[$i+1]['h'] && $h >= $candles[$i+2]['h']);
            $isLow  = ($l < $candles[$i-1]['l'] && $l < $candles[$i-2]['l'] &&
                       $l <= $candles[$i+1]['l'] && $l <= $candles[$i+2]['l']);
            if ($isHigh) $swHigh = $i;
            if ($isLow)  $swLow  = $i;
        }

        $fmt = function($epochUTC) use ($tzIST) {
            $d = new \DateTime('@'.$epochUTC); $d->setTimezone($tzIST);
            return $d->format('Y-m-d H:i') . ' IST';
        };

        $lastSwingHigh = $swHigh !== null ? [
            'time_ist' => $fmt($candles[$swHigh]['t']),
            'price'    => round($candles[$swHigh]['h'], 2),
            'index'    => $swHigh,
        ] : null;

        $lastSwingLow = $swLow !== null ? [
            'time_ist' => $fmt($candles[$swLow]['t']),
            'price'    => round($candles[$swLow]['l'], 2),
            'index'    => $swLow,
        ] : null;

        return $this->response->setJSON([
            'ok'          => true,
            'symbol'      => $symbol,
            'lookback'    => $N,
            'last'        => $lastBreakout,
            'signals'     => $breakouts,
            'swing_high'  => $lastSwingHigh,
            'swing_low'   => $lastSwingLow,
        ]);
    }

    // ----------------- JSON: expiries list -----------------
    public function expiries()
    {
        $symbol = $this->getSymbol();
        $db = \Config\Database::connect();

        // Prefer current/future range; fallback to last 5
        $rows = $db->query(
            "SELECT DISTINCT expiry
             FROM oi_snapshots
             WHERE symbol=? AND expiry >= CURDATE() - INTERVAL 14 DAY
             ORDER BY expiry ASC",
            [$symbol]
        )->getResultArray();

        if (!$rows) {
            $rows = $db->query(
                "SELECT DISTINCT expiry
                 FROM oi_snapshots
                 WHERE symbol=?
                 ORDER BY expiry DESC
                 LIMIT 5",
                [$symbol]
            )->getResultArray();
            $rows = array_reverse($rows);
        }

        $exp = array_values(array_map(fn($r)=>$r['expiry'], $rows));
        return $this->response->setJSON(['ok'=>true, 'symbol'=>$symbol, 'expiries'=>$exp]);
    }

    // ----------------- JSON: health -----------------
    public function healthz()
    {
        $db = \Config\Database::connect();
        $ok = true; $err = null;
        try { $db->query('SELECT 1'); } catch (\Throwable $e) { $ok=false; $err=$e->getMessage(); }

        $row = $db->table('oi_snapshots')->selectMax('ts')->get()->getRowArray();
        $last = $row['ts'] ?? null;

        $fetchLog  = WRITEPATH . 'logs/oi_fetch.log';
        $enrichLog = WRITEPATH . 'logs/oi_enrich.log';

        $age = function(?string $utcTs){
            if (!$utcTs) return null;
            $from = new \DateTime($utcTs,new \DateTimeZone('UTC'));
            $now  = new \DateTime('now',new \DateTimeZone('UTC'));
            return (int) floor(($now->getTimestamp() - $from->getTimestamp())/60);
        };
        $fileAge = function(string $p){
            if (!is_file($p)) return null;
            return (int) floor((time() - filemtime($p))/60);
        };

        return $this->response->setJSON([
            'db_ok'               => $ok,
            'error'               => $err,
            'last_insert_ts_utc'  => $last,
            'last_insert_age_min' => $age($last),
            'fetch_log_age_min'   => $fileAge($fetchLog),
            'enrich_log_age_min'  => $fileAge($enrichLog),
        ]);
    }

    // ================= Helpers =================
    private function toIST(?string $utcTs): ?string
    {
        if (!$utcTs) return null;
        try {
            $dt = new \DateTime($utcTs, new \DateTimeZone('UTC'));
            $dt->setTimezone(new \DateTimeZone('Asia/Kolkata'));
            return $dt->format('Y-m-d H:i:s') . ' IST';
        } catch (\Throwable $e) { return null; }
    }

    private function minsSince(?string $utcTs): ?int
    {
        if (!$utcTs) return null;
        try {
            $from = new \DateTime($utcTs, new \DateTimeZone('UTC'));
            $now  = new \DateTime('now', new \DateTimeZone('UTC'));
            return (int) floor(($now->getTimestamp() - $from->getTimestamp()) / 60);
        } catch (\Throwable $e) { return null; }
    }

    private function fileTimeIST(string $path): ?string
    {
        if (!is_file($path)) return null;
        $ts = @filemtime($path); if (!$ts) return null;
        $dt = new \DateTime('@'.$ts); $dt->setTimezone(new \DateTimeZone('Asia/Kolkata'));
        return $dt->format('Y-m-d H:i:s') . ' IST';
    }

    private function fileMinsAgo(string $path): ?int
    {
        if (!is_file($path)) return null;
        $ts = @filemtime($path); if (!$ts) return null;
        return (int) floor((time() - $ts) / 60);
    }

    public function fetchTest()
    {
        $symbol = $this->getSymbol();
        $client = new \App\Services\NseClient();
        try {
            $oc = $client->fetchOptionChain($symbol);
            return $this->response->setJSON([
                'ok' => true,
                'symbol' => $symbol,
                'expiries' => \App\Services\NseClient::expiryDates($oc),
                'sample' => array_slice($oc['records']['data'] ?? [], 0, 2),
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['ok'=>false,'msg'=>$e->getMessage()]);
        }
    }

    private function dbg(string $tag, array $ctx = []): void
    {
        // keep logs small & consistent
        $ctx['route'] = $this->request->getPath();
        $ctx['ip']    = $this->request->getIPAddress();
        log_message('debug', $tag, $ctx);
    }

    /**
     * Daywise OI/Price behaviour (IST days; snapshots.ts stored in UTC)
     * GET /oi/daywise?symbol=NIFTY&days=7&mode=front|all
     */

    public function daywise()
    {
        $symbol = $this->getSymbol();
        $days   = (int)($this->request->getGet('days') ?? 7);
        
        $segment = (string)($this->request->getGet('segment') ?? 'FO');
        $holidaySet = $this->getHolidayDateSet($segment);
        $fetchLimit = max($days * 3, $days + 10);
$mode   = strtolower(trim($this->request->getGet('mode') ?? 'front')); // front | all

        if ($days < 3) $days = 3;
        if ($days > 30) $days = 30;
        if (!in_array($mode, ['front','all'], true)) $mode = 'front';

        $db = \Config\Database::connect();

        // ts is stored as UTC in DB. Group by IST day, take max(ts) per day.
        $dailySql = "
            SELECT DATE(CONVERT_TZ(ts,'+00:00','+05:30')) AS d, MAX(ts) AS mts
            FROM oi_snapshots
            WHERE symbol = ?
            GROUP BY DATE(CONVERT_TZ(ts,'+00:00','+05:30'))
            ORDER BY d DESC
            LIMIT {$fetchLimit}
        ";
        $daily = $db->query($dailySql, [$symbol])->getResultArray();

        

        // Filter out weekends + NSE holidays (some feeds may still write a snapshot late night IST)
        $filtered = [];
        foreach ($daily as $row) {
            $d = $row['d'] ?? null;
            if (!$d) { continue; }

            // Weekend check (IST date)
            $wk = date('N', strtotime($d)); // 6=Sat,7=Sun
            if ($wk >= 6) { continue; }

            // Holiday check (by segment)
            if (isset($holidaySet[$d])) { continue; }

            $filtered[] = $row;
            if (count($filtered) >= $days) { break; }
        }
        $daily = $filtered;

$out = [];
        foreach ($daily as $row) {
            $d   = $row['d'];
            $mts = $row['mts'];

            // Underlying at that ts
            $u = $db->table('oi_snapshots')
                ->selectMax('underlying', 'u')
                ->where(['symbol'=>$symbol, 'ts'=>$mts])
                ->get()->getRowArray();
            $close = $u['u'] ?? null;

            $expiry = null;
            if ($mode === 'front') {
                $e = $db->table('oi_snapshots')
                    ->selectMin('expiry', 'e')
                    ->where(['symbol'=>$symbol, 'ts'=>$mts])
                    ->get()->getRowArray();
                $expiry = $e['e'] ?? null;
            }

            $qb = $db->table('oi_snapshots')
                ->select("opt, SUM(oi) AS s, SUM(COALESCE(vol,0)) AS v")
                ->where(['symbol'=>$symbol, 'ts'=>$mts])
                ->whereIn('opt', ['CE','PE'])
                ->groupBy('opt');
            if ($expiry) $qb->where('expiry', $expiry);
            $agg = $qb->get()->getResultArray();

            $ce = 0; $pe = 0; $ceVol = 0; $peVol = 0;
            foreach ($agg as $a) {
                if (($a['opt'] ?? '') === 'CE') { $ce = (int)$a['s']; $ceVol = (int)($a['v'] ?? 0); }
                if (($a['opt'] ?? '') === 'PE') { $pe = (int)$a['s']; $peVol = (int)($a['v'] ?? 0); }
            }
            $totalOi = $ce + $pe;
            $totalVol = $ceVol + $peVol;
            $pcr = ($ce > 0) ? round($pe / $ce, 2) : null;

            $out[] = [
                'day'      => $d,
                'ts'       => $mts,
                'expiry'   => $expiry,
                'close'    => ($close === null ? null : round((float)$close, 2)),
                'ce_oi'    => $ce,
                'pe_oi'    => $pe,
                'ce_vol'   => $ceVol,
                'pe_vol'   => $peVol,
                'total_vol'=> $totalVol,
                'total_oi' => $totalOi,
                'pcr'      => $pcr,
            ];
        }

        // Compute day-over-day diffs + behavior/signal
        for ($i=0; $i<count($out); $i++) {
            $prev = $out[$i+1] ?? null; // because sorted DESC
            $dPrice = null; $dOi = null;
            if ($prev && $out[$i]['close'] !== null && $prev['close'] !== null) {
                $dPrice = round($out[$i]['close'] - $prev['close'], 2);
            }
            if ($prev) {
                $dOi = $out[$i]['total_oi'] - ($prev['total_oi'] ?? 0);
                $out[$i]['d_vol'] = ($out[$i]['total_vol'] ?? 0) - ($prev['total_vol'] ?? 0);
            } else {
                $out[$i]['d_vol'] = null;
            }

            // 1) write diffs + classification onto the row FIRST
            $cls = $this->classifyDaywise($dPrice, $dOi);
            $out[$i]['d_price']  = $dPrice;
            $out[$i]['d_oi']     = $dOi;
            $out[$i]['behavior'] = $cls['behavior'];
            $out[$i]['signal']   = $cls['signal'];

            // 2) then compute strength (uses d_price/d_oi/d_vol/behavior)
            $strength = $this->strengthDaywiseRelative($out, $i);
            $out[$i]['strength'] = $strength['label'];
            $out[$i]['strength_score'] = $strength['score'];
            $out[$i]['oi_ratio']  = $strength['oi_ratio'];
            $out[$i]['vol_ratio'] = $strength['vol_ratio'];
        }

        return $this->response->setJSON([
            'ok'     => true,
            'symbol' => $symbol,
            'mode'   => $mode,
            'rows'   => $out,
        ]);
    }

    /**
     * Intraday addons summary for decision widgets.
     * GET /oi/intraday?symbol=NIFTY&mode=all
     * - Finds today's IST bucket (00:00..23:59 IST) using CONVERT_TZ on ts (stored UTC)
     * - Returns start snapshot totals and current snapshot totals (OI/Vol/PCR/Price)
     * - Also returns strike-shift from oi_metrics (latest vs ~60 minutes ago)
     * - Includes daywise baseline avg(|ΔOI|) and avg(|ΔVol|) from last 5 comparable days
     */
    public function intraday()
    {
        $symbol = $this->getSymbol();
        $mode   = strtolower(trim($this->request->getGet('mode') ?? 'all')); // all | front
        if ($mode !== 'front' && $mode !== 'all') $mode = 'all';

        $db = \Config\Database::connect();

        // Today's IST date
        $ist = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        $todayIst = $ist->format('Y-m-d');

        // Start/End ts (UTC) for today bucket
        $r = $db->query("
            SELECT MIN(ts) AS min_ts, MAX(ts) AS max_ts
            FROM oi_snapshots
            WHERE symbol=?
              AND DATE(CONVERT_TZ(ts,'+00:00','+05:30'))=?
        ", [$symbol, $todayIst])->getRowArray();

        $minTs = $r['min_ts'] ?? null;
        $maxTs = $r['max_ts'] ?? null;

        if (!$minTs || !$maxTs) {
            return $this->response->setJSON([
                'ok' => true,
                'symbol' => $symbol,
                'mode' => $mode,
                'today_ist' => $todayIst,
                'start' => null,
                'current' => null,
                'strike_shift' => null,
                'baseline' => null,
            ]);
        }

        // For front mode, lock expiry based on CURRENT snapshot
        $expiry = null;
        if ($mode === 'front') {
            $er = $db->query("SELECT MIN(expiry) AS expiry FROM oi_snapshots WHERE symbol=? AND ts=?", [$symbol, $maxTs])->getRowArray();
            $expiry = $er['expiry'] ?? null;
        }

        $start = $this->snapshotTotals($db, $symbol, $minTs, $mode, $expiry);
        $cur   = $this->snapshotTotals($db, $symbol, $maxTs, $mode, $expiry);

        // Deltas
        if ($start && $cur) {
            $cur['d_price'] = round(($cur['close'] ?? 0) - ($start['close'] ?? 0), 2);
            $cur['d_oi']    = (int)(($cur['total_oi'] ?? 0) - ($start['total_oi'] ?? 0));
            $cur['d_vol']   = (int)(($cur['total_vol'] ?? 0) - ($start['total_vol'] ?? 0));
        }

        // Strike shift from oi_metrics (latest vs 60m ago)
        $shift = $this->strikeShiftFromMetrics($db, $symbol);

        // Baseline from last 5 daywise comparable rows (abs deltas)
        $baseline = $this->daywiseBaseline($db, $symbol, 10, $mode); // scan last 10 days to collect 5 points

        return $this->response->setJSON([
            'ok' => true,
            'symbol' => $symbol,
            'mode' => $mode,
            'today_ist' => $todayIst,
            'start' => $start,
            'current' => $cur,
            'strike_shift' => $shift,
            'baseline' => $baseline,
        ]);
    }

    private function snapshotTotals($db, string $symbol, string $ts, string $mode, ?string $expiry = null): ?array
    {
        if ($mode === 'front') {
            if (!$expiry) return null;
            $row = $db->query(
                "SELECT
                  MAX(underlying) AS close_price,
                  SUM(CASE WHEN opt='CE' THEN oi ELSE 0 END) AS ce_oi,
                  SUM(CASE WHEN opt='PE' THEN oi ELSE 0 END) AS pe_oi,
                  SUM(CASE WHEN opt='CE' THEN vol ELSE 0 END) AS ce_vol,
                  SUM(CASE WHEN opt='PE' THEN vol ELSE 0 END) AS pe_vol
                FROM oi_snapshots
                WHERE symbol=? AND ts=? AND expiry=?
            ", [$symbol, $ts, $expiry])->getRowArray();
        } else {
            $row = $db->query(
                "SELECT
                  MAX(underlying) AS close_price,
                  SUM(CASE WHEN opt='CE' THEN oi ELSE 0 END) AS ce_oi,
                  SUM(CASE WHEN opt='PE' THEN oi ELSE 0 END) AS pe_oi,
                  SUM(CASE WHEN opt='CE' THEN vol ELSE 0 END) AS ce_vol,
                  SUM(CASE WHEN opt='PE' THEN vol ELSE 0 END) AS pe_vol
                FROM oi_snapshots
                WHERE symbol=? AND ts=?
            ", [$symbol, $ts])->getRowArray();
        }

        if (!$row) return null;
        $ceOi  = (int)($row['ce_oi'] ?? 0);
        $peOi  = (int)($row['pe_oi'] ?? 0);
        $ceVol = (int)($row['ce_vol'] ?? 0);
        $peVol = (int)($row['pe_vol'] ?? 0);
        $pcr = ($ceOi > 0) ? round(($peOi / $ceOi), 2) : null;

        return [
            'ts'        => $ts,
            'close'     => (float)($row['close_price'] ?? 0),
            'ce_oi'     => $ceOi,
            'pe_oi'     => $peOi,
            'total_oi'  => $ceOi + $peOi,
            'ce_vol'    => $ceVol,
            'pe_vol'    => $peVol,
            'total_vol' => $ceVol + $peVol,
            'pcr'       => $pcr,
        ];
    }

    private function strikeShiftFromMetrics($db, string $symbol): ?array
    {
        $latest = $db->query("SELECT * FROM oi_metrics WHERE symbol=? ORDER BY ts DESC LIMIT 1", [$symbol])->getRowArray();
        if (!$latest || empty($latest['ts'])) return null;

        $prev = $db->query(
            "SELECT * FROM oi_metrics
            WHERE symbol=? AND ts <= (SELECT DATE_SUB(?, INTERVAL 60 MINUTE))
            ORDER BY ts DESC
            LIMIT 1
        ", [$symbol, $latest['ts']])->getRowArray();

        return [
            'ts_now' => $latest['ts'] ?? null,
            'ts_prev'=> $prev['ts'] ?? null,
            'bias'   => $latest['bias'] ?? null,
            'pcr'    => isset($latest['pcr']) ? (float)$latest['pcr'] : null,
            'call_now' => isset($latest['top_call_strike']) ? (int)$latest['top_call_strike'] : null,
            'put_now'  => isset($latest['top_put_strike']) ? (int)$latest['top_put_strike'] : null,
            'call_prev'=> isset($prev['top_call_strike']) ? (int)$prev['top_call_strike'] : null,
            'put_prev' => isset($prev['top_put_strike']) ? (int)$prev['top_put_strike'] : null,
        ];
    }

    private function daywiseBaseline($db, string $symbol, int $scanDays, string $mode): ?array
    {
        // collect last N IST days
        $dayRows = $db->query(
            "SELECT DATE(CONVERT_TZ(ts,'+00:00','+05:30')) AS day_ist, MAX(ts) AS max_ts
            FROM oi_snapshots
            WHERE symbol=?
            GROUP BY day_ist
            ORDER BY day_ist DESC
            LIMIT ?
        ", [$symbol, $scanDays])->getResultArray();
        if (!$dayRows) return null;

        $tmp = [];
        foreach ($dayRows as $dr) {
            $ts = $dr['max_ts'] ?? null;
            if (!$ts) continue;
            $expiry = null;
            if ($mode === 'front') {
                $er = $db->query("SELECT MIN(expiry) AS expiry FROM oi_snapshots WHERE symbol=? AND ts=?", [$symbol, $ts])->getRowArray();
                $expiry = $er['expiry'] ?? null;
                if (!$expiry) continue;
            }
            $snap = $this->snapshotTotals($db, $symbol, $ts, $mode, $expiry);
            if (!$snap) continue;
            $tmp[] = $snap;
        }
        // compute deltas day-to-day (DESC)
        $absOi = [];
        $absVol = [];
        for ($i=0; $i<count($tmp)-1; $i++) {
            $dOi  = (int)(($tmp[$i]['total_oi'] ?? 0) - ($tmp[$i+1]['total_oi'] ?? 0));
            $dVol = (int)(($tmp[$i]['total_vol'] ?? 0) - ($tmp[$i+1]['total_vol'] ?? 0));
            if ($dOi !== 0)  $absOi[]  = abs($dOi);
            if ($dVol !== 0) $absVol[] = abs($dVol);
            if (count($absOi) >= 5 && count($absVol) >= 5) break;
        }
        if (count($absOi) < 3 || count($absVol) < 3) return null;
        $avgOi  = array_sum($absOi) / count($absOi);
        $avgVol = array_sum($absVol) / count($absVol);
        return [
            'avg_abs_doi' => (float)round($avgOi, 2),
            'avg_abs_dvol'=> (float)round($avgVol, 2),
            'points_oi'   => count($absOi),
            'points_vol'  => count($absVol),
        ];
    }


private function strengthDaywiseRelative(array $rows, int $i): array
{
    // Relative strength: compare today's |ΔOI| and |ΔVol| against rolling 5-day average (older days).
    // rows are sorted DESC (latest first). For day i, baseline uses days i+1 .. i+5 (older).
    $cur  = $rows[$i] ?? null;
    $prev = $rows[$i+1] ?? null;
    if (!$cur || !$prev) return ['label'=>'—', 'score'=>0, 'oi_ratio'=>null, 'vol_ratio'=>null];

    $dOi    = $cur['d_oi']   ?? null;
    $dVol   = $cur['d_vol']  ?? null;
    $dPrice = $cur['d_price']?? null;
    $behavior = $cur['behavior'] ?? '—';

    if ($dOi === null || $dVol === null || $dPrice === null) {
        // If missing diffs, keep neutral
        return ['label'=>'—', 'score'=>0, 'oi_ratio'=>null, 'vol_ratio'=>null];
    }

    // Collect rolling baselines
    $absOis = [];
    $absVols = [];
    for ($j = $i+1; $j <= $i+5; $j++) {
        if (!isset($rows[$j])) break;
        $xOi  = $rows[$j]['d_oi']  ?? null;
        $xVol = $rows[$j]['d_vol'] ?? null;
        if ($xOi !== null)  $absOis[]  = abs((float)$xOi);
        if ($xVol !== null) $absVols[] = abs((float)$xVol);
    }

    // Fallback: if not enough history, use prev-day percentages (old method) but scaled down
    if (count($absOis) < 2 || count($absVols) < 2) {
        $prevOi  = (float)($prev['total_oi']  ?? 0);
        $prevVol = (float)($prev['total_vol'] ?? 0);
        $oiPct  = ($prevOi > 0)  ? (abs((float)$dOi)  / $prevOi)  * 100.0 : 0.0;
        $volPct = ($prevVol > 0) ? (abs((float)$dVol) / $prevVol) * 100.0 : 0.0;

        $oiScore  = ($oiPct >= 12) ? 40 : (($oiPct >= 6) ? 25 : (($oiPct >= 3) ? 10 : 0));
        $volScore = ($volPct >= 25) ? 40 : (($volPct >= 12) ? 25 : (($volPct >= 6) ? 10 : 0));
        $pxScore  = $this->priceConfirmScore($behavior, (float)$dPrice);

        $score = min(100, $oiScore + $volScore + $pxScore);
        return ['label'=>$this->strengthLabel($score), 'score'=>$score, 'oi_ratio'=>null, 'vol_ratio'=>null];
    }

    $avgAbsOi  = array_sum($absOis)  / count($absOis);
    $avgAbsVol = array_sum($absVols) / count($absVols);

    $oiRatio  = ($avgAbsOi  > 0) ? abs((float)$dOi)  / $avgAbsOi  : 0.0;
    $volRatio = ($avgAbsVol > 0) ? abs((float)$dVol) / $avgAbsVol : 0.0;

    // Component scoring (0..40 each)
    $oiScore  = ($oiRatio  >= 1.5) ? 40 : (($oiRatio  >= 1.1) ? 25 : (($oiRatio  >= 0.7) ? 10 : 0));
    $volScore = ($volRatio >= 1.5) ? 40 : (($volRatio >= 1.1) ? 25 : (($volRatio >= 0.7) ? 10 : 0));

    // Price confirmation (0..20) when not range
    $pxScore  = $this->priceConfirmScore($behavior, (float)$dPrice);

    $score = min(100, $oiScore + $volScore + $pxScore);

    return [
        'label'     => $this->strengthLabel($score),
        'score'     => $score,
        'oi_ratio'  => round($oiRatio, 2),
        'vol_ratio' => round($volRatio, 2),
    ];
}

private function priceConfirmScore(string $behavior, float $dPrice): int
{
    // Range behaviors don't get price confirmation
    if (stripos($behavior, 'Range') === 0 || $behavior === '—' || $behavior === 'Mixed') return 0;

    // Confirm directional behaviors
    // Long Build-up / Short Covering => dPrice > 0
    // Short Build-up / Long Unwinding => dPrice < 0
    if (($behavior === 'Long Build-up' || $behavior === 'Short Covering') && $dPrice > 0) return 20;
    if (($behavior === 'Short Build-up' || $behavior === 'Long Unwinding') && $dPrice < 0) return 20;

    return 0;
}

private function strengthLabel(int $score): string
{
    if ($score >= 75) return 'Strong';
    if ($score >= 50) return 'Moderate';
    if ($score >= 25) return 'Weak';
    return '—';
}


private function classifyDaywise($dPrice, $dOi): array
    {
        // If no prev day, show neutral
        if ($dPrice === null || $dOi === null) {
            return ['behavior'=>'—', 'signal'=>'Neutral'];
        }

        // Treat very small price changes as range
        $rangePx = 10; // points
        if (abs((float)$dPrice) < $rangePx) {
            if ($dOi > 0) return ['behavior'=>'Range – Position Build', 'signal'=>'Sideways'];
            if ($dOi < 0) return ['behavior'=>'Range – Position Exit', 'signal'=>'Neutral'];
            return ['behavior'=>'Range – Flat', 'signal'=>'Neutral'];
        }

        if ($dPrice > 0 && $dOi > 0) return ['behavior'=>'Long Build-up', 'signal'=>'Bullish'];
        if ($dPrice < 0 && $dOi > 0) return ['behavior'=>'Short Build-up', 'signal'=>'Bearish'];
        if ($dPrice > 0 && $dOi < 0) return ['behavior'=>'Short Covering', 'signal'=>'Bullish (short-term)'];
        if ($dPrice < 0 && $dOi < 0) return ['behavior'=>'Long Unwinding', 'signal'=>'Bearish (short-term)'];

        return ['behavior'=>'Mixed', 'signal'=>'Neutral'];
    }





    /**
     * Build a fast lookup set (["YYYY-MM-DD"=>true]) of holiday trading dates.
     * Assumes a table named `nse_holidays` with a `holiday_date` column (DATE or YYYY-MM-DD string),
     * and an optional `segment` column (e.g., CM, FO, CDS, COM, ALL).
     */
    private function getHolidayDateSet(string $segment = 'CM'): array
    {
        try {
            $db = \Config\Database::connect();

            // Adjust these if your table/column names differ
            $table   = 'nse_holidays';
            $dateCol = 'holiday_date';
            $segCol  = 'segment';

            $builder = $db->table($table)->select($dateCol);

            // Some setups may not have segment column; guard it
            $fields = $db->getFieldNames($table);
            if (in_array($segCol, $fields, true)) {
                $seg = strtoupper(trim((string) $segment));

                // Normalize segment codes (your API/JSON may use different labels)
                $segList = [$seg];
                if (in_array($seg, ['FO', 'F&O', 'FNO', 'NFO', 'DERIV', 'DERIVATIVES'], true)) {
                    $segList = ['FO', 'F&O', 'FNO', 'NFO', 'DERIV', 'DERIVATIVES'];
                } elseif (in_array($seg, ['CM', 'CASH', 'EQUITY'], true)) {
                    $segList = ['CM', 'CASH', 'EQUITY'];
                }

                $builder->groupStart()
                        ->whereIn($segCol, $segList)
                        ->orWhere($segCol, 'ALL')
                        ->orWhere($segCol, null)
                        ->orWhere($segCol, '')
                        ->groupEnd();
            }

            $builder->orderBy($dateCol, 'asc');
            $list = $builder->get()->getResultArray();

            $set = [];
            foreach ($list as $row) {
                $d = $row[$dateCol] ?? null;
                if (!$d) continue;
                $d = substr((string)$d, 0, 10); // normalize
                $set[$d] = true;
            }
            return $set;
        } catch (\Throwable $e) {
            // If table not found or query fails, silently continue (do not break dashboard)
            log_message('warning', 'Holiday fetch failed: ' . $e->getMessage());
            return [];
        }
    }

    /** Convert a DB timestamp (UTC) to IST date string YYYY-MM-DD for holiday comparison */
    private function dateISTFromTs(string $ts): string
    {
        try {
            $dt = new \DateTime($ts, new \DateTimeZone('UTC'));
            $dt->setTimezone(new \DateTimeZone('Asia/Kolkata'));
            return $dt->format('Y-m-d');
        } catch (\Throwable $e) {
            return substr($ts, 0, 10);
        }
    }

     /**
     * Fetch full holiday rows from DB for display purposes.
     * Returns a date-window around today (sorted asc).
     */
    private function getHolidayRows(string $segment = 'FO', int $pastDays = 30, int $futureDays = 370): array
    {
        $db = \Config\Database::connect();
        $today = date('Y-m-d');

        $from = date('Y-m-d', strtotime($today . ' -' . max(0, $pastDays) . ' days'));
        $to   = date('Y-m-d', strtotime($today . ' +' . max(0, $futureDays) . ' days'));

        try {
            $rows = $db->table('nse_holidays')
                ->select('holiday_date, segment, weekday_name, description, morning_session, evening_session')
                ->where('segment', $segment)
                ->where('holiday_date >=', $from)
                ->where('holiday_date <=', $to)
                ->orderBy('holiday_date', 'ASC')
                ->get()->getResultArray();
        } catch (\Throwable $e) {
            // schema fallback
            $rows = $db->table('nse_holidays')
                ->select('holiday_date, segment')
                ->where('segment', $segment)
                ->where('holiday_date >=', $from)
                ->where('holiday_date <=', $to)
                ->orderBy('holiday_date', 'ASC')
                ->get()->getResultArray();
        }

        foreach ($rows as &$r) {
            if (empty($r['weekday_name']) && !empty($r['holiday_date'])) {
                $r['weekday_name'] = date('l', strtotime($r['holiday_date']));
            }
            $r['description'] = $r['description'] ?? '';
            $r['morning_session'] = $r['morning_session'] ?? null;
            $r['evening_session'] = $r['evening_session'] ?? null;
        }
        unset($r);

        return $rows;
    }

    private function nextHolidayInfo(array $holidayDateSet): array
    {
        $today = date('Y-m-d');

        // $holidayDateSet may be either:
        // 1) a list of dates: ['2026-01-26', '2026-03-06', ...]
        // 2) a set map: ['2026-01-26' => true, '2026-03-06' => true, ...]
        $dates = [];
        foreach ($holidayDateSet as $k => $v) {
            $d = is_string($k) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $k) ? $k : (is_string($v) ? $v : null);
            if (!$d) continue;
            $dates[] = substr($d, 0, 10);
        }
        sort($dates);

        $next = null;
        foreach ($dates as $d) {
            if ($d > $today) { $next = $d; break; } // strictly future
        }
        if (!$next) return ['date' => null, 'days' => null];

        $days = (int) floor((strtotime($next) - strtotime($today)) / 86400);
        return ['date' => $next, 'days' => $days];
    }



    /**
     * DB-driven Option Chain (NSE-style) for a given expiry.
     * Returns FULL expiry totals (for PCR) even when window slicing is used.
     *
     * GET:
     *  - symbol (default NIFTY)
     *  - expiry (required)
     *  - win (3|5|10|all)   => ATM ± win strikes, or all
     *  - atm (optional)    => ATM strike from track (recommended)
     */
    public function chain()
    {
        $db = db_connect();

        $symbol = strtoupper(trim((string)($this->request->getGet('symbol') ?? 'NIFTY')));
        $expiry = trim((string)($this->request->getGet('expiry') ?? ''));
        $winRaw = strtolower(trim((string)($this->request->getGet('win') ?? '5')));
        $atmParam = trim((string)($this->request->getGet('atm') ?? ''));

        if ($expiry === '') {
            return $this->response->setJSON(['ok' => false, 'error' => 'expiry required']);
        }

        $win = ($winRaw === 'all') ? 'all' : (int)$winRaw;
        if ($win !== 'all' && ($win < 1 || $win > 50)) $win = 5;

        // Latest snapshot timestamp for this expiry
        $rLatest = $db->query(
            "SELECT MAX(ts) AS latest_ts
             FROM oi_snapshots
             WHERE symbol=? AND expiry=?",
            [$symbol, $expiry]
        )->getRowArray();

        $latestTs = $rLatest['latest_ts'] ?? null;
        if (!$latestTs) {
            return $this->response->setJSON(['ok' => false, 'error' => 'no snapshots']);
        }

        // Full-expiry totals (PCR must be based on all strikes)
        $totRows = $db->query(
            "SELECT opt,
                    SUM(oi)  AS sum_oi,
                    SUM(chg_oi) AS sum_chg_oi,
                    SUM(vol) AS sum_vol
             FROM oi_snapshots
             WHERE symbol=? AND expiry=? AND ts=?
             GROUP BY opt",
            [$symbol, $expiry, $latestTs]
        )->getResultArray();

        $tot = [
            'ce_oi' => 0, 'pe_oi' => 0,
            'ce_chg_oi' => 0, 'pe_chg_oi' => 0,
            'ce_vol' => 0, 'pe_vol' => 0,
            'pcr' => null,
        ];
        foreach ($totRows as $tr) {
            $opt = strtoupper((string)($tr['opt'] ?? ''));
            if ($opt === 'CE') {
                $tot['ce_oi'] = (float)($tr['sum_oi'] ?? 0);
                $tot['ce_chg_oi'] = (float)($tr['sum_chg_oi'] ?? 0);
                $tot['ce_vol'] = (float)($tr['sum_vol'] ?? 0);
            } elseif ($opt === 'PE') {
                $tot['pe_oi'] = (float)($tr['sum_oi'] ?? 0);
                $tot['pe_chg_oi'] = (float)($tr['sum_chg_oi'] ?? 0);
                $tot['pe_vol'] = (float)($tr['sum_vol'] ?? 0);
            }
        }
        if ($tot['ce_oi'] > 0) $tot['pcr'] = round($tot['pe_oi'] / $tot['ce_oi'], 2);

        // Determine ATM strike (prefer client-provided from track)
        $atmStrike = null;
        if ($atmParam !== '' && is_numeric($atmParam)) {
            $atmStrike = (float)$atmParam;
        } else {
            // fallback: choose strike nearest to underlying if underlying exists
            $uRow = $db->query(
                "SELECT MAX(underlying) AS u
                 FROM oi_snapshots
                 WHERE symbol=? AND expiry=? AND ts=?",
                [$symbol, $expiry, $latestTs]
            )->getRowArray();
            $u = isset($uRow['u']) ? (float)$uRow['u'] : null;

            if ($u !== null) {
                $sRows = $db->query(
                    "SELECT DISTINCT strike
                     FROM oi_snapshots
                     WHERE symbol=? AND expiry=? AND ts=?
                     ORDER BY strike ASC",
                    [$symbol, $expiry, $latestTs]
                )->getResultArray();
                $best = null; $bestD = null;
                foreach ($sRows as $sr) {
                    $k = (float)$sr['strike'];
                    $d = abs($k - $u);
                    if ($bestD === null || $d < $bestD) { $bestD = $d; $best = $k; }
                }
                $atmStrike = $best;
            }
        }

        // Pull chain rows (pivot by strike)
        $allRows = $db->query(
            "SELECT strike,
                SUM(CASE WHEN opt='CE' THEN oi ELSE 0 END) AS ce_oi,
                SUM(CASE WHEN opt='CE' THEN chg_oi ELSE 0 END) AS ce_chg_oi,
                SUM(CASE WHEN opt='CE' THEN vol ELSE 0 END) AS ce_vol,
                SUM(CASE WHEN opt='PE' THEN oi ELSE 0 END) AS pe_oi,
                SUM(CASE WHEN opt='PE' THEN chg_oi ELSE 0 END) AS pe_chg_oi,
                SUM(CASE WHEN opt='PE' THEN vol ELSE 0 END) AS pe_vol
             FROM oi_snapshots
             WHERE symbol=? AND expiry=? AND ts=?
             GROUP BY strike
             ORDER BY strike ASC",
            [$symbol, $expiry, $latestTs]
        )->getResultArray();

        // If ATM still unknown, choose the median strike
        if ($atmStrike === null && count($allRows) > 0) {
            $atmStrike = (float)$allRows[(int)floor(count($allRows)/2)]['strike'];
        }

        // Apply window slice around ATM
        $rowsOut = $allRows;
        if ($win !== 'all' && $atmStrike !== null) {
            $strikes = array_map(fn($r)=> (float)$r['strike'], $allRows);
            $idx = 0; $bestD = null;
            foreach ($strikes as $i=>$k) {
                $d = abs($k - $atmStrike);
                if ($bestD === null || $d < $bestD) { $bestD = $d; $idx = $i; }
            }
            $lo = max(0, $idx - $win);
            $hi = min(count($allRows)-1, $idx + $win);
            $rowsOut = array_slice($allRows, $lo, $hi - $lo + 1);
            $atmStrike = (float)$allRows[$idx]['strike']; // snap to nearest available strike
        }

        // IST ts string for UI (optional)
        $latestIst = null;
        try {
            $dt = new \DateTime($latestTs, new \DateTimeZone('UTC'));
            $dt->setTimezone(new \DateTimeZone('Asia/Kolkata'));
            $latestIst = $dt->format('Y-m-d H:i:s') . ' IST';
        } catch (\Throwable $e) {}

        return $this->response->setJSON([
            'ok' => true,
            'symbol' => $symbol,
            'expiry' => $expiry,
            'latest_ts' => $latestTs,
            'latest_ts_ist' => $latestIst,
            'window' => $winRaw,
            'atm_strike' => $atmStrike,
            'totals' => $tot,
            // Back-compat for dashboard JS: full-expiry totals live under expiry_totals.now
            'expiry_totals' => [
                'now' => $tot,
                'pcr' => $tot['pcr'],
            ],
            'rows' => $rowsOut,
        ]);
    }


}