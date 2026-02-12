<?php
/**
 * @package    LookerSolution\SquarePayment
 * @author     Ali Ahmed <ali@lookersolution.com>
 * @copyright  Copyright (c) 2026 LookerSolution (https://www.lookersolution.com)
 * @license    https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link       https://github.com/LookerSolution/oc4-squareup-payment
 */
namespace Opencart\System\Library\Extension\Lookersolution;

use Opencart\System\Library\Extension\Lookersolution\Squareup\Exception;
use Opencart\System\Library\Extension\Lookersolution\Squareup\TokenManager;

class Squareup {
	private object $session;
	private object $url;
	private object $config;
	private object $log;
	private object $currency;
	private object $registry;
	private TokenManager $tokenManager;

	const API_URL = 'https://connect.squareup.com';
	const API_SANDBOX_URL = 'https://connect.squareupsandbox.com';
	const API_VERSION = 'v2';
	const ENDPOINT_AUTH = 'oauth2/authorize';
	const ENDPOINT_CANCEL_PAYMENT = 'payments/%s/cancel';
	const ENDPOINT_CAPTURE_PAYMENT = 'payments/%s/complete';
	const ENDPOINT_CARDS = 'cards';
	const ENDPOINT_CUSTOMERS = 'customers';
	const ENDPOINT_CUSTOMERS_SEARCH = 'customers/search';
	const ENDPOINT_LOCATIONS = 'locations';
	const ENDPOINT_ORDERS = 'orders';
	const ENDPOINT_PAYMENTS = 'payments';
	const ENDPOINT_PAYMENT_LINKS = 'online-checkout/payment-links';
	const ENDPOINT_REFUND = 'refunds';
	const ENDPOINT_TOKEN = 'oauth2/token';
	const ENDPOINT_APPLE_PAY_DOMAINS = 'apple-pay/domains';
	const ENDPOINT_WEBHOOKS = 'webhooks/subscriptions';
	const SCOPE = 'MERCHANT_PROFILE_READ PAYMENTS_READ PAYMENTS_WRITE ORDERS_READ SETTLEMENTS_READ CUSTOMERS_READ CUSTOMERS_WRITE';
	const SQUARE_VERSION = '2026-01-22';

	public function __construct(object $registry) {
		require_once(DIR_EXTENSION . 'lookersolution/vendor/autoload.php');

		$this->session = $registry->get('session');
		$this->url = $registry->get('url');
		$this->config = $registry->get('config');
		$this->log = $registry->get('log');
		$this->currency = $registry->get('currency');
		$this->registry = $registry;
		$this->tokenManager = new TokenManager($registry);
	}

	public function getTokenManager(): TokenManager {
		return $this->tokenManager;
	}

	public function api(array $request_data, bool $is_sandbox = false): array {
		$url = $is_sandbox ? self::API_SANDBOX_URL : self::API_URL;

		if (empty($request_data['no_version'])) {
			$url .= '/' . self::API_VERSION;
		}

		$url .= '/' . $request_data['endpoint'];

		$curl_options = [
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_CONNECTTIMEOUT => 10,
		];

		$content_type = $request_data['content_type'] ?? 'application/json';

		$params = null;
		if (isset($request_data['parameters']) && is_array($request_data['parameters']) && count($request_data['parameters'])) {
			$params = $this->encodeParameters($request_data['parameters'], $content_type);
		}

		switch ($request_data['method']) {
			case 'GET':
				$curl_options[CURLOPT_POST] = false;

				if (is_string($params)) {
					$curl_options[CURLOPT_URL] .= ((strpos($url, '?') === false) ? '?' : '&') . $params;
				}

				break;
			case 'POST':
				$curl_options[CURLOPT_POST] = true;

				if ($params !== null) {
					$curl_options[CURLOPT_POSTFIELDS] = $params;
				}

				break;
			default:
				$curl_options[CURLOPT_CUSTOMREQUEST] = $request_data['method'];

				if ($params !== null) {
					$curl_options[CURLOPT_POSTFIELDS] = $params;
				}

				break;
		}

		$headers = [];
		$headers[] = 'Square-Version: ' . self::SQUARE_VERSION;

		if (!empty($request_data['auth_type'])) {
			$token = $request_data['token'] ?? $this->tokenManager->getAccessToken();
			$headers[] = 'Authorization: ' . $request_data['auth_type'] . ' ' . $token;
		}

		if (!is_array($params)) {
			$headers[] = 'Content-Type: ' . $content_type;
		}

		if (isset($request_data['headers']) && is_array($request_data['headers'])) {
			$curl_options[CURLOPT_HTTPHEADER] = array_merge($headers, $request_data['headers']);
		} else {
			$curl_options[CURLOPT_HTTPHEADER] = $headers;
		}

		$this->debug('SQUARE API ' . $request_data['method'] . ' ' . $curl_options[CURLOPT_URL]);

		$ch = curl_init();
		curl_setopt_array($ch, $curl_options);
		$result = curl_exec($ch);

		if ($result) {
			$is_token_endpoint = !empty($request_data['no_version']);
			$this->debug('SQUARE API RESPONSE: ' . ($is_token_endpoint ? '[REDACTED - token endpoint]' : $result));

			curl_close($ch);

			$return = json_decode($result, true);

			if (!empty($return['errors'])) {
				throw new Exception($this->registry, $return['errors']);
			}

			return $return;
		}

		$info = curl_getinfo($ch);
		curl_close($ch);

		throw new Exception($this->registry, 'CURL error. HTTP code: ' . ($info['http_code'] ?? 'unknown'), true);
	}

