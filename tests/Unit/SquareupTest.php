<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Opencart\System\Library\Extension\Lookersolution\Squareup;
use Tests\Stub\RegistryFactory;

class SquareupTest extends TestCase {
    private Squareup $squareup;

    protected function setUp(): void {
        $this->squareup = new Squareup(RegistryFactory::create());
    }

    public function testPhoneFormatValidUS(): void {
        $this->assertSame('+12025551234', $this->squareup->phoneFormat('(202) 555-1234', 'US'));
    }

    public function testPhoneFormatValidUK(): void {
        $this->assertSame('+442071234567', $this->squareup->phoneFormat('020 7123 4567', 'GB'));
    }

    public function testPhoneFormatInvalidNumber(): void {
        $this->assertSame('not-a-phone', $this->squareup->phoneFormat('not-a-phone', 'US'));
    }

    public function testPhoneFormatAlreadyE164(): void {
        $this->assertSame('+14155551234', $this->squareup->phoneFormat('+14155551234', 'US'));
    }

    public function testLowestDenominationUSD(): void {
        $this->assertSame(1050, $this->squareup->lowestDenomination(10.50, 'USD'));
    }

    public function testLowestDenominationJPY(): void {
        $this->assertSame(1000, $this->squareup->lowestDenomination(1000, 'JPY'));
    }

    public function testLowestDenominationBHD(): void {
        $this->assertSame(10500, $this->squareup->lowestDenomination(10.50, 'BHD'));
    }

    public function testLowestDenominationStringInput(): void {
        $this->assertSame(2599, $this->squareup->lowestDenomination('25.99', 'USD'));
    }

    public function testStandardDenominationUSD(): void {
        $this->assertSame(10.50, $this->squareup->standardDenomination(1050, 'USD'));
    }

    public function testStandardDenominationJPY(): void {
        $this->assertSame(1000.0, $this->squareup->standardDenomination(1000, 'JPY'));
    }

    public function testLowestToStandardRoundtrip(): void {
        $original = 29.99;
        $lowest = $this->squareup->lowestDenomination($original, 'USD');
        $back = $this->squareup->standardDenomination($lowest, 'USD');
        $this->assertSame($original, $back);
    }
}
