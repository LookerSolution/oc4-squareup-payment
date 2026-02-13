<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Opencart\System\Library\Extension\Lookersolution\Squareup\WebhookHandler;
use Tests\Stub\RegistryFactory;

class WebhookHandlerTest extends TestCase {
    public function testValidateSignatureValid(): void {
        $sigKey = 'test-webhook-signature-key';
        $handler = new WebhookHandler(RegistryFactory::create([
            'payment_squareup_webhook_signature_key' => $sigKey,
        ]));

        $body = '{"type":"payment.created"}';
        $url = 'https://example.com/webhook';
        $expected = base64_encode(hash_hmac('sha256', $url . $body, $sigKey, true));

        $this->assertTrue($handler->validateSignature($body, $expected, $url));
    }

    public function testValidateSignatureInvalid(): void {
        $handler = new WebhookHandler(RegistryFactory::create([
            'payment_squareup_webhook_signature_key' => 'real-key',
        ]));

        $this->assertFalse($handler->validateSignature('body', 'wrong-signature', 'https://example.com'));
    }

    public function testValidateSignatureMissingKey(): void {
        $handler = new WebhookHandler(RegistryFactory::create());

        $this->assertFalse($handler->validateSignature('body', 'some-sig', 'https://example.com'));
    }

    public function testValidateSignatureEmptySignature(): void {
        $handler = new WebhookHandler(RegistryFactory::create([
            'payment_squareup_webhook_signature_key' => 'key',
        ]));

        $this->assertFalse($handler->validateSignature('body', '', 'https://example.com'));
    }
}
