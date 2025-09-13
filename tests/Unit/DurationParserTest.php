<?php

namespace Tests\Unit;

use App\Support\DurationParser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DurationParserTest extends TestCase
{
    #[Test]
    public function it_parses_mm_ss()
    {
        $this->assertSame(62, DurationParser::toSeconds('01:02'));
        $this->assertSame(600, DurationParser::toSeconds('10:00'));
        $this->assertSame(59, DurationParser::toSeconds('00:59'));
    }

    #[Test]
    public function it_parses_hh_mm_ss()
    {
        $this->assertSame(3723, DurationParser::toSeconds('1:02:03'));  // 3600 + 120 + 3
        $this->assertSame(0, DurationParser::toSeconds('0:00:00'));
    }

    #[Test]
    public function it_rejects_invalid_values()
    {
        $this->assertNull(DurationParser::toSeconds(null));
        $this->assertNull(DurationParser::toSeconds(''));
        $this->assertNull(DurationParser::toSeconds('abc'));
        $this->assertNull(DurationParser::toSeconds('12:61'));     // ss >= 60
        $this->assertNull(DurationParser::toSeconds('1:60:00'));   // mm >= 60
        $this->assertNull(DurationParser::toSeconds('99'));        // no colon
    }
}
