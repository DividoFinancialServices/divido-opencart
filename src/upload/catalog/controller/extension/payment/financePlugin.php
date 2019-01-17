<?php

use Divido\MerchantSDK\Client;
use Divido\MerchantSDK\Environment;
use Divido\MerchantSDK\Models\Application;

class ControllerExtensionPaymentFinancePlugin extends Controller {
	const
		STATUS_ACCEPTED = 'ACCEPTED',
		STATUS_ACTION_LENDER = 'ACTION-LENDER',
		STATUS_CANCELED = 'CANCELED',
		STATUS_COMPLETED = 'COMPLETED',
		STATUS_DEPOSIT_PAID = 'DEPOSIT-PAID',
		STATUS_DECLINED = 'DECLINED',
		STATUS_DEFERRED = 'DEFERRED',
		STATUS_REFERRED = 'REFERRED',
		STATUS_FULFILLED = 'FULFILLED',
		STATUS_SIGNED = 'SIGNED';

	private $status_id = array(
		self::STATUS_ACCEPTED => 1,
		self::STATUS_ACTION_LENDER => 2,
		self::STATUS_CANCELED => 0,
		self::STATUS_COMPLETED => 2,
		self::STATUS_DECLINED => 8,
		self::STATUS_DEFERRED => 1,
		self::STATUS_REFERRED => 1,
		self::STATUS_DEPOSIT_PAID => 1,
		self::STATUS_FULFILLED => 1,
		self::STATUS_SIGNED => 2,
	);

	private $history_messages = array(
		self::STATUS_ACCEPTED => 'Credit request accepted',
		self::STATUS_ACTION_LENDER => 'Lender notified',
		self::STATUS_CANCELED => 'Credit request canceled',
		self::STATUS_COMPLETED => 'Credit application completed',
		self::STATUS_DECLINED => 'Credit request declined',
		self::STATUS_DEFERRED => 'Credit request deferred',
		self::STATUS_REFERRED => 'Credit request referred',
		self::STATUS_DEPOSIT_PAID => 'Deposit paid',
		self::STATUS_FULFILLED => 'Credit request fulfilled',
		self::STATUS_SIGNED => 'Contract signed',
	);

	public function index() {
		$this->load->language('extension/payment/financePlugin');
		$this->load->model('extension/payment/financePlugin');
		$this->load->model('checkout/order');

		$api_key   = $this->config->get('payment_financePlugin_api_key');
		$key_parts = explode('.', $api_key);
		$js_key    = strtolower(array_shift($key_parts));

		list($total, $totals) = $this->model_extension_payment_financePlugin->getOrderTotals();

		$this->model_extension_payment_financePlugin->instantiateSDK($this->config->get('payment_financePlugin_api_key'));


		$plans = $this->model_extension_payment_financePlugin->getCartPlans($this->cart);
		foreach ($plans as $key => $plan) {
			$planMinTotal = $total - ($total * ($plan->min_deposit / 100));
			if ($plan->min_amount > $planMinTotal) {
				unset($plans[$key]);
			}
		}

		$plans_ids  = array_map(function ($plan) {
			return $plan->id;
		}, $plans);
		$plans_ids  = array_unique($plans_ids);
		$plans_list = implode(',', $plans_ids);

		$data = array(
			'button_confirm'			=> $this->language->get('financePlugin_checkout'),
			'api_key'					=> $js_key,
			'amount'					=> $total,
			'basket_plans'              => $plans_list,
			'generic_credit_req_error'	=> 'Credit request could not be initiated',
		);

		return $this->load->view('extension/payment/financePlugin', $data);
	}