	public function verifyToken(string $access_token): bool {
		try {
			$this->api([
				'method'    => 'GET',
				'endpoint'  => self::ENDPOINT_LOCATIONS,
				'auth_type' => 'Bearer',
				'token'     => $access_token,
			]);
		} catch (Exception $e) {
			if ($e->isAccessTokenRevoked() || $e->isAccessTokenExpired()) {
				return false;
			}

			throw $e;
		}

		return true;
	}

	public function authLink(string $client_id): string {
		$state = $this->authState();

		$redirect_uri = str_replace('&amp;', '&', $this->url->link('extension/lookersolution/payment/squareup.oauthCallback', 'user_token=' . $this->session->data['user_token']));

		$this->session->data['payment_squareup_oauth_redirect'] = $redirect_uri;

		$params = [
			'client_id'    => $client_id,
			'scope'        => self::SCOPE,
			'session'      => 'false',
			'state'        => $state,
			'redirect_uri' => $redirect_uri,
		];

		return self::API_URL . '/' . self::ENDPOINT_AUTH . '?' . http_build_query($params);
	}

	public function exchangeCodeForAccessAndRefreshTokens(string $code): array {
		return $this->api([
			'method'     => 'POST',
			'endpoint'   => self::ENDPOINT_TOKEN,
			'no_version' => true,
			'parameters' => [
				'client_id'     => $this->tokenManager->getClientId(),
				'client_secret' => $this->tokenManager->getClientSecret(),
				'redirect_uri'  => $this->session->data['payment_squareup_oauth_redirect'],
				'code'          => $code,
				'grant_type'    => 'authorization_code',
			],
		]);
	}

	public function refreshToken(): array {
		return $this->api([
			'method'     => 'POST',
			'endpoint'   => self::ENDPOINT_TOKEN,
			'no_version' => true,
			'parameters' => [
				'client_id'     => $this->tokenManager->getClientId(),
				'client_secret' => $this->tokenManager->getClientSecret(),
				'grant_type'    => 'refresh_token',
				'refresh_token' => $this->tokenManager->getRefreshToken(),
			],
		]);
	}

	public function listLocations(string $access_token, ?string &$first_location_id = null): array {
		$is_sandbox = ($access_token === $this->config->get('payment_squareup_sandbox_token'));

		$api_result = $this->api([
			'method'    => 'GET',
			'endpoint'  => self::ENDPOINT_LOCATIONS,
			'auth_type' => 'Bearer',
			'token'     => $access_token,
		], $is_sandbox);

		$locations = array_filter($api_result['locations'] ?? [], [$this, 'filterLocation']);

		if (!empty($locations)) {
			$first_location = current($locations);
			$first_location_id = $first_location['id'];
		} else {
			$first_location_id = null;
		}

		return $locations;
	}

	public function retrieveLocation(string $access_token, string $location_id): ?array {
		$is_sandbox = ($access_token === $this->config->get('payment_squareup_sandbox_token'));

		$api_result = $this->api([
			'method'    => 'GET',
			'endpoint'  => self::ENDPOINT_LOCATIONS . '/' . $location_id,
			'auth_type' => 'Bearer',
			'token'     => $access_token,
		], $is_sandbox);

		return $api_result['location'] ?? null;
	}

