<?php
/**
 * @package    LookerSolution\SquarePayment
 * @author     Ali Ahmed <ali@lookersolution.com>
 * @copyright  Copyright (c) 2026 LookerSolution (https://www.lookersolution.com)
 * @license    https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link       https://github.com/LookerSolution/oc4-squareup-payment
 */
namespace Opencart\Catalog\Controller\Extension\Lookersolution\Cron;

use Opencart\System\Library\Extension\Lookersolution\Squareup as SquareupLib;
use Opencart\System\Library\Extension\Lookersolution\Squareup\Exception as SquareupException;

class Squareup extends \Opencart\System\Engine\Controller {
	public function index(int $cron_id = 0, string $code = '', string $cycle = '', string $date_added = '', string $date_modified = ''): void {
		if ($cron_id > 0) {
			$this->cronJob();
		} else {
			$this->subscriptionPayment();
		}
	}

	private function cronJob(): void {
		$this->load->language('extension/lookersolution/payment/squareup');
		$this->load->model('extension/lookersolution/payment/squareup');

		if (!$this->config->get('payment_squareup_status')) {
			return;
		}

		$squareup = new SquareupLib($this->registry);
		$result = [
			'token_update_error'    => '',
			'transaction_error'     => [],
			'transaction_fail'      => [],
			'transaction_success'   => [],
		];

		if (!$this->config->get('payment_squareup_enable_sandbox')) {
			try {
				$response = $squareup->refreshToken();

				if (!isset($response['access_token']) || !isset($response['merchant_id']) || $response['merchant_id'] !== $this->config->get('payment_squareup_merchant_id')) {
					$result['token_update_error'] = $this->language->get('error_squareup_cron_token');
				} else {
					$this->model_extension_lookersolution_payment_squareup->editTokenSetting([
						'payment_squareup_access_token'         => $response['access_token'],
						'payment_squareup_access_token_expires' => $response['expires_at'],
					]);
				}
			} catch (SquareupException $e) {
				$result['token_update_error'] = $e->getMessage();
			}
		}

		if ($this->config->get('payment_squareup_cron_email_status')) {
			$this->model_extension_lookersolution_payment_squareup->cronEmail($result);
		}
	}

	private function subscriptionPayment(): void {
		$this->load->language('extension/lookersolution/payment/squareup');
		$this->load->model('extension/lookersolution/payment/squareup');
		$this->load->model('checkout/order');
		$this->load->model('checkout/subscription');

		$order_id = $this->session->data['order_id'] ?? 0;
		if (!$order_id) {
			return;
		}

		$order_info = $this->model_checkout_order->getOrder($order_id);
		if (!$order_info) {
			return;
		}

		$payment_method = $this->session->data['payment_method'] ?? [];
		$card_id = $payment_method['card_id'] ?? '';
		$customer_id = $payment_method['customer_id'] ?? '';

		$subscription_id = (int)($order_info['subscription_id'] ?? 0);
		if (!$subscription_id) {
			return;
		}

		$subscription_info = $this->model_checkout_subscription->getSubscription($subscription_id);
		if (!$subscription_info) {
			return;
		}

		if (!$card_id || !$customer_id) {
			$this->model_checkout_subscription->addHistory(
				$subscription_id,
				(int)$this->config->get('config_subscription_failed_status_id'),
				'Missing card-on-file or customer ID for recurring payment.'
			);
			$this->model_checkout_subscription->addLog($subscription_id, 'payment', 'Missing card_id or customer_id in payment_method.', false);
			return;
		}

		$in_trial = ($subscription_info['trial_status'] && $subscription_info['trial_remaining'] > 0);
		$price = $in_trial ? (float)$subscription_info['trial_price'] : (float)$subscription_info['price'];
		$is_free = ($price == 0);

		$squareup = new SquareupLib($this->registry);
		$payment = null;
		$payment_status = '';

		if (!$is_free) {
			try {
				$billing_address = $this->model_extension_lookersolution_payment_squareup->getBillingAddress($order_info);
				list($amount, $currency) = $this->model_extension_lookersolution_payment_squareup->getAmountAndCurrency($price);

				$email = $order_info['email'];
				$phone = $squareup->phoneFormat($order_info['telephone'], $order_info['payment_iso_code_2'] ?? '');
				$reference_id = (string)$order_info['order_id'];
				$statement_description = $this->language->get('text_order_id') . '=' . $order_info['order_id'];

				$payment = $squareup->createPayment(
					(string)round($amount, 2),
					$currency,
					$billing_address,
					$email,
					$phone,
					$card_id,
					$reference_id,
					$statement_description,
					$customer_id
				);

				if (!empty($payment['payment']['status'])) {
					$payment_status = $payment['payment']['status'];
				}

				if ($payment_status) {
					$this->model_extension_lookersolution_payment_squareup->addPayment(
						$payment,
						$this->config->get('payment_squareup_merchant_id'),
						$order_id,
						'CRON',
						''
					);
				}
			} catch (SquareupException $e) {
				if ($e->isAccessTokenRevoked()) {
					$this->model_extension_lookersolution_payment_squareup->tokenRevokedEmail();
				}
				if ($e->isAccessTokenExpired()) {
					$this->model_extension_lookersolution_payment_squareup->tokenExpiredEmail();
				}

				$this->model_checkout_subscription->addHistory(
					$subscription_id,
					(int)$this->config->get('config_subscription_failed_status_id'),
					$e->getMessage()
				);
				$this->model_checkout_subscription->addLog($subscription_id, 'payment', $e->getMessage(), false);

				$notify = (bool)$this->config->get('payment_squareup_notify_recurring_fail');
				$order_status_id = (int)$this->config->get('payment_squareup_status_failed');
				$this->model_checkout_order->addHistory($order_id, $order_status_id, $e->getMessage(), $notify);
				return;
			}
		} else {
			$payment_status = 'COMPLETED';
		}

		$success = in_array($payment_status, ['COMPLETED', 'APPROVED']);

		if ($success) {
			$this->handleSuccessfulPayment($order_id, $order_info, $subscription_id, $subscription_info, $payment_status, $is_free, $in_trial);
		} else {
			$this->handleFailedPayment($order_id, $subscription_id, $payment_status, $is_free);
		}
	}

