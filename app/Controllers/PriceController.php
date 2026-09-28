<?php namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;
use DateTime;
use DateTimeZone;

class PriceController extends BaseController
{
    /**
     * GET /price/breakouts?symbol=NIFTY&tf=5m&lookback=10
     * Builds 5m candles (IST day) and detects breakouts / breakdowns vs the rolling high/low.
     */
    public function breakouts()
    {
        $symbol   = strtoupper($this->request->getGet('symbol') ?? 'NIFTY');
        $tf       = $this->request->getGet('tf') ?? '5m';          // only 5m supported now
        $lookback = max(5, (int)($this->request->getGet('lookback') ?? 10));
        $bodyX    = 1.5;                                           // body >= 1.5 × avg body over lookback

        if ($tf !== '5m') {
            return $this->response->setJSON(['ok'=>false,'msg'=>'Only 5m supported']);
        }

        // --- Compute IST window once, convert to UTC (index friendly) ---
        $tzIst    = new DateTimeZone('Asia/Kolkata');
        $tzUtc    = new DateTimeZone('UTC');
        $startIst = new DateTime('today 00:00:00', $tzIst);
        $endIst   = new DateTime('today 23:59:59', $tzIst);
        $startUtc = (clone $startIst)->setTimezone($tzUtc)->format('Y-m-d H:i:s');
        $endUtc   = (clone $endIst)->setTimezone($tzUtc)->format('Y-m-d H:i:s');

        $db = Database::connect();

        // --- GROUP BY-safe 5m bucketing based on IST ---
        // One row per snapshot: every row of one fetch has the same ts and underlying, so the
        // ~170 strike rows of a snapshot add nothing to the candle.
        $sql = "
            SELECT
              FROM_UNIXTIME(bkt.bucket * 300 - 19800) AS ts_utc_bucket,
              MIN(bkt.ts) AS first_ts,
              MAX(bkt.ts) AS last_ts,
              SUBSTRING_INDEX(GROUP_CONCAT(bkt.underlying ORDER BY bkt.ts ASC), ',', 1) AS o,
              SUBSTRING_INDEX(GROUP_CONCAT(bkt.underlying ORDER BY bkt.ts DESC), ',', 1) AS c,
              MAX(bkt.underlying) AS h,
              MIN(bkt.underlying) AS l
            FROM (
              SELECT
                d.ts,
                (SELECT x.underlying FROM oi_snapshots x WHERE x.ts = d.ts AND x.symbol = ? LIMIT 1) AS underlying,
                FLOOR((UNIX_TIMESTAMP(d.ts) + 19800) / 300) AS bucket
              FROM (
                SELECT DISTINCT s.ts
                FROM oi_snapshots s
                WHERE s.symbol = ?
                  AND s.ts >= ?
                  AND s.ts <= ?
              ) AS d
            ) AS bkt
            GROUP BY bkt.bucket
            ORDER BY ts_utc_bucket ASC
        ";

        $rows = $db->query($sql, [$symbol, $symbol, $startUtc, $endUtc])->getResultArray();
        if (!$rows) {
            return $this->response->setJSON(['ok'=>false,'msg'=>'No data today']);
        }

        // --- Pack candles ---
        $candles = [];
        foreach ($rows as $r) {
            $o = (float)$r['o']; $h = (float)$r['h']; $l = (float)$r['l']; $c = (float)$r['c'];
            $dtIst = (new DateTime($r['ts_utc_bucket'], new DateTimeZone('UTC')))
                        ->setTimezone($tzIst)
                        ->format('Y-m-d H:i');
            $candles[] = [
                'ts_utc' => $r['ts_utc_bucket'],
                'ts_ist' => $dtIst.' IST',
                'o' => $o, 'h' => $h, 'l' => $l, 'c' => $c,
                'body' => abs($c - $o),
                'bull' => $c > $o,
            ];
        }

        if (count($candles) <= $lookback) {
            return $this->response->setJSON(['ok'=>false,'msg'=>'Not enough bars']);
        }

        // --- Helper: avg body over lookback ---
        $avgBody = function($i) use ($candles, $lookback){
            $sum=0; for($k=$i-$lookback; $k<$i; $k++){ $sum += $candles[$k]['body']; }
            return $sum / $lookback;
        };

        // --- Existing breakout/breakdown vs rolling highs/lows ---
        $signals = [];
        $last    = null;
        for ($i=$lookback; $i<count($candles); $i++) {
            $cndl = $candles[$i];
            $hi = -INF; $lo = INF;
            for ($k=$i-$lookback; $k<$i; $k++){ $hi = max($hi, $candles[$k]['h']); $lo = min($lo, $candles[$k]['l']); }
            $bodyOK = $cndl['body'] >= $bodyX * $avgBody($i);

            if ($cndl['c'] > $hi && $cndl['bull'] && $bodyOK) {
                $sig = ['type'=>'Bullish Breakout','time_ist'=>$cndl['ts_ist'],'close'=>$cndl['c'],'high_ref'=>$hi,'index'=>$i];
                $signals[] = $sig; $last = $sig;
            }
            if ($cndl['c'] < $lo && !$cndl['bull'] && $bodyOK) {
                $sig = ['type'=>'Bearish Breakdown','time_ist'=>$cndl['ts_ist'],'close'=>$cndl['c'],'low_ref'=>$lo,'index'=>$i];
                $signals[] = $sig; $last = $sig;
            }
        }

        return $this->response->setJSON([
            'ok'         => true,
            'symbol'     => $symbol,
            'tf'         => '5m',
            'lookback'   => $lookback,
            'last'       => $last,
            'signals'    => $signals,     // rolling high/low breakouts
        ], ResponseInterface::HTTP_OK);
    }
}