	public function getPayment(string $payment_id, ?string $access_token = null): ?array {
		$token = $access_token ?? $this->tokenManager->getAccessToken();

		$api_result = $this->api([
			'method'    => 'GET',
			'endpoint'  => self::ENDPOINT_PAYMENTS . '/' . $payment_id,
			'auth_type' => 'Bearer',
			'token'     => $token,
		], $this->tokenManager->isSandbox());

		return isset($api_result['payment']) ? $api_result : null;
	}

	public function createPayment(string $amount, string $currency, array $billing_address, string $email, string $phone, string $source_id, string $reference_id, string $statement_description_identifier, string $customer_id = '', ?string $verification_token = null, ?string $access_token = null): array {
		$token = $access_token ?? $this->tokenManager->getAccessToken();
		$location_id = $this->tokenManager->getLocationId();
		$autocomplete = !$this->config->get('payment_squareup_delay_capture');

		$parameters = [
			'idempotency_key' => bin2hex(random_bytes(16)),
			'amount_money'    => [
				'amount'   => $this->lowestDenomination($amount, $currency),
				'currency' => $currency,
			],
			'source_id'                          => $source_id,
			'autocomplete'                       => $autocomplete,
			'location_id'                        => $location_id,
			'reference_id'                       => $reference_id,
			'billing_address'                    => $billing_address ?: [],
			'buyer_email_address'                => $email,
			'buyer_phone_number'                 => $phone,
			'statement_description_identifier'   => $statement_description_identifier,
			'customer_details'                   => [
				'customer_initiated' => true,
				'seller_keyed_in'    => false,
			],
		];

		if ($verification_token) {
			$parameters['verification_token'] = $verification_token;
		}

		if (str_starts_with($source_id, 'ccof:')) {
			$parameters['customer_id'] = $customer_id;
			$parameters['customer_details']['customer_initiated'] = false;
		}

		return $this->api([
			'method'     => 'POST',
			'endpoint'   => self::ENDPOINT_PAYMENTS,
			'auth_type'  => 'Bearer',
			'token'      => $token,
			'parameters' => $parameters,
		], $this->tokenManager->isSandbox());
	}

	public function completePayment(string $payment_id, ?string $access_token = null): array {
		$token = $access_token ?? $this->tokenManager->getAccessToken();

		return $this->api([
			'method'     => 'POST',
			'endpoint'   => sprintf(self::ENDPOINT_CAPTURE_PAYMENT, $payment_id),
			'auth_type'  => 'Bearer',
			'token'      => $token,
			'parameters' => [],
		], $this->tokenManager->isSandbox());
	}

	public function cancelPayment(string $payment_id, ?string $access_token = null): array {
		$token = $access_token ?? $this->tokenManager->getAccessToken();

		return $this->api([
			'method'     => 'POST',
			'endpoint'   => sprintf(self::ENDPOINT_CANCEL_PAYMENT, $payment_id),
			'auth_type'  => 'Bearer',
			'token'      => $token,
			'parameters' => [],
		], $this->tokenManager->isSandbox());
	}

	public function refundPayment(string $payment_id, float $amount, string $currency, string $reason, ?string $access_token = null): array {
		$token = $access_token ?? $this->tokenManager->getAccessToken();

		return $this->api([
			'method'     => 'POST',
			'endpoint'   => self::ENDPOINT_REFUND,
			'auth_type'  => 'Bearer',
			'token'      => $token,
			'parameters' => [
				'idempotency_key' => bin2hex(random_bytes(16)),
				'payment_id'      => $payment_id,
				'amount_money'    => [
					'amount'   => $this->lowestDenomination($amount, $currency),
					'currency' => $currency,
				],
				'reason' => $reason,
			],
		], $this->tokenManager->isSandbox());
	}

