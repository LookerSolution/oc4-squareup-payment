<?php
namespace Tests\Stub;

class StubDb {
    public function query(string $sql): object {
        return (object)['row' => [], 'rows' => [], 'num_rows' => 0];
    }

    public function escape(string $value): string {
        return addslashes($value);
    }

    public function getLastId(): int {
        return 0;
    }
}
