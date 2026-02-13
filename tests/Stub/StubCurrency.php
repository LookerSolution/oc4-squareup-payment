<?php
namespace Tests\Stub;

class StubCurrency {
    private array $decimals = [
        'USD' => 2,
        'EUR' => 2,
        'GBP' => 2,
        'JPY' => 0,
        'BHD' => 3,
    ];

    public function getDecimalPlace(string $currency): int {
        return $this->decimals[$currency] ?? 2;
    }

    public function setDecimalPlace(string $currency, int $places): void {
        $this->decimals[$currency] = $places;
    }
}
