<?php

use App\Libraries\CliOptions;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class CliOptionsTest extends CIUnitTestCase
{
    public function testEqualsForm(): void
    {
        $argv = ['spark', 'oi:daywise:update', '--symbol=BANKNIFTY', '--days=10'];

        $this->assertSame('BANKNIFTY', CliOptions::get('symbol', $argv));
        $this->assertSame('10', CliOptions::get('days', $argv));
    }

    public function testSpaceForm(): void
    {
        $argv = ['spark', 'index:eod:update', '--from', '2025-01-01', '--to', '2025-12-31'];

        $this->assertSame('2025-01-01', CliOptions::get('from', $argv));
        $this->assertSame('2025-12-31', CliOptions::get('to', $argv));
    }

    public function testFlags(): void
    {
        $argv = ['spark', 'oi:prune', '1', '--yes', '--logs-keep-months', '6', '--dry-run'];

        $this->assertSame('1', CliOptions::get('yes', $argv));
        $this->assertSame('6', CliOptions::get('logs-keep-months', $argv));
        $this->assertTrue(CliOptions::has('dry-run', $argv));
    }

    public function testMissingAndPrefixCollision(): void
    {
        $argv = ['spark', 'oi:prune', '--symbolic=X'];

        $this->assertNull(CliOptions::get('symbol', $argv));
        $this->assertFalse(CliOptions::has('yes', $argv));
    }

    public function testEmptyValue(): void
    {
        $this->assertSame('', CliOptions::get('days', ['spark', 'x', '--days=']));
    }

    public function testIsDate(): void
    {
        $this->assertTrue(CliOptions::isDate('2026-02-28'));
        $this->assertFalse(CliOptions::isDate('2026-02-30'));
        $this->assertFalse(CliOptions::isDate('2026-13-45'));
        $this->assertFalse(CliOptions::isDate('28-09-2026'));
    }
}
