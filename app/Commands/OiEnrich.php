<?php
namespace App\Commands;

use App\Models\OiSnapshotModel;
use App\Models\OiMetricsModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class OiEnrich extends BaseCommand
{
    protected $group = 'OI';
    protected $name  = 'oi:enrich';
    protected $description = 'Compute PCR trend, wall shifts, and price-vs-OI classification and store to oi_metrics';
    protected $usage = 'oi:enrich [symbol] [windowMinutes]';
    protected $arguments = ['symbol'=>'NIFTY', 'windowMinutes'=>'10'];

    public function run(array $params)
    {
        $symbol = strtoupper($params[0] ?? 'NIFTY');
        $winMin = (int)($params[1] ?? 10);

        $snap = new OiSnapshotModel();
        $metrics = new OiMetricsModel();

        $latest = $snap->latestStamp($symbol);
        if (!$latest) { CLI::error('No snapshots yet. Run oi:fetch first.'); return; }

        $tsNow   = $latest['ts'];
        $expiry  = $latest['expiry'];
        $price   = (float)$latest['underlying'];
        $tsPrev  = date('Y-m-d H:i:00', strtotime("$tsNow -{$winMin} minutes"));

        // Pull rows at the two times
        $nowRows  = $snap->rowsAt($symbol, $expiry, $tsNow);
        $prevRows = $snap->nearestRowsBefore($symbol, $expiry, $tsPrev);

        // Build maps
        $nowCE = []; $nowPE = [];
        foreach ($nowRows as $r) {
            if ($r['opt'] === 'CE') { $nowCE[(int)$r['strike']] = (int)$r['oi']; }
            else                    { $nowPE[(int)$r['strike']] = (int)$r['oi']; }
        }
        $pvCE = []; $pvPE = [];
        foreach ($prevRows as $r) {
            if ($r['opt'] === 'CE') { $pvCE[(int)$r['strike']] = (int)$r['oi']; }
            else                    { $pvPE[(int)$r['strike']] = (int)$r['oi']; }
        }

        // PCR
        $sumCall = array_sum($nowCE);
        $sumPut  = array_sum($nowPE);
        $pcr     = $sumCall ? round($sumPut / $sumCall, 2) : null;

        // Find top walls (by OI)
        arsort($nowCE);
        arsort($nowPE);

        $topCallStrike = null; $topCallOi = null; $topCallDelta = null;
        if (!empty($nowCE)) {
            $callKeys = array_keys($nowCE);
            $topCallStrike = (int)$callKeys[0];
            $topCallOi     = (int)$nowCE[$topCallStrike];
            $prevOi        = (int)($pvCE[$topCallStrike] ?? 0);
            $topCallDelta  = $topCallOi - $prevOi;
        }

        $topPutStrike = null; $topPutOi = null; $topPutDelta = null;
        if (!empty($nowPE)) {
            $putKeys = array_keys($nowPE);
            $topPutStrike = (int)$putKeys[0];
            $topPutOi     = (int)$nowPE[$topPutStrike];
            $prevOi       = (int)($pvPE[$topPutStrike] ?? 0);
            $topPutDelta  = $topPutOi - $prevOi;
        }

        // ATM (50 step fine for NIFTY; BANKNIFTY will still work as a rough ATM)
        $step = 50;
        $atm  = (int)(round($price / $step) * $step);

        // Bias using deltas around ATM
        $sumPutAddAboveATM  = 0;
        foreach ($nowPE as $st => $oi) {
            $delta = $oi - (int)($pvPE[$st] ?? 0);
            if ($st >= $atm && $delta > 0) $sumPutAddAboveATM += $delta;
        }
        $sumCallAddBelowATM = 0;
        foreach ($nowCE as $st => $oi) {
            $delta = $oi - (int)($pvCE[$st] ?? 0);
            if ($st <= $atm && $delta > 0) $sumCallAddBelowATM += $delta;
        }

        $bias = 'Range-bound';
        if ($sumPutAddAboveATM > $sumCallAddBelowATM && $pcr !== null && $pcr >= 0.95) $bias = 'Bullish';
        if ($sumPutAddAboveATM < $sumCallAddBelowATM && $pcr !== null && $pcr <= 0.95) $bias = 'Bearish';

        // Previous underlying at/before $tsPrev
        $db = \Config\Database::connect();
        $prevURow = $db->query(
            "SELECT underlying FROM oi_snapshots WHERE symbol=? AND expiry=? AND ts<=? ORDER BY ts DESC LIMIT 1",
            [$symbol, $expiry, $tsPrev]
        )->getRowArray();
        $prevUnderlying = $prevURow['underlying'] ?? null;

        // Price vs OI quadrant
        $oiNow  = $sumCall + $sumPut;
        $oiPrev = array_sum($pvCE) + array_sum($pvPE);
        $oiChange = $oiNow - $oiPrev;

        $price_vs_oi = null;
        if ($prevUnderlying !== null) {
            if ($price > $prevUnderlying && $oiChange > 0)  $price_vs_oi = 'Long Build-up';
            elseif ($price < $prevUnderlying && $oiChange > 0)  $price_vs_oi = 'Short Build-up';
            elseif ($price > $prevUnderlying && $oiChange < 0)  $price_vs_oi = 'Short Covering';
            elseif ($price < $prevUnderlying && $oiChange < 0)  $price_vs_oi = 'Long Unwinding';
        }

        // Notes (simple breakout/breakdown hints)
        $notes = null;
        if ($topCallStrike !== null && $price > $topCallStrike && (int)$topCallDelta < 0) {
            $notes = 'Breakout risk (call unwind)';
        } elseif ($topPutStrike !== null && $price < $topPutStrike && (int)$topPutDelta < 0) {
            $notes = 'Breakdown risk (put unwind)';
        }

        // Save metrics (ignore duplicate minute)
        $metrics->ignore(true)->insert([
            'ts'              => $tsNow,
            'symbol'          => $symbol,
            'expiry'          => $expiry,
            'underlying'      => $price,
            'atm'             => $atm,
            'window_min'      => $winMin,
            'pcr'             => $pcr,
            'bias'            => $bias,
            'top_call_strike' => $topCallStrike,
            'top_call_oi'     => $topCallOi,
            'top_call_delta'  => $topCallDelta,
            'top_put_strike'  => $topPutStrike,
            'top_put_oi'      => $topPutOi,
            'top_put_delta'   => $topPutDelta,
            'price_vs_oi'     => $price_vs_oi,
            'notes'           => $notes,
        ]);

        CLI::write("✅ Saved metrics {$symbol} {$expiry} @ {$tsNow} | PCR {$pcr} | {$bias} | {$price_vs_oi}", 'green');
    }
}
