<?php
/**
 * @package    LookerSolution\SquarePayment
 * @author     Ali Ahmed <ali@lookersolution.com>
 * @copyright  Copyright (c) 2026 LookerSolution (https://www.lookersolution.com)
 * @license    https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0
 * @link       https://github.com/LookerSolution/oc4-squareup-payment
 */
namespace Opencart\Admin\Controller\Extension\Lookersolution\Payment;

class Squareup extends \Opencart\System\Engine\Controller {
	public function install(): void {
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
		$this->load->model('extension/lookersolution/payment/squareup');

		$this->model_extension_lookersolution_payment_squareup->dropTables();

		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('payment_squareup');

		$this->load->model('setting/cron');

		$this->model_setting_cron->deleteCronByCode('payment_squareup');
	}
}
