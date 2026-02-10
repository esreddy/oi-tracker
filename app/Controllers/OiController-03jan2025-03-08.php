<?php
namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use DateTime;
use DateTimeZone;
use App\Controllers\BaseController;

class OiController extends BaseController
{
    public function index()
    {
        $t0 = microtime(true);
        $db = \Config\Database::connect();

        $this->dbg('[OI_DASH] index() hit');

        // Latest DB insert (oi_snapshots.ts is UTC)
        $row = $db->table('oi_snapshots')->selectMax('ts')->get()->getRowArray();
        $lastFetched = $row['ts'] ?? null;

        $this->dbg('[OI_DASH] lastFetched from DB', [
            'lastFetched_utc' => $lastFetched,
            'minsAgo'         => $this->minsSince($lastFetched),
        ]);

        // Logs to infer cron heartbeat
        $fetchLog  = WRITEPATH . 'logs/oi_fetch.log';
        $enrichLog = WRITEPATH . 'logs/oi_enrich.log';
        //dd(WRITEPATH, is_writable(WRITEPATH.'logs'));exit;
        
        //log_message('info', "OIController::index - fetchLog: $fetchLog, enrichLog: $enrichLog");
        
        $health = [
            'db' => [
                'ist'     => $this->toIST($lastFetched),
                'minsAgo' => $this->minsSince($lastFetched),
            ],
            'fetch' => [
                'ist'     => $this->fileTimeIST($fetchLog),
                'minsAgo' => $this->fileMinsAgo($fetchLog),
            ],
            'enrich' => [
                'ist'     => $this->fileTimeIST($enrichLog),
                'minsAgo' => $this->fileMinsAgo($enrichLog),
            ],
        ];

        $this->dbg(
            sprintf('[OI_TRACK] response ready | elapsed_ms=%d', 
                (int) round((microtime(true) - $t0) * 1000)
            )
        );

        return view('oi_dashboard', [
            'health'      => $health,
            'lastFetched' => $lastFetched,
        ]);
    }

