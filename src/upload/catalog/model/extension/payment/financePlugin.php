<?php

use Divido\MerchantSDK\Client;
use Divido\MerchantSDK\Environment;
use Divido\MerchantSDK\Models\Application;
use Divido\MerchantSDK\Handlers\ApiRequestOptions;
use Divido\MerchantSDK\Exceptions\MerchantApiBadResponseException;

class ModelExtensionPaymentFinancePlugin extends Model {
	const CACHE_KEY_PLANS = 'finance_plugin_plans';

	private $sdk;

	public function getEnvironment($apiKey)
	{
		if (empty($apiKey)) {
			return false;
		} else {
			list($environment, $key) = explode("_", $apiKey);
			$environment = strtoupper($environment);
			if (!is_null(
				constant("Divido\MerchantSDK\Environment::$environment")
			)) {
				$environment
					= constant("Divido\MerchantSDK\Environment::$environment");
				return $environment;
			} else {
				return false;
			}
		}
	}

	public function instantiateSDK(string $apiKey, $environment = false)
	{
		$environment = ($environment) ? $environment : $this->getEnvironment($apiKey);

		$sdk = new Client(
			$apiKey,
			$environment
		);
		$this->sdk = $sdk;
		return $sdk;
	}

	public function getMethod($payment_address, $total) {
		$this->load->language('extension/payment/financePlugin');
		$this->load->model('localisation/currency');

		if (!$this->isEnabled()) {
			return array();
		}

		if ($this->session->data['currency'] != 'GBP') {
			return array();
		}

		if ($payment_address['iso_code_2'] != 'GB') {
			return array();
		}

		$cart_threshold = $this->config->get('payment_financePlugin_cart_threshold');
		if ($cart_threshold > $total) {
			return array();
		}

		$plans = $this->getCartPlans($this->cart);
		$has_plan = false;

		foreach ($plans as $plan) {
			$planMinTotal = $total - ($total * ($plan->min_deposit / 100));
			if ($plan->min_amount <= $planMinTotal) {
				$has_plan = true;
				break;
			}
		}

		if (!$has_plan) {
			return array();
		}

		$title = $this->language->get('text_checkout_title');
		if ($title_override = $this->config->get('payment_financePlugin_title')) {
			$title = $title_override;
		}

		$method_data = array(
			'code' => 'financePlugin',
			'title' => $title,
			'terms' => '',
			'sort_order' => $this->config->get('payment_financePlugin_sort_order')
		);

		return $method_data;
	}

	public function getProductSettings($product_id) {
		return $this->db->query("SELECT `display`, `plans` FROM `" . DB_PREFIX . "c8UMbuNcJ4_product` WHERE `product_id` = '" . (int)$product_id . "'")->row;
	}

	public function isEnabled() {
		$api_key = $this->config->get('payment_financePlugin_api_key');
		$enabled = $this->config->get('payment_financePlugin_status');

		return !empty($api_key) && $enabled == 1;
	}

	public function hashOrderId($order_id, $salt) {
		return hash('sha256', $order_id . $salt);
	}

	public function saveLookup($order_id, $salt, $proposal_id = null, $application_id = null, $deposit_amount = null) {
		$order_id = (int)$order_id;
		$salt = $this->db->escape($salt);
		$proposal_id = $this->db->escape($proposal_id);
		$application_id = $this->db->escape($application_id);
		$deposit_amount = $this->db->escape($deposit_amount);

		$query_get_lookup = "SELECT `application_id` from `" . DB_PREFIX . "c8UMbuNcJ4_lookup` WHERE order_id = " . $order_id;
		$result_get_lookup = $this->db->query($query_get_lookup);

		if ($result_get_lookup->num_rows == 0) {
			$proposal_id = ($proposal_id) ? "'" . $proposal_id . "'" : 'NULL';
			$application_id = ($application_id) ? "'" . $application_id . "'" : 'NULL';
			$deposit_amount = ($deposit_amount) ? $deposit_amount : 'NULL';

			$query_upsert = "INSERT INTO `" . DB_PREFIX . "c8UMbuNcJ4_lookup` (`order_id`, `salt`, `proposal_id`, `application_id`, `deposit_amount`) VALUES (" . $order_id . ", '" . $salt . "', " . $proposal_id . ", " . $application_id . ", " . $deposit_amount . ")";
		} else {
			$query_upsert = "UPDATE `" . DB_PREFIX . "c8UMbuNcJ4_lookup` SET `salt` = '" . $salt . "'";

			if ($proposal_id) {
				$query_upsert .= ", `proposal_id` = '" . $proposal_id . "'";
			}

			if ($application_id) {
				$query_upsert .= ", `application_id` = '" . $application_id . "'";
			}

			if ($deposit_amount) {
				$query_upsert .= ", `deposit_amount` = " . $deposit_amount;
			}

			$query_upsert .= " WHERE `order_id` = " . $order_id;
		}

		$this->db->query($query_upsert);
	}

