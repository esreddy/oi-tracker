<?php namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * NSE OI Fetcher (v3 API + auto expiry)
 *
 * - Uses:
 *    - option-chain-contract-info  -> to get expiryDates list
 *    - option-chain-v3             -> to get full chain for current expiry
 * - Auto-detects "current" and "next" expiry based on today's date.
 * - Second CLI argument is accepted but ignored (for backward-compatible crons).
 *
 * Usage examples:
 *   php spark oi:fetch NIFTY 5
 *   php spark oi:fetch BANKNIFTY 5
 */
class FetchOi extends BaseCommand
{
    protected $group       = 'OI';
    protected $name        = 'oi:fetch';
    protected $description = 'Fetch NSE option-chain (current + next expiry) from v3 API and save to oi_snapshots';

    // kept for help text only – the second arg is ignored
    protected $usage       = 'php spark oi:fetch <SYMBOL> [ignored_param]';
    protected $arguments   = ['symbol', 'ignored_param'];

    public function run(array $params)
    {
        CLI::write('DEBUG: Inside FetchOi command', 'yellow');

        $symbol     = strtoupper($params[0] ?? 'NIFTY');
        $cookieFile = WRITEPATH . 'nse_cookie.txt';

        // 1) Warm-up – sets cookies / session
        $this->warmup($cookieFile);
        usleep(600 * 1000);

        // 2) Get full expiry list from contract-info
        $allExpiries = $this->fetchExpiryDates($symbol, $cookieFile);
        if (!$allExpiries) {
            CLI::error("Could not get expiry list for $symbol");
            return;
        }

        // 3) Auto-select current + next expiry based on today's date
        $todayTs = strtotime(date('d-M-Y'));
        $current = null;
        $next    = null;

        foreach ($allExpiries as $idx => $exp) {
            $ts = strtotime($exp);
            if ($ts >= $todayTs) {
                $current = $exp;
                if (isset($allExpiries[$idx + 1])) {
                    $next = $allExpiries[$idx + 1];
                }
                break;
            }
        }

        if (!$current) {
            CLI::error("No valid expiry >= today found for $symbol");
            return;
        }

        CLI::write(
            "DEBUG: Auto-selected current expiry = $current, next = " . ($next ?? 'N/A'),
            'yellow'
        );

        // 4) Fetch full chain for CURRENT expiry from v3 API
        $json = $this->fetchChain($symbol, $cookieFile, $current);
        if (!$json) {
            CLI::error("Failed to fetch option chain from NSE");
            return;
        }

        if (!isset($json['records']['data']) || !is_array($json['records']['data'])) {
            CLI::error("Invalid NSE JSON (records.data missing)");
            return;
        }

        $rows = $json['records']['data'];
        if (!$rows) {
            CLI::error("No OI rows found");
            return;
        }

        // underlying value (index price)
        $underlying = isset($json['records']['underlyingValue'])
            ? (float)$json['records']['underlyingValue']
            : 0.0;

        // 5) Build batch for oi_snapshots
        $db     = Database::connect();
        $batch  = [];
        // use local time so it matches oi:enrich logic / MySQL NOW()
        $now    = date('Y-m-d H:i:s');

        foreach ($rows as $r) {
            // v3 row example:
            // {
            //   "expiryDates": "09-Dec-2025",
            //   "CE": {...},
            //   "PE": {...},
            //   "strikePrice": 23750
            // }
            $expiryStr = $r['expiryDates']
                ?? ($r['CE']['expiryDate'] ?? ($r['PE']['expiryDate'] ?? null));
            $strike    = $r['strikePrice'] ?? null;

            if (!$expiryStr || !$strike) {
                continue;
            }

            // filter only current + next expiry
            if (
                $expiryStr !== $current &&
                ($next !== null && $expiryStr !== $next)
            ) {
                continue;
            }

            $expiryDate = date('Y-m-d', strtotime($expiryStr));

            // CE leg
            if (isset($r['CE']) && is_array($r['CE'])) {
                $ce = $r['CE'];

                $batch[] = [
                    'ts'         => $now,
                    'symbol'     => $symbol,
                    'underlying' => $underlying,
                    'expiry'     => $expiryDate,
                    'strike'     => (int)$strike,
                    'opt'        => 'CE',
                    'oi'         => (int)($ce['openInterest'] ?? 0),
                    'chg_oi'     => (int)($ce['changeinOpenInterest'] ?? 0),
                    'ltp'        => isset($ce['lastPrice']) ? (float)$ce['lastPrice'] : null,
                    'chg_ltp'    => isset($ce['change']) ? (float)$ce['change'] : null,
                    'iv'         => isset($ce['impliedVolatility']) ? (float)$ce['impliedVolatility'] : null,
                    'vol'        => isset($ce['totalTradedVolume']) ? (int)$ce['totalTradedVolume'] : null,
                    'bid_qty'    => isset($ce['bidQty']) ? (int)$ce['bidQty'] : null,
                    'bid_price'  => isset($ce['bidprice']) ? (float)$ce['bidprice'] : null,
                    'ask_price'  => isset($ce['askPrice']) ? (float)$ce['askPrice'] : null,
                    'ask_qty'    => isset($ce['askQty']) ? (int)$ce['askQty'] : null,
                ];
            }

            // PE leg
            if (isset($r['PE']) && is_array($r['PE'])) {
                $pe = $r['PE'];

                $batch[] = [
                    'ts'         => $now,
                    'symbol'     => $symbol,
                    'underlying' => $underlying,
                    'expiry'     => $expiryDate,
                    'strike'     => (int)$strike,
                    'opt'        => 'PE',
                    'oi'         => (int)($pe['openInterest'] ?? 0),
                    'chg_oi'     => (int)($pe['changeinOpenInterest'] ?? 0),
                    'ltp'        => isset($pe['lastPrice']) ? (float)$pe['lastPrice'] : null,
                    'chg_ltp'    => isset($pe['change']) ? (float)$pe['change'] : null,
                    'iv'         => isset($pe['impliedVolatility']) ? (float)$pe['impliedVolatility'] : null,
                    'vol'        => isset($pe['totalTradedVolume']) ? (int)$pe['totalTradedVolume'] : null,
                    'bid_qty'    => isset($pe['bidQty']) ? (int)$pe['bidQty'] : null,
                    'bid_price'  => isset($pe['bidprice']) ? (float)$pe['bidprice'] : null,
                    'ask_price'  => isset($pe['askPrice']) ? (float)$pe['askPrice'] : null,
                    'ask_qty'    => isset($pe['askQty']) ? (int)$pe['askQty'] : null,
                ];
            }
        }

        if (!$batch) {
            CLI::write("No usable OI rows found for current/next expiry", 'yellow');
            return;
        }

        // 6) Insert into oi_snapshots
        $db->table('oi_snapshots')->insertBatch($batch, 500);

        CLI::write(
            'Saved ' . count($batch) . " rows | {$symbol} | Curr={$current} | Next=" . ($next ?? 'N/A'),
            'green'
        );
    }

