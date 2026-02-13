<?php
/**
 * @package    LookerSolution\SquarePayment
 * @author     Ali Ahmed <ali_a@lookersolution.com>
 * @copyright  Copyright (c) 2026 LookerSolution (https://www.lookersolution.com)
 * @license    https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link       https://github.com/LookerSolution/oc4-squareup-payment
 */
namespace Opencart\System\Library\Extension\Lookersolution\Squareup;

class WebhookHandler {
	private object $db;
	private object $config;
	private object $log;

	public function __construct(object $registry) {
		$this->db = $registry->get('db');
		$this->config = $registry->get('config');
		$this->log = $registry->get('log');
	}

	public function validateSignature(string $body, string $signature, string $notification_url): bool {
		$signature_key = $this->config->get('payment_squareup_webhook_signature_key');

		if (!$signature_key || !$signature) {
			return false;
		}

		$expected = base64_encode(hash_hmac('sha256', $notification_url . $body, $signature_key, true));

		return hash_equals($expected, $signature);
	}

	public function isProcessed(string $event_id): bool {
		$query = $this->db->query("SELECT `processed` FROM `" . DB_PREFIX . "squareup_webhook_event` WHERE `event_id` = '" . $this->db->escape($event_id) . "'");

		return !empty($query->row['processed']);
	}

	public function storeEvent(string $event_id, string $event_type, string $merchant_id, string $payment_id, string $data): int {
		$this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "squareup_webhook_event` SET
			`event_id` = '" . $this->db->escape($event_id) . "',
			`event_type` = '" . $this->db->escape($event_type) . "',
			`merchant_id` = '" . $this->db->escape($merchant_id) . "',
			`payment_id` = '" . $this->db->escape($payment_id) . "',
			`data` = '" . $this->db->escape($data) . "',
			`processed` = '0',
			`created_at` = NOW()");

		return $this->db->getLastId();
	}