	public function update() {
		$this->load->language('extension/payment/financePlugin');
		$this->load->model('extension/payment/financePlugin');
		$this->load->model('checkout/order');

		$data = json_decode(file_get_contents('php://input'));

		if (!isset($data->status)) {
			$this->response->setOutput('');
			return;
		}

		$lookup = $this->model_extension_payment_financePlugin->getLookupByOrderId($data->metadata->order_id);
		if ($lookup->num_rows != 1) {
			$this->response->setOutput('');
			return;
		}

		$hash = $this->model_extension_payment_financePlugin->hashOrderId($data->metadata->order_id, $lookup->row['salt']);
		if ($hash !== $data->metadata->order_hash) {
			$this->response->setOutput('');
			return;
		}

		$order_id = $data->metadata->order_id;
		$order_info = $this->model_checkout_order->getOrder($order_id);
		$status_id = $order_info['order_status_id'];
		$message = "Status: {$data->status}";
		if (isset($this->history_messages[$data->status])) {
			$message = $this->history_messages[$data->status];
		}

		if ($data->status == self::STATUS_SIGNED) {
			$status_override = $this->config->get('payment_financePlugin_order_status_id');
			if (!empty($status_override)) {
				$this->status_id[self::STATUS_SIGNED] = $status_override;
			}
		}

		if (isset($this->status_id[$data->status]) && $this->status_id[$data->status] > $status_id) {
			$status_id = $this->status_id[$data->status];
		}

		if ($data->status == self::STATUS_DECLINED && $order_info['order_status_id'] == 0) {
			$status_id = 0;
		}

		$this->model_extension_payment_financePlugin->saveLookup($data->metadata->order_id, $lookup->row['salt'], null, $data->application);
		$this->model_checkout_order->addOrderHistory($order_id, $status_id, $message, false);
		$this->response->setOutput('ok');
	}

	public function confirm() {
		$this->load->language('extension/payment/financePlugin');

		$this->load->model('extension/payment/financePlugin');

		ini_set('html_errors', 0);
		if (!$this->session->data['payment_method']['code'] == 'financePlugin' && isset($_POST['divido_deposit']) && isset($_POST['divido_plan'])) {
			return false;
		}

		$order_id = $this->session->data['order_id'];

		$salt = uniqid('', true);
		$hash = $this->model_extension_payment_financePlugin->hashOrderId($order_id, $salt);

		$request = array();
		$request['deposit_percentage'] = ($_POST['divido_deposit']/100);
		$request['plan'] = $_POST['divido_plan'];
		$request['country'] = $this->session->data['payment_address'];
		$request['language'] = $this->language->get('code');
		$request['currency'] = strtoupper($this->session->data['currency']);
		$request["products"] = $this->model_extension_payment_financePlugin->getProducts();
		$request["urls"] = [
			'merchant_response_url' => $this->url->link('extension/payment/financePlugin/update', '', true),
			'merchant_redirect_url' => $this->url->link('checkout/success', '', true),
			'merchant_checkout_url' => $this->url->link('checkout/checkout', '', true)
		];
		$request['customers'] = [
			$this->model_extension_payment_financePlugin->getCustomer()
		];
		$request["merchant_reference"] = '';
		$request["finalisation_required"] = false;
		$request["metadata"] = [
			'order_id' => $order_id,
			'order_hash' => $hash
		];

		$response_json = $this->model_extension_payment_financePlugin->apply($request);

		$response = json_decode($response_json);

		if (isset($response->error)) {
			$data = array(
				'status' => 'error',
				'message' => $response->message //$this->language->get($response->error),
			);
		} else {
			$payload = $response->data;
			$this->model_extension_payment_financePlugin->saveLookup($order_id, $salt, $payload->id, null, $payload->amounts->deposit_amount);

			$data = array(
				'status' => 'ok',
				'url'    => $payload->urls->application_url,
			);
		}

		$this->response->setOutput(json_encode($data));
	}

	public function calculator($args) {
		$this->load->language('extension/payment/financePlugin');

		$this->load->model('extension/payment/financePlugin');

		if (!$this->model_extension_payment_financePlugin->isEnabled()) {
			return null;
		}

		$this->model_extension_payment_financePlugin->setMerchant($this->config->get('payment_financePlugin_api_key'));

		$product_selection = $this->config->get('payment_financePlugin_productselection');
		$price_threshold   = $this->config->get('payment_financePlugin_price_threshold');
		$product_id        = $args['product_id'];
		$product_price     = $args['price'];
		$type              = $args['type'];

		if ($product_selection == 'threshold' && $product_price < $price_threshold) {
			return null;
		}

		$plans = $this->model_extension_payment_financePlugin->getProductPlans($product_id);
		if (empty($plans)) {
			return null;
		}

		$plans_ids = array_map(function ($plan) {
			return $plan->id;
		}, $plans);

		$plan_list = implode(',', $plans_ids);

		$data = array(
			'planList'     => $plan_list,
			'productPrice' => $product_price
		);

		$filename = ($type == 'full') ? 'extension/payment/financePlugin_calculator' : 'extension/payment/financePlugin_widget';

		return $this->load->view($filename, $data);
	}
}
