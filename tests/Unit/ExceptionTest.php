<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Opencart\System\Library\Extension\Lookersolution\Squareup\Exception;
use Tests\Stub\RegistryFactory;
use Tests\Stub\StubLog;

class ExceptionTest extends TestCase {
    public function testIsAccessTokenRevokedMatching(): void {
        $e = new Exception(RegistryFactory::create(), [
            ['code' => 'ACCESS_TOKEN_REVOKED', 'detail' => 'Token revoked'],
        ]);

        $this->assertTrue($e->isAccessTokenRevoked());
    }

    public function testIsAccessTokenRevokedNonMatching(): void {
        $e = new Exception(RegistryFactory::create(), [
            ['code' => 'SOME_OTHER_ERROR', 'detail' => 'Other error'],
        ]);

        $this->assertFalse($e->isAccessTokenRevoked());
    }

    public function testIsAccessTokenExpiredMatching(): void {
        $e = new Exception(RegistryFactory::create(), [
            ['code' => 'ACCESS_TOKEN_EXPIRED', 'detail' => 'Token expired'],
        ]);

        $this->assertTrue($e->isAccessTokenExpired());
    }

    public function testIsAccessTokenExpiredNonMatching(): void {
        $e = new Exception(RegistryFactory::create(), [
            ['code' => 'INVALID_REQUEST', 'detail' => 'Bad request'],
        ]);

        $this->assertFalse($e->isAccessTokenExpired());
    }

    public function testIsCurlError(): void {
        $curlErr = new Exception(RegistryFactory::create(), 'Connection failed', true);
        $this->assertTrue($curlErr->isCurlError());

        $apiErr = new Exception(RegistryFactory::create(), 'API error', false);
        $this->assertFalse($apiErr->isCurlError());
    }

    public function testStringErrorBecomesMessage(): void {
        $e = new Exception(RegistryFactory::create(), 'Something went wrong');
        $this->assertSame('Something went wrong', $e->getMessage());
    }

    public function testArrayErrorsConcatenated(): void {
        $e = new Exception(RegistryFactory::create(), [
            ['detail' => 'First error'],
            ['detail' => 'Second error'],
        ]);

        $this->assertSame('First error Second error', $e->getMessage());
    }

    public function testFieldInfoAppended(): void {
        $registry = RegistryFactory::create();
        $e = new Exception($registry, [
            ['detail' => 'Invalid value', 'field' => 'card_nonce'],
        ]);

        $this->assertStringContainsString('Invalid value', $e->getMessage());
        $this->assertStringContainsString('squareup_error_field', $e->getMessage());
    }

    public function testOverrideFieldUsesLanguageKey(): void {
        $e = new Exception(RegistryFactory::create(), [
            ['detail' => 'Country invalid', 'field' => 'billing_address.country'],
        ]);

        $this->assertSame('squareup_override_error_billing_address.country', $e->getMessage());
    }

    public function testLogsWhenErrorLogEnabled(): void {
        $registry = RegistryFactory::create([
            'config_error_log' => 1,
        ]);

        new Exception($registry, 'Test error for logging');

        $log = $registry->get('log');
        $this->assertInstanceOf(StubLog::class, $log);
        $messages = $log->getMessages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('Square API Error', $messages[0]);
        $this->assertStringContainsString('Test error for logging', $messages[0]);
    }
}
