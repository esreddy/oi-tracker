<?php

namespace App\Commands;

use App\Libraries\CliOptions;
use App\Libraries\TradingCalendar;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use DateTimeImmutable;

/**
 * ================================================================
 * OI DAY-WISE SNAPSHOT UPDATE (EOD)
 * ================================================================
 *
 * Creates/updates a compact day-wise OI snapshot table (oi_daywise_snapshots)
 * from your intraday oi_snapshots table.
 *
 * Run it on trading days after the close (crontab: 50 15 * * 1-5):
 *   php spark oi:daywise:update --symbol=NIFTY --days=10
 *   php spark oi:daywise:update --symbol=BANKNIFTY --days=10
 *
 * Notes:
 * - Uses IST date boundaries (oi_snapshots.ts is UTC).
 * - Picks the LAST snapshot timestamp within the IST date for that symbol+expiry.
 * - Stores ATM-window aggregates (ATM±5 strikes) + strongest strikes + PCR.
 * - Processes days oldest first, so each day's change is computed against the
 *   previous day's freshly rebuilt row.
 * - Skips weekends and FO holidays (special sessions count as trading days), and
 *   skips today until 5 minutes after the close, so a pre-open snapshot (which still
 *   carries yesterday's OI) never becomes today's row. An explicit --date is always processed.
 * - Exit code: 0 on success, 1 when no data can be found, 7 on invalid options.
 */
class OiDaywiseUpdate extends BaseCommand
{
    protected $group       = 'OI';
    protected $name        = 'oi:daywise:update';
    protected $description = 'Build/update day-wise OI snapshots (EOD) into oi_daywise_snapshots.';

    protected $usage = 'oi:daywise:update [options]';

    protected $options = [
        '--symbol' => 'NIFTY or BANKNIFTY (default: NIFTY)',
        '--days'   => 'How many recent calendar days to scan (default: 10)',
        '--expiry' => 'Specific expiry date YYYY-MM-DD (optional)',
        '--date'   => 'Specific trade date (IST) YYYY-MM-DD (optional)',
        '--dry-run'=> 'Do not write; just print computed numbers'
    ];

