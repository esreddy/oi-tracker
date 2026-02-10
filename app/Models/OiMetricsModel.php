<?php
namespace App\Models;

use CodeIgniter\Model;

class OiMetricsModel extends Model
{
    protected $table = 'oi_metrics';
    protected $allowedFields = [
        'ts','symbol','expiry','underlying','atm','window_min','pcr','bias',
        'top_call_strike','top_call_oi','top_call_delta',
        'top_put_strike','top_put_oi','top_put_delta',
        'price_vs_oi','notes'
    ];
    public $useTimestamps = false;

    public function lastN(string $symbol, int $limit = 12): array
    {
        return $this->where('symbol',$symbol)
            ->orderBy('ts','DESC')
            ->limit($limit)
            ->findAll();
    }
}
