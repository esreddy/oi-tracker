<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\IndexEodModel;

/**
 * ================================================================
 * INDEX EOD UPDATE COMMAND
 * ================================================================
 *
 * PURPOSE
 * -------
 * - Maintain DAILY EOD (Open, High, Low, Close, Prev Close)
 *   for NIFTY and BANKNIFTY
 * - Data stored in: oi_index_eod (same OI DB)
 * - Used ONLY for dashboard movement tracking
 *   (daily / weekly / monthly % move)
 *
 * KEY DESIGN GOALS
 * ----------------
 * ✔ Gap-safe (if system was OFF for days, it fills missing days)
 * ✔ Idempotent (safe to run multiple times)
 * ✔ Supports backfill (last year / custom range)
 * ✔ Same command works on Mac (launchd) and Server (cron)
 *
 * DATA SOURCE
 * -----------
 * - Yahoo Finance "chart" API (daily candles)
 * - Stable, no auth, suitable for automation
 *
 * HOW IT WORKS (HIGH LEVEL)
 * ------------------------
 * 1. For each symbol (NIFTY, BANKNIFTY)
 * 2. Find MAX(trade_date) already stored
 * 3. Decide FROM date:
 *      - If --from provided → use it
 *      - Else → (max_date + 1)
 *      - If table empty → default last 1 year
 * 4. Decide TO date:
 *      - If --to provided → use it
 *      - Else → yesterday
 * 5. Fetch Yahoo daily candles (wide range)
 * 6. UPSERT rows into DB
 * 7. Backfill prev_close using DB for consistency
 *
 * USAGE
 * -----
 * Normal daily run:
 *   php spark index:eod:update
 *
 * Backfill last year:
 *   php spark index:eod:update --from=2025-01-01 --to=2025-12-31
 *
 * Only one index:
 *   php spark index:eod:update --symbol=NIFTY
 *
 * SAFE TO RUN:
 * - On every boot
 * - Every day via cron / launchd
 */
class IndexEodUpdate extends BaseCommand
{
    protected $group       = 'OI';
    protected $name        = 'index:eod:update';
    protected $description = 'Fetch and upsert NIFTY/BANKNIFTY daily EOD (gap-safe).';

    /**
     * Mapping of our internal symbols
     * to Yahoo Finance tickers
     */
    private array $symbolMap = [
        'NIFTY'     => '^NSEI',
        'BANKNIFTY' => '^NSEBANK',
    ];

    /**
     * ============================================================
     * ENTRY POINT
     * ============================================================
     */
    public function run(array $params)
    {
        $model = new IndexEodModel();

        // Optional CLI arguments
        $fromArg   = $this->getOption($params, '--from');    // YYYY-MM-DD
        $toArg     = $this->getOption($params, '--to');      // YYYY-MM-DD
        $symbolArg = $this->getOption($params, '--symbol');  // NIFTY / BANKNIFTY

        // Decide which symbols to process
        $symbols = $symbolArg
            ? [strtoupper($symbolArg)]
            : array_keys($this->symbolMap);

        foreach ($symbols as $symbol) {

            if (!isset($this->symbolMap[$symbol])) {
                CLI::write("Invalid symbol: $symbol", 'red');
                continue;
            }

            /**
             * ----------------------------------------------------
             * STEP 1: Decide DATE RANGE
             * ----------------------------------------------------
             */
            $maxDate = $model->getMaxDate($symbol);

            // FROM date logic
            if ($fromArg) {
                $from = $fromArg;
            } elseif ($maxDate) {
                // Continue from last stored trading day
                $from = date('Y-m-d', strtotime($maxDate . ' +1 day'));
            } else {
                // First-time run → default last 1 year
                $from = date('Y-m-d', strtotime('-1 year'));
            }

            // TO date logic
            // Use yesterday to avoid partial/incomplete trading day
            //$to = $toArg ?: date('Y-m-d', strtotime('yesterday'));
            $to = $toArg ?: $this->latestTradingDayIST();

            if ($from > $to) {
                CLI::write("$symbol: No update needed (up-to-date)", 'green');
                continue;
            }

            CLI::write(
                "$symbol: Fetching EOD from $from → $to (last in DB: " . ($maxDate ?: 'none') . ")",
                'yellow'
            );

            /**
             * ----------------------------------------------------
             * STEP 2: FETCH DATA FROM YAHOO
             * ----------------------------------------------------
             */
            $rows = $this->fetchYahooDaily($symbol, $from, $to);

            if (empty($rows)) {
                CLI::write("$symbol: No data fetched", 'red');
                continue;
            }

            /**
             * ----------------------------------------------------
             * STEP 3: UPSERT INTO DB
             * ----------------------------------------------------
             */
            $count = 0;
            foreach ($rows as $row) {
                $model->upsertRow($row);
                $count++;
            }

            CLI::write("$symbol: Upserted $count rows", 'green');

            /**
             * ----------------------------------------------------
             * STEP 4: PREV CLOSE BACKFILL (DB-BASED)
             * ----------------------------------------------------
             * Why?
             * - Yahoo prev-close may be inconsistent around holidays
             * - DB trading sequence is authoritative for our use
             */
            $this->backfillPrevClose($model, $symbol, $from, $to);
        }
    }

