<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class OiPrune extends BaseCommand
{
    protected $group       = 'OI';
    protected $name        = 'oi:prune';
    protected $description = 'Prune old oi_snapshots rows older than N months. Supports --dry-run, --symbol, --yes. Logs results + cleans logs.';

    protected $usage = 'oi:prune [months] [--symbol NIFTY] [--dry-run] [--chunk 50000] [--yes] [--logs-keep-months 6] [--optimize-threshold 100000]';
    protected $options = [
        '--symbol'            => 'Prune only a specific symbol (e.g., NIFTY). Default: all symbols.',
        '--dry-run'           => 'Do not delete; only show counts and log run.',
        '--chunk'             => 'Delete chunk size. Default 50000.',
        '--yes'               => 'Skip interactive confirmation (required for cron).',
        '--logs-keep-months'  => 'Keep prune logs for N months. Default 6.',
        '--optimize-threshold'=> 'Suggest OPTIMIZE TABLE if deleted rows >= this threshold. Default 100000.',
    ];

    public function run(array $params)
    {
        $t0 = microtime(true);

        $months = isset($params[0]) ? (int)$params[0] : 3;
        if ($months < 1) $months = 1;

        $symbol = CLI::getOption('symbol'); // string|null
        $dryRun = CLI::getOption('dry-run') !== null;
        $yes    = CLI::getOption('yes') !== null;

        $chunk = (int) (CLI::getOption('chunk') ?? 50000);
        if ($chunk < 1000) $chunk = 1000;

        $logsKeepMonths = (int) (CLI::getOption('logs-keep-months') ?? 6);
        if ($logsKeepMonths < 1) $logsKeepMonths = 1;

        $optimizeThreshold = (int) (CLI::getOption('optimize-threshold') ?? 100000);
        if ($optimizeThreshold < 1) $optimizeThreshold = 1;

        $db = Database::connect();

        // cutoff = first day of current month minus N months
        $cutoff = date('Y-m-01 00:00:00', strtotime("-{$months} months"));
        $runTs  = date('Y-m-d H:i:s');

        // Build WHERE and bindings once
        $where = "ts < ?";
        $binds = [$cutoff];

        if ($symbol !== null && $symbol !== '') {
            $where .= " AND symbol = ?";
            $binds[] = $symbol;
        } else {
            $symbol = null;
        }

        $status = 'OK';
        $message = null;
        $rowsTarget = 0;
        $rowsDeleted = 0;

        try {
            // Count target rows
            $countSql = "SELECT COUNT(*) AS c FROM oi_snapshots WHERE {$where}";
            $rowsTarget = (int) ($db->query($countSql, $binds)->getRow()->c ?? 0);

            CLI::write("Run:    {$runTs}");
            CLI::write("Cutoff: {$cutoff}");
            CLI::write("Symbol: " . ($symbol ?? 'ALL'));
            CLI::write("Mode:   " . ($dryRun ? 'DRY-RUN (no delete)' : 'DELETE'));
            CLI::write("Target rows: {$rowsTarget}");
            CLI::newLine();

            // Confirmation (only for DELETE mode)
            if (!$dryRun) {
                if (!$yes) {
                    $prompt = "Proceed to DELETE {$rowsTarget} rows" . ($symbol ? " for {$symbol}" : "") . " older than {$cutoff}?";
                    if (!CLI::prompt($prompt, ['y', 'n'], 'n') || strtolower(trim(CLI::prompt("Type y to confirm", null, 'n'))) !== 'y') {
                        CLI::write("Aborted.");
                        $status = 'OK';
                        $message = 'User aborted';
                        $this->logRun($db, $runTs, $cutoff, $months, $symbol, $dryRun, $rowsTarget, 0, 0, 'OK', $message);
                        return;
                    }
                }
            }

            if (!$dryRun && $rowsTarget > 0) {
                // Chunked deletes to avoid long locks/undo explosion
                while (true) {
                    $delSql = "DELETE FROM oi_snapshots WHERE {$where} LIMIT {$chunk}";
                    $db->query($delSql, $binds);
                    $affected = $db->affectedRows();
                    $rowsDeleted += $affected;

                    if ($affected > 0) {
                        CLI::write("Deleted chunk: {$affected} (total: {$rowsDeleted})");
                    }
                    if ($affected < $chunk) break;
                }
            }

        } catch (\Throwable $e) {
            $status = 'FAIL';
            $message = substr($e->getMessage(), 0, 255);
            CLI::error("Prune failed: " . $e->getMessage());
        }

        $durationMs = (int) round((microtime(true) - $t0) * 1000);

        // Log run into DB (best effort)
        $this->logRun($db, $runTs, $cutoff, $months, $symbol, $dryRun, $rowsTarget, $rowsDeleted, $durationMs, $status, $message);

        // Cleanup old log rows (best effort)
        $this->cleanupLogs($db, $logsKeepMonths);

        CLI::newLine();
        CLI::write("Done. Deleted: {$rowsDeleted} | Duration: {$durationMs} ms | Status: {$status}");

        // Suggest OPTIMIZE only when it’s likely worth it
        if (!$dryRun && $rowsDeleted >= $optimizeThreshold) {
            CLI::newLine();
            CLI::write("Suggestion: You deleted {$rowsDeleted} rows. Consider running (during off-hours):");
            CLI::write("  OPTIMIZE TABLE oi_snapshots;");
            CLI::write("Note: OPTIMIZE can be heavy; run after market or night.");
        }
    }

    private function logRun($db, string $runTs, string $cutoff, int $months, ?string $symbol, bool $dryRun,
                            int $rowsTarget, int $rowsDeleted, int $durationMs, string $status, ?string $message): void
    {
        try {
            $db->table('oi_prune_logs')->insert([
                'run_ts'       => $runTs,
                'cutoff'       => $cutoff,
                'months'       => $months,
                'symbol'       => $symbol,
                'dry_run'      => $dryRun ? 1 : 0,
                'rows_target'  => $rowsTarget,
                'rows_deleted' => $rowsDeleted,
                'duration_ms'  => $durationMs,
                'status'       => $status,
                'message'      => $message,
            ]);
        } catch (\Throwable $e) {
            CLI::error("Logging failed (oi_prune_logs): " . $e->getMessage());
        }
    }

    private function cleanupLogs($db, int $keepMonths): void
    {
        try {
            $cutoffLogs = date('Y-m-01 00:00:00', strtotime("-{$keepMonths} months"));
            $db->query("DELETE FROM oi_prune_logs WHERE run_ts < ?", [$cutoffLogs]);
            $deleted = $db->affectedRows();
            if ($deleted > 0) {
                CLI::write("Log cleanup: deleted {$deleted} old prune log rows (keep last {$keepMonths} months).");
            }
        } catch (\Throwable $e) {
            CLI::error("Log cleanup failed: " . $e->getMessage());
        }
    }
}
