<?php

require_once DIR_SYSTEM . '/library/divido/Divido.php';

class ModelPaymentDivido extends Model
{
	const CACHE_KEY_PLANS = 'divido_plans';

	public function getSettings ($product_id)
	{
		$query = sprintf("
			select display, plans
			from %sdivido_product
			where product_id = %s
			",
			DB_PREFIX,
			$this->db->escape($product_id)
		);

		$result = $this->db->query($query);
		return $result->row;
	}

	public function editSettings ($product_id, $form)
	{
		$display = $form['divido_display'];
		$plans   = $form['divido_plans'];
		$plans   = implode(',', $plans);
		$query   = sprintf("
			replace into %sdivido_product (product_id, display, plans)
				values (%s, '%s', '%s');
			",
			DB_PREFIX,
			$product_id,
			$display,
			$plans
		);

		$result = $this->db->query($query);
	}

	public function getGlobalSelectedPlans ()
	{
		$all_plans     = $this->getAllPlans();
		$display_plans = $this->config->get('divido_planselection');

		if ($display_plans == 'all' || empty($display_plans)) {
			return $all_plans;
		}

		$selected_plans = $this->config->get('divido_plans_selected');

		$plans = array();
		foreach ($all_plans as $plan) {
			if (in_array($plan->id, $selected_plans)) {
				$plans[] = $plan;
			}
		}

		return $plans;
	}

	public function getAllPlans ()
	{
		if ($plans = $this->cache->get(self::CACHE_KEY_PLANS)) {
			// OpenCart 2.1 decodes json objects to associative arrays so we
			// need to make sure we're getting a list of simple objects back.
			$plans = array_map(function ($plan) {
				return (object)$plan;
			}, $plans);

			return $plans;
		}

		$api_key = $this->config->get('divido_api_key');
		if (!$api_key) {
			throw new Exception("No Divido api-key defined");
		}

		Divido::setMerchant($api_key);

		$response = Divido_Finances::all();
		if ($response->status != 'ok') {
			throw new Exception("Can't get list of finance plans from Divido!");
		}

		$plans = $response->finances;

		// OpenCart 2.1 switched to json for their file storage cache, so
		// we need to convert to a simple object.
		$plans_plain = array();
		foreach ($plans as $plan) {
			$plan_copy                     = new stdClass();
			$plan_copy->id                 = $plan->id;
			$plan_copy->text               = $plan->text;
			$plan_copy->country            = $plan->country;
			$plan_copy->min_amount         = $plan->min_amount;
			$plan_copy->min_deposit        = $plan->min_deposit;
			$plan_copy->max_deposit        = $plan->max_deposit;
			$plan_copy->interest_rate      = $plan->interest_rate;
			$plan_copy->deferral_period    = $plan->deferral_period;
			$plan_copy->agreement_duration = $plan->agreement_duration;

			$plans_plain[] = $plan_copy;
		}

		$this->cache->set(self::CACHE_KEY_PLANS, $plans_plain);

		return $plans_plain;
	}

}