    public function track()
    {
        $t0 = microtime(true);
        $symbol = strtoupper($this->request->getGet('symbol') ?? 'NIFTY');
        $expiry = $this->request->getGet('expiry'); // optional
        //$lookbacks = [5,10,15,30]; // minutes
        // read custom minutes from ?lookbacks=5,10,15,30
        $def = [5,10,15,30];
        $lookbacksParam = $this->request->getGet('lookbacks');
        if ($lookbacksParam) {
            $lookbacks = array_values(array_unique(array_filter(array_map(function($x){
                $v = (int)trim($x);
                return ($v >= 1 && $v <= 120) ? $v : null; // guardrails (1–120m)
            }, explode(',', $lookbacksParam)))));
            if (!$lookbacks) { $lookbacks = $def; }
        } else {
            $lookbacks = $def;
        }
        sort($lookbacks); // ascending for consistent keys like oi_5m, oi_10m, ...
        $limitStrikes = 10;        // last 10 strikes near ATM

        $db = \Config\Database::connect();

        // Latest expiry + timestamp
        $latest = $db->query("
            SELECT expiry, MAX(ts) as ts
            FROM oi_snapshots
            WHERE symbol=?
            GROUP BY expiry
            ORDER BY ts DESC
            LIMIT 1
        ", [$symbol])->getRowArray();
        if (!$latest) {
            return $this->response->setJSON(['ok'=>false,'msg'=>'No data']);
        }
        $expiry = $expiry ?: $latest['expiry'];
        $latestTs = $latest['ts'];

        // Current OI snapshot
        $rows = $db->query("
            SELECT strike,opt,oi,chg_oi,underlying
            FROM oi_snapshots
            WHERE symbol=? AND expiry=? AND ts=?
        ", [$symbol,$expiry,$latestTs])->getResultArray();

        $currCE=[]; $currPE=[]; $underlying=0;
        foreach ($rows as $r){
            $underlying=(float)$r['underlying'];
            if ($r['opt']==='CE') $currCE[$r['strike']]=$r;
            if ($r['opt']==='PE') $currPE[$r['strike']]=$r;
        }

        // ATM and strikes around
        $step=50; $atm=(int)round($underlying/$step)*$step;
        $strikes = array_unique(array_merge(array_keys($currCE),array_keys($currPE)));
        sort($strikes);
        // take 10 closest strikes to ATM
        usort($strikes, fn($a,$b)=>abs($a-$atm)-abs($b-$atm));
        $strikes = array_slice($strikes,0,$limitStrikes);
        sort($strikes);

        // Fetch historical OI at each lookback
        $data=[];
        foreach ($strikes as $st){
            $rowCE = $currCE[$st] ?? null;
            $rowPE = $currPE[$st] ?? null;
            $entry=['strike'=>$st];
            foreach(['CE'=>$rowCE,'PE'=>$rowPE] as $opt=>$cur){
                if(!$cur){ continue; }
                $snap=['cur_oi'=>(int)$cur['oi'],'cur_chg'=>(int)$cur['chg_oi']];
                foreach($lookbacks as $m){
                    $cutoff = (new \DateTime($latestTs,new \DateTimeZone('UTC')))
                                ->modify("-{$m} minutes")->format('Y-m-d H:i:s');
                    $prev = $db->query("
                        SELECT oi,chg_oi
                        FROM oi_snapshots
                        WHERE symbol=? AND expiry=? AND strike=? AND opt=? AND ts<=?
                        ORDER BY ts DESC LIMIT 1
                    ", [$symbol,$expiry,$st,$opt,$cutoff])->getRowArray();
                    $snap["oi_{$m}m"]  = $prev['oi']  ?? null;
                    $snap["chg_{$m}m"] = $prev['chg_oi'] ?? null;
                }
                $entry[$opt]=$snap;
            }
            $data[]=$entry;
        }

        $this->dbg(
            sprintf('[OI_TRACK] response ready | elapsed_ms=%d', 
                (int) round((microtime(true) - $t0) * 1000)
            )
        );

        return $this->response->setJSON([
            'ok'       => true,
            'symbol'   => $symbol,
            'expiry'   => $expiry,
            'latestTs' => $latestTs,
            'atm'      => $atm,
            'lookbacks'=> $lookbacks,   // <— add this
            'rows'     => $data
        ]);
    }


    // ----------------- JSON: snapshot panel (expiry-aware) -----------------
    // GET /oi/json?symbol=NIFTY&window=10&expiry=YYYY-MM-DD (expiry optional)
    public function json()
    {
        $symbol = strtoupper($this->request->getGet('symbol') ?? 'NIFTY');
        $window = max(1, (int)($this->request->getGet('window') ?? 10)); // minutes
        $expiry = $this->request->getGet('expiry'); // optional YYYY-MM-DD

        $db = \Config\Database::connect();

        // --- latest snapshot timestamp per expiry for this symbol ---
        $latestRows = $db->query("
            SELECT s.expiry, MAX(s.ts) AS ts
            FROM oi_snapshots s
            WHERE s.symbol = ?
            GROUP BY s.expiry
            ORDER BY ts DESC
        ", [$symbol])->getResultArray();
        if (!$latestRows) {
            return $this->response->setJSON(['ok'=>false,'msg'=>'No data found']);
        }

        // pick expiry (nearest active if not provided)
        $chosenExpiry = $expiry ?: $latestRows[0]['expiry'];
        $latestTs = null;
        foreach ($latestRows as $r) {
            if ($r['expiry'] === $chosenExpiry) { $latestTs = $r['ts']; break; }
        }
        if (!$latestTs) { $chosenExpiry = $latestRows[0]['expiry']; $latestTs = $latestRows[0]['ts']; }

        // --- CURRENT snapshot rows for chosen expiry & ts (pull chg_oi too) ---
        $cur = $db->query("
            SELECT t.strike, t.opt, t.oi, t.chg_oi, t.underlying
            FROM oi_snapshots t
            WHERE t.symbol = ? AND t.expiry = ? AND t.ts = ?
        ", [$symbol, $chosenExpiry, $latestTs])->getResultArray();

        if (!$cur) {
            return $this->response->setJSON(['ok'=>false,'msg'=>'No rows at latest ts']);
        }

        $currCE = []; $currPE = [];
        $callNseDelta = []; $putNseDelta = []; // NSE official
        $underlying = 0.0;

        foreach ($cur as $r) {
            $underlying = (float)$r['underlying'];
            $st = (int)$r['strike'];
            $oi = (int)$r['oi'];
            $nse = (int)($r['chg_oi'] ?? 0); // NSE “CHNG IN OI”

            if ($r['opt'] === 'CE') {
                $currCE[$st]      = $oi;
                $callNseDelta[$st]= $nse;
            } elseif ($r['opt'] === 'PE') {
                $currPE[$st]      = $oi;
                $putNseDelta[$st] = $nse;
            }
        }

        // strike step & ATM
        $strikes = array_keys($currCE + $currPE);
        sort($strikes);
        $step = 50;
        for ($i=1; $i<count($strikes); $i++) { $d=$strikes[$i]-$strikes[$i-1]; if ($d>0){ $step=$d; break; } }
        $atm = (int)round($underlying/$step)*$step;

        // --- INTRA ΔOI (vs window start) ---
        $windowStartUtc = (new DateTime($latestTs, new DateTimeZone('UTC')))
                            ->modify("-{$window} minutes")->format('Y-m-d H:i:s');

        $winRows = $db->query("
            SELECT s.strike, s.opt, s.oi
            FROM oi_snapshots s
            JOIN (
                SELECT strike, opt, MAX(ts) AS ts
                FROM oi_snapshots
                WHERE symbol = ? AND expiry = ? AND ts <= ?
                GROUP BY strike, opt
            ) x ON s.strike=x.strike AND s.opt=x.opt AND s.ts=x.ts
            WHERE s.symbol = ? AND s.expiry = ?
        ", [$symbol, $chosenExpiry, $windowStartUtc, $symbol, $chosenExpiry])->getResultArray();

        $winCE=[]; $winPE=[];
        foreach ($winRows as $r){
            $k=(int)$r['strike'];
            if ($r['opt']==='CE') $winCE[$k]=(int)$r['oi'];
            if ($r['opt']==='PE') $winPE[$k]=(int)$r['oi'];
        }

        $callDelta = []; $putDelta = [];
        foreach ($currCE as $k=>$v) { $callDelta[$k] = $v - ($winCE[$k] ?? 0); }
        foreach ($currPE as $k=>$v) { $putDelta[$k]  = $v - ($winPE[$k] ?? 0); }

        // --- DAY ΔOI (your computed: latest OI − yesterday EOD OI, in IST) ---
        $tzIST = new DateTimeZone('Asia/Kolkata');
        $todayIst = new DateTime('now', $tzIST); $todayIst->setTime(0,0,0);
        $todayUtc = clone $todayIst; $todayUtc->setTimezone(new DateTimeZone('UTC'));
        $todayUtcS = $todayUtc->format('Y-m-d H:i:s');

        $yRows = $db->query("
            SELECT s.strike, s.opt, s.oi
            FROM oi_snapshots s
            JOIN (
                SELECT strike, opt, MAX(ts) AS ts
                FROM oi_snapshots
                WHERE symbol = ? AND expiry = ? AND ts < ?
                GROUP BY strike, opt
            ) x ON s.strike=x.strike AND s.opt=x.opt AND s.ts=x.ts
            WHERE s.symbol = ? AND s.expiry = ?
        ", [$symbol, $chosenExpiry, $todayUtcS, $symbol, $chosenExpiry])->getResultArray();

        $prevDayCE=[]; $prevDayPE=[];
        foreach ($yRows as $r){
            $k=(int)$r['strike'];
            if ($r['opt']==='CE') $prevDayCE[$k]=(int)$r['oi'];
            if ($r['opt']==='PE') $prevDayPE[$k]=(int)$r['oi'];
        }

        $callDayDelta = []; $putDayDelta = [];
        foreach ($currCE as $k=>$v) { $callDayDelta[$k] = $v - ($prevDayCE[$k] ?? 0); }
        foreach ($currPE as $k=>$v) { $putDayDelta[$k]  = $v - ($prevDayPE[$k] ?? 0); }

        // --- Tops, PCR, Bias (unchanged) ---
        arsort($currCE); $topCalls = array_slice($currCE, 0, 6, true);
        arsort($currPE); $topPuts  = array_slice($currPE,  0, 6, true);

        $totCall = array_sum($currCE);
        $totPut  = array_sum($currPE);
        $pcr     = $totCall>0 ? round($totPut/$totCall, 2) : null;
        $bias    = $pcr===null ? null : ($pcr>1.05 ? 'Bullish' : ($pcr<0.95 ? 'Bearish' : 'Range-bound'));

        return $this->response->setJSON([
            'ok'        => true,
            'symbol'    => $symbol,
            'expiry'    => $chosenExpiry,
            'ts'        => $latestTs,        // UTC
            'price'     => $underlying,
            'window'    => $window,
            'atm'       => $atm,
            'pcr'       => $pcr,
            'bias'      => $bias,

            'topCalls'  => $topCalls,
            'topPuts'   => $topPuts,

            // INTRA
            'callDelta'    => $callDelta,
            'putDelta'     => $putDelta,

            // DAY (computed)
            'callDayDelta' => $callDayDelta,
            'putDayDelta'  => $putDayDelta,

            // DAY (NSE official)
            'callNseDelta' => $callNseDelta,
            'putNseDelta'  => $putNseDelta,
        ], ResponseInterface::HTTP_OK);
    }

    // ----------------- JSON: PCR trend & classifications -----------------
    // GET /oi/metrics?symbol=NIFTY&limit=12
    public function metrics()
    {
        $symbol = strtoupper($this->request->getGet('symbol') ?? 'NIFTY');
        $limit  = (int)($this->request->getGet('limit') ?? 12);

        $mm = new \App\Models\OiMetricsModel();
        $rows = $mm->lastN($symbol, $limit);

        return $this->response->setJSON(['ok'=>true,'symbol'=>$symbol,'rows'=>$rows]);
    }

    // ----------------- JSON: Breakout + Swing High/Low (15m) -----------------
    // GET /oi/daywise?symbol=NIFTY&days=7
     
    // GET /oi/signals?symbol=NIFTY
    public function signals()
    {
        $symbol = strtoupper($this->request->getGet('symbol') ?? 'NIFTY');

        $db = \Config\Database::connect();

        // Today's IST range -> query UTC
        $tzIST = new \DateTimeZone('Asia/Kolkata');
        $tzUTC = new \DateTimeZone('UTC');
        $startIST = new \DateTime('today', $tzIST);
        $endIST   = (clone $startIST)->modify('+1 day');

        $startUTC = (clone $startIST)->setTimezone($tzUTC)->format('Y-m-d H:i:s');
        $endUTC   = (clone $endIST )->setTimezone($tzUTC)->format('Y-m-d H:i:s');

        $rows = $db->query(
            "SELECT ts, underlying FROM oi_snapshots
             WHERE symbol=? AND ts>=? AND ts<? ORDER BY ts ASC",
            [$symbol, $startUTC, $endUTC]
        )->getResultArray();

        if (!$rows) {
            return $this->response->setJSON(['ok'=>true,'symbol'=>$symbol,'signals'=>[],'note'=>'no data today']);
        }

        // Build 15-min candles
        $candles = [];
        foreach ($rows as $r) {
            $t = strtotime($r['ts']); // UTC epoch
            $b = (int) (floor($t / 900) * 900); // 15-min bucket
            $p = (float)$r['underlying'];

            if (!isset($candles[$b])) {
                $candles[$b] = ['t'=>$b,'o'=>$p,'h'=>$p,'l'=>$p,'c'=>$p,'first_ts'=>$t,'last_ts'=>$t];
            } else {
                $c = &$candles[$b];
                if ($t < $c['first_ts']) { $c['o'] = $p; $c['first_ts'] = $t; }
                if ($t > $c['last_ts'])  { $c['c'] = $p; $c['last_ts']  = $t; }
                if ($p > $c['h']) $c['h'] = $p;
                if ($p < $c['l']) $c['l'] = $p;
                unset($c);
            }
        }
        ksort($candles);
        $candles = array_values($candles);

        // Breakout detection
        $N = 10; $breakouts = [];
        for ($i=$N; $i<count($candles); $i++) {
            $win = array_slice($candles, $i-$N, $N);
            $maxHigh = max(array_column($win, 'h'));
            $body    = abs($candles[$i]['c'] - $candles[$i]['o']);
            $avgBody = array_sum(array_map(fn($k)=>abs($k['c']-$k['o']), $win)) / $N;

            if ($candles[$i]['c'] > $maxHigh && $body >= 1.5 * $avgBody) {
                $dt = new \DateTime('@'.$candles[$i]['t']); // UTC
                $dt->setTimezone($tzIST);
                $breakouts[] = [
                    'type'     => 'Bullish Breakout',
                    'time_ist' => $dt->format('Y-m-d H:i') . ' IST',
                    'close'    => round($candles[$i]['c'], 2),
                    'index'    => $i,
                ];
            }
        }
        $lastBreakout = $breakouts ? end($breakouts) : null;

        // Swing High/Low (5-bar fractals)
        $swHigh = $swLow = null;
        $n = count($candles);
        for ($i = 2; $i <= $n-3; $i++) {
            $h = $candles[$i]['h']; $l = $candles[$i]['l'];
            $isHigh = ($h > $candles[$i-1]['h'] && $h > $candles[$i-2]['h'] &&
                       $h >= $candles[$i+1]['h'] && $h >= $candles[$i+2]['h']);
            $isLow  = ($l < $candles[$i-1]['l'] && $l < $candles[$i-2]['l'] &&
                       $l <= $candles[$i+1]['l'] && $l <= $candles[$i+2]['l']);
            if ($isHigh) $swHigh = $i;
            if ($isLow)  $swLow  = $i;
        }

        $fmt = function($epochUTC) use ($tzIST) {
            $d = new \DateTime('@'.$epochUTC); $d->setTimezone($tzIST);
            return $d->format('Y-m-d H:i') . ' IST';
        };

        $lastSwingHigh = $swHigh !== null ? [
            'time_ist' => $fmt($candles[$swHigh]['t']),
            'price'    => round($candles[$swHigh]['h'], 2),
            'index'    => $swHigh,
        ] : null;

        $lastSwingLow = $swLow !== null ? [
            'time_ist' => $fmt($candles[$swLow]['t']),
            'price'    => round($candles[$swLow]['l'], 2),
            'index'    => $swLow,
        ] : null;

        return $this->response->setJSON([
            'ok'          => true,
            'symbol'      => $symbol,
            'lookback'    => $N,
            'last'        => $lastBreakout,
            'signals'     => $breakouts,
            'swing_high'  => $lastSwingHigh,
            'swing_low'   => $lastSwingLow,
        ]);
    }

    // ----------------- JSON: expiries list -----------------
    public function expiries()
    {
        $symbol = strtoupper($this->request->getGet('symbol') ?? 'NIFTY');
        $db = \Config\Database::connect();

        // Prefer current/future range; fallback to last 5
        $rows = $db->query(
            "SELECT DISTINCT expiry
             FROM oi_snapshots
             WHERE symbol=? AND expiry >= CURDATE() - INTERVAL 14 DAY
             ORDER BY expiry ASC",
            [$symbol]
        )->getResultArray();

        if (!$rows) {
            $rows = $db->query(
                "SELECT DISTINCT expiry
                 FROM oi_snapshots
                 WHERE symbol=?
                 ORDER BY expiry DESC
                 LIMIT 5",
                [$symbol]
            )->getResultArray();
            $rows = array_reverse($rows);
        }

        $exp = array_values(array_map(fn($r)=>$r['expiry'], $rows));
        return $this->response->setJSON(['ok'=>true, 'symbol'=>$symbol, 'expiries'=>$exp]);
    }

    // ----------------- JSON: health -----------------
    public function healthz()
    {
        $db = \Config\Database::connect();
        $ok = true; $err = null;
        try { $db->query('SELECT 1'); } catch (\Throwable $e) { $ok=false; $err=$e->getMessage(); }

        $row = $db->table('oi_snapshots')->selectMax('ts')->get()->getRowArray();
        $last = $row['ts'] ?? null;

        $fetchLog  = WRITEPATH . 'logs/oi_fetch.log';
        $enrichLog = WRITEPATH . 'logs/oi_enrich.log';

        $age = function(?string $utcTs){
            if (!$utcTs) return null;
            $from = new \DateTime($utcTs,new \DateTimeZone('UTC'));
            $now  = new \DateTime('now',new \DateTimeZone('UTC'));
            return (int) floor(($now->getTimestamp() - $from->getTimestamp())/60);
        };
        $fileAge = function(string $p){
            if (!is_file($p)) return null;
            return (int) floor((time() - filemtime($p))/60);
        };

        return $this->response->setJSON([
            'db_ok'               => $ok,
            'error'               => $err,
            'last_insert_ts_utc'  => $last,
            'last_insert_age_min' => $age($last),
            'fetch_log_age_min'   => $fileAge($fetchLog),
            'enrich_log_age_min'  => $fileAge($enrichLog),
        ]);
    }

    // ================= Helpers =================
    private function toIST(?string $utcTs): ?string
    {
        if (!$utcTs) return null;
        try {
            $dt = new \DateTime($utcTs, new \DateTimeZone('UTC'));
            $dt->setTimezone(new \DateTimeZone('Asia/Kolkata'));
            return $dt->format('Y-m-d H:i:s') . ' IST';
        } catch (\Throwable $e) { return null; }
    }

    private function minsSince(?string $utcTs): ?int
    {
        if (!$utcTs) return null;
        try {
            $from = new \DateTime($utcTs, new \DateTimeZone('UTC'));
            $now  = new \DateTime('now', new \DateTimeZone('UTC'));
            return (int) floor(($now->getTimestamp() - $from->getTimestamp()) / 60);
        } catch (\Throwable $e) { return null; }
    }

    private function fileTimeIST(string $path): ?string
    {
        if (!is_file($path)) return null;
        $ts = @filemtime($path); if (!$ts) return null;
        $dt = new \DateTime('@'.$ts); $dt->setTimezone(new \DateTimeZone('Asia/Kolkata'));
        return $dt->format('Y-m-d H:i:s') . ' IST';
    }

    private function fileMinsAgo(string $path): ?int
    {
        if (!is_file($path)) return null;
        $ts = @filemtime($path); if (!$ts) return null;
        return (int) floor((time() - $ts) / 60);
    }

    public function fetchTest()
    {
        $symbol = strtoupper($this->request->getGet('symbol') ?? 'NIFTY');
        $client = new \App\Services\NseClient();
        try {
            $oc = $client->fetchOptionChain($symbol);
            return $this->response->setJSON([
                'ok' => true,
                'symbol' => $symbol,
                'expiries' => \App\Services\NseClient::expiryDates($oc),
                'sample' => array_slice($oc['records']['data'] ?? [], 0, 2),
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['ok'=>false,'msg'=>$e->getMessage()]);
        }
    }

    private function dbg(string $tag, array $ctx = []): void
    {
        // keep logs small & consistent
        $ctx['route'] = $this->request->getPath();
        $ctx['ip']    = $this->request->getIPAddress();
        log_message('debug', $tag, $ctx);
    }

    /**
     * Daywise OI/Price behaviour (IST days; snapshots.ts stored in UTC)
     * GET /oi/daywise?symbol=NIFTY&days=7&mode=front|all
     */

    public function daywise()
    {
        $symbol = strtoupper(trim($this->request->getGet('symbol') ?? 'NIFTY'));
        $days   = (int)($this->request->getGet('days') ?? 7);
        $mode   = strtolower(trim($this->request->getGet('mode') ?? 'front')); // front | all

        if ($days < 3) $days = 3;
        if ($days > 30) $days = 30;
        if (!in_array($mode, ['front','all'], true)) $mode = 'front';

        $db = \Config\Database::connect();

        // ts is stored as UTC in DB. Group by IST day, take max(ts) per day.
        $dailySql = "
            SELECT DATE(CONVERT_TZ(ts,'+00:00','+05:30')) AS d, MAX(ts) AS mts
            FROM oi_snapshots
            WHERE symbol = ?
            GROUP BY DATE(CONVERT_TZ(ts,'+00:00','+05:30'))
            ORDER BY d DESC
            LIMIT {$days}
        ";
        $daily = $db->query($dailySql, [$symbol])->getResultArray();

        $out = [];
        foreach ($daily as $row) {
            $d   = $row['d'];
            $mts = $row['mts'];

            // Underlying at that ts
            $u = $db->table('oi_snapshots')
                ->selectMax('underlying', 'u')
                ->where(['symbol'=>$symbol, 'ts'=>$mts])
                ->get()->getRowArray();
            $close = $u['u'] ?? null;

            $expiry = null;
            if ($mode === 'front') {
                $e = $db->table('oi_snapshots')
                    ->selectMin('expiry', 'e')
                    ->where(['symbol'=>$symbol, 'ts'=>$mts])
                    ->get()->getRowArray();
                $expiry = $e['e'] ?? null;
            }

            $qb = $db->table('oi_snapshots')
                ->select("opt, SUM(oi) AS s, SUM(COALESCE(vol,0)) AS v")
                ->where(['symbol'=>$symbol, 'ts'=>$mts])
                ->whereIn('opt', ['CE','PE'])
                ->groupBy('opt');
            if ($expiry) $qb->where('expiry', $expiry);
            $agg = $qb->get()->getResultArray();

            $ce = 0; $pe = 0; $ceVol = 0; $peVol = 0;
            foreach ($agg as $a) {
                if (($a['opt'] ?? '') === 'CE') { $ce = (int)$a['s']; $ceVol = (int)($a['v'] ?? 0); }
                if (($a['opt'] ?? '') === 'PE') { $pe = (int)$a['s']; $peVol = (int)($a['v'] ?? 0); }
            }
            $totalOi = $ce + $pe;
            $totalVol = $ceVol + $peVol;
            $pcr = ($ce > 0) ? round($pe / $ce, 2) : null;

            $out[] = [
                'day'      => $d,
                'ts'       => $mts,
                'expiry'   => $expiry,
                'close'    => ($close === null ? null : round((float)$close, 2)),
                'ce_oi'    => $ce,
                'pe_oi'    => $pe,
                'ce_vol'   => $ceVol,
                'pe_vol'   => $peVol,
                'total_vol'=> $totalVol,
                'total_oi' => $totalOi,
                'pcr'      => $pcr,
            ];
        }

        // Compute day-over-day diffs + behavior/signal
        for ($i=0; $i<count($out); $i++) {
            $prev = $out[$i+1] ?? null; // because sorted DESC
            $dPrice = null; $dOi = null;
            if ($prev && $out[$i]['close'] !== null && $prev['close'] !== null) {
                $dPrice = round($out[$i]['close'] - $prev['close'], 2);
            }
            if ($prev) {
                $dOi = $out[$i]['total_oi'] - ($prev['total_oi'] ?? 0);
                $out[$i]['d_vol'] = ($out[$i]['total_vol'] ?? 0) - ($prev['total_vol'] ?? 0);
            } else {
                $out[$i]['d_vol'] = null;
            }

            // 1) write diffs + classification onto the row FIRST
            $cls = $this->classifyDaywise($dPrice, $dOi);
            $out[$i]['d_price']  = $dPrice;
            $out[$i]['d_oi']     = $dOi;
            $out[$i]['behavior'] = $cls['behavior'];
            $out[$i]['signal']   = $cls['signal'];

            // 2) then compute strength (uses d_price/d_oi/d_vol/behavior)
            $strength = $this->strengthDaywiseRelative($out, $i);
            $out[$i]['strength'] = $strength['label'];
            $out[$i]['strength_score'] = $strength['score'];
            $out[$i]['oi_ratio']  = $strength['oi_ratio'];
            $out[$i]['vol_ratio'] = $strength['vol_ratio'];
        }

        return $this->response->setJSON([
            'ok'     => true,
            'symbol' => $symbol,
            'mode'   => $mode,
            'rows'   => $out,
        ]);
    }

    /**
     * Intraday addons summary for decision widgets.
     * GET /oi/intraday?symbol=NIFTY&mode=all
     * - Finds today's IST bucket (00:00..23:59 IST) using CONVERT_TZ on ts (stored UTC)
     * - Returns start snapshot totals and current snapshot totals (OI/Vol/PCR/Price)
     * - Also returns strike-shift from oi_metrics (latest vs ~60 minutes ago)
     * - Includes daywise baseline avg(|ΔOI|) and avg(|ΔVol|) from last 5 comparable days
     */
    public function intraday()
    {
        $symbol = strtoupper(trim($this->request->getGet('symbol') ?? 'NIFTY'));
        $mode   = strtolower(trim($this->request->getGet('mode') ?? 'all')); // all | front
        if ($mode !== 'front' && $mode !== 'all') $mode = 'all';

        $db = \Config\Database::connect();

        // Today's IST date
        $ist = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        $todayIst = $ist->format('Y-m-d');

        // Start/End ts (UTC) for today bucket
        $r = $db->query("
            SELECT MIN(ts) AS min_ts, MAX(ts) AS max_ts
            FROM oi_snapshots
            WHERE symbol=?
              AND DATE(CONVERT_TZ(ts,'+00:00','+05:30'))=?
        ", [$symbol, $todayIst])->getRowArray();

        $minTs = $r['min_ts'] ?? null;
        $maxTs = $r['max_ts'] ?? null;

        if (!$minTs || !$maxTs) {
            return $this->response->setJSON([
                'ok' => true,
                'symbol' => $symbol,
                'mode' => $mode,
                'today_ist' => $todayIst,
                'start' => null,
                'current' => null,
                'strike_shift' => null,
                'baseline' => null,
            ]);
        }

        // For front mode, lock expiry based on CURRENT snapshot
        $expiry = null;
        if ($mode === 'front') {
            $er = $db->query("SELECT MIN(expiry) AS expiry FROM oi_snapshots WHERE symbol=? AND ts=?", [$symbol, $maxTs])->getRowArray();
            $expiry = $er['expiry'] ?? null;
        }

        $start = $this->snapshotTotals($db, $symbol, $minTs, $mode, $expiry);
        $cur   = $this->snapshotTotals($db, $symbol, $maxTs, $mode, $expiry);

        // Deltas
        if ($start && $cur) {
            $cur['d_price'] = round(($cur['close'] ?? 0) - ($start['close'] ?? 0), 2);
            $cur['d_oi']    = (int)(($cur['total_oi'] ?? 0) - ($start['total_oi'] ?? 0));
            $cur['d_vol']   = (int)(($cur['total_vol'] ?? 0) - ($start['total_vol'] ?? 0));
        }

        // Strike shift from oi_metrics (latest vs 60m ago)
        $shift = $this->strikeShiftFromMetrics($db, $symbol);

        // Baseline from last 5 daywise comparable rows (abs deltas)
        $baseline = $this->daywiseBaseline($db, $symbol, 10, $mode); // scan last 10 days to collect 5 points

        return $this->response->setJSON([
            'ok' => true,
            'symbol' => $symbol,
            'mode' => $mode,
            'today_ist' => $todayIst,
            'start' => $start,
            'current' => $cur,
            'strike_shift' => $shift,
            'baseline' => $baseline,
        ]);
    }

    private function snapshotTotals($db, string $symbol, string $ts, string $mode, ?string $expiry = null): ?array
    {
        if ($mode === 'front') {
            if (!$expiry) return null;
            $row = $db->query("\
                SELECT
                  MAX(underlying) AS close_price,
                  SUM(CASE WHEN opt_type='CE' THEN oi ELSE 0 END) AS ce_oi,
                  SUM(CASE WHEN opt_type='PE' THEN oi ELSE 0 END) AS pe_oi,
                  SUM(CASE WHEN opt_type='CE' THEN vol ELSE 0 END) AS ce_vol,
                  SUM(CASE WHEN opt_type='PE' THEN vol ELSE 0 END) AS pe_vol
                FROM oi_snapshots
                WHERE symbol=? AND ts=? AND expiry=?
            ", [$symbol, $ts, $expiry])->getRowArray();
        } else {
            $row = $db->query("\
                SELECT
                  MAX(underlying) AS close_price,
                  SUM(CASE WHEN opt_type='CE' THEN oi ELSE 0 END) AS ce_oi,
                  SUM(CASE WHEN opt_type='PE' THEN oi ELSE 0 END) AS pe_oi,
                  SUM(CASE WHEN opt_type='CE' THEN vol ELSE 0 END) AS ce_vol,
                  SUM(CASE WHEN opt_type='PE' THEN vol ELSE 0 END) AS pe_vol
                FROM oi_snapshots
                WHERE symbol=? AND ts=?
            ", [$symbol, $ts])->getRowArray();
        }

        if (!$row) return null;
        $ceOi  = (int)($row['ce_oi'] ?? 0);
        $peOi  = (int)($row['pe_oi'] ?? 0);
        $ceVol = (int)($row['ce_vol'] ?? 0);
        $peVol = (int)($row['pe_vol'] ?? 0);
        $pcr = ($ceOi > 0) ? round(($peOi / $ceOi), 2) : null;

        return [
            'ts'        => $ts,
            'close'     => (float)($row['close_price'] ?? 0),
            'ce_oi'     => $ceOi,
            'pe_oi'     => $peOi,
            'total_oi'  => $ceOi + $peOi,
            'ce_vol'    => $ceVol,
            'pe_vol'    => $peVol,
            'total_vol' => $ceVol + $peVol,
            'pcr'       => $pcr,
        ];
    }

    private function strikeShiftFromMetrics($db, string $symbol): ?array
    {
        $latest = $db->query("SELECT * FROM oi_metrics WHERE symbol=? ORDER BY ts DESC LIMIT 1", [$symbol])->getRowArray();
        if (!$latest || empty($latest['ts'])) return null;

        $prev = $db->query("\
            SELECT * FROM oi_metrics
            WHERE symbol=? AND ts <= (SELECT DATE_SUB(?, INTERVAL 60 MINUTE))
            ORDER BY ts DESC
            LIMIT 1
        ", [$symbol, $latest['ts']])->getRowArray();

        return [
            'ts_now' => $latest['ts'] ?? null,
            'ts_prev'=> $prev['ts'] ?? null,
            'bias'   => $latest['bias'] ?? null,
            'pcr'    => isset($latest['pcr']) ? (float)$latest['pcr'] : null,
            'call_now' => isset($latest['top_call_strike']) ? (int)$latest['top_call_strike'] : null,
            'put_now'  => isset($latest['top_put_strike']) ? (int)$latest['top_put_strike'] : null,
            'call_prev'=> isset($prev['top_call_strike']) ? (int)$prev['top_call_strike'] : null,
            'put_prev' => isset($prev['top_put_strike']) ? (int)$prev['top_put_strike'] : null,
        ];
    }

    private function daywiseBaseline($db, string $symbol, int $scanDays, string $mode): ?array
    {
        // collect last N IST days
        $dayRows = $db->query("\
            SELECT DATE(CONVERT_TZ(ts,'+00:00','+05:30')) AS day_ist, MAX(ts) AS max_ts
            FROM oi_snapshots
            WHERE symbol=?
            GROUP BY day_ist
            ORDER BY day_ist DESC
            LIMIT ?
        ", [$symbol, $scanDays])->getResultArray();
        if (!$dayRows) return null;

        $tmp = [];
        foreach ($dayRows as $dr) {
            $ts = $dr['max_ts'] ?? null;
            if (!$ts) continue;
            $expiry = null;
            if ($mode === 'front') {
                $er = $db->query("SELECT MIN(expiry) AS expiry FROM oi_snapshots WHERE symbol=? AND ts=?", [$symbol, $ts])->getRowArray();
                $expiry = $er['expiry'] ?? null;
                if (!$expiry) continue;
            }
            $snap = $this->snapshotTotals($db, $symbol, $ts, $mode, $expiry);
            if (!$snap) continue;
            $tmp[] = $snap;
        }
        // compute deltas day-to-day (DESC)
        $absOi = [];
        $absVol = [];
        for ($i=0; $i<count($tmp)-1; $i++) {
            $dOi  = (int)(($tmp[$i]['total_oi'] ?? 0) - ($tmp[$i+1]['total_oi'] ?? 0));
            $dVol = (int)(($tmp[$i]['total_vol'] ?? 0) - ($tmp[$i+1]['total_vol'] ?? 0));
            if ($dOi !== 0)  $absOi[]  = abs($dOi);
            if ($dVol !== 0) $absVol[] = abs($dVol);
            if (count($absOi) >= 5 && count($absVol) >= 5) break;
        }
        if (count($absOi) < 3 || count($absVol) < 3) return null;
        $avgOi  = array_sum($absOi) / count($absOi);
        $avgVol = array_sum($absVol) / count($absVol);
        return [
            'avg_abs_doi' => (float)round($avgOi, 2),
            'avg_abs_dvol'=> (float)round($avgVol, 2),
            'points_oi'   => count($absOi),
            'points_vol'  => count($absVol),
        ];
    }


private function strengthDaywiseRelative(array $rows, int $i): array
{
    // Relative strength: compare today's |ΔOI| and |ΔVol| against rolling 5-day average (older days).
    // rows are sorted DESC (latest first). For day i, baseline uses days i+1 .. i+5 (older).
    $cur  = $rows[$i] ?? null;
    $prev = $rows[$i+1] ?? null;
    if (!$cur || !$prev) return ['label'=>'—', 'score'=>0, 'oi_ratio'=>null, 'vol_ratio'=>null];

    $dOi    = $cur['d_oi']   ?? null;
    $dVol   = $cur['d_vol']  ?? null;
    $dPrice = $cur['d_price']?? null;
    $behavior = $cur['behavior'] ?? '—';

    if ($dOi === null || $dVol === null || $dPrice === null) {
        // If missing diffs, keep neutral
        return ['label'=>'—', 'score'=>0, 'oi_ratio'=>null, 'vol_ratio'=>null];
    }

    // Collect rolling baselines
    $absOis = [];
    $absVols = [];
    for ($j = $i+1; $j <= $i+5; $j++) {
        if (!isset($rows[$j])) break;
        $xOi  = $rows[$j]['d_oi']  ?? null;
        $xVol = $rows[$j]['d_vol'] ?? null;
        if ($xOi !== null)  $absOis[]  = abs((float)$xOi);
        if ($xVol !== null) $absVols[] = abs((float)$xVol);
    }

    // Fallback: if not enough history, use prev-day percentages (old method) but scaled down
    if (count($absOis) < 2 || count($absVols) < 2) {
        $prevOi  = (float)($prev['total_oi']  ?? 0);
        $prevVol = (float)($prev['total_vol'] ?? 0);
        $oiPct  = ($prevOi > 0)  ? (abs((float)$dOi)  / $prevOi)  * 100.0 : 0.0;
        $volPct = ($prevVol > 0) ? (abs((float)$dVol) / $prevVol) * 100.0 : 0.0;

        $oiScore  = ($oiPct >= 12) ? 40 : (($oiPct >= 6) ? 25 : (($oiPct >= 3) ? 10 : 0));
        $volScore = ($volPct >= 25) ? 40 : (($volPct >= 12) ? 25 : (($volPct >= 6) ? 10 : 0));
        $pxScore  = $this->priceConfirmScore($behavior, (float)$dPrice);

        $score = min(100, $oiScore + $volScore + $pxScore);
        return ['label'=>$this->strengthLabel($score), 'score'=>$score, 'oi_ratio'=>null, 'vol_ratio'=>null];
    }

    $avgAbsOi  = array_sum($absOis)  / count($absOis);
    $avgAbsVol = array_sum($absVols) / count($absVols);

    $oiRatio  = ($avgAbsOi  > 0) ? abs((float)$dOi)  / $avgAbsOi  : 0.0;
    $volRatio = ($avgAbsVol > 0) ? abs((float)$dVol) / $avgAbsVol : 0.0;

    // Component scoring (0..40 each)
    $oiScore  = ($oiRatio  >= 1.5) ? 40 : (($oiRatio  >= 1.1) ? 25 : (($oiRatio  >= 0.7) ? 10 : 0));
    $volScore = ($volRatio >= 1.5) ? 40 : (($volRatio >= 1.1) ? 25 : (($volRatio >= 0.7) ? 10 : 0));

    // Price confirmation (0..20) when not range
    $pxScore  = $this->priceConfirmScore($behavior, (float)$dPrice);

    $score = min(100, $oiScore + $volScore + $pxScore);

    return [
        'label'     => $this->strengthLabel($score),
        'score'     => $score,
        'oi_ratio'  => round($oiRatio, 2),
        'vol_ratio' => round($volRatio, 2),
    ];
}

private function priceConfirmScore(string $behavior, float $dPrice): int
{
    // Range behaviors don't get price confirmation
    if (stripos($behavior, 'Range') === 0 || $behavior === '—' || $behavior === 'Mixed') return 0;

    // Confirm directional behaviors
    // Long Build-up / Short Covering => dPrice > 0
    // Short Build-up / Long Unwinding => dPrice < 0
    if (($behavior === 'Long Build-up' || $behavior === 'Short Covering') && $dPrice > 0) return 20;
    if (($behavior === 'Short Build-up' || $behavior === 'Long Unwinding') && $dPrice < 0) return 20;

    return 0;
}

private function strengthLabel(int $score): string
{
    if ($score >= 75) return 'Strong';
    if ($score >= 50) return 'Moderate';
    if ($score >= 25) return 'Weak';
    return '—';
}


private function classifyDaywise($dPrice, $dOi): array
    {
        // If no prev day, show neutral
        if ($dPrice === null || $dOi === null) {
            return ['behavior'=>'—', 'signal'=>'Neutral'];
        }

        // Treat very small price changes as range
        $rangePx = 10; // points
        if (abs((float)$dPrice) < $rangePx) {
            if ($dOi > 0) return ['behavior'=>'Range – Position Build', 'signal'=>'Sideways'];
            if ($dOi < 0) return ['behavior'=>'Range – Position Exit', 'signal'=>'Neutral'];
            return ['behavior'=>'Range – Flat', 'signal'=>'Neutral'];
        }

        if ($dPrice > 0 && $dOi > 0) return ['behavior'=>'Long Build-up', 'signal'=>'Bullish'];
        if ($dPrice < 0 && $dOi > 0) return ['behavior'=>'Short Build-up', 'signal'=>'Bearish'];
        if ($dPrice > 0 && $dOi < 0) return ['behavior'=>'Short Covering', 'signal'=>'Bullish (short-term)'];
        if ($dPrice < 0 && $dOi < 0) return ['behavior'=>'Long Unwinding', 'signal'=>'Bearish (short-term)'];

        return ['behavior'=>'Mixed', 'signal'=>'Neutral'];
    }




}
