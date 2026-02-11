<?php
/**
 * @package    LookerSolution\SquarePayment
 * @author     Ali Ahmed <ali@lookersolution.com>
 * @copyright  Copyright (c) 2026 LookerSolution (https://www.lookersolution.com)
 * @license    https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link       https://github.com/LookerSolution/oc4-squareup-payment
 */
namespace Opencart\Admin\Model\Extension\Lookersolution\Payment;

class Squareup extends \Opencart\System\Engine\Model {
	public function getPayment(int $squareup_payment_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "squareup_payment` WHERE `squareup_payment_id` = '" . (int)$squareup_payment_id . "'");

		return $query->row;
	}

	public function getPayments(array $data = []): array {
		$sql = "SELECT * FROM `" . DB_PREFIX . "squareup_payment`";

		if (!empty($data['order_id'])) {
			$sql .= " WHERE `opencart_order_id` = '" . (int)$data['order_id'] . "'";
		}

		$sql .= " ORDER BY `created_at` DESC";

		if (isset($data['start']) && isset($data['limit'])) {
			$sql .= " LIMIT " . (int)$data['start'] . ", " . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTotalPayments(array $data = []): int {
		$sql = "SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "squareup_payment`";

		if (!empty($data['order_id'])) {
			$sql .= " WHERE `opencart_order_id` = '" . (int)$data['order_id'] . "'";
		}

		$query = $this->db->query($sql);

		return (int)$query->row['total'];
	}

	public function updatePaymentRefund(int $squareup_payment_id, string $updated_at, int $amount): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "squareup_payment` SET `refunded_amount` = '" . (int)$amount . "', `refunded_currency` = `currency`, `updated_at` = '" . $this->db->escape($updated_at) . "' WHERE `squareup_payment_id` = '" . (int)$squareup_payment_id . "'");
	}

	public function updatePaymentStatus(int $squareup_payment_id, string $status, string $updated_at): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "squareup_payment` SET `status` = '" . $this->db->escape($status) . "', `updated_at` = '" . $this->db->escape($updated_at) . "' WHERE `squareup_payment_id` = '" . (int)$squareup_payment_id . "'");
	}

	public function updatePayment(int $squareup_payment_id, array $payment): void {
		$p = $payment['payment'];

		$refunded_amount = 0;
		$refunded_currency = $p['amount_money']['currency'];

		if (isset($p['refunded_money']['amount'])) {
			$refunded_amount = (int)$p['refunded_money']['amount'];
		}

		if (isset($p['refunded_money']['currency'])) {
			$refunded_currency = $p['refunded_money']['currency'];
		}

		$card_fingerprint = $p['card_details']['card']['fingerprint'] ?? '';
		$billing = $p['billing_address'] ?? [];

		$this->db->query("UPDATE `" . DB_PREFIX . "squareup_payment` SET
			`payment_id` = '" . $this->db->escape($p['id']) . "',
			`location_id` = '" . $this->db->escape($p['location_id']) . "',
			`order_id` = '" . $this->db->escape($p['order_id']) . "',
			`customer_id` = '" . $this->db->escape($p['customer_id'] ?? '') . "',
			`created_at` = '" . $this->db->escape($p['created_at']) . "',
			`updated_at` = '" . $this->db->escape($p['updated_at']) . "',
			`amount` = '" . (int)$p['amount_money']['amount'] . "',
			`currency` = '" . $this->db->escape($p['amount_money']['currency']) . "',
			`status` = '" . $this->db->escape($p['status']) . "',
			`source_type` = '" . $this->db->escape($p['source_type']) . "',
			`square_product` = '" . $this->db->escape($p['application_details']['square_product'] ?? '') . "',
			`application_id` = '" . $this->db->escape($p['application_details']['application_id'] ?? '') . "',
			`refunded_amount` = '" . (int)$refunded_amount . "',
			`refunded_currency` = '" . $this->db->escape($refunded_currency) . "',
			`card_fingerprint` = '" . $this->db->escape($card_fingerprint) . "',
			`first_name` = '" . $this->db->escape($billing['first_name'] ?? '') . "',
			`last_name` = '" . $this->db->escape($billing['last_name'] ?? '') . "',
			`address_line_1` = '" . $this->db->escape($billing['address_line_1'] ?? '') . "',
			`address_line_2` = '" . $this->db->escape($billing['address_line_2'] ?? '') . "',
			`address_line_3` = '" . $this->db->escape($billing['address_line_3'] ?? '') . "',
			`locality` = '" . $this->db->escape($billing['locality'] ?? '') . "',
			`sublocality` = '" . $this->db->escape($billing['sublocality'] ?? '') . "',
			`sublocality_2` = '" . $this->db->escape($billing['sublocality_2'] ?? '') . "',
			`sublocality_3` = '" . $this->db->escape($billing['sublocality_3'] ?? '') . "',
			`administrative_district_level_1` = '" . $this->db->escape($billing['administrative_district_level_1'] ?? '') . "',
			`administrative_district_level_2` = '" . $this->db->escape($billing['administrative_district_level_2'] ?? '') . "',
			`administrative_district_level_3` = '" . $this->db->escape($billing['administrative_district_level_3'] ?? '') . "',
			`postal_code` = '" . $this->db->escape($billing['postal_code'] ?? '') . "',
			`country` = '" . $this->db->escape($billing['country'] ?? 'ZZ') . "'
			WHERE `squareup_payment_id` = '" . (int)$squareup_payment_id . "'");
	}

	public function getOrderStatusId(int $order_id, ?string $payment_status = null): int {
		if ($payment_status) {
			return (int)$this->config->get('payment_squareup_status_' . strtolower($payment_status));
		}

		$this->load->model('sale/order');

		$order_info = $this->model_sale_order->getOrder($order_id);

		return (int)($order_info['order_status_id'] ?? 0);
	}

	public function editSubscriptionStatus(int $subscription_id, int $subscription_status_id): void {
		$this->load->model('sale/subscription');

		$this->model_sale_subscription->addHistory($subscription_id, $subscription_status_id, '', false);
	}

	public function inferOrderStatusId(string $search): int {
		$query = $this->db->query("SELECT `language_id` FROM `" . DB_PREFIX . "language` WHERE `code` LIKE 'en-%' LIMIT 1");

		if (empty($query->row['language_id'])) {
			return 0;
		}

		$language_id = (int)$query->row['language_id'];

		$status_query = $this->db->query("SELECT `order_status_id` FROM `" . DB_PREFIX . "order_status` WHERE LOWER(`name`) LIKE '" . $this->db->escape(strtolower($search)) . "%' AND `language_id` = '" . $language_id . "' ORDER BY LENGTH(`name`) ASC LIMIT 1");

		if (!empty($status_query->row['order_status_id'])) {
			return (int)$status_query->row['order_status_id'];
		}

		return 0;
	}

	public function getWebhookEvents(array $data = []): array {
		$sql = "SELECT * FROM `" . DB_PREFIX . "squareup_webhook_event` ORDER BY `created_at` DESC";

		if (isset($data['start']) && isset($data['limit'])) {
			$sql .= " LIMIT " . (int)$data['start'] . ", " . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTotalWebhookEvents(): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "squareup_webhook_event`");

		return (int)$query->row['total'];
	}

	public function createTables(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "squareup_payment` (
			`squareup_payment_id` INT NOT NULL AUTO_INCREMENT,
			`opencart_order_id` INT NOT NULL,
			`payment_id` VARCHAR(192) NOT NULL,
			`merchant_id` VARCHAR(255) NOT NULL,
			`location_id` VARCHAR(50) NOT NULL,
			`order_id` VARCHAR(192) NOT NULL,
			`customer_id` VARCHAR(191) NOT NULL DEFAULT '',
			`created_at` VARCHAR(32) NOT NULL,
			`updated_at` VARCHAR(32) NOT NULL,
			`amount` BIGINT NOT NULL DEFAULT '0',
			`currency` CHAR(3) NOT NULL,
			`status` VARCHAR(50) NOT NULL,
			`source_type` VARCHAR(50) NOT NULL,
			`square_product` VARCHAR(16) NOT NULL DEFAULT '',
			`application_id` VARCHAR(255) NOT NULL DEFAULT '',
			`refunded_amount` BIGINT NOT NULL DEFAULT '0',
			`refunded_currency` CHAR(3) NOT NULL DEFAULT '',
			`card_fingerprint` VARCHAR(255) NOT NULL DEFAULT '',
			`first_name` VARCHAR(300) NOT NULL DEFAULT '',
			`last_name` VARCHAR(300) NOT NULL DEFAULT '',
			`address_line_1` VARCHAR(500) NOT NULL DEFAULT '',
			`address_line_2` VARCHAR(500) NOT NULL DEFAULT '',
			`address_line_3` VARCHAR(500) NOT NULL DEFAULT '',
			`locality` VARCHAR(300) NOT NULL DEFAULT '',
			`sublocality` VARCHAR(300) NOT NULL DEFAULT '',
			`sublocality_2` VARCHAR(300) NOT NULL DEFAULT '',
			`sublocality_3` VARCHAR(300) NOT NULL DEFAULT '',
			`administrative_district_level_1` VARCHAR(200) NOT NULL DEFAULT '',
			`administrative_district_level_2` VARCHAR(200) NOT NULL DEFAULT '',
			`administrative_district_level_3` VARCHAR(200) NOT NULL DEFAULT '',
			`postal_code` VARCHAR(12) NOT NULL DEFAULT '',
			`country` CHAR(2) NOT NULL DEFAULT 'ZZ',
			`ip` VARCHAR(40) NOT NULL DEFAULT '',
			`user_agent` VARCHAR(255) NOT NULL DEFAULT '',
			PRIMARY KEY (`squareup_payment_id`),
			KEY `idx_opencart_order_id` (`opencart_order_id`),
			KEY `idx_payment_id` (`payment_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "squareup_webhook_event` (
			`webhook_event_id` INT NOT NULL AUTO_INCREMENT,
			`event_id` VARCHAR(255) NOT NULL,
			`event_type` VARCHAR(100) NOT NULL,
			`merchant_id` VARCHAR(255) NOT NULL DEFAULT '',
			`payment_id` VARCHAR(192) NOT NULL DEFAULT '',
			`data` TEXT NOT NULL,
			`processed` TINYINT(1) NOT NULL DEFAULT '0',
			`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`processed_at` DATETIME DEFAULT NULL,
			PRIMARY KEY (`webhook_event_id`),
			UNIQUE KEY `idx_event_id` (`event_id`),
			KEY `idx_event_type` (`event_type`),
			KEY `idx_processed` (`processed`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	}

	public function dropTables(): void {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "squareup_payment`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "squareup_webhook_event`");
	}
}
