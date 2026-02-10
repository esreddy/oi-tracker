<?php namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;
use DateTime;
use DateTimeZone;

class PriceController extends BaseController
{
    /**
     * GET /price/breakouts?symbol=NIFTY&tf=5m&lookback=10&lines=24000,24110&tol_pts=10
     * Builds 5m candles (IST day), detects breakouts AND classifies action near given horizontal lines.
     */
    public function breakouts()
    {
        $symbol   = strtoupper($this->request->getGet('symbol') ?? 'NIFTY');
        $tf       = $this->request->getGet('tf') ?? '5m';          // only 5m supported now
        $lookback = max(5, (int)($this->request->getGet('lookback') ?? 10));
        $bodyX    = 1.5;                                           // body >= 1.5 × avg body over lookback

        // --- NEW: support/resistance lines & tolerance (points) ---
        $linesStr = trim((string)$this->request->getGet('lines') ?? '');
        $tolPts   = (float)($this->request->getGet('tol_pts') ?? 10);   // ±points around a line
        $wickFrac = 0.4;  // min wick fraction of total range for a “rejection” candle

        $lines = [];
        if ($linesStr !== '') {
            foreach (explode(',', $linesStr) as $v) {
                $v = trim($v);
                if ($v !== '' && is_numeric($v)) {
                    $lines[] = (float)$v;
                }
            }
            sort($lines);
        }

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
                s.ts,
                s.underlying,
                FLOOR((UNIX_TIMESTAMP(s.ts) + 19800) / 300) AS bucket
              FROM oi_snapshots s
              WHERE s.symbol = ?
                AND s.ts >= ?
                AND s.ts <= ?
            ) AS bkt
            GROUP BY bkt.bucket
            ORDER BY ts_utc_bucket ASC
        ";

        $rows = $db->query($sql, [$symbol, $startUtc, $endUtc])->getResultArray();
        if (!$rows) {
            return $this->response->setJSON(['ok'=>false,'msg'=>'No data today']);
        }

        // --- Pack candles ---
        $candles = [];
        foreach ($rows as $r) {
            $o = (float)$r['o']; $h = (float)$r['h']; $l = (float)$r['l']; $c = (float)$r['c'];
            $range = max(0.0001, $h - $l);
            $dtIst = (new DateTime($r['ts_utc_bucket'], new DateTimeZone('UTC')))
                        ->setTimezone($tzIst)
                        ->format('Y-m-d H:i');
            $candles[] = [
                'ts_utc' => $r['ts_utc_bucket'],
                'ts_ist' => $dtIst.' IST',
                'o' => $o, 'h' => $h, 'l' => $l, 'c' => $c,
                'body' => abs($c - $o),
                'range'=> $range,
                'bull' => $c > $o,
                'upper_wick' => max(0.0, $h - max($o, $c)),
                'lower_wick' => max(0.0, min($o, $c) - $l),
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

        // --- NEW: Line analysis ---
        $lineSignals = [];  // [line_value => [events...]]
        if (!empty($lines)) {
            for ($i=1; $i<count($candles); $i++) {
                $prev = $candles[$i-1];
                $cur  = $candles[$i];

                foreach ($lines as $L) {
                    $nearNow  = ($cur['l'] <= $L + $tolPts) && ($cur['h'] >= $L - $tolPts);   // candle touches band
                    $nearPrev = ($prev['l'] <= $L + $tolPts) && ($prev['h'] >= $L - $tolPts);

                    // Strong body filter (optional) — reuse bodyX vs avg body
                    $strongBody = $cur['body'] >= $bodyX * max(0.0001, $avgBody(max($i, $lookback)));

                    // 1) Breakout Up: close crosses from ≤L to >L
                    if ($prev['c'] <= $L && $cur['c'] > $L && $strongBody) {
                        $lineSignals[(string)$L][] = [
                            'type'     => 'BreakoutUp',
                            'line'     => $L,
                            'time_ist' => $cur['ts_ist'],
                            'o'=>$cur['o'],'h'=>$cur['h'],'l'=>$cur['l'],'c'=>$cur['c'],
                        ];
                    }
                    // 2) Breakdown Down: close crosses from ≥L to <L
                    if ($prev['c'] >= $L && $cur['c'] < $L && $strongBody) {
                        $lineSignals[(string)$L][] = [
                            'type'     => 'BreakdownDn',
                            'line'     => $L,
                            'time_ist' => $cur['ts_ist'],
                            'o'=>$cur['o'],'h'=>$cur['h'],'l'=>$cur['l'],'c'=>$cur['c'],
                        ];
                    }
                    // 3) Bearish Rejection at resistance L: touch near/above L and close below open with long upper wick
                    if ($nearNow && !$cur['bull']) {
                        $upperOk = $cur['upper_wick'] >= $wickFrac * $cur['range'];
                        $closedBelowL = $cur['c'] < $L && $cur['h'] >= $L - $tolPts;
                        if ($upperOk && $closedBelowL) {
                            $lineSignals[(string)$L][] = [
                                'type'     => 'BearishRejection',
                                'line'     => $L,
                                'time_ist' => $cur['ts_ist'],
                                'o'=>$cur['o'],'h'=>$cur['h'],'l'=>$cur['l'],'c'=>$cur['c'],
                            ];
                        }
                    }
                    // 4) Bullish Rejection at support L: touch near/below L and close above open with long lower wick
                    if ($nearNow && $cur['bull']) {
                        $lowerOk = $cur['lower_wick'] >= $wickFrac * $cur['range'];
                        $closedAboveL = $cur['c'] > $L && $cur['l'] <= $L + $tolPts;
                        if ($lowerOk && $closedAboveL) {
                            $lineSignals[(string)$L][] = [
                                'type'     => 'BullishRejection',
                                'line'     => $L,
                                'time_ist' => $cur['ts_ist'],
                                'o'=>$cur['o'],'h'=>$cur['h'],'l'=>$cur['l'],'c'=>$cur['c'],
                            ];
                        }
                    }
                    // 5) Bullish Retest & Hold: dipped to L band but closed back above L
                    if ($nearNow && $cur['c'] > $L && $prev['c'] > $L) {
                        $lineSignals[(string)$L][] = [
                            'type'     => 'BullishRetestHold',
                            'line'     => $L,
                            'time_ist' => $cur['ts_ist'],
                            'o'=>$cur['o'],'h'=>$cur['h'],'l'=>$cur['l'],'c'=>$cur['c'],
                        ];
                    }
                    // 6) Bearish Retest Fail: spiked into L band from below but closed back under L
                    if ($nearNow && $cur['c'] < $L && $prev['c'] < $L) {
                        $lineSignals[(string)$L][] = [
                            'type'     => 'BearishRetestFail',
                            'line'     => $L,
                            'time_ist' => $cur['ts_ist'],
                            'o'=>$cur['o'],'h'=>$cur['h'],'l'=>$cur['l'],'c'=>$cur['c'],
                        ];
                    }
                }
            }
        }

        return $this->response->setJSON([
            'ok'         => true,
            'symbol'     => $symbol,
            'tf'         => '5m',
            'lookback'   => $lookback,
            'lines'      => $lines,
            'tol_pts'    => $tolPts,
            'last'       => $last,
            'signals'    => $signals,     // rolling high/low breakouts
            'lineSignals'=> $lineSignals, // events near your marked lines
        ], ResponseInterface::HTTP_OK);
    }
}
