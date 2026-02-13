<?php
/**
 * @package    LookerSolution\SquarePayment
 * @author     Ali Ahmed <ali_a@lookersolution.com>
 * @copyright  Copyright (c) 2026 LookerSolution (https://www.lookersolution.com)
 * @license    https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link       https://github.com/LookerSolution/oc4-squareup-payment
 */
namespace Opencart\Admin\Controller\Extension\Lookersolution\Payment;

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

	public function index(): void {
		$this->load->language('extension/lookersolution/payment/squareup');
		$this->load->model('extension/lookersolution/payment/squareup');
		$this->load->model('setting/setting');

		$squareup = $this->getSquareup();

		$previous_setting = $this->model_setting_setting->getSetting('payment_squareup');

		$alerts = [];

		try {
			if ($this->config->get('payment_squareup_access_token')) {
				if (!$squareup->verifyToken($squareup->getTokenManager()->getAccessToken())) {
					unset($previous_setting['payment_squareup_merchant_id']);
					unset($previous_setting['payment_squareup_merchant_name']);
					unset($previous_setting['payment_squareup_access_token']);
					unset($previous_setting['payment_squareup_refresh_token']);
					unset($previous_setting['payment_squareup_access_token_expires']);
					unset($previous_setting['payment_squareup_locations']);
					unset($previous_setting['payment_squareup_sandbox_locations']);

					$this->config->set('payment_squareup_merchant_id', null);
				} else {
					if (!$this->config->get('payment_squareup_locations')) {
						$first_location_id = null;
						$previous_setting['payment_squareup_locations'] = $squareup->listLocations($this->config->get('payment_squareup_access_token'), $first_location_id);
						$previous_setting['payment_squareup_location_id'] = $first_location_id;
					}
				}
			}

			if (!$this->config->get('payment_squareup_sandbox_locations') && $this->config->get('payment_squareup_sandbox_token')) {
				$first_location_id = null;
				$previous_setting['payment_squareup_sandbox_locations'] = $squareup->listLocations($this->config->get('payment_squareup_sandbox_token'), $first_location_id);
				$previous_setting['payment_squareup_sandbox_location_id'] = $first_location_id;
			}

			$this->model_setting_setting->editSetting('payment_squareup', $previous_setting);
		} catch (SquareupException $e) {
			$alerts[] = ['type' => 'danger', 'text' => sprintf($this->language->get('text_location_error'), $e->getMessage())];
		} catch (\Exception $e) {
			$alerts[] = ['type' => 'danger', 'text' => sprintf($this->language->get('text_location_error'), $e->getMessage())];
		}

		$previous_config = new \Opencart\System\Engine\Config();
		foreach ($previous_setting as $key => $value) {
			$previous_config->set($key, $value);
		}

		if ($previous_config->get('payment_squareup_access_token') && $previous_config->get('payment_squareup_access_token_expires')) {
			$expiration_time = date_create_from_format('Y-m-d\TH:i:s\Z', $previous_config->get('payment_squareup_access_token_expires'));
			$now = date_create();

			if ($expiration_time) {
				$delta = $expiration_time->getTimestamp() - $now->getTimestamp();
				$expiration_date_formatted = $expiration_time->format('l, F jS, Y h:i:s A, e');

				if ($delta < 0) {
					$alerts[] = ['type' => 'danger', 'text' => sprintf($this->language->get('text_token_expired'), $this->url->link('extension/lookersolution/payment/squareup.refreshToken', 'user_token=' . $this->session->data['user_token']))];
				} elseif ($delta < (5 * 24 * 60 * 60)) {
					$alerts[] = ['type' => 'warning', 'text' => sprintf($this->language->get('text_token_expiry_warning'), $expiration_date_formatted, $this->url->link('extension/lookersolution/payment/squareup.refreshToken', 'user_token=' . $this->session->data['user_token']))];
				}

				$data['access_token_expires_time'] = $expiration_date_formatted;
			} else {
				$data['access_token_expires_time'] = $this->language->get('text_na');
			}
		} elseif ($previous_config->get('payment_squareup_client_id')) {
			$alerts[] = ['type' => 'danger', 'text' => sprintf($this->language->get('text_token_revoked'), $squareup->authLink($previous_config->get('payment_squareup_client_id')))];
			$data['access_token_expires_time'] = $this->language->get('text_na');
		} else {
			$data['access_token_expires_time'] = $this->language->get('text_na');
		}

		if ($previous_config->get('payment_squareup_client_id')) {
			$data['payment_squareup_auth_link'] = $squareup->authLink($previous_config->get('payment_squareup_client_id'));
		} else {
			$data['payment_squareup_auth_link'] = null;
		}

		if ($this->config->get('payment_squareup_enable_sandbox')) {
			$alerts[] = ['type' => 'warning', 'text' => $this->language->get('text_sandbox_enabled')];
		}

		if (isset($this->session->data['success'])) {
			$alerts[] = ['type' => 'success', 'text' => $this->session->data['success']];
			unset($this->session->data['success']);
		}

		if (isset($this->session->data['payment_squareup_alerts'])) {
			$alerts = array_merge($alerts, $this->session->data['payment_squareup_alerts']);
			unset($this->session->data['payment_squareup_alerts']);
		}

		$this->document->setTitle($this->language->get('heading_title'));

		$data['alerts'] = $alerts;

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = ['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])];
		$data['breadcrumbs'][] = ['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment')];
		$data['breadcrumbs'][] = ['text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/lookersolution/payment/squareup', 'user_token=' . $this->session->data['user_token'])];

		$data['save'] = $this->url->link('extension/lookersolution/payment/squareup.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment');
		$data['url_list_payments'] = html_entity_decode($this->url->link('extension/lookersolution/payment/squareup.payments', 'user_token=' . $this->session->data['user_token'] . '&page={PAGE}'));
		$data['payment_squareup_redirect_uri'] = str_replace('&amp;', '&', $this->url->link('extension/lookersolution/payment/squareup.oauthCallback', '', true));
		$data['payment_squareup_refresh_link'] = $this->url->link('extension/lookersolution/payment/squareup.refreshToken', 'user_token=' . $this->session->data['user_token']);
		$data['webhook_url'] = str_replace('&amp;', '&', HTTP_CATALOG . 'index.php?route=extension/lookersolution/webhook/squareup');
		$data['url_list_webhook_events'] = html_entity_decode($this->url->link('extension/lookersolution/payment/squareup.webhookEvents', 'user_token=' . $this->session->data['user_token'] . '&page={PAGE}'));
		$data['url_register_apple_pay_domain'] = $this->url->link('extension/lookersolution/payment/squareup.registerApplePayDomain', 'user_token=' . $this->session->data['user_token']);

		$default_csp  = "default-src 'self';\n";
		$default_csp .= "script-src 'self' https://js.squareup.com https://js.squareupsandbox.com https://web.squarecdn.com https://sandbox.web.squarecdn.com 'unsafe-inline' 'unsafe-eval';\n";
		$default_csp .= "style-src 'self' https://js.squareup.com https://js.squareupsandbox.com https://web.squarecdn.com https://sandbox.web.squarecdn.com https://fonts.googleapis.com 'unsafe-inline';\n";
		$default_csp .= "font-src 'self' https://fonts.gstatic.com https://square-fonts-production-f.squarecdn.com https://d1g145x70srn7h.cloudfront.net;\n";
		$default_csp .= "img-src 'self' data: https://js.squareup.com https://js.squareupsandbox.com https://web.squarecdn.com https://sandbox.web.squarecdn.com;\n";
		$default_csp .= "frame-src 'self' https://js.squareup.com https://js.squareupsandbox.com https://web.squarecdn.com https://sandbox.web.squarecdn.com https://connect.squareup.com https://connect.squareupsandbox.com https://api.squareupsandbox.com https://api.squareup.com;\n";
		$default_csp .= "connect-src 'self' https://connect.squareup.com https://connect.squareupsandbox.com https://pci-connect.squareup.com https://pci-connect.squareupsandbox.com;\n";
		$default_csp .= "base-uri 'self';\n";
		$default_csp .= "form-action 'self' https://api.squareupsandbox.com https://api.squareup.com;";

		$data['payment_square_default_csp'] = $default_csp;

		$settings_keys = [
			'payment_squareup_status'                    => 0,
			'payment_squareup_status_authorized'         => $this->model_extension_lookersolution_payment_squareup->inferOrderStatusId('processing'),
			'payment_squareup_status_captured'           => $this->model_extension_lookersolution_payment_squareup->inferOrderStatusId('processed'),
			'payment_squareup_status_voided'             => $this->model_extension_lookersolution_payment_squareup->inferOrderStatusId('void'),
			'payment_squareup_status_failed'             => $this->model_extension_lookersolution_payment_squareup->inferOrderStatusId('fail'),
			'payment_squareup_display_name'              => null,
			'payment_squareup_client_id'                 => '',
			'payment_squareup_client_secret'             => '',
			'payment_squareup_enable_sandbox'            => 0,
			'payment_squareup_debug'                     => 0,
			'payment_squareup_sort_order'                => 0,
			'payment_squareup_total'                     => '',
			'payment_squareup_geo_zone_id'               => 0,
			'payment_squareup_sandbox_client_id'         => '',
			'payment_squareup_sandbox_token'             => '',
			'payment_squareup_locations'                 => $previous_config->get('payment_squareup_locations'),
			'payment_squareup_location_id'               => '',
			'payment_squareup_sandbox_locations'          => $previous_config->get('payment_squareup_sandbox_locations'),
			'payment_squareup_sandbox_location_id'       => '',
			'payment_squareup_quick_pay'                 => 1,
			'payment_squareup_delay_capture'             => 0,
			'payment_squareup_content_security'          => $default_csp,
			'payment_squareup_recurring_status'          => 0,
			'payment_squareup_cron_email_status'         => 0,
			'payment_squareup_cron_email'                => $this->config->get('config_email'),
			'payment_squareup_cron_token'                => '',
			'payment_squareup_cron_acknowledge'          => 0,
			'payment_squareup_notify_recurring_success'  => 0,
			'payment_squareup_notify_recurring_fail'     => 0,
			'payment_squareup_merchant_id'               => $previous_config->get('payment_squareup_merchant_id'),
			'payment_squareup_merchant_name'             => $previous_config->get('payment_squareup_merchant_name'),
			'payment_squareup_apple_pay'                 => 0,
			'payment_squareup_google_pay'                => 0,
			'payment_squareup_cashapp_pay'               => 0,
			'payment_squareup_afterpay'                  => 0,
			'payment_squareup_ach'                       => 0,
			'payment_squareup_webhook_signature_key'     => '',
		];

		foreach ($settings_keys as $key => $default) {
			$data[$key] = $this->config->get($key) ?? $default;
		}

		if (!$this->config->get('payment_squareup_cron_token')) {
			$data['payment_squareup_cron_token'] = bin2hex(random_bytes(16));
		}

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$this->load->model('localisation/geo_zone');
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/lookersolution/payment/squareup', $data));
	}

	public function save(): void {
		$this->load->language('extension/lookersolution/payment/squareup');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/lookersolution/payment/squareup')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$errors = $this->validate();

			if ($errors) {
				$json['error'] = implode('<br>', $errors);
			}
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$allowed_keys = [
				'payment_squareup_status',
				'payment_squareup_status_authorized',
				'payment_squareup_status_captured',
				'payment_squareup_status_voided',
				'payment_squareup_status_failed',
				'payment_squareup_display_name',
				'payment_squareup_client_id',
				'payment_squareup_client_secret',
				'payment_squareup_enable_sandbox',
				'payment_squareup_debug',
				'payment_squareup_sort_order',
				'payment_squareup_total',
				'payment_squareup_geo_zone_id',
				'payment_squareup_sandbox_client_id',
				'payment_squareup_sandbox_token',
				'payment_squareup_location_id',
				'payment_squareup_sandbox_location_id',
				'payment_squareup_quick_pay',
				'payment_squareup_delay_capture',
				'payment_squareup_content_security',
				'payment_squareup_recurring_status',
				'payment_squareup_cron_email_status',
				'payment_squareup_cron_email',
				'payment_squareup_cron_token',
				'payment_squareup_cron_acknowledge',
				'payment_squareup_notify_recurring_success',
				'payment_squareup_notify_recurring_fail',
				'payment_squareup_apple_pay',
				'payment_squareup_google_pay',
				'payment_squareup_cashapp_pay',
				'payment_squareup_afterpay',
				'payment_squareup_ach',
				'payment_squareup_webhook_signature_key',
			];

			$existing = $this->model_setting_setting->getSetting('payment_squareup');
			$settings = $existing;

			foreach ($this->request->post as $key => $value) {
				if (in_array($key, $allowed_keys)) {
					$settings[$key] = $value;
				}
			}

			$location_id = $settings['payment_squareup_location_id'] ?? '';
			$locations = $settings['payment_squareup_locations'] ?? [];

			if ($location_id && is_array($locations)) {
				foreach ($locations as $loc) {
					if (($loc['id'] ?? '') === $location_id) {
						$settings['payment_squareup_location_currency'] = $loc['currency'] ?? '';
						break;
					}
				}
			}

			$sandbox_location_id = $settings['payment_squareup_sandbox_location_id'] ?? '';
			$sandbox_locations = $settings['payment_squareup_sandbox_locations'] ?? [];

			if ($sandbox_location_id && is_array($sandbox_locations)) {
				foreach ($sandbox_locations as $loc) {
					if (($loc['id'] ?? '') === $sandbox_location_id) {
						$settings['payment_squareup_sandbox_location_currency'] = $loc['currency'] ?? '';
						break;
					}
				}
			}

			$this->model_setting_setting->editSetting('payment_squareup', $settings);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function oauthCallback(): void {
		$this->load->language('extension/lookersolution/payment/squareup');

		if (!$this->user->hasPermission('modify', 'extension/lookersolution/payment/squareup')) {
			$this->session->data['payment_squareup_alerts'][] = ['type' => 'danger', 'text' => $this->language->get('error_permission')];
			$this->response->redirect($this->url->link('extension/lookersolution/payment/squareup', 'user_token=' . $this->session->data['user_token']));
			return;
		}

		$this->load->model('setting/setting');

		$squareup = $this->getSquareup();

		if (isset($this->request->get['error'])) {
			if ($this->request->get['error'] === 'access_denied') {
				$this->session->data['payment_squareup_alerts'][] = ['type' => 'warning', 'text' => $this->language->get('error_user_rejected_connect_attempt')];
			}
			$this->response->redirect($this->url->link('extension/lookersolution/payment/squareup', 'user_token=' . $this->session->data['user_token']));
			return;
		}

		if (!isset($this->request->get['state']) || !isset($this->request->get['code']) || !isset($this->request->get['response_type'])) {
			$this->session->data['payment_squareup_alerts'][] = ['type' => 'danger', 'text' => $this->language->get('error_possible_xss')];
			$this->response->redirect($this->url->link('extension/lookersolution/payment/squareup', 'user_token=' . $this->session->data['user_token']));
			return;
		}

		if (!isset($this->session->data['payment_squareup_oauth_state']) || $this->session->data['payment_squareup_oauth_state'] !== $this->request->get['state']) {
			$this->session->data['payment_squareup_alerts'][] = ['type' => 'danger', 'text' => $this->language->get('error_possible_xss')];
			$this->response->redirect($this->url->link('extension/lookersolution/payment/squareup', 'user_token=' . $this->session->data['user_token']));
			return;
		}

		try {
			$token = $squareup->exchangeCodeForAccessAndRefreshTokens($this->request->get['code']);

			$previous_setting = $this->model_setting_setting->getSetting('payment_squareup');

			$first_location_id = null;
			$previous_setting['payment_squareup_locations'] = $squareup->listLocations($token['access_token'], $first_location_id);

			if (empty($previous_setting['payment_squareup_location_id'])) {
				$previous_setting['payment_squareup_location_id'] = $first_location_id;
			} else {
				$location_ids = array_column($previous_setting['payment_squareup_locations'], 'id');
				if (!in_array($previous_setting['payment_squareup_location_id'], $location_ids)) {
					$previous_setting['payment_squareup_location_id'] = $first_location_id;
				}
			}

			$selected_loc_id = $previous_setting['payment_squareup_location_id'];
			foreach ($previous_setting['payment_squareup_locations'] as $loc) {
				if (($loc['id'] ?? '') === $selected_loc_id) {
					$previous_setting['payment_squareup_location_currency'] = $loc['currency'] ?? '';
					break;
				}
			}

			if ($this->config->get('payment_squareup_sandbox_token')) {
				$previous_setting['payment_squareup_sandbox_locations'] = $squareup->listLocations($this->config->get('payment_squareup_sandbox_token'), $first_location_id);
				$previous_setting['payment_squareup_sandbox_location_id'] = $first_location_id;
			}

			$previous_setting['payment_squareup_merchant_id'] = $token['merchant_id'];
			$previous_setting['payment_squareup_merchant_name'] = '';
			$previous_setting['payment_squareup_access_token'] = $token['access_token'];
			$previous_setting['payment_squareup_refresh_token'] = $token['refresh_token'];
			$previous_setting['payment_squareup_access_token_expires'] = $token['expires_at'];

			$squareup->getTokenManager()->encryptTokenSettings($previous_setting);

			$this->model_setting_setting->editSetting('payment_squareup', $previous_setting);

			unset($this->session->data['payment_squareup_oauth_state']);
			unset($this->session->data['payment_squareup_oauth_redirect']);

			$this->session->data['payment_squareup_alerts'][] = ['type' => 'success', 'text' => $this->language->get('text_refresh_access_token_success')];
		} catch (SquareupException $e) {
			$this->session->data['payment_squareup_alerts'][] = ['type' => 'danger', 'text' => sprintf($this->language->get('error_token'), $e->getMessage())];
		}

		$this->response->redirect($this->url->link('extension/lookersolution/payment/squareup', 'user_token=' . $this->session->data['user_token']));
	}

	public function refreshToken(): void {
		$this->load->language('extension/lookersolution/payment/squareup');

		if (!$this->user->hasPermission('modify', 'extension/lookersolution/payment/squareup')) {
			$this->session->data['payment_squareup_alerts'][] = ['type' => 'danger', 'text' => $this->language->get('error_permission')];
			$this->response->redirect($this->url->link('extension/lookersolution/payment/squareup', 'user_token=' . $this->session->data['user_token']));
			return;
		}

		$this->load->model('setting/setting');

		$squareup = $this->getSquareup();

		try {
			$response = $squareup->refreshToken();

			if (!isset($response['access_token']) || !isset($response['expires_at']) || !isset($response['merchant_id']) || $response['merchant_id'] !== $this->config->get('payment_squareup_merchant_id')) {
				$this->session->data['payment_squareup_alerts'][] = ['type' => 'danger', 'text' => $this->language->get('error_refresh_access_token')];
			} else {
				$settings = $this->model_setting_setting->getSetting('payment_squareup');
				$settings['payment_squareup_access_token'] = $response['access_token'];
				$settings['payment_squareup_access_token_expires'] = $response['expires_at'];

				if (!empty($response['refresh_token'])) {
					$settings['payment_squareup_refresh_token'] = $response['refresh_token'];
				}

				$squareup->getTokenManager()->encryptTokenSettings($settings);

				$this->model_setting_setting->editSetting('payment_squareup', $settings);

				$this->session->data['payment_squareup_alerts'][] = ['type' => 'success', 'text' => $this->language->get('text_refresh_access_token_success')];
			}
		} catch (SquareupException $e) {
			$this->session->data['payment_squareup_alerts'][] = ['type' => 'danger', 'text' => sprintf($this->language->get('error_token'), $e->getMessage())];
		}

		$this->response->redirect($this->url->link('extension/lookersolution/payment/squareup', 'user_token=' . $this->session->data['user_token']));
	}

	public function paymentInfo(): void {
		$this->load->language('extension/lookersolution/payment/squareup');

		if (!$this->user->hasPermission('access', 'extension/lookersolution/payment/squareup')) {
			$this->response->redirect($this->url->link('extension/lookersolution/payment/squareup', 'user_token=' . $this->session->data['user_token']));
			return;
		}

		$this->load->model('extension/lookersolution/payment/squareup');

		$squareup = $this->getSquareup();

		$squareup_payment_id = (int)($this->request->get['squareup_payment_id'] ?? 0);
		$payment_info = $this->model_extension_lookersolution_payment_squareup->getPayment($squareup_payment_id);

		if (empty($payment_info)) {
			$this->response->redirect($this->url->link('extension/lookersolution/payment/squareup', 'user_token=' . $this->session->data['user_token']));
			return;
		}

		$this->document->setTitle(sprintf($this->language->get('heading_title_payment'), $payment_info['payment_id']));

		$amount = $squareup->standardDenomination($payment_info['amount'], $payment_info['currency']);
		$amount = $this->currency->format($amount, $payment_info['currency'], 1);

		$refunded_currency = empty($payment_info['refunded_currency']) ? $payment_info['currency'] : $payment_info['refunded_currency'];
		$refunded_amount = $squareup->standardDenomination($payment_info['refunded_amount'], $refunded_currency);
		$refunded_amount = $this->currency->format($refunded_amount, $refunded_currency, 1);

		$data['is_fully_refunded'] = ($refunded_amount === $amount);
		$data['confirm_capture'] = sprintf($this->language->get('text_confirm_capture'), $amount);
		$data['confirm_void'] = sprintf($this->language->get('text_confirm_void'), $amount);
		$data['confirm_refund'] = $this->language->get('text_confirm_refund');
		$data['insert_amount'] = sprintf($this->language->get('text_insert_amount'), $amount, $payment_info['currency']);
		$data['text_loading'] = $this->language->get('text_loading_short');
		$data['text_edit'] = sprintf($this->language->get('heading_title_payment'), $payment_info['payment_id']);

		$fields = ['opencart_order_id', 'payment_id', 'merchant_id', 'location_id', 'order_id', 'customer_id', 'status', 'source_type', 'square_product', 'application_id', 'card_fingerprint', 'first_name', 'last_name', 'address_line_1', 'address_line_2', 'address_line_3', 'locality', 'sublocality', 'sublocality_2', 'sublocality_3', 'administrative_district_level_1', 'administrative_district_level_2', 'administrative_district_level_3', 'postal_code', 'country', 'user_agent', 'ip'];

		foreach ($fields as $field) {
			$data[$field] = $payment_info[$field];
		}

		$data['amount'] = $amount;
		$data['currency'] = $payment_info['currency'];
		$data['refunded_amount'] = $refunded_amount;
		$data['refunded_currency'] = $refunded_currency;
		$data['created_at'] = date($this->language->get('datetime_format'), strtotime($payment_info['created_at']));
		$data['updated_at'] = date($this->language->get('datetime_format'), strtotime($payment_info['updated_at']));

		$data['cancel'] = $this->url->link('extension/lookersolution/payment/squareup', 'user_token=' . $this->session->data['user_token'] . '&tab=tab-payment');
		$data['url_order'] = $this->url->link('sale/order.info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $payment_info['opencart_order_id']);
		$data['url_void'] = $this->url->link('extension/lookersolution/payment/squareup.cancel', 'user_token=' . $this->session->data['user_token'] . '&squareup_payment_id=' . $squareup_payment_id);
		$data['url_capture'] = $this->url->link('extension/lookersolution/payment/squareup.capture', 'user_token=' . $this->session->data['user_token'] . '&squareup_payment_id=' . $squareup_payment_id);
		$data['url_refund'] = $this->url->link('extension/lookersolution/payment/squareup.refund', 'user_token=' . $this->session->data['user_token'] . '&squareup_payment_id=' . $squareup_payment_id);
		$data['url_refresh'] = $this->url->link('extension/lookersolution/payment/squareup.refresh', 'user_token=' . $this->session->data['user_token'] . '&squareup_payment_id=' . $squareup_payment_id);
		$data['is_authorized'] = in_array($payment_info['status'], ['APPROVED']);
		$data['is_captured'] = in_array($payment_info['status'], ['COMPLETED']);
		$data['payment_squareup_quick_pay'] = $this->config->get('payment_squareup_quick_pay');

		$data['alerts'] = $this->session->data['payment_squareup_alerts'] ?? [];
		unset($this->session->data['payment_squareup_alerts']);

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = ['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])];
		$data['breadcrumbs'][] = ['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment')];
		$data['breadcrumbs'][] = ['text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/lookersolution/payment/squareup', 'user_token=' . $this->session->data['user_token'])];
		$data['breadcrumbs'][] = ['text' => sprintf($this->language->get('heading_title_payment'), $squareup_payment_id), 'href' => $this->url->link('extension/lookersolution/payment/squareup.paymentInfo', 'user_token=' . $this->session->data['user_token'] . '&squareup_payment_id=' . $squareup_payment_id)];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/lookersolution/payment/squareup_payment_info', $data));
	}

	public function payments(): void {
		$this->load->language('extension/lookersolution/payment/squareup');

		if (!$this->user->hasPermission('access', 'extension/lookersolution/payment/squareup')) {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode(['error' => $this->language->get('error_permission')]));
			return;
		}

		$this->load->model('extension/lookersolution/payment/squareup');

		$squareup = $this->getSquareup();

		$page = (int)($this->request->get['page'] ?? 1);

		$filter_data = [
			'start' => ($page - 1) * (int)$this->config->get('config_pagination_admin'),
			'limit' => (int)$this->config->get('config_pagination_admin'),
		];

		if (isset($this->request->get['order_id'])) {
			$filter_data['order_id'] = (int)$this->request->get['order_id'];
		}

		$payments_total = $this->model_extension_lookersolution_payment_squareup->getTotalPayments($filter_data);
		$payments = $this->model_extension_lookersolution_payment_squareup->getPayments($filter_data);

		$result = ['payments' => [], 'pagination' => ''];

		foreach ($payments as $payment) {
			$amount = $squareup->standardDenomination($payment['amount'], $payment['currency']);
			$amount = $this->currency->format($amount, $payment['currency'], 1);
			$refunded_currency = empty($payment['refunded_currency']) ? $payment['currency'] : $payment['refunded_currency'];
			$refunded_amount = $squareup->standardDenomination($payment['refunded_amount'], $refunded_currency);
			$refunded_amount = $this->currency->format($refunded_amount, $refunded_currency, 1);

			$result['payments'][] = [
				'squareup_payment_id' => $payment['squareup_payment_id'],
				'payment_id'          => $payment['payment_id'],
				'url_order'           => $this->url->link('sale/order.info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $payment['opencart_order_id']),
				'url_void'            => $this->url->link('extension/lookersolution/payment/squareup.cancel', 'user_token=' . $this->session->data['user_token'] . '&squareup_payment_id=' . $payment['squareup_payment_id']),
				'url_capture'         => $this->url->link('extension/lookersolution/payment/squareup.capture', 'user_token=' . $this->session->data['user_token'] . '&squareup_payment_id=' . $payment['squareup_payment_id']),
				'url_refund'          => $this->url->link('extension/lookersolution/payment/squareup.refund', 'user_token=' . $this->session->data['user_token'] . '&squareup_payment_id=' . $payment['squareup_payment_id']),
				'url_refresh'         => $this->url->link('extension/lookersolution/payment/squareup.refresh', 'user_token=' . $this->session->data['user_token'] . '&squareup_payment_id=' . $payment['squareup_payment_id']),
				'confirm_capture'     => sprintf($this->language->get('text_confirm_capture'), $amount),
				'confirm_void'        => sprintf($this->language->get('text_confirm_void'), $amount),
				'confirm_refund'      => $this->language->get('text_confirm_refund'),
				'confirm_refresh'     => $this->language->get('text_confirm_refresh'),
				'insert_amount'       => sprintf($this->language->get('text_insert_amount'), $amount, $payment['currency']),
				'order_id'            => $payment['opencart_order_id'],
				'source_type'         => $payment['source_type'],
				'status'              => $payment['status'],
				'amount'              => $amount,
				'refunded_amount'     => $refunded_amount,
				'customer'            => $payment['customer'] ?? '',
				'ip'                  => $payment['ip'],
				'date_created'        => date($this->language->get('datetime_format'), strtotime($payment['created_at'])),
				'date_updated'        => date($this->language->get('datetime_format'), strtotime($payment['updated_at'])),
				'url_info'            => $this->url->link('extension/lookersolution/payment/squareup.paymentInfo', 'user_token=' . $this->session->data['user_token'] . '&squareup_payment_id=' . $payment['squareup_payment_id']),
			];
		}

		$pagination = new \Opencart\System\Library\Pagination();
		$pagination->total = $payments_total;
		$pagination->page = $page;
		$pagination->limit = (int)$this->config->get('config_pagination_admin');
		$pagination->url = '{page}';

		$result['pagination'] = $pagination->render();

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($result));
	}

	public function capture(): void {
		$this->load->language('extension/lookersolution/payment/squareup');
		$this->load->model('extension/lookersolution/payment/squareup');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/lookersolution/payment/squareup')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$squareup_payment_id = (int)($this->request->get['squareup_payment_id'] ?? 0);
		$payment_info = $this->model_extension_lookersolution_payment_squareup->getPayment($squareup_payment_id);

		if (empty($payment_info)) {
			$json['error'] = $this->language->get('error_payment_missing');
		}

		if (empty($json['error'])) {
			try {
				$squareup = $this->getSquareup();
				$payment = $squareup->completePayment($payment_info['payment_id']);

				if (!empty($payment['payment'])) {
					$this->model_extension_lookersolution_payment_squareup->updatePaymentStatus($squareup_payment_id, $payment['payment']['status'], $payment['payment']['updated_at']);
					$this->model_extension_lookersolution_payment_squareup->addOrderHistory((int)$payment_info['opencart_order_id'], (int)$this->config->get('payment_squareup_status_captured'));

					$json['success'] = $this->language->get('text_success_capture');
				} else {
					$json['error'] = $this->language->get('error_capture_payment');
				}
			} catch (SquareupException $e) {
				$json['error'] = $e->getMessage();
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function cancel(): void {
		$this->load->language('extension/lookersolution/payment/squareup');
		$this->load->model('extension/lookersolution/payment/squareup');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/lookersolution/payment/squareup')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$squareup_payment_id = (int)($this->request->get['squareup_payment_id'] ?? 0);
		$payment_info = $this->model_extension_lookersolution_payment_squareup->getPayment($squareup_payment_id);

		if (empty($payment_info)) {
			$json['error'] = $this->language->get('error_payment_missing');
		}

		if (empty($json['error'])) {
			try {
				$squareup = $this->getSquareup();
				$payment = $squareup->cancelPayment($payment_info['payment_id']);

				if (!empty($payment['payment'])) {
					$this->model_extension_lookersolution_payment_squareup->updatePaymentStatus($squareup_payment_id, $payment['payment']['status'], $payment['payment']['updated_at']);
					$this->model_extension_lookersolution_payment_squareup->addOrderHistory((int)$payment_info['opencart_order_id'], (int)$this->config->get('payment_squareup_status_voided'));

					$json['success'] = $this->language->get('text_success_void');
				} else {
					$json['error'] = $this->language->get('error_cancel_payment');
				}
			} catch (SquareupException $e) {
				$json['error'] = $e->getMessage();
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function refund(): void {
		$this->load->language('extension/lookersolution/payment/squareup');
		$this->load->model('extension/lookersolution/payment/squareup');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/lookersolution/payment/squareup')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$squareup_payment_id = (int)($this->request->get['squareup_payment_id'] ?? 0);
		$payment_info = $this->model_extension_lookersolution_payment_squareup->getPayment($squareup_payment_id);

		if (empty($payment_info)) {
			$json['error'] = $this->language->get('error_payment_missing');
		}

		if (empty($json['error'])) {
			try {
				$squareup = $this->getSquareup();

				$reason = !empty($this->request->post['reason']) ? $this->request->post['reason'] : $this->language->get('text_no_reason_provided');

				$amount = 0;
				if (!empty($this->request->post['amount'])) {
					$raw = preg_replace('~[^0-9\.\,]~', '', $this->request->post['amount']);
					if (str_contains($raw, ',') && str_contains($raw, '.')) {
						$amount = (float)str_replace(',', '', $raw);
					} elseif (str_contains($raw, ',')) {
						$amount = (float)str_replace(',', '.', $raw);
					} else {
						$amount = (float)$raw;
					}
				}

				$currency = $payment_info['currency'];
				$paid_amount = $payment_info['amount'];
				$refunded_amount = $payment_info['refunded_amount'];
				$planned_amount = $squareup->lowestDenomination($amount, $currency);

				if ($planned_amount > $paid_amount - $refunded_amount) {
					$json['error'] = $this->language->get('error_refund_too_large');
				}

				if (empty($json['error'])) {
					$last_refund = $squareup->refundPayment($payment_info['payment_id'], $amount, $currency, $reason);
					$new_refunded_amount = $refunded_amount + $last_refund['refund']['amount_money']['amount'];

					$this->model_extension_lookersolution_payment_squareup->updatePaymentRefund($squareup_payment_id, $last_refund['refund']['updated_at'], $new_refunded_amount);

					$last_refunded_formatted = $this->currency->format(
						$squareup->standardDenomination($last_refund['refund']['amount_money']['amount'], $last_refund['refund']['amount_money']['currency']),
						$last_refund['refund']['amount_money']['currency'],
						1
					);

					$refund_comment = sprintf($this->language->get('text_refunded_amount'), $last_refunded_formatted, $last_refund['refund']['status'], $last_refund['refund']['reason']);
					$this->model_extension_lookersolution_payment_squareup->addOrderHistory((int)$payment_info['opencart_order_id'], $this->model_extension_lookersolution_payment_squareup->getOrderStatusId((int)$payment_info['opencart_order_id']), $refund_comment);

					$json['success'] = $this->language->get('text_success_refund');
				}
			} catch (SquareupException $e) {
				$json['error'] = $e->getMessage();
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function refresh(): void {
		$this->load->language('extension/lookersolution/payment/squareup');
		$this->load->model('extension/lookersolution/payment/squareup');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/lookersolution/payment/squareup')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$squareup_payment_id = (int)($this->request->get['squareup_payment_id'] ?? 0);
		$payment_info = $this->model_extension_lookersolution_payment_squareup->getPayment($squareup_payment_id);

		if (empty($payment_info)) {
			$json['error'] = $this->language->get('error_payment_missing');
		}

		if (empty($json['error'])) {
			try {
				$squareup = $this->getSquareup();
				$payment = $squareup->getPayment($payment_info['payment_id']);

				if (!empty($payment['payment'])) {
					$this->model_extension_lookersolution_payment_squareup->updatePayment($squareup_payment_id, $payment);
					$this->model_extension_lookersolution_payment_squareup->addOrderHistory((int)$payment_info['opencart_order_id'], $this->paymentStatusToOrderStatus($payment['payment']['status']));

					$json['success'] = $this->language->get('text_success_refresh');
				} else {
					$json['error'] = $this->language->get('error_refresh_payment');
				}
			} catch (SquareupException $e) {
				$json['error'] = $e->getMessage();
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function order(string &$route, array &$data, &$output): void {
		$this->load->language('extension/lookersolution/payment/squareup');

		if (!isset($data['order_id'])) {
			return;
		}

		$this->load->model('extension/lookersolution/payment/squareup');

		$payments = $this->model_extension_lookersolution_payment_squareup->getPayments(['order_id' => (int)$data['order_id']]);

		if (empty($payments)) {
			return;
		}

		$tab_data['url_list_payments'] = html_entity_decode($this->url->link('extension/lookersolution/payment/squareup.payments', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . (int)$data['order_id'] . '&page={PAGE}'));
		$tab_data['user_token'] = $this->session->data['user_token'];
		$tab_data['order_id'] = (int)$data['order_id'];

		$output .= $this->load->view('extension/lookersolution/payment/squareup_order', $tab_data);
	}

	public function webhookEvents(): void {
		$this->load->language('extension/lookersolution/payment/squareup');

		if (!$this->user->hasPermission('access', 'extension/lookersolution/payment/squareup')) {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode(['error' => $this->language->get('error_permission')]));
			return;
		}

		$this->load->model('extension/lookersolution/payment/squareup');

		$page = (int)($this->request->get['page'] ?? 1);

		$filter_data = [
			'start' => ($page - 1) * (int)$this->config->get('config_pagination_admin'),
			'limit' => (int)$this->config->get('config_pagination_admin'),
		];

		$events_total = $this->model_extension_lookersolution_payment_squareup->getTotalWebhookEvents();
		$events = $this->model_extension_lookersolution_payment_squareup->getWebhookEvents($filter_data);

		$result = ['events' => [], 'pagination' => ''];

		foreach ($events as $event) {
			$result['events'][] = [
				'event_id'    => $event['event_id'],
				'event_type'  => $event['event_type'],
				'merchant_id' => $event['merchant_id'],
				'payment_id'  => $event['payment_id'],
				'processed'   => (int)$event['processed'],
				'created_at'  => date($this->language->get('datetime_format'), strtotime($event['created_at'])),
				'processed_at' => $event['processed_at'] ? date($this->language->get('datetime_format'), strtotime($event['processed_at'])) : '',
			];
		}

		$pagination = new \Opencart\System\Library\Pagination();
		$pagination->total = $events_total;
		$pagination->page = $page;
		$pagination->limit = (int)$this->config->get('config_pagination_admin');
		$pagination->url = '{page}';

		$result['pagination'] = $pagination->render();

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($result));
	}

	public function registerApplePayDomain(): void {
		$this->load->language('extension/lookersolution/payment/squareup');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/lookersolution/payment/squareup')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$domain = parse_url(HTTP_CATALOG, PHP_URL_HOST);

			if (!$domain) {
				$json['error'] = $this->language->get('error_apple_pay_domain');
			}
		}

		if (!$json) {
			try {
				$squareup = $this->getSquareup();
				$result = $squareup->registerApplePayDomain($domain);

				if (!empty($result['status']) && $result['status'] === 'VERIFIED') {
					$json['success'] = sprintf($this->language->get('text_apple_pay_domain_success'), $domain);
				} else {
					$json['success'] = sprintf($this->language->get('text_apple_pay_domain_registered'), $domain);
				}
			} catch (SquareupException $e) {
				$json['error'] = $e->getMessage();
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function subscriptionCancel(): void {
		$this->load->language('extension/lookersolution/payment/squareup');

		$json = [];

		if (!$this->user->hasPermission('modify', 'sale/subscription')) {
			$json['error'] = $this->language->get('error_permission_subscription');
		} else {
			$subscription_id = (int)($this->request->get['subscription_id'] ?? 0);

			$this->load->model('sale/subscription');

			$subscription_info = $this->model_sale_subscription->getSubscription($subscription_id);

			if ($subscription_info) {
				$canceled_status_id = (int)$this->config->get('config_subscription_canceled_status_id');

				$this->load->model('extension/lookersolution/payment/squareup');
				$this->model_extension_lookersolution_payment_squareup->editSubscriptionStatus($subscription_id, $canceled_status_id);

				$json['success'] = $this->language->get('text_canceled_success');
			} else {
				$json['error'] = $this->language->get('error_not_found');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		if (!$this->user->hasPermission('modify', 'extension/lookersolution/payment/squareup')) {
			return;
		}

		$this->load->model('extension/lookersolution/payment/squareup');

		$this->model_extension_lookersolution_payment_squareup->createTables();

		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'payment_squareup',
			'description' => 'Square Payment CSP Header Injection',
			'trigger'     => 'catalog/view/common/header/after',
			'action'      => 'extension/lookersolution/payment/squareup.eventViewCommonHeaderAfter',
			'status'      => true,
			'sort_order'  => 0,
		]);

		$this->model_setting_event->addEvent([
			'code'        => 'payment_squareup',
			'description' => 'Square Payment Order Info Tab',
			'trigger'     => 'admin/view/sale/order_info/after',
			'action'      => 'extension/lookersolution/payment/squareup.order',
			'status'      => true,
			'sort_order'  => 0,
		]);

		$this->model_setting_event->addEvent([
			'code'        => 'payment_squareup',
			'description' => 'Square Saved Cards Account Link',
			'trigger'     => 'catalog/view/account/account/after',
			'action'      => 'extension/lookersolution/module/squareup.eventViewAccountAccountAfter',
			'status'      => true,
			'sort_order'  => 0,
		]);

		$this->load->model('setting/cron');

		$this->model_setting_cron->addCron(
			'payment_squareup',
			'Square Payment Subscription Processing',
			'hour',
			'extension/lookersolution/cron/squareup',
			true
		);
	}

	public function uninstall(): void {
		if (!$this->user->hasPermission('modify', 'extension/lookersolution/payment/squareup')) {
			return;
		}

		$this->load->model('extension/lookersolution/payment/squareup');

		$this->model_extension_lookersolution_payment_squareup->dropTables();

		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('payment_squareup');

		$this->load->model('setting/cron');

		$this->model_setting_cron->deleteCronByCode('payment_squareup');
	}

	protected function paymentStatusToOrderStatus(string $payment_status): int {
		return match ($payment_status) {
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

	protected function validate(): array {
		$errors = [];

		if (empty($this->request->post['payment_squareup_client_id'])) {
			$errors[] = $this->language->get('error_client_id');
		}

		if (empty($this->request->post['payment_squareup_client_secret'])) {
			$errors[] = $this->language->get('error_client_secret');
		}

		if (!empty($this->request->post['payment_squareup_enable_sandbox'])) {
			if (empty($this->request->post['payment_squareup_sandbox_client_id'])) {
				$errors[] = $this->language->get('error_sandbox_client_id');
			}

			if (empty($this->request->post['payment_squareup_sandbox_token'])) {
				$errors[] = $this->language->get('error_sandbox_token');
			}
		}

		if (!empty($this->request->post['payment_squareup_cron_email_status'])) {
			if (!filter_var($this->request->post['payment_squareup_cron_email'] ?? '', FILTER_VALIDATE_EMAIL)) {
				$errors[] = $this->language->get('error_invalid_email');
			}
		}

		return $errors;
	}
}