	public function createPaymentLink(string $amount, string $currency, string $redirect_url, array $billing_address, string $email, string $phone, string $item_summary, ?string $access_token = null): array {
		$token = $access_token ?? $this->tokenManager->getAccessToken();
		$location_id = $this->tokenManager->getLocationId();

		return $this->api([
			'method'     => 'POST',
			'endpoint'   => self::ENDPOINT_PAYMENT_LINKS,
			'auth_type'  => 'Bearer',
			'token'      => $token,
			'parameters' => [
				'idempotency_key' => bin2hex(random_bytes(16)),
				'quick_pay'       => [
					'name'        => $this->config->get('config_name') . ' - ' . $item_summary,
					'price_money' => [
						'amount'   => $this->lowestDenomination($amount, $currency),
						'currency' => $currency,
					],
					'location_id' => $location_id,
				],
				'checkout_options' => [
					'redirect_url'             => $redirect_url,
					'ask_for_shipping_address' => false,
					'enable_coupon'            => false,
					'accepted_payment_methods' => [
						'apple_pay'         => (bool)$this->config->get('payment_squareup_apple_pay'),
						'google_pay'        => (bool)$this->config->get('payment_squareup_google_pay'),
						'cash_app_pay'      => (bool)$this->config->get('payment_squareup_cashapp_pay'),
						'afterpay_clearpay' => (bool)$this->config->get('payment_squareup_afterpay'),
					],
				],
				'pre_populated_data' => [
					'buyer_email'        => $email,
					'buyer_address'      => $billing_address ?: [],
					'buyer_phone_number' => $phone,
				],
			],
		], $this->tokenManager->isSandbox());
	}

	public function retrieveOrder(string $order_id, ?string $access_token = null): ?array {
		$token = $access_token ?? $this->tokenManager->getAccessToken();

		$api_result = $this->api([
			'method'    => 'GET',
			'endpoint'  => self::ENDPOINT_ORDERS . '/' . $order_id,
			'auth_type' => 'Bearer',
			'token'     => $token,
		], $this->tokenManager->isSandbox());

		return isset($api_result['order']) ? $api_result : null;
	}

	public function searchCustomers(string $email, string $phone, ?string $access_token = null): array {
		$token = $access_token ?? $this->tokenManager->getAccessToken();

		return $this->api([
			'method'     => 'POST',
			'endpoint'   => self::ENDPOINT_CUSTOMERS_SEARCH,
			'auth_type'  => 'Bearer',
			'token'      => $token,
			'parameters' => [
				'query' => [
					'filter' => [
						'email_address' => ['exact' => $email],
						'phone_number'  => ['exact' => $phone],
					],
				],
			],
		], $this->tokenManager->isSandbox());
	}

	public function createCustomer(array $billing_address, string $email, string $phone, ?string $access_token = null): array {
		$token = $access_token ?? $this->tokenManager->getAccessToken();

		return $this->api([
			'method'     => 'POST',
			'endpoint'   => self::ENDPOINT_CUSTOMERS,
			'auth_type'  => 'Bearer',
			'token'      => $token,
			'parameters' => [
				'idempotency_key' => bin2hex(random_bytes(16)),
				'given_name'      => $billing_address['first_name'] ?? '',
				'family_name'     => $billing_address['last_name'] ?? '',
				'email_address'   => $email,
				'address'         => $billing_address ?: [],
				'phone_number'    => $phone,
			],
		], $this->tokenManager->isSandbox());
	}

	public function listCards(string $customer_id, ?string $access_token = null): array {
		$token = $access_token ?? $this->tokenManager->getAccessToken();
		$all_cards = [];
		$cursor = null;

		do {
			$params = [
				'customer_id'      => $customer_id,
				'include_disabled' => false,
			];

			if ($cursor) {
				$params['cursor'] = $cursor;
			}

			$result = $this->api([
				'method'     => 'GET',
				'endpoint'   => self::ENDPOINT_CARDS,
				'auth_type'  => 'Bearer',
				'token'      => $token,
				'parameters' => $params,
			], $this->tokenManager->isSandbox());

			if (!empty($result['cards'])) {
				$all_cards = array_merge($all_cards, $result['cards']);
			}

			$cursor = $result['cursor'] ?? null;
		} while ($cursor);

		return ['cards' => $all_cards];
	}

	public function createCard(string $source_id, string $verification_token, string $customer_id, array $billing_address, ?string $access_token = null): array {
		$token = $access_token ?? $this->tokenManager->getAccessToken();

		return $this->api([
			'method'     => 'POST',
			'endpoint'   => self::ENDPOINT_CARDS,
			'auth_type'  => 'Bearer',
			'token'      => $token,
			'parameters' => [
				'idempotency_key'  => bin2hex(random_bytes(16)),
				'source_id'        => $source_id,
				'verification_token' => $verification_token,
				'card'             => [
					'customer_id'     => $customer_id,
					'billing_address' => $billing_address ?: [],
				],
			],
		], $this->tokenManager->isSandbox());
	}

