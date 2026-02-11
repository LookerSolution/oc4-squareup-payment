<?php
/**
 * @package    LookerSolution\SquarePayment
 * @author     Ali Ahmed <ali@lookersolution.com>
 * @copyright  Copyright (c) 2026 LookerSolution (https://www.lookersolution.com)
 * @license    https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link       https://github.com/LookerSolution/oc4-squareup-payment
 */
namespace Opencart\Catalog\Model\Extension\Lookersolution\Payment;

use Opencart\System\Library\Extension\Lookersolution\Squareup\Exception as SquareupException;

class Squareup extends \Opencart\System\Engine\Model {
	public function getMethods(array $address = []): array {
		$this->load->language('extension/lookersolution/payment/squareup');

		$squareup_display_name = $this->config->get('payment_squareup_display_name');

		if (!empty($squareup_display_name[$this->config->get('config_language_id')])) {
			$title = $squareup_display_name[$this->config->get('config_language_id')];
		} else {
			$title = $this->language->get('text_default_squareup_name');
		}

		$status = true;

		$minimum_total = (float)$this->config->get('payment_squareup_total');

		if ($minimum_total > 0 && $minimum_total > $this->cart->getTotal()) {
			$status = false;
		} elseif (!$this->config->get('payment_squareup_geo_zone_id')) {
			$status = true;
		} elseif (!empty($address['country_id'])) {
			$this->load->model('localisation/geo_zone');

			$results = $this->model_localisation_geo_zone->getGeoZone(
				(int)$this->config->get('payment_squareup_geo_zone_id'),
				(int)$address['country_id'],
				(int)($address['zone_id'] ?? 0)
			);

			if (!$results) {
				$status = false;
			}
		}

		if ($status && $this->cart->hasSubscription()) {
			if ($this->config->get('payment_squareup_quick_pay')) {
				$status = false;
			} elseif ($this->config->get('payment_squareup_delay_capture')) {
				$status = false;
			}
		}

		$method_data = [];

		if ($status) {
			$option_data['squareup'] = [
				'code' => 'squareup.squareup',
				'name' => $title,
			];

			$method_data = [
				'code'       => 'squareup',
				'name'       => $title,
				'option'     => $option_data,
				'sort_order' => (int)$this->config->get('payment_squareup_sort_order'),
			];
		}

		return $method_data;
	}

