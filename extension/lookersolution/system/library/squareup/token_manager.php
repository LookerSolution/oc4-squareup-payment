<?php
/**
 * @package    LookerSolution\SquarePayment
 * @author     Ali Ahmed <ali@lookersolution.com>
 * @copyright  Copyright (c) 2026 LookerSolution (https://www.lookersolution.com)
 * @license    https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link       https://github.com/LookerSolution/oc4-squareup-payment
 */
namespace Opencart\System\Library\Extension\Lookersolution\Squareup;

class TokenManager {
	private object $config;
	private const CIPHER = 'aes-256-gcm';
	private const TAG_LENGTH = 16;

	public function __construct(object $registry) {
		$this->config = $registry->get('config');
	}

	public function isSandbox(): bool {
		return (bool)$this->config->get('payment_squareup_enable_sandbox');
	}

	public function getAccessToken(): string {
		if ($this->isSandbox()) {
			return (string)$this->config->get('payment_squareup_sandbox_token');
		}

		$encrypted = $this->config->get('payment_squareup_access_token_encrypted');

		if ($encrypted) {
			$key = $this->getEncryptionKey();
			return $key ? $this->decrypt($encrypted, $key) : (string)$this->config->get('payment_squareup_access_token');
		}

		return (string)$this->config->get('payment_squareup_access_token');
	}

	public function getRefreshToken(): string {
		$encrypted = $this->config->get('payment_squareup_refresh_token_encrypted');

		if ($encrypted) {
			$key = $this->getEncryptionKey();
			return $key ? $this->decrypt($encrypted, $key) : (string)$this->config->get('payment_squareup_refresh_token');
		}

		return (string)$this->config->get('payment_squareup_refresh_token');
	}

	public function getLocationId(): string {
		if ($this->isSandbox()) {
			return (string)$this->config->get('payment_squareup_sandbox_location_id');
		}

		return (string)$this->config->get('payment_squareup_location_id');
	}

	public function getClientId(): string {
		return (string)$this->config->get('payment_squareup_client_id');
	}

	public function getClientSecret(): string {
		return (string)$this->config->get('payment_squareup_client_secret');
	}

	public function getMerchantId(): string {
		return (string)$this->config->get('payment_squareup_merchant_id');
	}

	public function encrypt(string $plaintext, string $key): string {
		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::CIPHER));
		$tag = '';

		$ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_LENGTH);

		return base64_encode($iv . $tag . $ciphertext);
	}

	public function decrypt(string $encoded, string $key): string {
		$data = base64_decode($encoded);

		if ($data === false) {
			return '';
		}

		$iv_length = openssl_cipher_iv_length(self::CIPHER);
		$iv = substr($data, 0, $iv_length);
		$tag = substr($data, $iv_length, self::TAG_LENGTH);
		$ciphertext = substr($data, $iv_length + self::TAG_LENGTH);

		$result = openssl_decrypt($ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);

		return $result !== false ? $result : '';
	}

	public function generateEncryptionKey(): string {
		return bin2hex(openssl_random_pseudo_bytes(32));
	}

	private function getEncryptionKey(): string {
		return (string)$this->config->get('payment_squareup_encryption_key');
	}
}
