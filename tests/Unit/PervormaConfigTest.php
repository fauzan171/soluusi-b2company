<?php

namespace Tests\Unit;

use Tests\TestCase;

class PervormaConfigTest extends TestCase
{
    public function test_hap_rates_match_playbook(): void
    {
        $hap = config('pervorma.hap');

        $this->assertSame(250_000, $hap['stages'][1]['amount']);
        $this->assertSame(250_000, $hap['stages'][2]['amount']);
        $this->assertSame(500_000, $hap['stages'][3]['amount']);
        $this->assertSame(5, $hap['stages'][1]['trigger']);
        $this->assertSame(25, $hap['stages'][2]['trigger']);
        $this->assertSame(40, $hap['stages'][3]['trigger']);
        $this->assertSame(90, $hap['window_days']);
    }

    public function test_sap_rates_match_playbook(): void
    {
        // SAP = sekolah.
        $sap = config('pervorma.sap');

        $this->assertSame(300_000, $sap['stages'][1]['amount']);
        $this->assertSame(400_000, $sap['stages'][2]['amount']);
        $this->assertSame(800_000, $sap['stages'][3]['amount']);
        $this->assertSame(5, $sap['stages'][1]['trigger']);
        $this->assertSame(25, $sap['stages'][2]['trigger']);
        $this->assertSame(40, $sap['stages'][3]['trigger']);
    }

    public function test_cap_rates_match_playbook(): void
    {
        // CAP = perusahaan.
        $cap = config('pervorma.cap');

        $this->assertSame(600_000, $cap['stages'][1]['amount']);
        $this->assertSame(800_000, $cap['stages'][2]['amount']);
        $this->assertSame(1_600_000, $cap['stages'][3]['amount']);
        // Stage 1 CAP trigger = tenant activated (mou_at), bukan hitungan sesi.
        $this->assertSame('mou_at', $cap['stages'][1]['trigger']);
        $this->assertSame(50, $cap['stages'][2]['trigger']);
        $this->assertSame(150, $cap['stages'][3]['trigger']);
    }

    public function test_three_verticals_present(): void
    {
        $this->assertSame(['hap', 'sap', 'cap'], array_keys(config('pervorma')));
    }
}
