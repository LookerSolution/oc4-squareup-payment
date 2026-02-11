<?php
/**
 * @package    LookerSolution\SquarePayment
 * @author     Ali Ahmed <ali@lookersolution.com>
 * @copyright  Copyright (c) 2026 LookerSolution (https://www.lookersolution.com)
 * @license    https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link       https://github.com/LookerSolution/oc4-squareup-payment
 */
namespace Opencart\Catalog\Controller\Extension\Lookersolution\Payment;

use Opencart\System\Library\Extension\Lookersolution\Squareup as SquareupLib;
use Opencart\System\Library\Extension\Lookersolution\Squareup\Exception as SquareupException;

class Squareup extends \Opencart\System\Engine\Controller {
	private ?SquareupLib $squareup = null;

	private function getSquareup(): SquareupLib {
		if ($this->squareup === null) {
			$this->squareup = new SquareupLib($this->registry);
		}
		return $this->squareup;
	}

	public function index(): string {
		$this->load->language('extension/lookersolution/payment/squareup');

		$data['language'] = $this->config->get('config_language');

		$data['sandbox_message'] = $this->config->get('payment_squareup_enable_sandbox')
			? $this->language->get('warning_test_mode')
			: '';

		$data['is_quick_pay'] = (bool)$this->config->get('payment_squareup_quick_pay');

		if ($data['is_quick_pay']) {
			$data['url_checkout'] = $this->url->link('extension/lookersolution/payment/squareup.checkout', 'language=' . $this->config->get('config_language'));
			return $this->load->view('extension/lookersolution/payment/squareup', $data);
		}

		$squareup = $this->getSquareup();
		$token_manager = $squareup->getTokenManager();

		if ($this->config->get('payment_squareup_enable_sandbox')) {
			$data['application_id'] = $this->config->get('payment_squareup_sandbox_client_id');
		} else {
			$data['application_id'] = $this->config->get('payment_squareup_client_id');
		}

		$data['location_id'] = $token_manager->getLocationId();
		$data['url_confirm'] = $this->url->link('extension/lookersolution/payment/squareup.confirm', 'language=' . $this->config->get('config_language'));

		$data['text_payment'] = $this->config->get('payment_squareup_delay_capture')
			? $this->language->get('text_authorize')
			: $this->language->get('text_capture');

		$this->load->model('checkout/order');
		$this->load->model('extension/lookersolution/payment/squareup');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		list($amount, $currency) = $this->model_extension_lookersolution_payment_squareup->getAmountAndCurrency($this->firstPayment($order_info));

		$data['given_name'] = $order_info['payment_firstname'];
		$data['family_name'] = $order_info['payment_lastname'];
		$data['email'] = $order_info['email'];
		$data['phone'] = $squareup->phoneFormat($order_info['telephone'], $order_info['payment_iso_code_2']);
		$data['address_line_1'] = $order_info['payment_address_1'];
		$data['address_line_2'] = $order_info['payment_address_2'];
		$data['city'] = $order_info['payment_city'];
		$data['state'] = $order_info['payment_zone'];
		$data['postal_code'] = $order_info['payment_postcode'];
		$data['country_code'] = $order_info['payment_iso_code_2'];
		$data['is_sandbox'] = (bool)$this->config->get('payment_squareup_enable_sandbox');
		$data['currency'] = $currency;
		$data['amount'] = (string)round($amount, 2);

		$data['apple_pay'] = (bool)$this->config->get('payment_squareup_apple_pay');
		$data['google_pay'] = (bool)$this->config->get('payment_squareup_google_pay');
		$data['cashapp_pay'] = (bool)$this->config->get('payment_squareup_cashapp_pay');
		$data['afterpay'] = (bool)$this->config->get('payment_squareup_afterpay');
		$data['ach'] = (bool)$this->config->get('payment_squareup_ach');

		if ($this->cart->hasSubscription()) {
			if ($amount > 0) {
				$data['intent'] = 'CHARGE_AND_STORE';
			} else {
				$data['intent'] = 'STORE';
			}
		} else {
			$data['intent'] = 'CHARGE';
		}

		$this->session->data['squareup_amount'] = $data['amount'];
		$this->session->data['squareup_currency'] = $data['currency'];
		$this->session->data['squareup_intent'] = $data['intent'];

		return $this->load->view('extension/lookersolution/payment/squareup', $data);
	}

