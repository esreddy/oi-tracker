<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;

class FetchNseHolidays extends BaseCommand
{
    protected $group       = 'OI';
    protected $name        = 'oi:holidays';
    protected $description = 'Fetch NSE holiday-master (trading) and store into DB table nse_holidays';

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        // Optional: allow filtering segments: php spark oi:holidays CM,FO
        $segFilter = [];
        if (!empty($params[0])) {
            $segFilter = array_filter(array_map('trim', explode(',', $params[0])));
        }

        $url = 'https://www.nseindia.com/api/holiday-master?type=trading';

        // CI4 CurlRequest (Guzzle). Enable cookies so NSE handshake works.
        $client = Services::curlrequest([
            'timeout' => 20,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0',
                'Accept'     => 'application/json,text/plain,*/*',
                'Referer'    => 'https://www.nseindia.com/',
            ],
            'cookies' => true,
        ]);

        // 1) Handshake (sets cookies)
        try {
            $client->get('https://www.nseindia.com/');
        } catch (\Throwable $e) {
            // NSE sometimes blocks; still try API call next
        }

        // 2) Fetch JSON
        try {
            $resp = $client->get($url);
            $json = (string) $resp->getBody();
        } catch (\Throwable $e) {
            CLI::error('Failed fetching NSE holidays: ' . $e->getMessage());
            return;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            CLI::error('Invalid JSON returned from NSE.');
            return;
        }

        $table = $db->table('nse_holidays');

        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;

        foreach ($data as $segment => $rows) {
            if (!is_array($rows)) continue;

            if ($segFilter && !in_array($segment, $segFilter, true)) {
                continue;
            }

            foreach ($rows as $row) {
                if (!is_array($row)) continue;

                $tradingDateStr  = trim((string)($row['tradingDate'] ?? $row['date'] ?? ''));
                $weekdayName     = trim((string)($row['weekDay'] ?? $row['weekday'] ?? ''));
                $desc            = trim((string)($row['description'] ?? ''));

                $morningSession  = isset($row['morning_session']) ? trim((string)$row['morning_session']) : null;
                $eveningSession  = isset($row['evening_session']) ? trim((string)$row['evening_session']) : null;

                // Normalize "null" strings or empty -> NULL
                $morningSession = ($morningSession === '' || strtolower($morningSession) === 'null') ? null : $morningSession;
                $eveningSession = ($eveningSession === '' || strtolower($eveningSession) === 'null') ? null : $eveningSession;

                if ($tradingDateStr === '') { $skipped++; continue; }

                // NSE gives like "26-Jan-2026"
                $dt = \DateTime::createFromFormat('d-M-Y', $tradingDateStr);
                if (!$dt) { $skipped++; continue; }
                $holidayDate = $dt->format('Y-m-d');

                $sql = "INSERT INTO nse_holidays
                            (segment, holiday_date, trading_date_str, weekday_name, description, morning_session, evening_session, raw_json, fetched_at)
                        VALUES
                            (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE
                            trading_date_str = VALUES(trading_date_str),
                            weekday_name     = VALUES(weekday_name),
                            description      = VALUES(description),
                            morning_session  = VALUES(morning_session),
                            evening_session  = VALUES(evening_session),
                            raw_json         = VALUES(raw_json),
                            fetched_at       = NOW()";

                $ok = $db->query($sql, [
                    $segment,
                    $holidayDate,
                    $tradingDateStr,
                    $weekdayName ?: null,
                    $desc ?: null,
                    $morningSession,
                    $eveningSession,
                    json_encode($row, JSON_UNESCAPED_UNICODE),
                ]);

                if ($ok) {
                    // MySQL doesn't easily tell insert vs update here without extra work.
                    // We'll just count as "upserted".
                    $inserted++;
                }
            }
        }

        CLI::write("Done. Upserted rows: {$inserted}, skipped: {$skipped}");
    }
}
