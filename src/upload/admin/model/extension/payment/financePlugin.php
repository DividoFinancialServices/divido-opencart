<?php

use Divido\MerchantSDK\Client;
use Divido\MerchantSDK\Environment;
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

		return $sdk;
	}

	public function getAllPlans() {

		if(is_null($this->sdk)){
			$api_key = $this->config->get('payment_financePlugin_api_key');

			if (!$api_key) {
				throw new Exception("No Finance Plugin api-key defined");
			}

			$this->sdk = $this->instantiateSDK($api_key);
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

	public function getLookupByOrderId($order_id) {
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "c8UMbuNcJ4_lookup` WHERE `order_id` = " . (int)$order_id);
	}

	public function install() {
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "c8UMbuNcJ4_product` (
				`product_id` INT(11) NOT NULL,
				`display` CHAR(7) NOT NULL,
				`plans` text,
				PRIMARY KEY (`product_id`)
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "c8UMbuNcJ4_lookup` (
				`order_id` INT(11) NOT NULL,
				`salt` CHAR(64) NOT NULL,
				`proposal_id` CHAR(40),
				`application_id` CHAR(40),
				`deposit_amount` NUMERIC(6,2),
			  PRIMARY KEY (`order_id`)
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
	}

	public function uninstall() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "c8UMbuNcJ4_product`;");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "c8UMbuNcJ4_lookup`;");
	}
}
