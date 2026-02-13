<?php
namespace Tests\Stub;

class StubLog {
    private array $messages = [];

    public function write(string $message): void {
        $this->messages[] = $message;
    }

    public function getMessages(): array {
        return $this->messages;
    }

    public function clear(): void {
        $this->messages = [];
    }
}