	public function markProcessed(string $event_id): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "squareup_webhook_event` SET `processed` = '1', `processed_at` = NOW() WHERE `event_id` = '" . $this->db->escape($event_id) . "'");
	}

	public function processEvent(string $event_type, array $object_data): void {
		match ($event_type) {
			'payment.created' => $this->processPaymentCreated($object_data['payment'] ?? []),
			'payment.updated' => $this->processPaymentUpdated($object_data['payment'] ?? []),
			'refund.created'  => $this->processRefundCreated($object_data['refund'] ?? []),
			'refund.updated'  => $this->processRefundUpdated($object_data['refund'] ?? []),
			default           => $this->debug('Webhook: Unhandled event type ' . $event_type),
		};
	}

	private function processPaymentCreated(array $payment_data): void {
		$payment_id = $payment_data['id'] ?? '';
		$status = $payment_data['status'] ?? '';

		if (!$payment_id || !$status) {
			return;
		}

		$query = $this->db->query("SELECT `squareup_payment_id` FROM `" . DB_PREFIX . "squareup_payment` WHERE `payment_id` = '" . $this->db->escape($payment_id) . "'");

		if ($query->num_rows) {
			return;
		}

		$this->debug('Webhook: Payment created ' . $payment_id . ' with status ' . $status);
	}

	private function processPaymentUpdated(array $payment_data): void {
		$payment_id = $payment_data['id'] ?? '';
		$new_status = $payment_data['status'] ?? '';

		if (!$payment_id || !$new_status) {
			return;
		}

		$query = $this->db->query("SELECT `squareup_payment_id`, `opencart_order_id`, `status` FROM `" . DB_PREFIX . "squareup_payment` WHERE `payment_id` = '" . $this->db->escape($payment_id) . "'");

		if (!$query->num_rows) {
			return;
		}

		$current_status = $query->row['status'];

		if ($current_status === $new_status) {
			return;
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "squareup_payment` SET
			`status` = '" . $this->db->escape($new_status) . "',
			`updated_at` = '" . $this->db->escape($payment_data['updated_at'] ?? date('c')) . "'
			WHERE `payment_id` = '" . $this->db->escape($payment_id) . "'");

		$order_id = (int)$query->row['opencart_order_id'];
		$order_status_id = $this->mapPaymentStatusToOrderStatus($new_status);

		if ($order_id && $order_status_id) {
			$this->addOrderHistory($order_id, $order_status_id, 'Payment status updated via Square webhook: ' . $new_status);
		}

		$this->debug('Webhook: Payment ' . $payment_id . ' status updated from ' . $current_status . ' to ' . $new_status);
	}

	private function processRefundCreated(array $refund_data): void {
		$payment_id = $refund_data['payment_id'] ?? '';
		$refund_amount = (int)($refund_data['amount_money']['amount'] ?? 0);
		$refund_currency = $refund_data['amount_money']['currency'] ?? '';
		$refund_status = $refund_data['status'] ?? '';
		$refund_reason = $refund_data['reason'] ?? '';

		if (!$payment_id || !$refund_amount) {
			return;
		}

		$query = $this->db->query("SELECT `squareup_payment_id`, `opencart_order_id`, `refunded_amount`, `currency` FROM `" . DB_PREFIX . "squareup_payment` WHERE `payment_id` = '" . $this->db->escape($payment_id) . "'");

		if (!$query->num_rows) {
			return;
		}

		$new_refunded = (int)$query->row['refunded_amount'] + $refund_amount;
		$currency = $refund_currency ?: $query->row['currency'];

		$this->db->query("UPDATE `" . DB_PREFIX . "squareup_payment` SET
			`refunded_amount` = '" . (int)$new_refunded . "',
			`refunded_currency` = '" . $this->db->escape($currency) . "'
			WHERE `payment_id` = '" . $this->db->escape($payment_id) . "'");

		$order_id = (int)$query->row['opencart_order_id'];

		if ($order_id) {
			$current_order_status_id = $this->getOrderStatusId($order_id);
			$comment = 'Refund received via Square webhook. Status: ' . $refund_status;

			if ($refund_reason) {
				$comment .= '. Reason: ' . $refund_reason;
			}

			$this->addOrderHistory($order_id, $current_order_status_id, $comment);
		}

		$this->debug('Webhook: Refund created for payment ' . $payment_id . ' amount ' . $refund_amount . ' ' . $currency);
	}

	private function processRefundUpdated(array $refund_data): void {
		$payment_id = $refund_data['payment_id'] ?? '';
		$refund_status = $refund_data['status'] ?? '';

		if (!$payment_id || !$refund_status) {
			return;
		}

		$query = $this->db->query("SELECT `opencart_order_id` FROM `" . DB_PREFIX . "squareup_payment` WHERE `payment_id` = '" . $this->db->escape($payment_id) . "'");

		if (!$query->num_rows) {
			return;
		}

		$order_id = (int)$query->row['opencart_order_id'];

		if ($order_id && in_array($refund_status, ['COMPLETED', 'FAILED', 'REJECTED'])) {
			$current_order_status_id = $this->getOrderStatusId($order_id);

			$this->addOrderHistory($order_id, $current_order_status_id, 'Refund status updated via Square webhook: ' . $refund_status);
		}

		$this->debug('Webhook: Refund updated for payment ' . $payment_id . ' status ' . $refund_status);
	}

	private function mapPaymentStatusToOrderStatus(string $status): int {
		return match ($status) {
			'APPROVED'  => (int)$this->config->get('payment_squareup_status_authorized'),
			'PENDING'   => $this->config->get('payment_squareup_delay_capture')
				? (int)$this->config->get('payment_squareup_status_authorized')
				: (int)$this->config->get('payment_squareup_status_captured'),
			'COMPLETED' => (int)$this->config->get('payment_squareup_status_captured'),
			'CANCELED'  => (int)$this->config->get('payment_squareup_status_voided'),
			'FAILED'    => (int)$this->config->get('payment_squareup_status_failed'),
			default     => 0,
		};
	}

	private function addOrderHistory(int $order_id, int $order_status_id, string $comment): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "order_history` SET
			`order_id` = '" . (int)$order_id . "',
			`order_status_id` = '" . (int)$order_status_id . "',
			`comment` = '" . $this->db->escape($comment) . "',
			`notify` = '0',
			`date_added` = NOW()");

		$this->db->query("UPDATE `" . DB_PREFIX . "order` SET
			`order_status_id` = '" . (int)$order_status_id . "',
			`date_modified` = NOW()
			WHERE `order_id` = '" . (int)$order_id . "'");
	}

	private function getOrderStatusId(int $order_id): int {
		$query = $this->db->query("SELECT `order_status_id` FROM `" . DB_PREFIX . "order` WHERE `order_id` = '" . (int)$order_id . "'");

		return (int)($query->row['order_status_id'] ?? 0);
	}

	private function debug(string $text): void {
		if ($this->config->get('payment_squareup_debug')) {
			$this->log->write($text);
		}
	}
}
