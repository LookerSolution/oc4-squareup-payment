<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Opencart\System\Library\Extension\Lookersolution\Squareup\TokenManager;
use Tests\Stub\RegistryFactory;

class TokenManagerTest extends TestCase {
    public function testEncryptDecryptRoundtrip(): void {
        $tm = new TokenManager(RegistryFactory::create());
        $key = base64_decode($tm->generateEncryptionKey());
        $plaintext = 'my-secret-access-token-value';

        $encrypted = $tm->encrypt($plaintext, $key);
        $this->assertSame($plaintext, $tm->decrypt($encrypted, $key));
    }

    public function testDecryptWithWrongKey(): void {
        $tm = new TokenManager(RegistryFactory::create());
        $key1 = base64_decode($tm->generateEncryptionKey());
        $key2 = base64_decode($tm->generateEncryptionKey());

        $encrypted = $tm->encrypt('secret', $key1);
        $this->assertSame('', $tm->decrypt($encrypted, $key2));
    }

    public function testDecryptCorruptData(): void {
        $tm = new TokenManager(RegistryFactory::create());
        $key = base64_decode($tm->generateEncryptionKey());

        $this->assertSame('', $tm->decrypt('not-valid-base64!!!', $key));
    }

    public function testDecryptTruncatedData(): void {
        $tm = new TokenManager(RegistryFactory::create());
        $key = base64_decode($tm->generateEncryptionKey());

        $encrypted = $tm->encrypt('secret', $key);
        $truncated = base64_encode(substr(base64_decode($encrypted), 0, 5));
        $this->assertSame('', $tm->decrypt($truncated, $key));
    }

    public function testGenerateEncryptionKeyFormat(): void {
        $tm = new TokenManager(RegistryFactory::create());
        $key = $tm->generateEncryptionKey();

        $decoded = base64_decode($key, true);
        $this->assertNotFalse($decoded);
        $this->assertSame(32, strlen($decoded));
    }

    public function testGenerateEncryptionKeyUniqueness(): void {
        $tm = new TokenManager(RegistryFactory::create());
        $keys = [];

        for ($i = 0; $i < 10; $i++) {
            $keys[] = $tm->generateEncryptionKey();
        }

        $this->assertCount(10, array_unique($keys));
    }

    public function testEncryptTokenSettingsEncryptsBothTokens(): void {
        $encKey = base64_encode(random_bytes(32));
        $tm = new TokenManager(RegistryFactory::create([
            'payment_squareup_encryption_key' => $encKey,
        ]));

        $settings = [
            'payment_squareup_access_token'  => 'access-123',
            'payment_squareup_refresh_token' => 'refresh-456',
        ];

        $tm->encryptTokenSettings($settings);

        $this->assertSame('***', $settings['payment_squareup_access_token']);
        $this->assertSame('***', $settings['payment_squareup_refresh_token']);
        $this->assertNotEmpty($settings['payment_squareup_access_token_encrypted']);
        $this->assertNotEmpty($settings['payment_squareup_refresh_token_encrypted']);

        $rawKey = base64_decode($encKey);
        $this->assertSame('access-123', $tm->decrypt($settings['payment_squareup_access_token_encrypted'], $rawKey));
        $this->assertSame('refresh-456', $tm->decrypt($settings['payment_squareup_refresh_token_encrypted'], $rawKey));
    }

    public function testEncryptTokenSettingsGeneratesKeyIfMissing(): void {
        $tm = new TokenManager(RegistryFactory::create());

        $settings = [
            'payment_squareup_access_token' => 'access-token',
        ];

        $tm->encryptTokenSettings($settings);

        $this->assertArrayHasKey('payment_squareup_encryption_key', $settings);
        $this->assertNotEmpty($settings['payment_squareup_encryption_key']);
    }

    public function testEncryptTokenSettingsClearsPlaintext(): void {
        $tm = new TokenManager(RegistryFactory::create());

        $settings = [
            'payment_squareup_access_token'  => 'token-value',
            'payment_squareup_refresh_token' => 'refresh-value',
        ];

        $tm->encryptTokenSettings($settings);
        $this->assertSame('***', $settings['payment_squareup_access_token']);
        $this->assertSame('***', $settings['payment_squareup_refresh_token']);
    }

    public function testGetAccessTokenSandboxMode(): void {
        $tm = new TokenManager(RegistryFactory::create([
            'payment_squareup_enable_sandbox' => 1,
            'payment_squareup_sandbox_token'  => 'sandbox-token-123',
        ]));

        $this->assertSame('sandbox-token-123', $tm->getAccessToken());
    }

    public function testGetAccessTokenEncrypted(): void {
        $encKey = base64_encode(random_bytes(32));
        $rawKey = base64_decode($encKey);

        $tempTm = new TokenManager(RegistryFactory::create());
        $encrypted = $tempTm->encrypt('live-access-token', $rawKey);

        $tm = new TokenManager(RegistryFactory::create([
            'payment_squareup_encryption_key'          => $encKey,
            'payment_squareup_access_token_encrypted'  => $encrypted,
        ]));

        $this->assertSame('live-access-token', $tm->getAccessToken());
    }

    public function testGetAccessTokenPlaintextFallback(): void {
        $tm = new TokenManager(RegistryFactory::create([
            'payment_squareup_access_token' => 'plaintext-token',
        ]));

        $this->assertSame('plaintext-token', $tm->getAccessToken());
    }

    public function testGetAccessTokenStarPlaceholderReturnsEmpty(): void {
        $tm = new TokenManager(RegistryFactory::create([
            'payment_squareup_access_token' => '***',
        ]));

        $this->assertSame('', $tm->getAccessToken());
    }

    public function testGetRefreshTokenEncrypted(): void {
        $encKey = base64_encode(random_bytes(32));
        $rawKey = base64_decode($encKey);

        $tempTm = new TokenManager(RegistryFactory::create());
        $encrypted = $tempTm->encrypt('refresh-token-value', $rawKey);

        $tm = new TokenManager(RegistryFactory::create([
            'payment_squareup_encryption_key'           => $encKey,
            'payment_squareup_refresh_token_encrypted'  => $encrypted,
        ]));

        $this->assertSame('refresh-token-value', $tm->getRefreshToken());
    }

    public function testGetRefreshTokenStarPlaceholderReturnsEmpty(): void {
        $tm = new TokenManager(RegistryFactory::create([
            'payment_squareup_refresh_token' => '***',
        ]));

        $this->assertSame('', $tm->getRefreshToken());
    }

    public function testIsSandbox(): void {
        $tmOn = new TokenManager(RegistryFactory::create([
            'payment_squareup_enable_sandbox' => 1,
        ]));
        $this->assertTrue($tmOn->isSandbox());

        $tmOff = new TokenManager(RegistryFactory::create([
            'payment_squareup_enable_sandbox' => 0,
        ]));
        $this->assertFalse($tmOff->isSandbox());
    }

    public function testGetLocationId(): void {
        $tmSandbox = new TokenManager(RegistryFactory::create([
            'payment_squareup_enable_sandbox'       => 1,
            'payment_squareup_sandbox_location_id'  => 'sandbox-loc',
            'payment_squareup_location_id'          => 'live-loc',
        ]));
        $this->assertSame('sandbox-loc', $tmSandbox->getLocationId());

        $tmLive = new TokenManager(RegistryFactory::create([
            'payment_squareup_enable_sandbox' => 0,
            'payment_squareup_location_id'    => 'live-loc',
        ]));
        $this->assertSame('live-loc', $tmLive->getLocationId());
    }
}