    /**
     * Warm-up request (set cookies / session)
     */
    private function warmup(string $cookieFile): void
    {
        $this->http('https://www.nseindia.com', $cookieFile);
        usleep(500 * 1000);

        $this->http('https://www.nseindia.com/option-chain', $cookieFile);
        usleep(300 * 1000);
    }

    /**
     * Get expiryDates list from option-chain-contract-info
     */
    private function fetchExpiryDates(string $symbol, string $cookieFile): ?array
    {
        $url = 'https://www.nseindia.com/api/option-chain-contract-info?symbol='
             . urlencode($symbol);

        $raw = $this->http($url, $cookieFile);
        if (!$raw) {
            CLI::error("HTTP failed while getting contract info for $symbol");
            return null;
        }

        $json = json_decode($raw, true);
        if (!is_array($json)) {
            CLI::error('JSON decode failed (contract-info): ' . json_last_error_msg());
            CLI::write(substr($raw, 0, 300) . '...', 'yellow');
            return null;
        }

        if (empty($json['expiryDates']) || !is_array($json['expiryDates'])) {
            CLI::error('contract-info JSON has no expiryDates');
            CLI::write(substr($raw, 0, 300) . '...', 'yellow');
            return null;
        }

        return $json['expiryDates'];
    }

    /**
     * Fetch option-chain JSON from v3 API for a given expiry
     */
    private function fetchChain(string $symbol, string $cookieFile, string $expiry): ?array
    {
        $url = 'https://www.nseindia.com/api/option-chain-v3'
            . '?type=Indices'
            . '&symbol=' . urlencode($symbol)
            . '&expiry=' . rawurlencode($expiry);

        $raw = $this->http($url, $cookieFile);
        if ($raw === null) {
            CLI::error("HTTP fetch failed for $symbol from $url");
            return null;
        }

        $json = json_decode($raw, true);
        if (!is_array($json)) {
            CLI::error('JSON decode failed (v3): ' . json_last_error_msg());
            CLI::write(substr($raw, 0, 300) . '...', 'yellow');
            return null;
        }

        if (
            !isset($json['records']) ||
            !is_array($json['records']) ||
            !isset($json['records']['data']) ||
            !is_array($json['records']['data']) ||
            count($json['records']['data']) === 0
        ) {
            CLI::error('NSE v3 JSON has no records.data rows.');
            CLI::write(substr($raw, 0, 300) . '...', 'yellow');
            return null;
        }

        // Derive expiryDates list once (for debug), if missing
        if (!isset($json['records']['expiryDates']) || !is_array($json['records']['expiryDates'])) {
            $rows      = $json['records']['data'];
            $expiryMap = [];

            foreach ($rows as $r) {
                if (!empty($r['expiryDates'])) {
                    $expiryMap[$r['expiryDates']] = true;
                } elseif (!empty($r['CE']['expiryDate'])) {
                    $expiryMap[$r['CE']['expiryDate']] = true;
                } elseif (!empty($r['PE']['expiryDate'])) {
                    $expiryMap[$r['PE']['expiryDate']] = true;
                }
            }

            $expiries = array_keys($expiryMap);
            $json['records']['expiryDates'] = $expiries;
        }

        CLI::write(
            'DEBUG: Derived expiry list from v3 JSON: '
            . implode(', ', $json['records']['expiryDates']),
            'yellow'
        );

        return $json;
    }

    /**
     * NSE-compatible cURL wrapper
     */
    private function http(string $url, string $cookieFile): ?string
    {
        $headers = [
            'Host: www.nseindia.com',
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) ' .
            'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
            'Accept: application/json, text/plain, */*',
            'Accept-Language: en-US,en;q=0.9',
            'Accept-Encoding: gzip, deflate, br',
            'Referer: https://www.nseindia.com/option-chain',
            'Connection: keep-alive',
            'DNT: 1',
            'X-Requested-With: XMLHttpRequest',
            'Sec-Fetch-Site: same-origin',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Dest: empty',
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_ENCODING       => '',
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_USERAGENT      => $headers[1],

            CURLOPT_COOKIEJAR      => $cookieFile,
            CURLOPT_COOKIEFILE     => $cookieFile,

            // macOS / Herd CA issues workaround
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $out  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);

        curl_close($ch);

        if ($code !== 200 || $out === false || $out === null) {
            CLI::error("NSE HTTP error: code=$code url=$url err=$err");
            if (is_string($out) && $out !== '') {
                CLI::write(substr($out, 0, 200) . '...', 'yellow');
            }
            return null;
        }

        return $out;
    }
}