	private function handleSuccessfulPayment(int $order_id, array $order_info, int $subscription_id, array $subscription_info, string $payment_status, bool $is_free, bool $in_trial): void {
		$order_status_id = $this->paymentStatusToOrderStatus($payment_status);
		$comment = '';

		if (!$is_free) {
			$comment = $this->language->get('squareup_status_comment_captured');
		}

		$trial_expired = false;
		$subscription_expired = false;

		if ($in_trial) {
			$new_trial_remaining = $subscription_info['trial_remaining'] - 1;
			$this->model_checkout_subscription->editTrialRemaining($subscription_id, $new_trial_remaining);

			if ($new_trial_remaining <= 0) {
				$trial_expired = true;
				$date_next = date('Y-m-d', strtotime('+' . $subscription_info['cycle'] . ' ' . $subscription_info['frequency']));
			} else {
				$date_next = date('Y-m-d', strtotime('+' . $subscription_info['trial_cycle'] . ' ' . $subscription_info['trial_frequency']));
			}
		} else {
			if ($subscription_info['duration'] && $subscription_info['remaining'] > 0) {
				$new_remaining = $subscription_info['remaining'] - 1;
				$this->model_checkout_subscription->editRemaining($subscription_id, $new_remaining);

				if ($new_remaining <= 0) {
					$subscription_expired = true;
				}
			}

			$date_next = date('Y-m-d', strtotime('+' . $subscription_info['cycle'] . ' ' . $subscription_info['frequency']));
		}

		$this->model_checkout_subscription->editDateNext($subscription_id, $date_next);

		if ($trial_expired) {
			$comment .= $this->language->get('text_squareup_trial_expired');
		}

		if ($subscription_expired) {
			$comment .= $this->language->get('text_squareup_subscription_expired');

			$this->model_checkout_subscription->addHistory(
				$subscription_id,
				(int)$this->config->get('config_subscription_expired_status_id'),
				trim($comment)
			);
		} else {
			$this->model_checkout_subscription->addHistory(
				$subscription_id,
				(int)$this->config->get('config_subscription_active_status_id'),
				trim($comment)
			);
		}

		$target_currency = $order_info['currency_code'];
		$price = $in_trial ? (float)$subscription_info['trial_price'] : (float)$subscription_info['price'];
		$this->model_checkout_subscription->addLog(
			$subscription_id,
			'payment',
			'Payment successful: ' . $this->currency->format($price, $target_currency),
			true
		);

		$notify = (bool)$this->config->get('payment_squareup_notify_recurring_success');
		$this->model_checkout_order->addHistory($order_id, $order_status_id, trim($comment), $notify);
	}

	private function handleFailedPayment(int $order_id, int $subscription_id, string $payment_status, bool $is_free): void {
		$comment = $this->language->get('text_squareup_profile_suspended');

		$this->model_checkout_subscription->addHistory(
			$subscription_id,
			(int)$this->config->get('config_subscription_suspended_status_id'),
			trim($comment)
		);
		$this->model_checkout_subscription->addLog($subscription_id, 'payment', 'Payment failed with status: ' . $payment_status, false);

		$notify = (bool)$this->config->get('payment_squareup_notify_recurring_fail');
		$order_status_id = (int)$this->config->get('payment_squareup_status_failed');
		$this->model_checkout_order->addHistory($order_id, $order_status_id, trim($comment), $notify);
	}

	private function paymentStatusToOrderStatus(string $payment_status): int {
		return match ($payment_status) {
			'APPROVED'  => (int)$this->config->get('payment_squareup_status_authorized'),
			'COMPLETED' => (int)$this->config->get('payment_squareup_status_captured'),
			default     => (int)$this->config->get('config_order_status_id'),
		};
	}
}
