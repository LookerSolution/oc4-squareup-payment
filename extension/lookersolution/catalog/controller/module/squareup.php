<?php
/**
 * @package    LookerSolution\SquarePayment
 * @author     Ali Ahmed <ali@lookersolution.com>
 * @copyright  Copyright (c) 2026 LookerSolution (https://www.lookersolution.com)
 * @license    https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link       https://github.com/LookerSolution/oc4-squareup-payment
 */
namespace Opencart\Catalog\Controller\Extension\Lookersolution\Module;

use Opencart\System\Library\Extension\Lookersolution\Squareup as SquareupLib;
use Opencart\System\Library\Extension\Lookersolution\Squareup\Exception as SquareupException;

class Squareup extends \Opencart\System\Engine\Controller {
	public function index(): void {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('extension/lookersolution/module/squareup', 'language=' . $this->config->get('config_language'));
			$this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language')));
			return;
		}

		if (!$this->config->get('payment_squareup_status')) {
			$this->response->redirect($this->url->link('account/account', 'language=' . $this->config->get('config_language')));
			return;
		}

		$this->load->language('extension/lookersolution/module/squareup');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language')),
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', 'language=' . $this->config->get('config_language')),
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/lookersolution/module/squareup', 'language=' . $this->config->get('config_language')),
		];

		$data['success'] = $this->session->data['success'] ?? '';
		unset($this->session->data['success']);

		$data['error'] = $this->session->data['error'] ?? '';
		unset($this->session->data['error']);

		$data['cards'] = [];
		$error = '';

		try {
			$squareup = new SquareupLib($this->registry);

			$this->load->model('account/address');

			$email = $this->customer->getEmail();
			$address_id = $this->customer->getAddressId();
			$address = $this->model_account_address->getAddress($this->customer->getId(), $address_id);
			$country_code = $address['iso_code_2'] ?? '';
			$phone = $squareup->phoneFormat($this->customer->getTelephone(), $country_code);

			$customers = $squareup->searchCustomers($email, $phone);

			if (!empty($customers['customers'][0])) {
				$cards = $squareup->listCards($customers['customers'][0]['id']);
				if (!empty($cards['cards'])) {
					foreach ($cards['cards'] as $card) {
						$data['cards'][] = [
							'text'    => sprintf(
								$this->language->get('text_card_ends_in'),
								$card['card_brand'],
								$card['last_4'],
								date($this->language->get('datetime_format'), strtotime($card['created_at']))
							),
							'disable' => $this->url->link('extension/lookersolution/module/squareup.forget', 'language=' . $this->config->get('config_language') . '&card_id=' . $card['id']),
						];
					}
				}
			}
		} catch (SquareupException $e) {
			$error = $this->handleException($e);
		}

		if ($error) {
			$data['error'] = $data['error'] ? $data['error'] . '<br>' . $error : $error;
		}

		$data['back'] = $this->url->link('account/account', 'language=' . $this->config->get('config_language'));

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('extension/lookersolution/module/squareup_cards', $data));
	}

	public function forget(): void {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('extension/lookersolution/module/squareup', 'language=' . $this->config->get('config_language'));
			$this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language')));
			return;
		}

		$this->load->language('extension/lookersolution/module/squareup');

		$card_id = $this->request->get['card_id'] ?? '';
		$error = '';

		if ($card_id) {
			try {
				$squareup = new SquareupLib($this->registry);

				$this->load->model('account/address');

				$email = $this->customer->getEmail();
				$address_id = $this->customer->getAddressId();
				$address = $this->model_account_address->getAddress($this->customer->getId(), $address_id);
				$country_code = $address['iso_code_2'] ?? '';
				$phone = $squareup->phoneFormat($this->customer->getTelephone(), $country_code);

				$customers = $squareup->searchCustomers($email, $phone);

				if (!empty($customers['customers'][0])) {
					$cards = $squareup->listCards($customers['customers'][0]['id']);
					$found = false;
					if (!empty($cards['cards'])) {
						foreach ($cards['cards'] as $card) {
							if ($card['id'] === $card_id) {
								$squareup->disableCard($card_id);
								$this->session->data['success'] = $this->language->get('text_success_card_delete');
								$found = true;
								break;
							}
						}
					}
					if (!$found) {
						$error = $this->language->get('error_card');
					}
				} else {
					$error = str_replace('%2', $phone, str_replace('%1', $email, $this->language->get('error_customer')));
				}
			} catch (SquareupException $e) {
				$error = $this->handleException($e);
			}
		}

		if ($error) {
			$this->session->data['error'] = $error;
		}

		$this->response->redirect($this->url->link('extension/lookersolution/module/squareup', 'language=' . $this->config->get('config_language')));
	}

	public function eventViewAccountAccountAfter(string &$route, array &$data, string &$output): void {
		if (!$this->config->get('payment_squareup_status')) {
			return;
		}

		if (!$this->customer->isLogged()) {
			return;
		}

		$this->load->language('extension/lookersolution/module/squareup');

		$link = $this->url->link('extension/lookersolution/module/squareup', 'language=' . $this->config->get('config_language'));
		$text = $this->language->get('heading_title');
		$item = '<li><a href="' . $link . '">' . $text . '</a></li>';

		$search = '<li><a href="' . $data['wishlist'] . '">';
		if (strpos($output, $search) !== false) {
			$output = str_replace($search, $item . "\n        " . $search, $output);
		}
	}

	private function handleException(SquareupException $e): string {
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