    public function run(array $params)
    {
        // Accepts --name=value and --name value (CodeIgniter's parser only handles the latter)
        $symbol       = strtoupper(CliOptions::get('symbol') ?: 'NIFTY');
        $days         = (int) (CliOptions::get('days') ?: 10);
        $expiryFilter = CliOptions::get('expiry');
        $dateFilter   = CliOptions::get('date');
        $dryRun       = CliOptions::has('dry-run');

        foreach (['--expiry' => $expiryFilter, '--date' => $dateFilter] as $opt => $val) {
            if ($val !== null && !CliOptions::isDate($val)) {
                CLI::error("{$opt} must be YYYY-MM-DD, got '{$val}'");
                return EXIT_USER_INPUT;
            }
        }

        if ($days < 1) $days = 1;
        if ($days > 60) $days = 60;

        $db = \Config\Database::connect();

        // Determine which expiries to process
        $expiries = [];
        if ($expiryFilter) {
            $expiries = [ $expiryFilter ];
        } else {
            // default: latest 2 expiries seen in oi_snapshots for the symbol
            $expiries = array_map(function($r){ return $r['expiry']; }, $db->query("
                SELECT expiry, MAX(ts) AS ts
                FROM oi_snapshots
                WHERE symbol=?
                GROUP BY expiry
                ORDER BY ts DESC
                LIMIT 2
            ", [$symbol])->getResultArray());
        }

        if (!$expiries) {
            CLI::error("No expiries found in oi_snapshots for {$symbol}");
            return EXIT_ERROR;
        }

        // Build date list (IST dates), oldest first
        $calendar = new TradingCalendar($db);
        $skipped  = [];
        if ($dateFilter) {
            $dates = [$dateFilter];
        } else {
            $now   = new DateTimeImmutable('now', TradingCalendar::timezone());
            $dates = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $d = $now->modify("-{$i} day")->format('Y-m-d');
                if (!$calendar->isTradingDay($d)) {
                    $skipped[] = $d;                  // weekend / FO holiday
                } elseif ($i === 0 && !$calendar->hasSessionEnded($d, $now, 5)) {
                    $skipped[] = $d . ' (not closed)'; // today, before the close
                } else {
                    $dates[] = $d;
                }
            }
        }

        CLI::write("OI Day-wise Update", 'yellow');
        CLI::write("Symbol: {$symbol}");
        CLI::write("Expiries: " . implode(', ', $expiries));
        CLI::write("Dates: " . implode(', ', $dates));
        if ($skipped) CLI::write("Skipped: " . implode(', ', $skipped));
        if ($dryRun) CLI::write("DRY-RUN enabled (no DB writes)", 'yellow');

        foreach ($expiries as $expiry) {
            foreach ($dates as $tradeDate) {

                // Find last snapshot timestamp within this IST date (ts is UTC; range keeps the index usable)
                [$fromUtc, $toUtc] = TradingCalendar::utcBoundsOfIstDay($tradeDate);
                $row = $db->query("
                    SELECT MAX(ts) AS ts
                    FROM oi_snapshots
                    WHERE symbol=? AND expiry=?
                      AND ts >= ? AND ts < ?
                ", [$symbol, $expiry, $fromUtc, $toUtc])->getRowArray();

                $ts = $row['ts'] ?? null;
                if (!$ts) continue; // no data for this day

                // Pull snapshot at that timestamp
                $snapRows = $db->query("
                    SELECT strike,opt,oi,vol,underlying
                    FROM oi_snapshots
                    WHERE symbol=? AND expiry=? AND ts=?
                ", [$symbol, $expiry, $ts])->getResultArray();

                if (!$snapRows) continue;

                // Compute aggregates
                $underlying = 0.0;
                $ce = [];
                $pe = [];
                $ceVol = [];
                $peVol = [];
                foreach ($snapRows as $r){
                    $st = (int)$r['strike'];
                    $opt = strtoupper($r['opt']);
                    $oi = (int)$r['oi'];
                    $vol = (int)($r['vol'] ?? 0);
                    $u = (float)($r['underlying'] ?? 0);
                    if ($u > 0) $underlying = $u;

                    if ($opt === 'CE'){
                        $ce[$st] = $oi;
                        $ceVol[$st] = $vol;
                    } elseif ($opt === 'PE'){
                        $pe[$st] = $oi;
                        $peVol[$st] = $vol;
                    }
                }

                $step = ($symbol === 'BANKNIFTY') ? 100 : 50;
                $atm  = (int)(round($underlying / $step) * $step);

                $strikes = array_unique(array_merge(array_keys($ce), array_keys($pe)));
                sort($strikes);
                if ($strikes) {
                    $best = $strikes[0]; $bestD = abs($best - $atm);
                    foreach ($strikes as $s){
                        $d = abs($s - $atm);
                        if ($d < $bestD){ $bestD=$d; $best=$s; }
                    }
                    $atm = $best;
                }

                $winN = 5; // ATM ±5
                $minStrike = $atm - ($winN*$step);
                $maxStrike = $atm + ($winN*$step);

                $totalCeOi = array_sum($ce);
                $totalPeOi = array_sum($pe);
                $totalCeVol = array_sum($ceVol);
                $totalPeVol = array_sum($peVol);

                $winCeOi = 0; $winPeOi = 0; $winCeVol=0; $winPeVol=0;
                foreach ($strikes as $s){
                    if ($s < $minStrike || $s > $maxStrike) continue;
                    $winCeOi += (int)($ce[$s] ?? 0);
                    $winPeOi += (int)($pe[$s] ?? 0);
                    $winCeVol += (int)($ceVol[$s] ?? 0);
                    $winPeVol += (int)($peVol[$s] ?? 0);
                }

                $pcrTotal  = ($totalCeOi > 0) ? round($totalPeOi / $totalCeOi, 4) : 0.0;
                $pcrWindow = ($winCeOi > 0) ? round($winPeOi / $winCeOi, 4) : 0.0;

                // Strongest strikes
                $maxCeStrike = 0; $maxCe = 0;
                foreach ($ce as $s=>$oi){
                    if ($oi > $maxCe){ $maxCe=$oi; $maxCeStrike=(int)$s; }
                }
                $maxPeStrike = 0; $maxPe = 0;
                foreach ($pe as $s=>$oi){
                    if ($oi > $maxPe){ $maxPe=$oi; $maxPeStrike=(int)$s; }
                }

                // Deltas vs previous stored day (if any)
                $prev = $db->query("
                    SELECT trade_date, win_ce_oi, win_pe_oi, pcr_window
                    FROM oi_daywise_snapshots
                    WHERE symbol=? AND expiry_date=? AND trade_date < ?
                    ORDER BY trade_date DESC
                    LIMIT 1
                ", [$symbol, $expiry, $tradeDate])->getRowArray();

                $dCeWin = $prev ? ((int)$winCeOi - (int)$prev['win_ce_oi']) : 0;
                $dPeWin = $prev ? ((int)$winPeOi - (int)$prev['win_pe_oi']) : 0;
                $dPcrWin = $prev ? round($pcrWindow - (float)$prev['pcr_window'], 4) : 0.0;

                // Bias score (simple + stable)
                $score = 0;
                if ($dPeWin > 0) $score += 30;
                if ($dCeWin < 0) $score += 20;
                if ($pcrWindow > 1.05) $score += 20;
                if ($dCeWin > 0) $score -= 30;
                if ($dPeWin < 0) $score -= 20;
                if ($pcrWindow < 0.95) $score -= 20;
                if ($dPcrWin > 0.02) $score += 10;
                if ($dPcrWin < -0.02) $score -= 10;

                if ($score > 100) $score = 100;
                if ($score < -100) $score = -100;

                $bias = 'NEUTRAL';
                if ($score >= 25) $bias = 'BULLISH';
                if ($score <= -25) $bias = 'BEARISH';

                $payload = [
                    'symbol' => $symbol,
                    'expiry_date' => $expiry,
                    'trade_date' => $tradeDate,
                    'spot' => $underlying,
                    'atm_strike' => $atm,
                    'strike_step' => $step,
                    'window_strikes' => $winN,

                    'total_ce_oi' => $totalCeOi,
                    'total_pe_oi' => $totalPeOi,
                    'total_ce_vol' => $totalCeVol,
                    'total_pe_vol' => $totalPeVol,

                    'win_ce_oi' => $winCeOi,
                    'win_pe_oi' => $winPeOi,
                    'win_ce_vol' => $winCeVol,
                    'win_pe_vol' => $winPeVol,

                    'pcr_total' => $pcrTotal,
                    'pcr_window' => $pcrWindow,

                    'max_oi_ce_strike' => $maxCeStrike,
                    'max_oi_ce' => $maxCe,
                    'max_oi_pe_strike' => $maxPeStrike,
                    'max_oi_pe' => $maxPe,

                    'd_ce_oi_total' => 0,
                    'd_pe_oi_total' => 0,
                    'd_ce_oi_window' => $dCeWin,
                    'd_pe_oi_window' => $dPeWin,
                    'd_pcr_window' => $dPcrWin,

                    'day_bias' => $bias,
                    'bias_score' => $score,
                    'source' => 'oi_snapshots_eod'
                ];

                CLI::write("{$symbol} {$expiry} {$tradeDate} @{$ts}  ATM={$atm} PCRw={$pcrWindow} Bias={$bias} Score={$score}");

                if ($dryRun) continue;

                // Upsert
                $db->query("
                    INSERT INTO oi_daywise_snapshots
                    (symbol, expiry_date, trade_date, spot, atm_strike, strike_step, window_strikes,
                     total_ce_oi, total_pe_oi, total_ce_vol, total_pe_vol,
                     win_ce_oi, win_pe_oi, win_ce_vol, win_pe_vol,
                     pcr_total, pcr_window,
                     max_oi_ce_strike, max_oi_ce, max_oi_pe_strike, max_oi_pe,
                     d_ce_oi_total, d_pe_oi_total, d_ce_oi_window, d_pe_oi_window, d_pcr_window,
                     day_bias, bias_score, source)
                    VALUES
                    (?,?,?,?,?,?,?,
                     ?,?,?,?,
                     ?,?,?,?,
                     ?,?,
                     ?,?,?,?,
                     ?,?,?,?,?,
                     ?,?,?)
                    ON DUPLICATE KEY UPDATE
                      spot=VALUES(spot),
                      atm_strike=VALUES(atm_strike),
                      strike_step=VALUES(strike_step),
                      window_strikes=VALUES(window_strikes),
                      total_ce_oi=VALUES(total_ce_oi),
                      total_pe_oi=VALUES(total_pe_oi),
                      total_ce_vol=VALUES(total_ce_vol),
                      total_pe_vol=VALUES(total_pe_vol),
                      win_ce_oi=VALUES(win_ce_oi),
                      win_pe_oi=VALUES(win_pe_oi),
                      win_ce_vol=VALUES(win_ce_vol),
                      win_pe_vol=VALUES(win_pe_vol),
                      pcr_total=VALUES(pcr_total),
                      pcr_window=VALUES(pcr_window),
                      max_oi_ce_strike=VALUES(max_oi_ce_strike),
                      max_oi_ce=VALUES(max_oi_ce),
                      max_oi_pe_strike=VALUES(max_oi_pe_strike),
                      max_oi_pe=VALUES(max_oi_pe),
                      d_ce_oi_total=VALUES(d_ce_oi_total),
                      d_pe_oi_total=VALUES(d_pe_oi_total),
                      d_ce_oi_window=VALUES(d_ce_oi_window),
                      d_pe_oi_window=VALUES(d_pe_oi_window),
                      d_pcr_window=VALUES(d_pcr_window),
                      day_bias=VALUES(day_bias),
                      bias_score=VALUES(bias_score),
                      source=VALUES(source)
                ", [
                    $payload['symbol'], $payload['expiry_date'], $payload['trade_date'],
                    $payload['spot'], $payload['atm_strike'], $payload['strike_step'], $payload['window_strikes'],

                    $payload['total_ce_oi'], $payload['total_pe_oi'], $payload['total_ce_vol'], $payload['total_pe_vol'],
                    $payload['win_ce_oi'], $payload['win_pe_oi'], $payload['win_ce_vol'], $payload['win_pe_vol'],
                    $payload['pcr_total'], $payload['pcr_window'],
                    $payload['max_oi_ce_strike'], $payload['max_oi_ce'],
                    $payload['max_oi_pe_strike'], $payload['max_oi_pe'],
                    $payload['d_ce_oi_total'], $payload['d_pe_oi_total'],
                    $payload['d_ce_oi_window'], $payload['d_pe_oi_window'], $payload['d_pcr_window'],
                    $payload['day_bias'], $payload['bias_score'], $payload['source']
                ]);
            }
        }

        CLI::write("Done.", 'green');

        return EXIT_SUCCESS;
    }
}
