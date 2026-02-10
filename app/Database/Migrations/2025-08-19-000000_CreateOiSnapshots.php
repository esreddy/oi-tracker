<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOiSnapshots extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type'=>'BIGINT','auto_increment'=>true],
            'ts'          => ['type'=>'DATETIME','null'=>false],
            'symbol'      => ['type'=>'VARCHAR','constraint'=>16,'null'=>false],
            'underlying'  => ['type'=>'DECIMAL','constraint'=>'10,2','null'=>false],
            'expiry'      => ['type'=>'DATE','null'=>false],
            'strike'      => ['type'=>'INT','null'=>false],
            'opt'         => ['type'=>"ENUM('CE','PE')",'null'=>false],
            'oi'          => ['type'=>'BIGINT','null'=>false,'default'=>0],
            'chg_oi'      => ['type'=>'BIGINT','null'=>false,'default'=>0],
            'ltp'         => ['type'=>'DECIMAL','constraint'=>'10,2','null'=>true],
            'vol'         => ['type'=>'BIGINT','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['ts','symbol','expiry','strike','opt'], 'uniq_row');
        $this->forge->addKey(['symbol','expiry','ts'], false, false, 'sym_exp_ts');
        $this->forge->addKey(['symbol','expiry','strike','opt','ts'], false, false, 'strike_idx');
        $this->forge->createTable('oi_snapshots', true);
    }

    public function down()
    {
        $this->forge->dropTable('oi_snapshots', true);
    }
}
