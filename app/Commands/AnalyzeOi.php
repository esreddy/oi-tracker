<?php
namespace App\Commands;

use App\Models\OiSnapshotModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AnalyzeOi extends BaseCommand
{
    protected $group = 'OI';
    protected $name  = 'oi:analyze';
    protected $description = 'Analyze last X minutes to derive Support/Resistance/PCR/Bias';
    protected $usage = 'oi:analyze [symbol] [windowMinutes]';
    protected $arguments = ['symbol'=>'NIFTY', 'windowMinutes'=>'e.g., 10'];

    public function run(array $params)
    {
        $symbol = strtoupper($params[0] ?? 'NIFTY');
        $winMin = (int)($params[1] ?? 10);

        $m = new OiSnapshotModel();
        $latest = $m->latestStamp($symbol);
        if (!$latest) { CLI::error('No data'); return; }

        $tsNow = $latest['ts']; $expiry = $latest['expiry']; $price = (float)$latest['underlying'];
        $tsPrev = date('Y-m-d H:i:00', strtotime("$tsNow -{$winMin} minutes"));

        $nowRows  = $m->rowsAt($symbol,$expiry,$tsNow);
        $prevRows = $m->nearestRowsBefore($symbol,$expiry,$tsPrev);

        $mapNow = ['CE'=>[],'PE'=>[]];
        foreach ($nowRows as $r) { $mapNow[$r['opt']][$r['strike']] = (int)$r['oi']; }
        $mapPrev= ['CE'=>[],'PE'=>[]];
        foreach ($prevRows as $r){ $mapPrev[$r['opt']][$r['strike']] = (int)$r['oi']; }

        $callOI=$mapNow['CE']; $putOI=$mapNow['PE'];
        $callPrev=$mapPrev['CE']; $putPrev=$mapPrev['PE'];

        $callDelta=[]; foreach($callOI as $k=>$v){ $callDelta[$k]=$v-($callPrev[$k]??0); }
        $putDelta =[]; foreach($putOI  as $k=>$v){ $putDelta[$k] =$v-($putPrev[$k] ??0); }

        arsort($callOI); arsort($putOI);
        $topCalls = array_slice($callOI,0,3,true);
        $topPuts  = array_slice($putOI ,0,3,true);

        $pcr = array_sum($callOI) ? round(array_sum($putOI)/array_sum($callOI),2) : null;

        $step = 50; $atm = round($price/$step)*$step;

        $sumPutAddAboveATM  = 0; foreach($putDelta as $st=>$d){ if($st>=$atm && $d>0) $sumPutAddAboveATM += $d; }
        $sumCallAddBelowATM = 0; foreach($callDelta as $st=>$d){ if($st<=$atm && $d>0) $sumCallAddBelowATM += $d; }

        $bias = 'Range-bound';
        if ($sumPutAddAboveATM > $sumCallAddBelowATM && $pcr !== null && $pcr >= 0.95) $bias = 'Bullish';
        if ($sumPutAddAboveATM < $sumCallAddBelowATM && $pcr !== null && $pcr <= 0.95) $bias = 'Bearish';

        $resistance = array_key_first($topCalls) ?? null;
        $support    = array_key_first($topPuts)  ?? null;

        CLI::write("=== $symbol | Exp: $expiry | Price: $price | Window: {$winMin}m ===", 'yellow');
        CLI::write("PCR: $pcr");
        CLI::write(PHP_EOL.'Top Call OI (Resistance):','light_gray');
        foreach ($topCalls as $k=>$v){
            $d = $callDelta[$k] ?? 0;
            CLI::write(sprintf("  %6d  OI=%8d  ΔOI=%8d %s",
                $k,$v,$d,($d>0?'(↑ add)':($d<0?'(↓ unwind)':''))));
        }
        CLI::write(PHP_EOL.'Top Put OI (Support):','light_gray');
        foreach ($topPuts as $k=>$v){
            $d = $putDelta[$k] ?? 0;
            CLI::write(sprintf("  %6d  OI=%8d  ΔOI=%8d %s",
                $k,$v,$d,($d>0?'(↑ add)':($d<0?'(↓ unwind)':''))));
        }
        CLI::write(PHP_EOL."Bias: $bias");
        CLI::write("Key levels -> Support: $support | Resistance: $resistance | ATM: $atm");
    }
}