	public function checkout(): void {
		$this->load->language('extension/lookersolution/payment/squareup');
		$this->load->model('extension/lookersolution/payment/squareup');
		$this->load->model('checkout/order');

		$json = [];

		if (empty($this->session->data['order_id'])) {
			$json['error'] = $this->language->get('error_missing_order');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		$squareup = $this->getSquareup();
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		unset($this->session->data['squareup_payment_link']);

		try {
			$billing_address = $this->model_extension_lookersolution_payment_squareup->getBillingAddress($order_info);
			list($amount, $currency) = $this->model_extension_lookersolution_payment_squareup->getAmountAndCurrency($order_info['total']);

			$redirect_url = $this->url->link('extension/lookersolution/payment/squareup.callback', 'language=' . $this->config->get('config_language'), true);
			$email = $order_info['email'];
			$phone = $squareup->phoneFormat($order_info['telephone'], $order_info['payment_iso_code_2']);
			$item_summary = $this->language->get('text_order_id') . '=' . $order_info['order_id'];

			$result = $squareup->createPaymentLink($amount, $currency, $redirect_url, $billing_address, $email, $phone, $item_summary);

			$json['payment_link'] = $result['payment_link']['long_url'];

			$this->session->data['squareup_payment_link'] = $result;
			$this->session->data['squareup_payment_ip'] = $order_info['ip'];
			$this->session->data['squareup_payment_user_agent'] = $order_info['user_agent'];
		} catch (SquareupException $e) {
			$json['error'] = $this->handleApiException($e);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function callback(): void {
		$this->load->language('extension/lookersolution/payment/squareup');

		$lang = 'language=' . $this->config->get('config_language');

		if (empty($this->session->data['squareup_payment_link'])) {
			$this->session->data['error'] = $this->language->get('error_missing_payment_link');
			$this->response->redirect($this->url->link('checkout/checkout', $lang));
			return;
		}

		$squareup = $this->getSquareup();
		$square_payment_link = $this->session->data['squareup_payment_link'];

		if (!isset($square_payment_link['payment_link']['order_id'])) {
			$this->response->redirect($this->url->link('checkout/checkout', $lang));
			return;
		}

		$order_id = $square_payment_link['payment_link']['order_id'];

		try {
			$order = $squareup->retrieveOrder($order_id);
		} catch (SquareupException $e) {
			$this->session->data['error'] = $e->getMessage();
			$this->response->redirect($this->url->link('checkout/checkout', $lang));
			return;
		}

		if (!isset($order['order']['tenders'][0]['id'])) {
			$this->session->data['error'] = $this->language->get('error_missing_order_tender_id');
			$this->response->redirect($this->url->link('checkout/checkout', $lang));
			return;
		}

		$payment_id = $order['order']['tenders'][0]['id'];

		try {
			$payment = $squareup->getPayment($payment_id);
		} catch (SquareupException $e) {
			$this->session->data['error'] = $e->getMessage();
			$this->response->redirect($this->url->link('checkout/checkout', $lang));
			return;
		}

		if (!isset($payment['payment']['status'])) {
			$this->session->data['error'] = $this->language->get('error_payment');
			$this->response->redirect($this->url->link('checkout/checkout', $lang));
			return;
		}

		$status = $payment['payment']['status'];

		if (!in_array($status, ['COMPLETED', 'PENDING', 'APPROVED'])) {
			$this->session->data['error'] = str_replace('%1', $status, $this->language->get('error_payment_status'));
			$this->response->redirect($this->url->link('checkout/checkout', $lang));
			return;
		}

		$this->load->model('checkout/order');

		$order_status_id = $this->paymentStatusToOrderStatus($status);
		$this->model_checkout_order->addHistory($this->session->data['order_id'], $order_status_id);

		$this->load->model('extension/lookersolution/payment/squareup');

		$user_agent = $this->session->data['squareup_payment_user_agent'] ?? '';
		$ip = $this->session->data['squareup_payment_ip'] ?? '';

		$this->model_extension_lookersolution_payment_squareup->addPayment(
			$payment,
			$this->config->get('payment_squareup_merchant_id'),
			$this->session->data['order_id'],
			$user_agent,
			$ip
		);

		unset(
			$this->session->data['squareup_payment_link'],
			$this->session->data['squareup_payment_ip'],
			$this->session->data['squareup_payment_user_agent']
		);

		$this->response->redirect($this->url->link('checkout/success', $lang));
	}

	public function confirm(): void {
		$this->load->language('extension/lookersolution/payment/squareup');
		$this->load->model('extension/lookersolution/payment/squareup');
		$this->load->model('checkout/order');

		$json = [];
		$lang = 'language=' . $this->config->get('config_language');
		$error = '';

		if (empty($this->request->post['source_id'])) {
			$error = $this->language->get('error_missing_source_id');
		} elseif (empty($this->session->data['squareup_intent'])) {
			$error = $this->language->get('error_missing_intent');
		} elseif (!isset($this->session->data['squareup_amount'])) {
			$error = $this->language->get('error_missing_amount');
		} elseif (empty($this->session->data['squareup_currency'])) {
			$error = $this->language->get('error_missing_currency');
		} elseif (empty($this->session->data['order_id'])) {
			$error = $this->language->get('error_payment');
		}

		if (!$error && (!isset($this->session->data['payment_method']) || $this->session->data['payment_method']['code'] !== 'squareup.squareup')) {
			$error = $this->language->get('error_payment');
		}

		if ($error) {
			$this->session->data['error'] = $error;
			$json['redirect'] = $this->url->link('checkout/checkout', $lang);
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		$source_id = $this->request->post['source_id'];
		$verification_token = $this->request->post['verification_token'] ?? '';
		$intent = $this->session->data['squareup_intent'];
		$amount = $this->session->data['squareup_amount'];
		$currency = $this->session->data['squareup_currency'];
		$order_id = $this->session->data['order_id'];

		unset(
			$this->session->data['squareup_amount'],
			$this->session->data['squareup_currency'],
			$this->session->data['squareup_intent']
		);

		$order_info = $this->model_checkout_order->getOrder($order_id);
		$squareup = $this->getSquareup();

		$email = $order_info['email'];
		$phone = $squareup->phoneFormat($order_info['telephone'], $order_info['payment_iso_code_2']);
		$statement_description = $this->language->get('text_order_id') . '=' . $order_info['order_id'];
		$reference_id = (string)$order_info['order_id'];

		$payment = null;
		$status = '';

		if ($intent === 'CHARGE' || $intent === 'CHARGE_AND_STORE') {
			try {
				$billing_address = $this->model_extension_lookersolution_payment_squareup->getBillingAddress($order_info);
				$payment = $squareup->createPayment(
					$amount,
					$currency,
					$billing_address,
					$email,
					$phone,
					$source_id,
					$reference_id,
					$statement_description,
					'',
					$verification_token ?: null
				);
			} catch (SquareupException $e) {
				$this->session->data['error'] = $this->handleApiException($e);
				$json['redirect'] = $this->url->link('checkout/checkout', $lang);
				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode($json));
				return;
			}

			if (!isset($payment['payment'])) {
				$this->session->data['error'] = $this->language->get('error_payment');
				$json['redirect'] = $this->url->link('checkout/checkout', $lang);
				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode($json));
				return;
			}

			$status = $payment['payment']['status'];

			if (!in_array($status, ['APPROVED', 'COMPLETED', 'PENDING'])) {
				$this->session->data['error'] = str_replace('%s', $status, $this->language->get('error_payment_status'));
				$json['redirect'] = $this->url->link('checkout/checkout', $lang);
				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode($json));
				return;
			}

			$this->model_extension_lookersolution_payment_squareup->addPayment(
				$payment,
				$this->config->get('payment_squareup_merchant_id'),
				$order_id,
				$order_info['user_agent'],
				$order_info['ip']
			);

			$source_id = $payment['payment']['id'];
		}

		$order_status_id = $this->paymentStatusToOrderStatus($status);
		$this->model_checkout_order->addHistory($order_id, $order_status_id);

		if ($intent === 'STORE' || $intent === 'CHARGE_AND_STORE') {
			$order_info = $this->model_checkout_order->getOrder($order_id);
			$sub_error = $this->initSubscriptions($source_id, $verification_token, $order_info, $payment);

			if ($sub_error) {
				$this->session->data['error'] = $sub_error;
				$json['redirect'] = $this->url->link('checkout/checkout', $lang);
				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode($json));
				return;
			}
		}

		$json['redirect'] = $this->url->link('checkout/success', $lang);
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	protected function initSubscriptions(string $source_id, string $verification_token, array $order_info, ?array $payment = null): string {
		$squareup = $this->getSquareup();
		$billing_address = $this->model_extension_lookersolution_payment_squareup->getBillingAddress($order_info);
		$email = $order_info['email'];
		$phone = $squareup->phoneFormat($order_info['telephone'], $order_info['payment_iso_code_2']);
		$card_fingerprint = $payment['payment']['card_details']['card']['fingerprint'] ?? '';

		$error = '';
		if (!$email) {
			$error .= $this->language->get('error_missing_email');
		}
		if (!$phone) {
			if ($error) {
				$error .= '<br>';
			}
			$error .= $this->language->get('error_missing_phone');
		}

		if ($error) {
			return $error;
		}

		try {
			$customers = $squareup->searchCustomers($email, $phone);
			if (!empty($customers['customers'][0])) {
				$customer = ['customer' => $customers['customers'][0]];
			} else {
				$customer = $squareup->createCustomer($billing_address, $email, $phone);
			}

			if (empty($customer['customer']['id'])) {
				return str_replace('%2', $phone, str_replace('%1', $email, $this->language->get('error_customer')));
			}

			$customer_id = $customer['customer']['id'];
			$payment_card = null;

			if ($card_fingerprint) {
				$cards = $squareup->listCards($customer_id);
				if (!empty($cards['cards'])) {
					foreach ($cards['cards'] as $card) {
						if ($card['fingerprint'] === $card_fingerprint) {
							$payment_card = ['card' => $card];
							break;
						}
					}
				}
				if (empty($payment_card['card'])) {
					$payment_card = $squareup->createCard($source_id, $verification_token, $customer_id, $billing_address);
				}
			} else {
				$new_card = $squareup->createCard($source_id, $verification_token, $customer_id, $billing_address);
				if ($new_card) {
					$new_card_fingerprint = $new_card['card']['fingerprint'];
					$new_card_id = $new_card['card']['id'];
					$cards = $squareup->listCards($customer_id);
					if (!empty($cards['cards'])) {
						foreach ($cards['cards'] as $card) {
							if ($card['id'] === $new_card_id) {
								continue;
							}
							if ($card['fingerprint'] === $new_card_fingerprint) {
								$squareup->disableCard($new_card['card']['id']);
								$new_card = ['card' => $card];
								break;
							}
						}
					}
					$payment_card = $new_card;
				}
			}

			if (empty($payment_card['card'])) {
				return str_replace('%1', $email, $this->language->get('error_card'));
			}

			$payment_card_id = $payment_card['card']['id'];

			$payment_id = $payment['payment']['id'] ?? '';
			if ($payment_id) {
				$this->model_extension_lookersolution_payment_squareup->updatePaymentCustomerId($payment_id, $customer_id);
			}

			$order_product_query = $this->db->query("SELECT `order_product_id`, `product_id` FROM `" . DB_PREFIX . "order_product` WHERE `order_id` = '" . (int)$this->session->data['order_id'] . "'");
			$order_product_map = [];
			foreach ($order_product_query->rows as $op) {
				$order_product_map[$op['product_id']] = (int)$op['order_product_id'];
			}

			$this->load->model('checkout/subscription');

			foreach ($this->cart->getProducts() as $product) {
				if (empty($product['subscription'])) {
					continue;
				}

				$subscription = $product['subscription'];
				$trial_price = 0;
				$trial_tax = 0;

				if ($subscription['trial_status']) {
					$trial_price = $this->tax->calculate($subscription['trial_price'] * $product['quantity'], $product['tax_class_id']);
					$trial_tax = $this->tax->getTax($subscription['trial_price'] * $product['quantity'], $product['tax_class_id']);
				}

				$price = $this->tax->calculate($subscription['price'] * $product['quantity'], $product['tax_class_id']);
				$tax = $this->tax->getTax($subscription['price'] * $product['quantity'], $product['tax_class_id']);

				$subscription_data = [
					'order_id'             => $this->session->data['order_id'],
					'store_id'             => $this->config->get('config_store_id'),
					'customer_id'          => $order_info['customer_id'],
					'payment_address_id'   => $order_info['payment_address_id'] ?? 0,
					'payment_method'       => [
						'name'        => $this->session->data['payment_method']['name'] ?? '',
						'code'        => 'squareup.squareup',
						'card_id'     => $payment_card_id,
						'customer_id' => $customer_id,
					],
					'shipping_address_id'  => $order_info['shipping_address_id'] ?? 0,
					'shipping_method'      => $this->session->data['shipping_method'] ?? [],
					'subscription_plan_id' => $subscription['subscription_plan_id'],
					'trial_price'          => $trial_price,
					'trial_tax'            => $trial_tax,
					'trial_frequency'      => $subscription['trial_frequency'],
					'trial_cycle'          => $subscription['trial_cycle'],
					'trial_duration'       => $subscription['trial_duration'],
					'trial_status'         => $subscription['trial_status'],
					'price'                => $price,
					'tax'                  => $tax,
					'frequency'            => $subscription['frequency'],
					'cycle'                => $subscription['cycle'],
					'duration'             => $subscription['duration'],
					'comment'              => '',
					'language'             => $this->config->get('config_language'),
					'currency'             => $this->session->data['currency'],
					'subscription_product' => [
						[
							'order_id'         => $this->session->data['order_id'],
							'order_product_id' => $order_product_map[$product['product_id']] ?? 0,
							'product_id'       => $product['product_id'],
							'name'             => $product['name'],
							'model'            => $product['model'],
							'quantity'         => $product['quantity'],
							'trial_price'      => $trial_price,
							'price'            => $price,
							'option'           => [],
						],
					],
				];

				$subscription_id = $this->model_checkout_subscription->addSubscription($subscription_data);

				$this->model_checkout_subscription->addHistory(
					$subscription_id,
					(int)$this->config->get('config_subscription_active_status_id'),
					''
				);
			}
		} catch (SquareupException $e) {
			return $this->handleApiException($e);
		}

		return '';
	}

	protected function firstPayment(array $order_info): float {
		if (!$this->cart->hasSubscription()) {
			return (float)$order_info['total'];
		}

		$total = (float)$order_info['total'];

		foreach ($this->cart->getProducts() as $product) {
			if (!empty($product['subscription'])) {
				$subscription = $product['subscription'];
				$total_subscription = (float)$subscription['price'] * (int)$subscription['duration'];
				$total_subscription += (float)$subscription['trial_price'] * (int)$subscription['trial_duration'];
				$total_subscription = $total_subscription * $product['quantity'];
				$total -= $total_subscription;

				if ($subscription['trial_status']) {
					$total += (float)$subscription['trial_price'] * $product['quantity'];
				} else {
					$total += (float)$subscription['price'] * $product['quantity'];
				}
			}
		}

		return $total;
	}

	public function eventViewCommonHeaderAfter(string &$route, array &$data, string &$output): void {
		if (!$this->config->get('payment_squareup_status')) {
			return;
		}

		$page_route = $this->request->get['route'] ?? '';
		if ($page_route !== 'checkout/checkout') {
			return;
		}

		if ($this->config->get('payment_squareup_quick_pay')) {
			return;
		}

		$csp = $this->config->get('payment_squareup_content_security');

		if ($csp) {
			$search = '<title>';
			$add = '<meta http-equiv="Content-Security-Policy" content="' . $csp . '">';
			$output = str_replace($search, $add . "\n" . $search, $output);
		}
	}

	protected function paymentStatusToOrderStatus(string $payment_status): int {
		return match ($payment_status) {
			'APPROVED'  => (int)$this->config->get('payment_squareup_status_authorized'),
			'PENDING'   => $this->config->get('payment_squareup_delay_capture')
				? (int)$this->config->get('payment_squareup_status_authorized')
				: (int)$this->config->get('payment_squareup_status_captured'),
			'COMPLETED' => (int)$this->config->get('payment_squareup_status_captured'),
			default     => (int)$this->config->get('config_order_status_id'),
		};
	}

	private function handleApiException(SquareupException $e): string {
		if ($e->isCurlError() || $e->isAccessTokenRevoked() || $e->isAccessTokenExpired()) {
			$this->load->model('extension/lookersolution/payment/squareup');

			if ($e->isAccessTokenRevoked()) {
				$this->model_extension_lookersolution_payment_squareup->tokenRevokedEmail();
			}
			if ($e->isAccessTokenExpired()) {
				$this->model_extension_lookersolution_payment_squareup->tokenExpiredEmail();
			}

			return $this->language->get('text_token_issue_customer_error');
		}

		return $e->getMessage();
	}
}