	public function getLookupByOrderId($order_id) {
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "c8UMbuNcJ4_lookup` WHERE `order_id` = " . $order_id);
	}
	public function getGlobalSelectedPlans() {
		$all_plans     = $this->getAllPlans();
		$display_plans = $this->config->get('payment_financePlugin_planselection');

		if ($display_plans == 'all' || empty($display_plans)) {
			return $all_plans;
		}

		$selected_plans = $this->config->get('payment_financePlugin_plans_selected');
		if (!$selected_plans) {
			return array();
		}

		$plans = array();
		foreach ($all_plans as $plan) {
			if (in_array($plan->id, $selected_plans)) {
				$plans[] = $plan;
			}
		}

		return $plans;
	}

	public function getAllPlans() {
		if ($plans = $this->cache->get(self::CACHE_KEY_PLANS)) {
			// OpenCart 2.1 decodes json objects to associative arrays so we
			// need to make sure we're getting a list of simple objects back.
			$plans = array_map(function ($plan) {
				return (object)$plan;
			}, $plans);

			return $plans;
		}

		if(is_null($this->sdk)){
			$api_key = $this->config->get('payment_financePlugin_api_key');
			if (!$api_key) {
				throw new Exception("No api-key defined");
			}
			$this->instantiateSDK($api_key);
		}

		$requestOptions = new ApiRequestOptions();

		try {
			$response = $this->sdk->getAllPlans($requestOptions);

			$plans = $response->getResources();
			// OpenCart 2.1 switched to json for their file storage cache, so
			// we need to convert to a simple object.
			$plans_plain = array();
			foreach ($plans as $plan) {
				$plan_copy = new stdClass();
				$plan_copy->id = $plan->id;
				$plan_copy->text = $plan->description;
				$plan_copy->country = $plan->country->name;
				$plan_copy->min_amount = $plan->credit_amount->minimum_amount;
				$plan_copy->min_deposit = $plan->deposit->minimum_percentage;
				$plan_copy->max_deposit = $plan->deposit->maximum_percentage;
				$plan_copy->interest_rate = $plan->interest_rate_percentage;
				$plan_copy->deferral_period = $plan->deferral_period_months;
				$plan_copy->agreement_duration = $plan->agreement_duration_months;

				$plans_plain[] = $plan_copy;
			}
			$this->cache->set(self::CACHE_KEY_PLANS, $plans_plain);

			return $plans_plain;
		} catch (MerchantApiBadResponseException $e) {
			throw new Exception($e->getMessage());
		}
	}

	public function getCartPlans($cart)	{
		$plans = array();
		$products = $cart->getProducts();
		foreach ($products as $product) {
			$product_plans = $this->getProductPlans($product['product_id']);
			if ($product_plans) {
				$plans = array_merge($plans, $product_plans);
			}
		}

		return $plans;
	}

	public function getPlans($default_plans) {
		if ($default_plans) {
			$plans = $this->getGlobalSelectedPlans();
		} else {
			$plans = $this->getAllPlans();
		}

		return $plans;
	}

	public function getOrderTotals() {
		$totals = array();
		$taxes = $this->cart->getTaxes();
		$total = 0;

		// Because __call can not keep var references so we put them into an array.
		$total_data = array(
			'totals' => &$totals,
			'taxes'  => &$taxes,
			'total'  => &$total
		);

		$this->load->model('setting/extension');

		$sort_order = array();

		$results = $this->model_setting_extension->getExtensions('total');

		foreach ($results as $key => $value) {
			$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
		}

		array_multisort($sort_order, SORT_ASC, $results);

		foreach ($results as $result) {
			if ($this->config->get('total_' . $result['code'] . '_status')) {
				$this->load->model('extension/total/' . $result['code']);

				// We have to put the totals in an array so that they pass by reference.
				$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
			}
		}

		$sort_order = array();

		foreach ($totals as $key => $value) {
			$sort_order[$key] = $value['sort_order'];
		}

		array_multisort($sort_order, SORT_ASC, $totals);

		return array($total, $totals);
	}

	public function getProductPlans($product_id) {
		$this->load->model('catalog/product');

		$product_info       = $this->model_catalog_product->getProduct($product_id);
		$settings           = $this->getProductSettings($product_id);
		$product_selection  = $this->config->get('payment_financePlugin_productselection');
		$financePlugin_categories  = $this->config->get('payment_financePlugin_categories');
		$price_threshold    = $this->config->get('payment_financePlugin_price_threshold');

		if ($financePlugin_categories) {
			$product_categories = $this->model_catalog_product->getCategories($product_id);

			$all_categories = array();
			foreach ($product_categories as $product_category) {
				$all_categories[] = $product_category['category_id'];
			}
			$category_matches = array_intersect($all_categories, $financePlugin_categories);

			if (!$category_matches) {
				return null;
			}
		}

		if (empty($settings)) {
			$settings = array(
				'display' => 'default',
				'plans'   => '',
			);
		}

		if ($product_selection == 'selected' && $settings['display'] == 'custom' && empty($settings['plans'])) {
			return null;
		}

		$price = 0;
		if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
			$base_price = !empty($product_info['special']) ? $product_info['special'] : $product_info['price'];
			$price = $this->tax->calculate($base_price, $product_info['tax_class_id'], $this->config->get('config_tax'));
		}

		if ($product_selection == 'threshold' && !empty($price_threshold) && $price < $price_threshold) {
			return null;
		}

		if ($settings['display'] == 'default') {
			$plans = $this->getPlans(true);
			return $plans;
		}

		// If the product has non-default plans, fetch all of them.
		$available_plans = $this->getPlans(false);
		$selected_plans  = explode(',', $settings['plans']);

		$plans = array();
		foreach ($available_plans as $plan) {
			if (in_array($plan->id, $selected_plans)) {
				$plans[] = $plan;
			}
		}

		if (empty($plans)) {
			return null;
		}

		return $plans;
	}

	public function apply($application){
		if (is_null($this->sdk)) {
			$api_key = $this->config->get('payment_financePlugin_api_key');
			if (!$api_key) {
				throw new Exception("No api-key defined");
			}
			$this->instantiateSDK($api_key);
		}

		$application = (new Application())
			->withCountryId($application['country'])
			->withCurrencyId($application['currency'])
			->withLanguageId($application['language'])
			->withFinancePlanId($application['plan'])
			->withApplicants($application['customers'])
			->withOrderItems($application['products'])
			->withDepositPercentage($application['deposit_percentage'])
			->withFinalisationRequired($application['finalisation_required'])
			->withMerchantReference($application['merchant_reference'])
			->withUrls($application['urls'])
			->withMetadata($application['metadata']);

		$response = $this->sdk->applications()->createApplication($application);

		$applicationResponseBody = $response->getBody()->getContents();

		return $applicationResponseBody;
	}

	public function getCustomer(){
		$address = $this->session->data['payment_address'];
		$billingAddress = [
			"postcode" => $address['postcode'],
			"street" => $address['address_1'],
			"town" => $address['city']
		];

		if(isset($this->session->data['shipping_address'])) {
			$address = $this->session->data['shipping_address'];
			$shippingAddress = [
				"postcode" => $address['postcode'],
				"street" => $address['address_1'],
				"town" => $address['city']
			];
		} else {
			$shippingAddress = $billingAddress;
		}

		if ($this->customer->isLogged()) {
			$this->load->model('account/customer');
			$customer_info = $this->model_account_customer->getCustomer($this->customer->getId());
		} elseif (isset($this->session->data['guest'])) {
			$customer_info = $this->session->data['guest'];
		}

		$customer = [
			"firstName" => $customer_info['firstname'],
			"lastName" => $customer_info['lastname'],
			"email" => $customer_info['email'],
			"phoneNumber" => $customer_info['telephone'],
			"addresses" => [
				$billingAddress
			],
			"shippingAddress" => $shippingAddress
		];
		return $customer;
	}

	public function getProducts(){
		$products = array();
		foreach ($this->cart->getProducts() as $product) {
			$products[] = array(
				'type' => 'product',
				'name' => $product['name'],
				'quantity' => intval($product['quantity']),
				'price' => ($product['price']*100),
			);
		}

		list($total, $totals) = $this->getOrderTotals();

		$sub_total = $total;
		$cart_total = $this->cart->getSubTotal();
		$shiphandle = $sub_total - $cart_total;

		$products[] = array(
			'type' => 'product',
			'name' => 'Shipping & Handling',
			'quantity' => 1,
			'price' => ($shiphandle*100),
		);
		return $products;
	}

}
