<?php
namespace App\Models;

use CodeIgniter\Model;

class OiSnapshotModel extends Model
{
    protected $table = 'oi_snapshots';
    protected $allowedFields = [
        'ts','symbol','underlying','expiry','strike','opt','oi','chg_oi','ltp','vol'
    ];
    protected $useTimestamps = false;

    public function latestStamp(string $symbol): ?array
    {
        $row = $this->select('ts, expiry, underlying')
            ->where('symbol', $symbol)
            ->orderBy('ts','DESC')
            ->first();

             // ✅ ADD LOG HERE
            log_message('debug', '[OI_SNAPSHOT] latestStamp()', [
                'symbol' => $symbol,
                'found'  => $row ? 'YES' : 'NO',
                'ts'     => $row['ts'] ?? null,
                'expiry' => $row['expiry'] ?? null,
            ]);

        return $row;
    }

    public function rowsAt(string $symbol, string $expiry, string $ts): array
    {
        return $this->where([
                'symbol'=>$symbol,
                'expiry'=>$expiry,
                'ts'=>$ts
            ])
            ->select('strike, opt, oi')
            ->findAll();
    }

    public function nearestRowsBefore(string $symbol, string $expiry, string $ts): array
    {
        // nearest snapshot at or before $ts for each strike+opt
        $sql = "SELECT t1.strike, t1.opt, t1.oi
                FROM oi_snapshots t1
                JOIN (
                    SELECT strike, opt, MAX(ts) AS mts
                    FROM oi_snapshots
                    WHERE symbol=? AND expiry=? AND ts<=?
                    GROUP BY strike, opt
                ) t2
                ON t1.strike=t2.strike AND t1.opt=t2.opt AND t1.ts=t2.mts
                WHERE t1.symbol=? AND t1.expiry=?";
        return $this->db->query($sql, [$symbol,$expiry,$ts,$symbol,$expiry])->getResultArray();
    }
}
