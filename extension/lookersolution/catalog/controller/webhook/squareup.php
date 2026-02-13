<?php
/**
 * @package    LookerSolution\SquarePayment
 * @author     Ali Ahmed <ali_a@lookersolution.com>
 * @copyright  Copyright (c) 2026 LookerSolution (https://www.lookersolution.com)
 * @license    https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link       https://github.com/LookerSolution/oc4-squareup-payment
 */
namespace Opencart\Catalog\Controller\Extension\Lookersolution\Webhook;

use Opencart\System\Library\Extension\Lookersolution\Squareup\WebhookHandler;

class Squareup extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->response->addHeader('Content-Type: application/json');

		if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 405 Method Not Allowed');
			$this->response->setOutput(json_encode(['error' => 'Method not allowed']));
			return;
		}

		$body = file_get_contents('php://input');

		if (!$body) {
			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 400 Bad Request');
			$this->response->setOutput(json_encode(['error' => 'Empty body']));
			return;
		}

		$signature = $this->request->server['HTTP_X_SQUARE_HMACSHA256_SIGNATURE'] ?? '';

		$notification_url = HTTP_SERVER . 'index.php?route=extension/lookersolution/webhook/squareup';

		$handler = new WebhookHandler($this->registry);

		if (!$handler->validateSignature($body, $signature, $notification_url)) {
			$this->debug('Webhook: Invalid signature');
			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 401 Unauthorized');
			$this->response->setOutput(json_encode(['error' => 'Invalid signature']));
			return;
		}

		$event = json_decode($body, true);

		if (!$event || empty($event['event_id']) || empty($event['type'])) {
			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 400 Bad Request');
			$this->response->setOutput(json_encode(['error' => 'Invalid payload']));
			return;
		}

		$event_id = $event['event_id'];
		$event_type = $event['type'];
		$merchant_id = $event['merchant_id'] ?? '';

		if ($handler->isProcessed($event_id)) {
			$this->response->setOutput(json_encode(['status' => 'already_processed']));
			return;
		}

		$object_data = $event['data']['object'] ?? [];
		$object_type = $event['data']['type'] ?? '';
		$object_id = $event['data']['id'] ?? '';

		$payment_id = '';

		if ($object_type === 'payment' && isset($object_data['payment']['id'])) {
			$payment_id = $object_data['payment']['id'];
		} elseif ($object_type === 'refund' && isset($object_data['refund']['payment_id'])) {
			$payment_id = $object_data['refund']['payment_id'];
		}

		$handler->storeEvent($event_id, $event_type, $merchant_id, $payment_id, $body);

		$handler->processEvent($event_type, $object_data);

		$handler->markProcessed($event_id);

		$this->response->setOutput(json_encode(['status' => 'ok']));
	}

	private function debug(string $text): void {
		if ($this->config->get('payment_squareup_debug')) {
			$this->log->write($text);
		}
	}
}
