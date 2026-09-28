<?php

use App\Libraries\TradingCalendar;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class TradingCalendarTest extends CIUnitTestCase
{
    private string $conf;

    protected function setUp(): void
    {
        parent::setUp();

        // Sat 2026-09-26 full-day special; Sun 2026-11-08 timed (Muhurat-style) session
        $this->conf = tempnam(sys_get_temp_dir(), 'special');
        file_put_contents($this->conf, implode("\n", [
            '# special sessions',
            'EXEMPT_DATES="2026-09-26"',
            "export EXEMPT_SCHEDULE='2026-11-08@18:00-19:15'   # evening session",
            '',
        ]));
    }

    protected function tearDown(): void
    {
        @unlink($this->conf);
        parent::tearDown();
    }

    private function calendar(): TradingCalendar
    {
        // Fri 2026-10-02 is an FO holiday
        return new TradingCalendar(null, $this->conf, 'FO', ['2026-10-02']);
    }

    private function ist(string $datetime): DateTimeImmutable
    {
        return new DateTimeImmutable($datetime, TradingCalendar::timezone());
    }

    public function testTradingDays(): void
    {
        $cal = $this->calendar();

        $this->assertTrue($cal->isTradingDay('2026-09-28'));   // Monday
        $this->assertFalse($cal->isTradingDay('2026-09-27'));  // Sunday
        $this->assertTrue($cal->isTradingDay('2026-09-26'));   // Saturday, but a special session
        $this->assertFalse($cal->isTradingDay('2026-10-02'));  // FO holiday
        $this->assertTrue($cal->isTradingDay('2026-11-08'));   // Sunday, timed special session
    }

    public function testSessionEnd(): void
    {
        $cal = $this->calendar();

        $this->assertSame('1530', $cal->sessionEnd('2026-09-28'));
        $this->assertSame('1915', $cal->sessionEnd('2026-11-08'));
        $this->assertFalse($cal->hasSessionEnded('2026-11-08', $this->ist('2026-11-08 19:20'), 15));
        $this->assertTrue($cal->hasSessionEnded('2026-11-08', $this->ist('2026-11-08 19:30'), 15));
    }

    public function testLatestClosedTradingDay(): void
    {
        $cal = $this->calendar();

        // Before the 15:45 cut-off, today does not count yet
        $this->assertSame('2026-09-26', $cal->latestClosedTradingDay($this->ist('2026-09-28 15:44')));
        $this->assertSame('2026-09-28', $cal->latestClosedTradingDay($this->ist('2026-09-28 15:45')));

        // Holiday Friday is skipped
        $this->assertSame('2026-10-01', $cal->latestClosedTradingDay($this->ist('2026-10-02 18:00')));

        // Without the special Saturday, Monday morning falls back to Friday
        $plain = new TradingCalendar(null, '/nonexistent.conf', 'FO', []);
        $this->assertSame('2026-09-25', $plain->latestClosedTradingDay($this->ist('2026-09-28 10:00')));
    }

    public function testSpecialSessions(): void
    {
        $conf = tempnam(sys_get_temp_dir(), 'special');
        file_put_contents($conf, implode("\n", [
            'EXEMPT_DATES="2026-09-26"',
            'EXEMPT_SCHEDULE="2026-11-08@18:00-19:15, 2026-11-08@09:00-09:15,2026-03-01@1100-1230"',
        ]));
        $cal = new TradingCalendar(null, $conf, 'FO', []);
        @unlink($conf);

        $this->assertSame([
            ['date' => '2026-03-01', 'start' => '11:00', 'end' => '12:30', 'full' => false],
            ['date' => '2026-09-26', 'start' => '09:15', 'end' => '15:30', 'full' => true],
            ['date' => '2026-11-08', 'start' => '09:00', 'end' => '09:15', 'full' => false],
            ['date' => '2026-11-08', 'start' => '18:00', 'end' => '19:15', 'full' => false],
        ], $cal->specialSessions());

        // Several windows on one date: the latest end counts
        $this->assertSame('1915', $cal->sessionEnd('2026-11-08'));
        $this->assertSame('1530', $cal->sessionEnd('2026-03-01'));
    }

    public function testUtcBoundsOfIstDay(): void
    {
        $this->assertSame(
            ['2026-09-27 18:30:00', '2026-09-28 18:30:00'],
            TradingCalendar::utcBoundsOfIstDay('2026-09-28'),
        );
    }
}
