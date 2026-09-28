<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * NSE trading calendar (IST).
 *
 * A date is a trading day when it is a special session listed in
 * writable/cache/special_trading_days.conf (EXEMPT_DATES / EXEMPT_SCHEDULE,
 * the same file scripts/oi_cron_guard.sh reads), or a weekday that is not an
 * NSE holiday for the segment (default FO) in the `nse_holidays` table.
 */
final class TradingCalendar
{
    public const TZ = 'Asia/Kolkata';

    /** Regular session close (IST, HHMM). */
    public const REGULAR_CLOSE = '1530';

    /** @var array<string, true>|null Holiday dates, loaded lazily. */
    private ?array $holidays;

    /** @var array<string, true> Full-day special sessions (EXEMPT_DATES). */
    private array $specialDates = [];

    /** @var array<string, array{start: string, end: string}> Timed special sessions (EXEMPT_SCHEDULE), HHMM. */
    private array $schedule = [];

    /**
     * @param list<string>|null $holidays Pre-loaded holiday dates (Y-m-d); skips the DB lookup.
     */
    public function __construct(
        private readonly ?BaseConnection $db = null,
        ?string $specialConfPath = null,
        private readonly string $segment = 'FO',
        ?array $holidays = null,
    ) {
        $this->holidays = $holidays === null ? null : array_fill_keys($holidays, true);
        $this->loadSpecialConf($specialConfPath ?? WRITEPATH . 'cache/special_trading_days.conf');
    }

    public static function timezone(): DateTimeZone
    {
        return new DateTimeZone(self::TZ);
    }

    public function isHoliday(string $ymd): bool
    {
        $this->holidays ??= $this->loadHolidays();

        return isset($this->holidays[$ymd]);
    }

    public function isSpecialSession(string $ymd): bool
    {
        return isset($this->specialDates[$ymd]) || isset($this->schedule[$ymd]);
    }

    public function isTradingDay(string $ymd): bool
    {
        if ($this->isSpecialSession($ymd)) {
            return true;
        }

        $dow = (int) (new DateTimeImmutable($ymd, self::timezone()))->format('N'); // 6 = Sat, 7 = Sun

        return $dow <= 5 && ! $this->isHoliday($ymd);
    }

    /**
     * Session end (IST, HHMM): a timed special session's end when it is later
     * than the regular close (e.g. Muhurat trading), else the regular close.
     */
    public function sessionEnd(string $ymd): string
    {
        $end = $this->schedule[$ymd]['end'] ?? self::REGULAR_CLOSE;

        return max($end, self::REGULAR_CLOSE);
    }

    /**
     * True once the session of $ymd plus $bufferMin minutes is over at $now.
     */
    public function hasSessionEnded(string $ymd, DateTimeImmutable $now, int $bufferMin = 0): bool
    {
        $end = DateTimeImmutable::createFromFormat('!Y-m-d Hi', $ymd . ' ' . $this->sessionEnd($ymd), self::timezone());

        if ($end === false) {
            return true;
        }

        return $now >= $end->modify("+{$bufferMin} minutes");
    }

    /**
     * Latest trading day whose session (plus $bufferMin) has ended, or null if
     * there is none within $maxLookbackDays. On a normal day, today counts from 15:45 IST.
     */
    public function latestClosedTradingDay(?DateTimeImmutable $now = null, int $bufferMin = 15, int $maxLookbackDays = 10): ?string
    {
        $now = ($now ?? new DateTimeImmutable('now'))->setTimezone(self::timezone());
        $day = $now;

        for ($i = 0; $i < $maxLookbackDays; $i++) {
            $ymd = $day->format('Y-m-d');

            if ($this->isTradingDay($ymd) && ($i > 0 || $this->hasSessionEnded($ymd, $now, $bufferMin))) {
                return $ymd;
            }

            $day = $day->modify('-1 day');
        }

        return null;
    }

    /**
     * UTC bounds [from, to) of an IST calendar day, for index-friendly queries
     * on UTC timestamps such as oi_snapshots.ts.
     *
     * @return array{0: string, 1: string} 'Y-m-d H:i:s'
     */
    public static function utcBoundsOfIstDay(string $ymd): array
    {
        $utc   = new DateTimeZone('UTC');
        $start = new DateTimeImmutable($ymd . ' 00:00:00', self::timezone());

        return [
            $start->setTimezone($utc)->format('Y-m-d H:i:s'),
            $start->modify('+1 day')->setTimezone($utc)->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array<string, true>
     */
    private function loadHolidays(): array
    {
        if ($this->db === null) {
            return [];
        }

        try {
            $rows = $this->db->table('nse_holidays')
                ->select('holiday_date')
                ->where('segment', $this->segment)
                ->get()->getResultArray();
        } catch (Throwable $e) {
            log_message('warning', 'TradingCalendar: holiday lookup failed: ' . $e->getMessage());

            return [];
        }

        $set = [];

        foreach ($rows as $row) {
            $ymd = substr((string) ($row['holiday_date'] ?? ''), 0, 10);

            if ($ymd !== '') {
                $set[$ymd] = true;
            }
        }

        return $set;
    }

    /**
     * Parses EXEMPT_DATES="YYYY-MM-DD,..." and EXEMPT_SCHEDULE="YYYY-MM-DD@HH:MM-HH:MM,..."
     * written in shell syntax (the guard `source`s the same file).
     */
    private function loadSpecialConf(string $path): void
    {
        if (! is_readable($path)) {
            return;
        }

        $vars    = [];
        $pattern = '/^\s*(?:export\s+)?(EXEMPT_DATES|EXEMPT_SCHEDULE)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s#"\']*))\s*(?:#.*)?$/';

        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if (preg_match($pattern, $line, $m)) {
                // Last assignment wins, as in the shell
                $vars[$m[1]] = ($m[2] ?? '') . ($m[3] ?? '') . ($m[4] ?? '');
            }
        }

        foreach (explode(',', $vars['EXEMPT_DATES'] ?? '') as $item) {
            $item = str_replace(' ', '', $item);

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $item)) {
                $this->specialDates[$item] = true;
            }
        }

        foreach (explode(',', $vars['EXEMPT_SCHEDULE'] ?? '') as $item) {
            $item = str_replace(' ', '', $item);

            if (preg_match('/^(\d{4}-\d{2}-\d{2})@(\d{2}):?(\d{2})-(\d{2}):?(\d{2})$/', $item, $m)) {
                $this->schedule[$m[1]] = ['start' => $m[2] . $m[3], 'end' => $m[4] . $m[5]];
            }
        }
    }
}