	public function addPayment(array $payment, string $merchant_id, int $order_id, string $user_agent, string $ip): void {
		$card_fingerprint = $payment['payment']['card_details']['card']['fingerprint'] ?? '';
		$billing = $payment['payment']['billing_address'] ?? [];

		$sql = "INSERT INTO `" . DB_PREFIX . "squareup_payment` SET ";
		$sql .= "`opencart_order_id` = '" . (int)$order_id . "', ";
		$sql .= "`payment_id` = '" . $this->db->escape($payment['payment']['id']) . "', ";
		$sql .= "`merchant_id` = '" . $this->db->escape($merchant_id) . "', ";
		$sql .= "`location_id` = '" . $this->db->escape($payment['payment']['location_id']) . "', ";
		$sql .= "`order_id` = '" . $this->db->escape($payment['payment']['order_id']) . "', ";
		$sql .= "`customer_id` = '" . $this->db->escape($payment['payment']['customer_id'] ?? '') . "', ";
		$sql .= "`created_at` = '" . $this->db->escape($payment['payment']['created_at']) . "', ";
		$sql .= "`updated_at` = '" . $this->db->escape($payment['payment']['updated_at']) . "', ";
		$sql .= "`amount` = '" . (int)$payment['payment']['amount_money']['amount'] . "', ";
		$sql .= "`currency` = '" . $this->db->escape($payment['payment']['amount_money']['currency']) . "', ";
		$sql .= "`status` = '" . $this->db->escape($payment['payment']['status']) . "', ";
		$sql .= "`source_type` = '" . $this->db->escape($payment['payment']['source_type']) . "', ";
		$sql .= "`square_product` = '" . $this->db->escape($payment['payment']['application_details']['square_product'] ?? '') . "', ";
		$sql .= "`application_id` = '" . $this->db->escape($payment['payment']['application_details']['application_id'] ?? '') . "', ";
		$sql .= "`refunded_amount` = '0', ";
		$sql .= "`refunded_currency` = '', ";
		$sql .= "`card_fingerprint` = '" . $this->db->escape($card_fingerprint) . "', ";

		if ($billing) {
			$sql .= "`first_name` = '" . $this->db->escape($billing['first_name'] ?? '') . "', ";
			$sql .= "`last_name` = '" . $this->db->escape($billing['last_name'] ?? '') . "', ";
			$sql .= "`address_line_1` = '" . $this->db->escape($billing['address_line_1'] ?? '') . "', ";
			$sql .= "`address_line_2` = '" . $this->db->escape($billing['address_line_2'] ?? '') . "', ";
			$sql .= "`address_line_3` = '" . $this->db->escape($billing['address_line_3'] ?? '') . "', ";
			$sql .= "`locality` = '" . $this->db->escape($billing['locality'] ?? '') . "', ";
			$sql .= "`sublocality` = '" . $this->db->escape($billing['sublocality'] ?? '') . "', ";
			$sql .= "`sublocality_2` = '" . $this->db->escape($billing['sublocality_2'] ?? '') . "', ";
			$sql .= "`sublocality_3` = '" . $this->db->escape($billing['sublocality_3'] ?? '') . "', ";
			$sql .= "`administrative_district_level_1` = '" . $this->db->escape($billing['administrative_district_level_1'] ?? '') . "', ";
			$sql .= "`administrative_district_level_2` = '" . $this->db->escape($billing['administrative_district_level_2'] ?? '') . "', ";
			$sql .= "`administrative_district_level_3` = '" . $this->db->escape($billing['administrative_district_level_3'] ?? '') . "', ";
			$sql .= "`postal_code` = '" . $this->db->escape($billing['postal_code'] ?? '') . "', ";
			$sql .= "`country` = '" . $this->db->escape($billing['country'] ?? 'ZZ') . "', ";
		}

		$sql .= "`ip` = '" . $this->db->escape($ip) . "', ";
		$sql .= "`user_agent` = '" . $this->db->escape($user_agent) . "'";

		$this->db->query($sql);
	}

