<?php
namespace Tests\Stub;

class StubConfig {
    private array $data = [];

    public function get(string $key): mixed {
        return $this->data[$key] ?? null;
    }

    public function set(string $key, mixed $value): void {
        $this->data[$key] = $value;
    }
}
