<?php
namespace Tests\Stub;

class StubLanguage {
    public function get(string $key): string {
        return $key;
    }
}