	public function disableCard(string $card_id, ?string $access_token = null): array {
		$token = $access_token ?? $this->tokenManager->getAccessToken();

		return $this->api([
			'method'     => 'POST',
			'endpoint'   => self::ENDPOINT_CARDS . '/' . $card_id . '/disable',
			'auth_type'  => 'Bearer',
			'token'      => $token,
			'parameters' => [],
		], $this->tokenManager->isSandbox());
	}

	public function createWebhookSubscription(string $notification_url, array $event_types, ?string $access_token = null): array {
		$token = $access_token ?? $this->tokenManager->getAccessToken();

		return $this->api([
			'method'     => 'POST',
			'endpoint'   => self::ENDPOINT_WEBHOOKS,
			'auth_type'  => 'Bearer',
			'token'      => $token,
			'parameters' => [
				'idempotency_key' => bin2hex(random_bytes(16)),
				'subscription'    => [
					'name'             => 'OpenCart Square Payment',
					'event_types'      => $event_types,
					'notification_url' => $notification_url,
					'api_version'      => self::SQUARE_VERSION,
				],
			],
		], $this->tokenManager->isSandbox());
	}

	public function listWebhookSubscriptions(?string $access_token = null): array {
		$token = $access_token ?? $this->tokenManager->getAccessToken();

		return $this->api([
			'method'    => 'GET',
			'endpoint'  => self::ENDPOINT_WEBHOOKS,
			'auth_type' => 'Bearer',
			'token'     => $token,
		], $this->tokenManager->isSandbox());
	}

	public function deleteWebhookSubscription(string $subscription_id, ?string $access_token = null): array {
		$token = $access_token ?? $this->tokenManager->getAccessToken();

		return $this->api([
			'method'    => 'DELETE',
			'endpoint'  => self::ENDPOINT_WEBHOOKS . '/' . $subscription_id,
			'auth_type' => 'Bearer',
			'token'     => $token,
		], $this->tokenManager->isSandbox());
	}

	public function registerApplePayDomain(string $domain_name, ?string $access_token = null): array {
		$token = $access_token ?? $this->tokenManager->getAccessToken();

		return $this->api([
			'method'     => 'POST',
			'endpoint'   => self::ENDPOINT_APPLE_PAY_DOMAINS,
			'auth_type'  => 'Bearer',
			'token'      => $token,
			'parameters' => [
				'domain_name' => $domain_name,
			],
		], $this->tokenManager->isSandbox());
	}

	public function lowestDenomination(float|string $value, string $currency): int {
		$power = $this->currency->getDecimalPlace($currency);
		return (int)round((float)$value * pow(10, $power));
	}

	public function standardDenomination(int|string $value, string $currency): float {
		$power = $this->currency->getDecimalPlace($currency);
		return (float)((int)$value / pow(10, $power));
	}

	public function phoneFormat(string $raw_number, string $country_code): string {
		$phone_util = \libphonenumber\PhoneNumberUtil::getInstance();

		try {
			$number_proto = $phone_util->parse($raw_number, $country_code);
			return $phone_util->format($number_proto, \libphonenumber\PhoneNumberFormat::E164);
		} catch (\libphonenumber\NumberParseException $e) {
			return $raw_number;
		}
	}

	public function debug(string $text): void {
		if ($this->config->get('payment_squareup_debug')) {
			$this->log->write($text);
		}
	}

	protected function filterLocation(array $location): bool {
		if (empty($location['capabilities'])) {
			return false;
		}

		return in_array('CREDIT_CARD_PROCESSING', $location['capabilities']);
	}

	protected function encodeParameters(array $params, string $content_type): string|array {
		return match ($content_type) {
			'application/json'                  => json_encode($params),
			'application/x-www-form-urlencoded' => http_build_query($params),
			default                             => $params,
		};
	}

	protected function authState(): string {
		if (!isset($this->session->data['payment_squareup_oauth_state'])) {
			$this->session->data['payment_squareup_oauth_state'] = bin2hex(random_bytes(32));
		}

		return $this->session->data['payment_squareup_oauth_state'];
	}
}
