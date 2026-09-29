<?php
namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use DateTime;
use DateTimeZone;
use App\Controllers\BaseController;
use App\Libraries\TradingCalendar;

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
    /**
     * Latest trading day whose session has closed (IST, 15:45 cut-off on normal days).
     * Weekends and FO holidays are skipped; special sessions count as trading days.
     */
    private function latestTradingDayIST(): ?string
    {
        return (new TradingCalendar(\Config\Database::connect()))->latestClosedTradingDay();
    }

    private function getIndexMoves(): array
    {
        $db = \Config\Database::connect();
        $calc = self::moveStats(...);

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
                "SELECT trade_date, close FROM oi_index_eod
                WHERE symbol=? AND trade_date = (
                SELECT MAX(trade_date) FROM oi_index_eod
                WHERE symbol=? AND YEARWEEK(trade_date,1) < YEARWEEK(?,1)
                )",
                [$sym, $sym, $latestDate]
            )->getRowArray();

            $prevWeekClose = $prevWeek ? (float)$prevWeek['close'] : null;

$prevWeekDate  = $prevWeek ? ($prevWeek['trade_date'] ?? null) : null;

            // 7D (calendar) base close: nearest trading day on/before (as-of - 7 days)
            $base7Date = date('Y-m-d', strtotime($latestDate . ' -7 day'));
            $base7Row = $db->query(
                "SELECT trade_date, close FROM oi_index_eod
                 WHERE symbol=? AND trade_date <= ?
                 ORDER BY trade_date DESC
                 LIMIT 1",
                [$sym, $base7Date]
            )->getRowArray();

            $base7Close = $base7Row ? (float)$base7Row['close'] : null;
            $base7TD    = $base7Row ? ($base7Row['trade_date'] ?? null) : null;

            // ===== YTD (Year-to-Date) =====
            $ytdBase = $this->ytdBaseClose($db, $sym, substr($latestDate, 0, 4) . '-01-01');

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
                'wtd'   => array_merge($calc($prevWeekClose, $latestClose), ['tip' => 'WTD (previous week close → as-of) (' . ($prevWeekDate ?: '—') . ' → ' . $latestDate . ')']),
                'd7'    => array_merge($calc($base7Close, $latestClose), ['tip' => '7D change (' . ($base7TD ?: '—') . ' → ' . $latestDate . ')']),
                'd30'   => $calc($base30d, $latestClose),   // 30D rolling
                'mtd'   => $calc($mtdBase, $latestClose),   // Month-to-Date

                // keep YTD
                'year'  => $calc($ytdBase, $latestClose),
            ];
        }

        return $out;
    }

    /**
     * Change from $base to $now: points, percent and direction (+1/-1/0); nulls when there is no base.
     *
     * @return array{pts: ?float, pct: ?float, dir: int}
     */
    private static function moveStats(?float $base, float $now): array
    {
        if ($base === null || $base == 0.0) {
            return ['pts' => null, 'pct' => null, 'dir' => 0];
        }

        $pts = $now - $base;

        return [
            'pts' => $pts,
            'pct' => ($pts / $base) * 100.0,
            'dir' => ($pts > 0) ? 1 : (($pts < 0) ? -1 : 0),
        ];
    }

    /**
     * YTD base: the last close before $yearStart, else the first close of the year.
     */
    private function ytdBaseClose($db, string $symbol, string $yearStart): ?float
    {
        $row = $db->query(
            "SELECT close FROM oi_index_eod WHERE symbol=? AND trade_date < ? ORDER BY trade_date DESC LIMIT 1",
            [$symbol, $yearStart]
        )->getRowArray();

        if (! $row) {
            $row = $db->query(
                "SELECT close FROM oi_index_eod WHERE symbol=? AND trade_date >= ? ORDER BY trade_date ASC LIMIT 1",
                [$symbol, $yearStart]
            )->getRowArray();
        }

        return $row ? (float) $row['close'] : null;
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
        $calcMove = self::moveStats(...);

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
        $ytdBaseClose = $this->ytdBaseClose($db, $symbol, $yearStart);

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

        // Status bar: DB last insert + cron log heartbeats (same data as /oi/healthz, which refreshes it)
        $health      = $this->buildHealth($db);
        $lastFetched = $health['db']['utc'];

        $this->dbg('[OI_DASH] lastFetched from DB', [
            'lastFetched_utc' => $lastFetched,
            'minsAgo'         => $health['db']['minsAgo'],
        ]);

        $this->dbg(
            sprintf('[OI_TRACK] response ready | elapsed_ms=%d', 
                (int) round((microtime(true) - $t0) * 1000)
            )
        );

        $holidayDateSetFO = $this->getHolidayDateSet('FO');
        $nextHolidayFO    = $this->nextHolidayInfo($holidayDateSetFO);
        $holidayRowsFO    = $this->getHolidayRows('FO');

        // Special sessions (writable/cache/special_trading_days.conf, shared with the cron guard)
        $calendar       = new TradingCalendar(null);
        $special        = $this->specialSessionInfo($calendar);
        $marketCalendar = $this->marketCalendarForUi($calendar, $holidayDateSetFO, $holidayRowsFO);


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
            'special'          => $special,         // special-session banner (IST)
            'marketCalendar'   => $marketCalendar,  // holidays + special sessions for the live market badge
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
        // Default intraday windows (minutes)
        // NOTE: These drive BOTH the OI Track table and the NSE-style OC table.
        // Include hrs as minutes (60/120/180).
        $def = [1,2,3,5,10,15,30,60,120,180];
        $lookbacksParam = $this->request->getGet('lookbacks');
        if ($lookbacksParam) {
            $lookbacks = array_values(array_unique(array_filter(array_map(function($x){
                $v = (int)trim($x);
                // guardrails (1–240m) to support 1hr/2hr/3hr + future expansion
                return ($v >= 1 && $v <= 240) ? $v : null;
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

        // Latest snapshot and the nearest expiry in it (one fetch writes the current and next expiry
        // with the same ts). "ORDER BY ts DESC LIMIT 1" is one index lookup; grouping by expiry read
        // every index entry of the symbol.
        $latest = $db->query("
            SELECT ts
            FROM oi_snapshots
            WHERE symbol=?
            ORDER BY ts DESC
            LIMIT 1
        ", [$symbol])->getRowArray();
        if (!$latest) {
            return $this->response->setJSON(['ok'=>false,'msg'=>'No data']);
        }
        $latestTs = $latest['ts'];
        $expiry = $expiry ?: $db->query(
            "SELECT MIN(expiry) AS expiry FROM oi_snapshots WHERE symbol=? AND ts=?",
            [$symbol, $latestTs]
        )->getRow('expiry');

        // Current OI snapshot
        // We also try to fetch LTP + change-in-LTP (chg_ltp) for NSE-style table LTP columns.
        // If your table doesn't have these columns, the fallback query will still work.
        try {
            $rows = $db->query("
                SELECT strike,opt,oi,chg_oi,vol,underlying,ltp,chg_ltp
                FROM oi_snapshots
                WHERE symbol=? AND expiry=? AND ts=?
            ", [$symbol,$expiry,$latestTs])->getResultArray();
        } catch (\Throwable $e) {
            $rows = $db->query("
                SELECT strike,opt,oi,chg_oi,vol,underlying
                FROM oi_snapshots
                WHERE symbol=? AND expiry=? AND ts=?
            ", [$symbol,$expiry,$latestTs])->getResultArray();
        }

        $currCE=[]; $currPE=[]; $underlying=0;
        $ceTot = 0.0; $peTot = 0.0;
        foreach ($rows as $r){
            $underlying=(float)$r['underlying'];
            $oiVal = (float)($r['oi'] ?? 0);
            if ($r['opt']==='CE') { $currCE[$r['strike']]=$r; $ceTot += $oiVal; }
            if ($r['opt']==='PE') { $currPE[$r['strike']]=$r; $peTot += $oiVal; }
        }


        // ---- Top 5 OI (All Strikes) ----
        // IMPORTANT: this must be computed from ALL strikes at latestTs (not UI filtered strikes window)
        $top5CE = array_values($currCE);
        $top5PE = array_values($currPE);

        usort($top5CE, function($a,$b){
            return ((float)($b['oi'] ?? 0)) <=> ((float)($a['oi'] ?? 0));
        });
        usort($top5PE, function($a,$b){
            return ((float)($b['oi'] ?? 0)) <=> ((float)($a['oi'] ?? 0));
        });

        $top5CE = array_slice($top5CE, 0, 5);
        $top5PE = array_slice($top5PE, 0, 5);

        $top5CE_out = array_map(function($r) use ($underlying){
            $strike = (int)($r['strike'] ?? 0);
            $oi     = (float)($r['oi'] ?? 0);
            return [
                'strike' => $strike,
                'oi'     => (int)round($oi),
                'dist'   => round($strike - (float)$underlying, 2),
            ];
        }, $top5CE);

        $top5PE_out = array_map(function($r) use ($underlying){
            $strike = (int)($r['strike'] ?? 0);
            $oi     = (float)($r['oi'] ?? 0);
            return [
                'strike' => $strike,
                'oi'     => (int)round($oi),
                'dist'   => round($strike - (float)$underlying, 2),
            ];
        }, $top5PE);

        // ATM and strikes note:
        // - If strikes=all => return all strikes
        // - Else strikes=N => return symmetric window ATM ± N (2N+1 strikes)
        $step = ($symbol === 'BANKNIFTY') ? 100 : 50;
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

        // ---- Top 5 OI Near ATM (±5 strikes, ALL chain) ----
        // Independent of UI strikes filter. Uses full chain at latestTs.
        $nearWin = 5; // ±5 strikes
        $nearLo = $atmStrike - ($step * $nearWin);
        $nearHi = $atmStrike + ($step * $nearWin);

        $nearCE = [];
        foreach ($currCE as $s => $r) {
            $strike = (int)$s;
            if ($strike < $nearLo || $strike > $nearHi) continue;
            $nearCE[] = ['strike' => $strike, 'oi' => (float)($r['oi'] ?? 0)];
        }

        $nearPE = [];
        foreach ($currPE as $s => $r) {
            $strike = (int)$s;
            if ($strike < $nearLo || $strike > $nearHi) continue;
            $nearPE[] = ['strike' => $strike, 'oi' => (float)($r['oi'] ?? 0)];
        }

        usort($nearCE, function($a,$b){ return ((float)$b['oi']) <=> ((float)$a['oi']); });
        usort($nearPE, function($a,$b){ return ((float)$b['oi']) <=> ((float)$a['oi']); });

        $nearCE = array_slice($nearCE, 0, 5);
        $nearPE = array_slice($nearPE, 0, 5);

        $top5NearCE_out = array_map(function($r) use ($underlying, $atmStrike){
            $strike = (int)($r['strike'] ?? 0);
            $oi     = (float)($r['oi'] ?? 0);
            return [
                'strike' => $strike,
                'oi'     => (int)round($oi),
                'dist_spot' => round($strike - (float)$underlying, 2),
                'dist_atm'  => round($strike - (float)$atmStrike, 2),
            ];
        }, $nearCE);

        $top5NearPE_out = array_map(function($r) use ($underlying, $atmStrike){
            $strike = (int)($r['strike'] ?? 0);
            $oi     = (float)($r['oi'] ?? 0);
            return [
                'strike' => $strike,
                'oi'     => (int)round($oi),
                'dist_spot' => round($strike - (float)$underlying, 2),
                'dist_atm'  => round($strike - (float)$atmStrike, 2),
            ];
        }, $nearPE);

        // -------------------------------
        
        // -------------------------------
        // Top ΔOI (5m / 15m) - ALL strikes (backend)
        // -------------------------------
        $topDoi = [];
        foreach ([5, 15] as $doiMins) {
            $cutoffTs = (new \DateTime($latestTs, new \DateTimeZone('UTC')))
                ->modify("-{$doiMins} minutes")
                ->format('Y-m-d H:i:s');

            // Find the nearest snapshot at/before cutoff (same symbol+expiry); NULL when there is none
            $prevTsRow = $db->query(
                "SELECT MAX(ts) AS ts
                 FROM oi_snapshots
                 WHERE symbol=? AND expiry=? AND ts<=?",
                [$symbol, $expiry, $cutoffTs]
            )->getRowArray();

            $prevTs = $prevTsRow['ts'] ?? null;
            if (!$prevTs) {
                $topDoi[(string)$doiMins] = ['ts' => null, 'ce' => [], 'pe' => []];
                continue;
            }

            $prevRows = $db->query(
                "SELECT strike,opt,oi
                 FROM oi_snapshots
                 WHERE symbol=? AND expiry=? AND ts=?",
                [$symbol, $expiry, $prevTs]
            )->getResultArray();

            $prevCE = []; $prevPE = [];
            foreach ($prevRows as $r) {
                if ($r['opt'] === 'CE') $prevCE[(int)$r['strike']] = (float)($r['oi'] ?? 0);
                else if ($r['opt'] === 'PE') $prevPE[(int)$r['strike']] = (float)($r['oi'] ?? 0);
            }

            $doiCE = [];
            foreach ($currCE as $s => $r) {
                $strike = (int)$s;
                $curOi = (float)($r['oi'] ?? 0);
                $pOi = (float)($prevCE[$strike] ?? 0);
                $doi = $curOi - $pOi;
                $doiCE[] = ['strike' => $strike, 'doi' => (int)round($doi), 'oi' => (int)round($curOi)];
            }

            $doiPE = [];
            foreach ($currPE as $s => $r) {
                $strike = (int)$s;
                $curOi = (float)($r['oi'] ?? 0);
                $pOi = (float)($prevPE[$strike] ?? 0);
                $doi = $curOi - $pOi;
                $doiPE[] = ['strike' => $strike, 'doi' => (int)round($doi), 'oi' => (int)round($curOi)];
            }

            usort($doiCE, function($a,$b){ return ((int)$b['doi']) <=> ((int)$a['doi']); });
            usort($doiPE, function($a,$b){ return ((int)$b['doi']) <=> ((int)$a['doi']); });

            $doiCE = array_slice($doiCE, 0, 5);
            $doiPE = array_slice($doiPE, 0, 5);

            // add distances
            $doiCE_out = array_map(function($x) use ($underlying, $atmStrike){
                $strike = (int)$x['strike'];
                return [
                    'strike' => $strike,
                    'doi' => (int)$x['doi'],
                    'oi'  => (int)$x['oi'],
                    'dist_spot' => round($strike - (float)$underlying, 2),
                    'dist_atm'  => round($strike - (float)$atmStrike, 2),
                ];
            }, $doiCE);

            $doiPE_out = array_map(function($x) use ($underlying, $atmStrike){
                $strike = (int)$x['strike'];
                return [
                    'strike' => $strike,
                    'doi' => (int)$x['doi'],
                    'oi'  => (int)$x['oi'],
                    'dist_spot' => round($strike - (float)$underlying, 2),
                    'dist_atm'  => round($strike - (float)$atmStrike, 2),
                ];
            }, $doiPE);

            $topDoi[(string)$doiMins] = [
                'ts' => $prevTs,
                'ce' => $doiCE_out,
                'pe' => $doiPE_out,
            ];
        }

        // -------------------------------
        // Walls near spot (±2 strikes around ATM)
        // -------------------------------
        $wallWin = 2;
        $wallLo = $atmStrike - ($step * $wallWin);
        $wallHi = $atmStrike + ($step * $wallWin);

        $sumWallCE = 0.0; $sumWallPE = 0.0;
        $maxWallCE = ['strike' => null, 'oi' => 0.0];
        $maxWallPE = ['strike' => null, 'oi' => 0.0];

        foreach ($currCE as $s => $r) {
            $st = (int)$s;
            if ($st < $wallLo || $st > $wallHi) continue;
            $oi = (float)($r['oi'] ?? 0);
            $sumWallCE += $oi;
            if ($oi > $maxWallCE['oi']) $maxWallCE = ['strike' => $st, 'oi' => $oi];
        }
        foreach ($currPE as $s => $r) {
            $st = (int)$s;
            if ($st < $wallLo || $st > $wallHi) continue;
            $oi = (float)($r['oi'] ?? 0);
            $sumWallPE += $oi;
            if ($oi > $maxWallPE['oi']) $maxWallPE = ['strike' => $st, 'oi' => $oi];
        }

        $walls = [
            'win' => $wallWin,
            'range' => ['from' => $wallLo, 'to' => $wallHi, 'step' => $step],
            'ce' => [
                'strike' => $maxWallCE['strike'],
                'oi'     => (int)round((float)$maxWallCE['oi']),
                'share'  => ($sumWallCE > 0 ? round(((float)$maxWallCE['oi']) / $sumWallCE, 4) : null),
            ],
            'pe' => [
                'strike' => $maxWallPE['strike'],
                'oi'     => (int)round((float)$maxWallPE['oi']),
                'share'  => ($sumWallPE > 0 ? round(((float)$maxWallPE['oi']) / $sumWallPE, 4) : null),
            ],
        ];

// Underlying price snapshots (for NSE-style table RHS)
        // -------------------------------
        // latestTs is stored in UTC in DB (typical). We return ts as-is and format to IST on client.
        // $prevTs[m] = the snapshot at/before each lookback cutoff; the OI lookbacks below reuse it.
        $underPrev = [];
        $cutoffs = [];
        $prevTs = [];
        foreach ($lookbacks as $m) {
            $cutoff = (new \DateTime($latestTs, new \DateTimeZone('UTC')))
                ->modify("-{$m} minutes")
                ->format('Y-m-d H:i:s');
            $cutoffs[$m] = $cutoff;

            // MAX(ts) is one index lookup; "ts<=? ORDER BY ts DESC LIMIT 1" was read backwards from the
            // latest row, skipping every row newer than the cutoff. (underlying is the same on all rows
            // of one fetch.)
            $uRow = $db->query(
                "SELECT ts, underlying FROM oi_snapshots
                 WHERE symbol=? AND expiry=?
                   AND ts = (SELECT MAX(ts) FROM oi_snapshots WHERE symbol=? AND expiry=? AND ts<=?)
                 LIMIT 1",
                [$symbol, $expiry, $symbol, $expiry, $cutoff]
            )->getRowArray();

            if ($uRow) {
                $prevTs[$m] = $uRow['ts'];
            }
            if ($uRow && isset($uRow['underlying'])) {
                $underPrev[(int)$m] = [
                    'price' => (float)$uRow['underlying'],
                    'ts'    => $uRow['ts'] ?? null,
                ];
            }
        }

        // Day-open underlying snapshot (first snapshot of the same IST day as latestTs).
        // The IST day as a UTC range, not DATE(CONVERT_TZ(ts, ...)), so the ts index is used.
        [$dayFromUtc, $dayToUtc] = TradingCalendar::utcBoundsOfIstDay($this->dateISTFromTs($latestTs));
        $uDayOpen = $db->query(
            "SELECT ts, underlying
             FROM oi_snapshots
             WHERE symbol=? AND expiry=? AND ts>=? AND ts<?
             ORDER BY ts ASC
             LIMIT 1",
            [$symbol, $expiry, $dayFromUtc, $dayToUtc]
        )->getRowArray();

        $underDayOpenPrice = ($uDayOpen && isset($uDayOpen['underlying'])) ? (float)$uDayOpen['underlying'] : null;
        $underDayOpenTs    = $uDayOpen['ts'] ?? null;

        if ($limitStrikes !== 99999 && $limitStrikes < 99999 && $strikes) {
            $idx = array_search($atmStrike, $strikes, true);
            if ($idx === false) { $idx = 0; }
            $from = max(0, $idx - $limitStrikes);
            $to   = min(count($strikes) - 1, $idx + $limitStrikes);
            $strikes = array_slice($strikes, $from, $to - $from + 1);
        }

        // Lookback OI and day-open volume of the window's contracts come from the snapshots found
        // above ($prevTs, $underDayOpenTs): one query for all of them instead of 1 + one per
        // lookback for every strike x CE/PE. A contract missing from such a snapshot (strike added
        // later in the day, skipped row) falls back to its own latest/first row, as before.
        $snapRows = [];   // ts => opt => strike => row
        $wantTs = array_values(array_unique(array_merge(
            array_values($prevTs),
            $underDayOpenTs === null ? [] : [$underDayOpenTs]
        )));
        if ($strikes && $wantTs) {
            $rowsAtTs = $db->query(
                "SELECT ts, strike, opt, oi, vol
                 FROM oi_snapshots
                 WHERE symbol=? AND expiry=? AND ts IN ? AND strike IN ?",
                [$symbol, $expiry, $wantTs, $strikes]
            )->getResultArray();
            foreach ($rowsAtTs as $r) {
                $snapRows[$r['ts']][$r['opt']][(int)$r['strike']] = $r;
            }
        }
        $rowAt = static fn (?string $ts, string $opt, int $strike): ?array =>
            ($ts !== null && isset($snapRows[$ts][$opt]) && array_key_exists($strike, $snapRows[$ts][$opt]))
                ? $snapRows[$ts][$opt][$strike]
                : null;

        // Fetch historical OI at each lookback
        $data=[];
        foreach ($strikes as $st){
            $rowCE = $currCE[$st] ?? null;
            $rowPE = $currPE[$st] ?? null;
            $entry=['strike'=>$st];
            foreach(['CE'=>$rowCE,'PE'=>$rowPE] as $opt=>$cur){
                if(!$cur){ continue; }
                $snap=[
                    'cur_oi'  => (int)$cur['oi'],
                    'cur_chg' => (int)$cur['chg_oi'],
                ];

                // LTP + change in LTP (if available)
                $snap['cur_ltp'] = array_key_exists('ltp', $cur) ? (is_null($cur['ltp']) ? null : (float)$cur['ltp']) : null;
                $snap['chg_ltp'] = array_key_exists('chg_ltp', $cur) ? (is_null($cur['chg_ltp']) ? null : (float)$cur['chg_ltp']) : null;
                // Volume (from oi_snapshots.vol)
                $curVol = array_key_exists('vol',$cur) ? (is_null($cur['vol']) ? null : (int)$cur['vol']) : null;
                $snap['cur_vol'] = $curVol;

                // Today's change in Volume: current vol - first vol of the same IST date (based on latestTs)
                $snap['cur_vol_chg'] = null;
                if ($curVol !== null) {
                    $baseVolRow = $rowAt($underDayOpenTs, $opt, (int)$st) ?? $db->query("
                        SELECT vol
                        FROM oi_snapshots
                        WHERE symbol=? AND expiry=? AND strike=? AND opt=? AND ts>=? AND ts<?
                        ORDER BY ts ASC
                        LIMIT 1
                    ", [$symbol,$expiry,$st,$opt,$dayFromUtc,$dayToUtc])->getRowArray();
                    $baseVol = ($baseVolRow && isset($baseVolRow['vol'])) ? (int)$baseVolRow['vol'] : null;
                    if ($baseVol !== null) {
                        $snap['cur_vol_chg'] = $curVol - $baseVol;
                    }
                }

                foreach($lookbacks as $m){
                    // No snapshot at/before the cutoff at all -> no row for this contract either
                    $prev = !isset($prevTs[$m]) ? null : ($rowAt($prevTs[$m], $opt, (int)$st) ?? $db->query("
                        SELECT oi,chg_oi
                        FROM oi_snapshots
                        WHERE symbol=? AND expiry=? AND strike=? AND opt=? AND ts<=?
                        ORDER BY ts DESC LIMIT 1
                    ", [$symbol,$expiry,$st,$opt,$cutoffs[$m]])->getRowArray());
                    $snap["oi_{$m}m"]  = $prev['oi'] ?? null;
                    // Window ΔOI must match: (Current OI - OI@cutoff)
                    // NOTE: oi_snapshots.chg_oi is per-snapshot change (not cumulative), so don't use it here.
                    $snap["chg_{$m}m"] = (isset($prev['oi']) ? ((int)$cur['oi'] - (int)$prev['oi']) : null);
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


        // =========================
        // Strike hints (▲/▼ badges in the option chain)
        // =========================
        [$rowHints] = $this->buildRowHints($data, (float)$atm);

        // ---- Day-wise OI (last 5 days) + Expiry Range Auto-Box ----
        $daywiseRows = $this->fetchDaywiseOiRows($symbol, $expiry, 5);
        $expiryBox   = $this->computeExpiryRangeBox($daywiseRows, $step);

        return $this->response->setJSON([
            'ok'       => true,
            'symbol'   => $symbol,
            'expiry'   => $expiry,
            'latestTs' => $latestTs,
            // underlying spot (from latest snapshot) + previous prices per selected lookbacks
            'underlying_now' => $underlying,
            'underlying_prev' => $underPrev,
            'underlying_day_open' => $underDayOpenPrice,
            'underlying_day_open_ts' => $underDayOpenTs,
            'atm'      => $atm,
            'step'     => $step,   // strike interval (NIFTY 50, BANKNIFTY 100)
            'max_pain' => $this->computeMaxPain(
                array_map(static fn ($r) => (float) ($r['oi'] ?? 0), $currCE),
                array_map(static fn ($r) => (float) ($r['oi'] ?? 0), $currPE)
            ),                     // full chain, not the ±strikes window
            'lookbacks'=> $lookbacks,
            'atm_strike' => $atmStrike ?? $atm,
            'strikes_param' => $strikesRaw,
            'daywise' => ['ok'=> true, 'rows'=> $daywiseRows],
            'expiry_box' => $expiryBox,
            'top5_oi' => ['ce' => $top5CE_out, 'pe' => $top5PE_out],
            'top_doi' => $topDoi,
            'walls' => $walls,
            'top5_oi_near_atm' => [
                'win' => 5,
                'range' => ['from' => $nearLo, 'to' => $nearHi, 'step' => $step],
                'ce' => $top5NearCE_out,
                'pe' => $top5NearPE_out,
            ],
            'row_hints' => $rowHints,
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

        // --- latest snapshot of the requested expiry, else the nearest expiry in the symbol's latest
        // snapshot (one fetch writes the current and next expiry with the same ts). Each is an index
        // lookup; grouping by expiry read every index entry of the symbol.
        // Only a real YYYY-MM-DD can equal a stored expiry; anything else never matched and would
        // make MySQL 8 fail ('Incorrect DATE value') or match loosely (e.g. '2026-10-6').
        $latest = null;
        if (is_string($expiry) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $expiry, $m)
            && checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
            $latest = $db->query(
                "SELECT expiry, ts FROM oi_snapshots WHERE symbol=? AND expiry=? ORDER BY ts DESC LIMIT 1",
                [$symbol, $expiry]
            )->getRowArray();
        }
        if (!$latest) {
            $ts = $db->query("SELECT ts FROM oi_snapshots WHERE symbol=? ORDER BY ts DESC LIMIT 1", [$symbol])->getRow('ts');
            if ($ts === null) {
                return $this->response->setJSON(['ok'=>false,'msg'=>'No data found']);
            }
            $latest = [
                'expiry' => $db->query("SELECT MIN(expiry) AS expiry FROM oi_snapshots WHERE symbol=? AND ts=?", [$symbol, $ts])->getRow('expiry'),
                'ts'     => $ts,
            ];
        }
        $chosenExpiry = $latest['expiry'];
        $latestTs     = $latest['ts'];

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

        // strike step & ATM: the smallest gap between listed strikes (far OTM strikes can be
        // listed at wider intervals, so the first gap of the sorted list is not reliable)
        $strikes = array_keys($currCE + $currPE);
        sort($strikes);
        $step = 0;
        for ($i=1; $i<count($strikes); $i++) { $d=$strikes[$i]-$strikes[$i-1]; if ($d>0 && ($step===0 || $d<$step)) $step=$d; }
        if ($step <= 0) $step = ($symbol === 'BANKNIFTY') ? 100 : 50;
        $atm = (int)round($underlying/$step)*$step;

        // --- INTRA ΔOI (vs window start) ---
        $windowStartUtc = (new DateTime($latestTs, new DateTimeZone('UTC')))
                            ->modify("-{$window} minutes")->format('Y-m-d H:i:s');

        [$winCE, $winPE] = $this->baselineOi(
            $db, $symbol, $chosenExpiry, 'MAX', 'ts <= ?', [$windowStartUtc], $currCE, $currPE
        );

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
        [$baseDayCE, $baseDayPE, $dayBaseTs] = $this->baselineOi(
            $db, $symbol, $chosenExpiry, 'MIN', 'ts >= ? AND ts <= ?', [$openUtcS, $latestTs], $currCE, $currPE
        );

        // Fallback: previous-day last available snapshot (old behavior)
        if ($dayBaseTs === null) {
            $todayIst = new DateTime($dtLatestIst->format('Y-m-d') . ' 00:00:00', $tzIST);
            $todayUtc = clone $todayIst; $todayUtc->setTimezone(new DateTimeZone('UTC'));
            $todayUtcS = $todayUtc->format('Y-m-d H:i:s');

            [$baseDayCE, $baseDayPE, $dayBaseTs] = $this->baselineOi(
                $db, $symbol, $chosenExpiry, 'MAX', 'ts < ?', [$todayUtcS], $currCE, $currPE
            );
        }

        // Label = the snapshot dayΔ is measured from (planned open if there is none). The dashboard's
        // day quick read switches to NSE's CHNG IN OI when it is later than 09:20 IST (late first
        // snapshot, or the previous day's last one before the open).
        $dayBaselineIst = ($dayBaseTs !== null)
            ? (new DateTime($dayBaseTs, new DateTimeZone('UTC')))->setTimezone($tzIST)->format('Y-m-d H:i:s') . ' IST'
            : $openIst->format('Y-m-d H:i:s') . ' IST';

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
            'step'      => $step,        // strike interval inferred from the chain
            'max_pain'  => $this->computeMaxPain($currCE, $currPE), // full chain
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

    /**
     * OI of each contract at a ΔOI baseline of one expiry: its latest ($pick 'MAX') or earliest
     * ('MIN') row within $range (SQL on ts, values in $args). The snapshot at the range's MAX/MIN(ts)
     * holds that row for every contract it lists; a current contract missing from it gets its own
     * row in the range (one index lookup). Same values as the "MAX/MIN(ts) GROUP BY strike, opt"
     * join this replaces, which read every row of the expiry up to the range end.
     *
     * @return array{0: array<int,int>, 1: array<int,int>, 2: ?string} CE OI, PE OI, and the
     *         baseline snapshot's ts (UTC; null when the range has no rows)
     */
    private function baselineOi($db, string $symbol, string $expiry, string $pick, string $range, array $args, array $currCE, array $currPE): array
    {
        $oi = ['CE' => [], 'PE' => []];
        $rows = $db->query(
            "SELECT strike, opt, oi, ts FROM oi_snapshots
             WHERE symbol=? AND expiry=? AND ts = (SELECT {$pick}(ts) FROM oi_snapshots WHERE symbol=? AND expiry=? AND {$range})",
            array_merge([$symbol, $expiry, $symbol, $expiry], $args)
        )->getResultArray();
        if (!$rows) {
            return [[], [], null];
        }
        foreach ($rows as $r) {
            if (isset($oi[$r['opt']])) $oi[$r['opt']][(int)$r['strike']] = (int)$r['oi'];
        }

        $order = ($pick === 'MAX') ? 'DESC' : 'ASC';
        foreach (['CE' => $currCE, 'PE' => $currPE] as $opt => $curr) {
            foreach (array_keys($curr) as $strike) {
                if (isset($oi[$opt][$strike])) continue;
                $v = $db->query(
                    "SELECT oi FROM oi_snapshots
                     WHERE symbol=? AND expiry=? AND strike=? AND opt=? AND {$range}
                     ORDER BY ts {$order} LIMIT 1",
                    array_merge([$symbol, $expiry, $strike, $opt], $args)
                )->getRow('oi');
                if ($v !== null) $oi[$opt][$strike] = (int)$v;
            }
        }

        return [$oi['CE'], $oi['PE'], $rows[0]['ts']];
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

        // One row per snapshot: every row of one fetch has the same ts and underlying, so the
        // ~170 strike rows of a snapshot add nothing to the candles.
        $rows = $db->query(
            "SELECT d.ts,
                    (SELECT x.underlying FROM oi_snapshots x WHERE x.ts = d.ts AND x.symbol = ? LIMIT 1) AS underlying
             FROM (SELECT DISTINCT ts FROM oi_snapshots WHERE symbol=? AND ts>=? AND ts<?) d
             ORDER BY d.ts ASC",
            [$symbol, $symbol, $startUTC, $endUTC]
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

        // Prefer current/future range; fallback to last 5.
        // Each step finds the next expiry with one index lookup; DISTINCT read every index entry
        // of those expiries.
        $exp = [];
        $e = $db->query(
            "SELECT expiry FROM oi_snapshots
             WHERE symbol=? AND expiry >= CURDATE() - INTERVAL 14 DAY
             ORDER BY expiry ASC LIMIT 1",
            [$symbol]
        )->getRow('expiry');
        while ($e !== null) {
            $exp[] = $e;
            $e = $db->query(
                "SELECT expiry FROM oi_snapshots WHERE symbol=? AND expiry > ? ORDER BY expiry ASC LIMIT 1",
                [$symbol, $e]
            )->getRow('expiry');
        }

        if (!$exp) {
            $e = $db->query(
                "SELECT expiry FROM oi_snapshots WHERE symbol=? ORDER BY expiry DESC LIMIT 1",
                [$symbol]
            )->getRow('expiry');
            while ($e !== null && count($exp) < 5) {
                $exp[] = $e;
                $e = $db->query(
                    "SELECT expiry FROM oi_snapshots WHERE symbol=? AND expiry < ? ORDER BY expiry DESC LIMIT 1",
                    [$symbol, $e]
                )->getRow('expiry');
            }
            $exp = array_reverse($exp);
        }

        return $this->response->setJSON(['ok'=>true, 'symbol'=>$symbol, 'expiries'=>$exp]);
    }

    // ----------------- JSON: health -----------------
    // Also polled by the dashboard every refresh cycle to update the status bar.
    public function healthz()
    {
        $db = \Config\Database::connect();
        $ok = true; $err = null;
        try { $db->query('SELECT 1'); } catch (\Throwable $e) { $ok=false; $err=$e->getMessage(); }

        $health = $this->buildHealth($db);

        return $this->response->setJSON([
            'db_ok'               => $ok,
            'error'               => $err,
            'last_insert_ts_utc'  => $health['db']['utc'],
            'last_insert_age_min' => $health['db']['minsAgo'],
            'fetch_log_age_min'   => $health['fetch']['minsAgo'],
            'enrich_log_age_min'  => $health['enrich']['minsAgo'],
            'health'              => $health, // same shape as the status bar data rendered by index()
        ]);
    }

    /**
     * Status bar data: last snapshot insert (oi_snapshots.ts is UTC) and the
     * fetch/enrich cron log heartbeats (file mtime), as IST text + minutes ago.
     *
     * @return array{db: array{utc: ?string, ist: ?string, minsAgo: ?int}, fetch: array{ist: ?string, minsAgo: ?int}, enrich: array{ist: ?string, minsAgo: ?int}}
     */
    private function buildHealth($db): array
    {
        $lastInsert = null;

        try {
            $row        = $db->table('oi_snapshots')->selectMax('ts')->get()->getRowArray();
            $lastInsert = $row['ts'] ?? null;
        } catch (\Throwable $e) {
            log_message('error', 'OI health: last insert lookup failed: ' . $e->getMessage());
        }

        $fetchLog  = WRITEPATH . 'logs/oi_fetch.log';
        $enrichLog = WRITEPATH . 'logs/oi_enrich.log';

        return [
            'db' => [
                'utc'     => $lastInsert,
                'ist'     => $this->toIST($lastInsert),
                'minsAgo' => $this->minsSince($lastInsert),
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
    }

    /**
     * Special-session banner data, evaluated in IST.
     *
     * @return array{active: bool, meta: array<string, string>, next: ?array<string, string>, alert: bool}
     */
    private function specialSessionInfo(TradingCalendar $calendar, ?\DateTimeImmutable $now = null): array
    {
        $now   = ($now ?? new \DateTimeImmutable('now'))->setTimezone(TradingCalendar::timezone());
        $today = $now->format('Y-m-d');
        $nowHM = $now->format('H:i');

        $special = ['active' => false, 'meta' => [], 'next' => null, 'alert' => false];

        foreach ($calendar->specialSessions() as $s) { // sorted by date, start
            $info = [
                'date'  => $s['date'],
                'start' => $s['start'],
                'end'   => $s['end'],
                'label' => $s['full'] ? 'Full Day Special Session' : 'Timed Special Session',
            ];

            if ($s['date'] === $today) {
                // One-time pre-market alert (09:00–09:02) on any special date
                if ($nowHM >= '09:00' && $nowHM < '09:02') {
                    $special['alert'] = true;
                }

                if (! $special['active'] && $nowHM >= $s['start'] && $nowHM < $s['end']) {
                    $special['active'] = true;
                    $special['meta']   = $info;
                }
            } elseif ($s['date'] > $today && $special['next'] === null) {
                $special['next'] = $info;
            }
        }

        return $special;
    }

    /**
     * Calendar the dashboard uses (in the browser, IST) for the market badge, the stale-data
     * alarms and the auto-refresh window: FO holidays (date => description) and special
     * sessions (date => windows). Mirrors the cron guard's rules.
     *
     * @return array{holidays: object, special: object}
     */
    private function marketCalendarForUi(TradingCalendar $calendar, array $holidayDateSet, array $holidayRows): array
    {
        $descriptions = [];

        foreach ($holidayRows as $r) {
            $d = substr((string) ($r['holiday_date'] ?? ''), 0, 10);

            if ($d !== '') {
                $descriptions[$d] = (string) ($r['description'] ?? '');
            }
        }

        // A page can stay open for days; a window around today is plenty
        $today = new \DateTimeImmutable('now', TradingCalendar::timezone());
        $from  = $today->modify('-7 days')->format('Y-m-d');
        $to    = $today->modify('+400 days')->format('Y-m-d');

        $holidays = [];

        foreach (array_keys($holidayDateSet) as $d) {
            if ($d >= $from && $d <= $to) {
                $holidays[$d] = $descriptions[$d] ?? '';
            }
        }

        $special = [];

        foreach ($calendar->specialSessions() as $s) {
            $special[$s['date']][] = ['start' => $s['start'], 'end' => $s['end'], 'full' => $s['full']];
        }

        return ['holidays' => (object) $holidays, 'special' => (object) $special];
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

        // Latest IST days with snapshots (ts is stored in UTC) and each day's last snapshot ts
        $daily = $this->latestSnapshotDays($db, $symbol, $fetchLimit);


        // Filter out weekends + NSE holidays (some feeds may still write a snapshot late night IST).
        // Special sessions (e.g. a Budget Saturday) are kept.
        $calendar = new TradingCalendar();
        $filtered = [];
        foreach ($daily as $row) {
            $d = $row['d'] ?? null;
            if (!$d) { continue; }
            $special = $calendar->isSpecialSession($d);

            // Weekend check (IST date)
            $wk = date('N', strtotime($d)); // 6=Sat,7=Sun
            if ($wk >= 6 && !$special) { continue; }

            // Holiday check (by segment)
            if (isset($holidaySet[$d]) && !$special) { continue; }

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
     * The latest $limit IST days that have snapshots for $symbol, newest first, each with its
     * last snapshot ts: [['d' => 'Y-m-d' (IST), 'mts' => 'Y-m-d H:i:s' (UTC)], ...].
     *
     * Same rows as GROUP BY DATE(CONVERT_TZ(ts,'+00:00','+05:30')) ... ORDER BY day DESC LIMIT n,
     * but one index lookup per day ("newest ts before the start of the last day found") instead
     * of reading and grouping the symbol's whole snapshot history.
     */
    private function latestSnapshotDays($db, string $symbol, int $limit): array
    {
        $days   = [];
        $before = null;   // exclusive UTC bound: start of the (IST) day found last

        while (count($days) < $limit) {
            $row = ($before === null)
                ? $db->query('SELECT ts FROM oi_snapshots WHERE symbol=? ORDER BY ts DESC LIMIT 1', [$symbol])->getRowArray()
                : $db->query('SELECT ts FROM oi_snapshots WHERE symbol=? AND ts<? ORDER BY ts DESC LIMIT 1', [$symbol, $before])->getRowArray();
            if (!$row) {
                break;
            }

            $day    = $this->dateISTFromTs($row['ts']);
            $days[] = ['d' => $day, 'mts' => $row['ts']];
            [$before] = TradingCalendar::utcBoundsOfIstDay($day);
        }

        return $days;
    }

    /**
     * Intraday addons summary for decision widgets.
     * GET /oi/intraday?symbol=NIFTY&mode=all
     * - Finds today's IST bucket (00:00..23:59 IST) as a UTC range on ts (stored UTC)
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

        // Start/End ts (UTC) for today bucket: first and last snapshot within today's UTC bounds
        // (two index lookups; DATE(CONVERT_TZ(ts, ...)) read the symbol's whole history)
        [$dayFromUtc, $dayToUtc] = TradingCalendar::utcBoundsOfIstDay($todayIst);
        $first = $db->query(
            "SELECT ts FROM oi_snapshots WHERE symbol=? AND ts>=? AND ts<? ORDER BY ts ASC LIMIT 1",
            [$symbol, $dayFromUtc, $dayToUtc]
        )->getRowArray();
        $last = $db->query(
            "SELECT ts FROM oi_snapshots WHERE symbol=? AND ts>=? AND ts<? ORDER BY ts DESC LIMIT 1",
            [$symbol, $dayFromUtc, $dayToUtc]
        )->getRowArray();

        $minTs = $first['ts'] ?? null;
        $maxTs = $last['ts'] ?? null;

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
        $dayRows = $this->latestSnapshotDays($db, $symbol, $scanDays);
        if (!$dayRows) return null;

        $tmp = [];
        foreach ($dayRows as $dr) {
            $ts = $dr['mts'] ?? null;
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
        $today = (new \DateTimeImmutable('now', TradingCalendar::timezone()))->format('Y-m-d'); // IST (app timezone is UTC)

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
        $today = (new \DateTimeImmutable('now', TradingCalendar::timezone()))->format('Y-m-d'); // IST (app timezone is UTC)

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
     * Build per-strike BUY/SELL row hints for the NSE-style option chain.
     * Heuristic: within the current strike-window, compare Put ΔOI vs Call ΔOI for each strike.
     * Top positive (PutΔOI >> CallΔOI) => BUY (support). Top negative => SELL (resistance).
     *
     * @param array $rows Track rows (each: ['strike'=>..., 'CE'=>..., 'PE'=>...])
     * @param float $atm
     * @return array [$rowHints, $supports, $resistances, $netSum]
     */
    private function buildRowHints(array $rows, float $atm): array
    {
        $stats = [];
        $netSum = 0.0;

        foreach ($rows as $r) {
            $st = (int)($r['strike'] ?? 0);
            if (!$st) continue;

            $ce = (array)($r['CE'] ?? []);
            $pe = (array)($r['PE'] ?? []);

            // Use core ΔOI (cur_chg) which is current snapshot change OI.
            $ceChg = (float)($ce['cur_chg'] ?? 0);
            $peChg = (float)($pe['cur_chg'] ?? 0);

            $net = $peChg - $ceChg; // +ve => support; -ve => resistance
            $stats[] = [
                'strike' => $st,
                'net'    => $net,
                'ce_chg' => $ceChg,
                'pe_chg' => $peChg,
                'dist'   => abs($st - $atm),
            ];
            $netSum += $net;
        }

        // If we have nothing, return empty
        if (!$stats) return [[], [], [], 0.0];

        // Prefer nearer-to-ATM strikes when sorting ties (distance)
        usort($stats, function($a,$b){
            if ($a['net'] === $b['net']) return $a['dist'] <=> $b['dist'];
            return ($a['net'] < $b['net']) ? 1 : -1; // desc net
        });

        $supports = array_slice(array_filter($stats, fn($x)=>$x['net'] > 0), 0, 3);

        // For resistances: sort asc net
        $statsAsc = $stats;
        usort($statsAsc, function($a,$b){
            if ($a['net'] === $b['net']) return $a['dist'] <=> $b['dist'];
            return ($a['net'] > $b['net']) ? 1 : -1; // asc net
        });
        $resistances = array_slice(array_filter($statsAsc, fn($x)=>$x['net'] < 0), 0, 3);

        // Build row hint map
        $rowHints = [];
        foreach ($supports as $s) {
            // Require meaningful net to avoid noisy hints
            if (abs($s['net']) < 5000) continue;
            $rowHints[(string)$s['strike']] = 'BUY';
        }
        foreach ($resistances as $s) {
            if (abs($s['net']) < 5000) continue;
            $rowHints[(string)$s['strike']] = 'SELL';
        }

        // Reduce netSum influence if too small
        if (abs($netSum) < 20000) $netSum = 0.0;

        // Simplify supports/resistances payload
        $supportsOut = array_map(fn($x)=>[
            'strike'=>$x['strike'],
            'net'=>$x['net'],
            'pe_chg'=>$x['pe_chg'],
            'ce_chg'=>$x['ce_chg'],
        ], $supports);

        $resOut = array_map(fn($x)=>[
            'strike'=>$x['strike'],
            'net'=>$x['net'],
            'pe_chg'=>$x['pe_chg'],
            'ce_chg'=>$x['ce_chg'],
        ], $resistances);

        return [$rowHints, $supportsOut, $resOut, $netSum];
    }

    /**
     * Fetch day-wise OI snapshot rows for a given symbol+expiry (latest N days).
     * Uses oi_daywise_snapshots table.
     */
    private function fetchDaywiseOiRows(string $symbol, string $expiry, int $limit = 5): array
    {
        try {
            $db = \Config\Database::connect();
            $rows = $db->query(
                "SELECT trade_date, day_bias, bias_score, pcr_window, win_ce_oi, win_pe_oi, d_ce_oi_window, d_pe_oi_window,
                        max_oi_ce_strike, max_oi_pe_strike
                 FROM oi_daywise_snapshots
                 WHERE symbol=? AND expiry_date=?
                 ORDER BY trade_date DESC
                 LIMIT ".(int)$limit,
                [$symbol, $expiry]
            )->getResultArray();

            // Ensure newest-first -> keep as-is; dashboard may want DESC.
            return $rows ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Compute expiry range (auto) + confidence score (0-100) from last N day-wise rows.
     * - Uses strike persistence + PCR stability + bias consistency + days-in-series.
     */
    /**
     * Max pain over the FULL chain: the strike S at which option writers pay the least
     * at expiry, where calls pay OI × max(0, S − K) and puts pay OI × max(0, K − S).
     * Same formula the dashboard used on partial data (top strikes / visible rows).
     *
     * @param array<int|string, float|int> $ceOi strike => call OI
     * @param array<int|string, float|int> $peOi strike => put OI
     */
    private function computeMaxPain(array $ceOi, array $peOi): ?int
    {
        $ce = $pe = [];
        foreach ($ceOi as $k => $oi) { $ce[] = [(float) $k, (float) $oi]; }
        foreach ($peOi as $k => $oi) { $pe[] = [(float) $k, (float) $oi]; }

        $strikes = array_unique(array_merge(array_column($ce, 0), array_column($pe, 0)), SORT_NUMERIC);
        if ($strikes === []) {
            return null;
        }
        sort($strikes, SORT_NUMERIC);

        $best = null;
        $bestPay = INF;
        foreach ($strikes as $s) {
            $pay = 0.0;
            foreach ($ce as [$k, $oi]) { if ($s > $k) { $pay += $oi * ($s - $k); } }
            foreach ($pe as [$k, $oi]) { if ($k > $s) { $pay += $oi * ($k - $s); } }
            if ($pay < $bestPay) {
                $bestPay = $pay;
                $best = $s;
            }
        }

        return $best === null ? null : (int) round($best);
    }

    private function computeExpiryRangeBox(array $rows, int $step): array
    {
        if (!$rows || count($rows) < 1) {
            return ['lower'=>null,'upper'=>null,'confidence'=>0,'status'=>'No data'];
        }

        // Work oldest->newest for stability measures
        $r = array_reverse($rows);

        $ceHits = [];
        $peHits = [];
        $pcrs   = [];
        $biases = [];

        foreach ($r as $x) {
            $ceHits[] = (int)($x['max_oi_ce_strike'] ?? 0);
            $peHits[] = (int)($x['max_oi_pe_strike'] ?? 0);
            $pcrs[]   = (float)($x['pcr_window'] ?? 0);
            $biases[] = (string)($x['day_bias'] ?? 'NEUTRAL');
        }

        $ceCount = array_count_values(array_filter($ceHits));
        $peCount = array_count_values(array_filter($peHits));
        arsort($ceCount);
        arsort($peCount);

        $strongCE = $ceCount ? (int)array_key_first($ceCount) : null;
        $strongPE = $peCount ? (int)array_key_first($peCount) : null;
        $ceTopN   = $ceCount ? (int)reset($ceCount) : 0;
        $peTopN   = $peCount ? (int)reset($peCount) : 0;

        // ---- Confidence scoring (0-100) ----
        $score = 0;

        // (1) Strike persistence: 40
        if ($ceTopN >= 3) $score += 20;
        else if ($ceTopN == 2) $score += 10;

        if ($peTopN >= 3) $score += 20;
        else if ($peTopN == 2) $score += 10;

        // (2) PCR stability: 25 (range across available days)
        $pcrMin = min($pcrs);
        $pcrMax = max($pcrs);
        $pcrSpan = $pcrMax - $pcrMin;
        if ($pcrSpan <= 0.10) $score += 25;
        else if ($pcrSpan <= 0.20) $score += 15;
        else if ($pcrSpan <= 0.30) $score += 8;

        // (3) Bias consistency: 20
        $uniqBias = array_values(array_unique($biases));
        if (count($uniqBias) === 1) $score += 20;
        else if (count($uniqBias) === 2) $score += 10;

        // (4) Series maturity: 15 (based on available days)
        $days = count($rows);
        if ($days >= 7) $score += 15;
        else if ($days >= 4) $score += 10;
        else $score += 5;

        if ($score < 30) $status = 'Low confidence';
        else if ($score < 60) $status = 'Moderate confidence';
        else $status = 'High confidence';

        $lower = $strongPE ? ($strongPE - $step) : null;
        $upper = $strongCE ? ($strongCE + $step) : null;

        return [
            'lower' => $lower,
            'upper' => $upper,
            'confidence' => max(0, min(100, (int)$score)),
            'status' => $status,
            'strong_ce' => $strongCE,
            'strong_pe' => $strongPE,
        ];
    }


}