	public function updatePaymentCustomerId(string $payment_id, string $customer_id): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "squareup_payment` SET `customer_id` = '" . $this->db->escape($customer_id) . "' WHERE `payment_id` = '" . $this->db->escape($payment_id) . "'");
	}

	public function getBillingAddress(array $order_info): array {
		$this->load->model('localisation/country');

		$billing_country_info = $this->model_localisation_country->getCountry($order_info['payment_country_id']);

		if (empty($billing_country_info)) {
			throw new SquareupException($this->registry, $this->language->get('error_missing_billing_address'));
		}

		return [
			'first_name'                      => $order_info['payment_firstname'],
			'last_name'                       => $order_info['payment_lastname'],
			'address_line_1'                  => $order_info['payment_address_1'],
			'address_line_2'                  => $order_info['payment_address_2'],
			'address_line_3'                  => '',
			'locality'                        => $order_info['payment_city'],
			'sublocality'                     => '',
			'sublocality_2'                   => '',
			'sublocality_3'                   => '',
			'administrative_district_level_1' => $order_info['payment_zone'],
			'administrative_district_level_2' => '',
			'administrative_district_level_3' => '',
			'postal_code'                     => $order_info['payment_postcode'],
			'country'                         => $billing_country_info['iso_code_2'],
			'organization'                    => $order_info['payment_company'],
		];
	}

	public function getAmountAndCurrency(float $order_amount): array {
		$currency = $this->config->get('config_currency');
		$amount = $order_amount;

		$squareup = new \Opencart\System\Library\Extension\Lookersolution\Squareup($this->registry);
		$token_manager = $squareup->getTokenManager();

		try {
			$location = $squareup->retrieveLocation($token_manager->getAccessToken(), $token_manager->getLocationId());
		} catch (SquareupException $e) {
			$location = null;
		}

		if (isset($location['currency'])) {
			$this->load->model('localisation/currency');

			$available_currencies = $this->model_localisation_currency->getCurrencies();

			foreach ($available_currencies as $available_currency) {
				if ($available_currency['code'] === $location['currency']) {
					$amount = $this->currency->convert($order_amount, $currency, $location['currency']);
					$currency = $location['currency'];
					break;
				}
			}
		}

		return [$amount, $currency];
	}

	public function tokenExpiredEmail(): void {
		if (!$this->mailResendPeriodExpired('token_expired')) {
			return;
		}

		$this->sendAdminMail(
			$this->language->get('text_token_expired_subject'),
			$this->language->get('text_token_expired_message')
		);
	}

	public function tokenRevokedEmail(): void {
		if (!$this->mailResendPeriodExpired('token_revoked')) {
			return;
		}

		$this->sendAdminMail(
			$this->language->get('text_token_revoked_subject'),
			$this->language->get('text_token_revoked_message')
		);
	}

	public function cronEmail(array $result): void {
		$br = '<br />';

		$subject = $this->language->get('text_cron_subject');
		$message = $this->language->get('text_cron_message') . $br . $br;
		$message .= '<strong>' . $this->language->get('text_cron_summary_token_heading') . '</strong>' . $br;

		if ($result['token_update_error']) {
			$message .= $result['token_update_error'] . $br . $br;
		} else {
			$message .= $this->language->get('text_cron_summary_token_updated') . $br . $br;
		}

		if (!empty($result['transaction_error'])) {
			$message .= '<strong>' . $this->language->get('text_cron_summary_error_heading') . '</strong>' . $br;
			$message .= implode($br, $result['transaction_error']) . $br . $br;
		}

		if (!empty($result['transaction_fail'])) {
			$message .= '<strong>' . $this->language->get('text_cron_summary_fail_heading') . '</strong>' . $br;

			foreach ($result['transaction_fail'] as $subscription_id => $amount) {
				$message .= sprintf($this->language->get('text_cron_fail_charge'), $subscription_id, $amount) . $br;
			}
		}

		if (!empty($result['transaction_success'])) {
			$message .= '<strong>' . $this->language->get('text_cron_summary_success_heading') . '</strong>' . $br;

			foreach ($result['transaction_success'] as $subscription_id => $amount) {
				$message .= sprintf($this->language->get('text_cron_success_charge'), $subscription_id, $amount) . $br;
			}
		}

		$mail = new \Opencart\System\Library\Mail($this->config->get('config_mail_engine'));
		$mail->parameter = $this->config->get('config_mail_parameter');
		$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
		$mail->smtp_username = $this->config->get('config_mail_smtp_username');
		$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		$mail->smtp_port = $this->config->get('config_mail_smtp_port');
		$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
		$mail->setTo($this->config->get('payment_squareup_cron_email'));
		$mail->setFrom($this->config->get('config_email'));
		$mail->setReplyTo($this->config->get('config_email'));
		$mail->setSender($this->config->get('config_name'));
		$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
		$mail->setText(strip_tags($message));
		$mail->setHtml($message);
		$mail->send();
	}

	public function editTokenSetting(array $settings): void {
		foreach ($settings as $key => $value) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'payment_squareup' AND `key` = '" . $this->db->escape($key) . "'");
			$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `code` = 'payment_squareup', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "', `serialized` = '0', `store_id` = '0'");
		}
	}

	private function sendAdminMail(string $subject, string $message): void {
		$mail = new \Opencart\System\Library\Mail($this->config->get('config_mail_engine'));
		$mail->parameter = $this->config->get('config_mail_parameter');
		$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
		$mail->smtp_username = $this->config->get('config_mail_smtp_username');
		$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		$mail->smtp_port = $this->config->get('config_mail_smtp_port');
		$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
		$mail->setTo($this->config->get('config_email'));
		$mail->setFrom($this->config->get('config_email'));
		$mail->setReplyTo($this->config->get('config_email'));
		$mail->setSender($this->config->get('config_name'));
		$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
		$mail->setText(strip_tags($message));
		$mail->setHtml($message);
		$mail->send();
	}

	private function mailResendPeriodExpired(string $key): bool {
		$result = (int)$this->cache->get('squareup.' . $key);

		if (!$result) {
			$this->cache->set('squareup.' . $key, time());
		} else {
			$delta = time() - $result;

			if ($delta >= 15 * 60) {
				$this->cache->set('squareup.' . $key, time());
			} else {
				return false;
			}
		}

		return true;
	}
}
