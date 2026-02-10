<?php

namespace App\Models;

use CodeIgniter\Model;

class IndexEodModel extends Model
{
    protected $table         = 'oi_index_eod';
    protected $primaryKey    = ''; // composite key handled by upsert SQL
    protected $returnType    = 'array';
    protected $allowedFields = [
        'symbol','trade_date','open','high','low','close','prev_close','source','fetched_at'
    ];

    public function getMaxDate(string $symbol): ?string
    {
        $row = $this->db->table($this->table)
            ->selectMax('trade_date', 'maxd')
            ->where('symbol', $symbol)
            ->get()->getRowArray();

        return $row['maxd'] ?? null;
    }

    public function upsertRow(array $r): void
    {
        $sql = "INSERT INTO {$this->table}
                (symbol, trade_date, open, high, low, close, prev_close, source, fetched_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                  open=VALUES(open),
                  high=VALUES(high),
                  low=VALUES(low),
                  close=VALUES(close),
                  prev_close=VALUES(prev_close),
                  source=VALUES(source),
                  fetched_at=NOW()";

        $this->db->query($sql, [
            $r['symbol'],
            $r['trade_date'],
            $r['open'],
            $r['high'],
            $r['low'],
            $r['close'],
            $r['prev_close'],
            $r['source'] ?? 'yahoo_chart',
        ]);
    }
}