    /**
     * ============================================================
     * FETCH DAILY CANDLES FROM YAHOO
     * ============================================================
     *
     * - Fetches a wider range (2 years) to ensure prev_close exists
     * - Filters locally to required date range
     */
    private function fetchYahooDaily(string $symbol, string $from, string $to): array
    {
        $ticker = $this->symbolMap[$symbol];

        $url = "https://query1.finance.yahoo.com/v8/finance/chart/"
            . rawurlencode($ticker)
            . "?interval=1d&range=2y";

        $json = $this->curlGet($url);
        if (!$json) return [];

        $data = json_decode($json, true);
        $res  = $data['chart']['result'][0] ?? null;
        if (!$res) return [];

        $timestamps = $res['timestamp'] ?? [];
        $quote      = $res['indicators']['quote'][0] ?? [];

        $out = [];

        for ($i = 0; $i < count($timestamps); $i++) {
            if (!isset($quote['close'][$i])) continue;

            // Yahoo timestamps are UTC seconds
            $tradeDate = gmdate('Y-m-d', $timestamps[$i]);

            if ($tradeDate < $from || $tradeDate > $to) continue;

            // Find previous non-null close
            $prevClose = null;
            for ($k = $i - 1; $k >= 0; $k--) {
                if (!empty($quote['close'][$k])) {
                    $prevClose = $quote['close'][$k];
                    break;
                }
            }

            $out[] = [
                'symbol'     => $symbol,
                'trade_date' => $tradeDate,
                'open'       => $quote['open'][$i]  ?? null,
                'high'       => $quote['high'][$i]  ?? null,
                'low'        => $quote['low'][$i]   ?? null,
                'close'      => $quote['close'][$i],
                'prev_close' => $prevClose,
                'source'     => 'yahoo_chart',
            ];
        }

        return $out;
    }

    /**
     * ============================================================
     * BACKFILL PREV CLOSE USING DB
     * ============================================================
     *
     * Ensures:
     * - prev_close = previous trading day's close
     * - Independent of data source quirks
     */
    private function backfillPrevClose(IndexEodModel $model, string $symbol, string $from, string $to): void
    {
        $db = $model->db;

        $rows = $db->table('oi_index_eod')
            ->where('symbol', $symbol)
            ->where('trade_date >=', $from)
            ->where('trade_date <=', $to)
            ->orderBy('trade_date', 'ASC')
            ->get()->getResultArray();

        foreach ($rows as $row) {
            if (!empty($row['prev_close'])) continue;

            $prev = $db->table('oi_index_eod')
                ->select('close')
                ->where('symbol', $symbol)
                ->where('trade_date <', $row['trade_date'])
                ->orderBy('trade_date', 'DESC')
                ->limit(1)
                ->get()->getRowArray();

            if ($prev && $prev['close'] !== null) {
                $db->table('oi_index_eod')
                    ->where('symbol', $symbol)
                    ->where('trade_date', $row['trade_date'])
                    ->update(['prev_close' => $prev['close']]);
            }
        }
    }

    /**
     * ============================================================
     * SIMPLE CURL GET
     * ============================================================
     */
    private function curlGet(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_USERAGENT      => 'Mozilla/5.0',
        ]);

        $out  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($code >= 200 && $code < 300) ? $out : null;
    }

    /**
     * ============================================================
     * CLI OPTION PARSER
     * ============================================================
     * Supports:
     *   --from=YYYY-MM-DD
     *   --from YYYY-MM-DD
     */
    private function getOption(array $params, string $key): ?string
    {
        foreach ($params as $i => $p) {
            if (strpos($p, $key . '=') === 0) {
                return substr($p, strlen($key) + 1);
            }
            if ($p === $key && isset($params[$i + 1])) {
                return $params[$i + 1];
            }
        }
        return null;
    }

    private function latestTradingDayIST(): string
    {
        $db = \Config\Database::connect();
        $d = new \DateTime('now', new \DateTimeZone('Asia/Kolkata'));

        for ($i=0; $i<10; $i++) {
            $dateStr = $d->format('Y-m-d');
            $dow = (int)$d->format('N');
            $isWeekend = ($dow >= 6);

            $isHoliday = (bool)$db->table('nse_holidays')
                ->where('segment', 'FO')
                ->where('holiday_date', $dateStr)
                ->countAllResults();

            if ($isWeekend || $isHoliday) { $d->modify('-1 day'); continue; }

            // if today before 15:45 IST, use previous day
            if ($i === 0 && (int)$d->format('Hi') < 1545) {
                $d->modify('-1 day');
                continue;
            }

            return $dateStr;
        }

        return date('Y-m-d', strtotime('yesterday'));
    }

}
