<?php

namespace App\Commands;

use App\Libraries\CliOptions;
use App\Libraries\TradingCalendar;
use App\Models\IndexEodModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use DateTimeImmutable;

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
 *      - Else → latest trading day whose session has closed (IST, today from 15:45)
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
 * Options work as --name=value or --name value.
 *
 * EXIT CODE
 * ---------
 * 0 = all symbols up to date or updated, 1 = a symbol got no data (network /
 * Yahoo problem), 7 = invalid option. scripts/run_index_eod_update.sh counts
 * non-zero exits and disables the launchd job after repeated failures.
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
        $fromArg   = CliOptions::get('from');    // YYYY-MM-DD
        $toArg     = CliOptions::get('to');      // YYYY-MM-DD
        $symbolArg = CliOptions::get('symbol');  // NIFTY / BANKNIFTY

        foreach (['--from' => $fromArg, '--to' => $toArg] as $opt => $val) {
            if ($val !== null && !CliOptions::isDate($val)) {
                CLI::error("{$opt} must be YYYY-MM-DD, got '{$val}'");
                return EXIT_USER_INPUT;
            }
        }

        // TO date: latest trading day whose session has closed (IST, 15:45 cut-off)
        $calendar = new TradingCalendar($model->db);
        $to = $toArg
            ?: ($calendar->latestClosedTradingDay()
                ?? (new DateTimeImmutable('yesterday', TradingCalendar::timezone()))->format('Y-m-d'));

        $failed = false;

        // Decide which symbols to process
        $symbols = $symbolArg
            ? [strtoupper($symbolArg)]
            : array_keys($this->symbolMap);

        foreach ($symbols as $symbol) {

            if (!isset($this->symbolMap[$symbol])) {
                CLI::write("Invalid symbol: $symbol", 'red');
                $failed = true;
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
                $failed = true;
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

        return $failed ? EXIT_ERROR : EXIT_SUCCESS;
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

}
