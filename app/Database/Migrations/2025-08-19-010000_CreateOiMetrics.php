<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOiMetrics extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type'=>'BIGINT','auto_increment'=>true],
            'ts'            => ['type'=>'DATETIME','null'=>false],       // snapshot time (same as oi_snapshots ts)
            'symbol'        => ['type'=>'VARCHAR','constraint'=>16,'null'=>false],
            'expiry'        => ['type'=>'DATE','null'=>false],
            'underlying'    => ['type'=>'DECIMAL','constraint'=>'10,2','null'=>false],
            'atm'           => ['type'=>'INT','null'=>false],
            'window_min'    => ['type'=>'INT','null'=>false,'default'=>10],

            'pcr'           => ['type'=>'DECIMAL','constraint'=>'6,2','null'=>true],
            'bias'          => ['type'=>'VARCHAR','constraint'=>16,'null'=>false,'default'=>'Range-bound'],

            'top_call_strike'=>['type'=>'INT','null'=>true],
            'top_call_oi'    =>['type'=>'BIGINT','null'=>true],
            'top_call_delta' =>['type'=>'BIGINT','null'=>true],

            'top_put_strike' =>['type'=>'INT','null'=>true],
            'top_put_oi'     =>['type'=>'BIGINT','null'=>true],
            'top_put_delta'  =>['type'=>'BIGINT','null'=>true],

            'price_vs_oi'   => ['type'=>'VARCHAR','constraint'=>24,'null'=>true], // Long Build-up, Short Build-up, etc.
            'notes'         => ['type'=>'VARCHAR','constraint'=>255,'null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['ts','symbol','expiry','window_min'], 'uniq_metric');
        $this->forge->addKey(['symbol','expiry','ts'], false, false, 'sym_exp_ts');
        $this->forge->createTable('oi_metrics', true);
    }

    public function down()
    {
        $this->forge->dropTable('oi_metrics', true);
    }
}
