<?php


require_once DIR_SYSTEM . '/library/divido/Divido.php';

class ModelPaymentDivido extends Model
{
    const CACHE_KEY_PLANS = 'divido_plans';

    private $api_key;

    public function __construct ($registry)
    {
        parent::__construct($registry);

        $this->api_key = $this->config->get('divido_api_key');

        if ($this->api_key) {
            Divido::setMerchant($this->api_key);
        }

    }

    public function getMethod ($payment_address, $total)
    {
        $method_data = array(
            'code'       => 'divido',
            'title'      => $this->config->get('divido_title'),
            'terms'      => '',
            'sort_order' => $this->config->get('divido_sort_order')
        );

        return $method_data;
    }

    public function getProductSettings ($product_id)
    {
        $query = sprintf("
            select display, plans
            from %sproduct_divido
            where product_id = %s
            ", 
            DB_PREFIX,
            $this->db->escape($product_id)
        );

        $result = $this->db->query($query);

        return $result->row;
    }

    public function isEnabled ()
    {
        $api_key = $this->config->get('divido_api_key');
        $enabled = $this->config->get('divido_status');

        return !empty($api_key) && $enabled == 1;
    }

    public function getGlobalSelectedPlans ()
    {
        $all_plans     = $this->getAllPlans();
        $display_plans = $this->config->get('divido_planselection');

        if ($display_plans == 'all' || empty($display_plans)) {
            return $all_plans;
        }
        
        $selected_plans = $this->config->get('divido_plans_selected');
        if (! $selected_plans) {
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

    public function getAllPlans ()
    {
        if ($plans = $this->cache->get(self::CACHE_KEY_PLANS)) {
            // OpenCart 2.1 decodes json objects to associative arrays so we
            // need to make sure we're getting a list of simple objects back.
            $plans = array_map(function ($plan) { return (object) $plan; }, $plans);

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
            $plan_copy = new stdClass();
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

    public function getCartPlans ($cart)
    {
        $plans    = array();
        $products = $cart->getProducts();
        foreach ($products as $product) {
            $product_plans = $this->getProductPlans($product['product_id']);
            if ($product_plans) {
                $plans = array_merge($plans, $product_plans);
            }
        }

        return $plans;
    }

    public function getPlans ($default_plans)
    {
        if ($default_plans) {
            $plans = $this->getGlobalSelectedPlans();
        } else {
            $plans = $this->getAllPlans();
        }

        return $plans;
    }

    public function getOrderTotals ()
    {
        $this->load->model('extension/extension');
        $results    = $this->model_extension_extension->getExtensions('total');
        $sort_order = array();
        foreach ($results as $key => $value) {
            $sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
        }

        array_multisort($sort_order, SORT_ASC, $results);

        $total  = 0;
        $taxes  = $this->cart->getTaxes();
        $totals = array();
        foreach ($results as $result) {
            if ($this->config->get($result['code'] . '_status')) {
                $this->load->model('total/' . $result['code']);
                $this->{'model_total_' . $result['code']}->getTotal($totals, $total, $taxes);
            }
        }

        return array($total, $totals);
    }

    public function getProductPlans ($product_id)
    {
        $settings          = $this->getProductSettings($product_id);
        $product_selection = $this->config->get('divido_productselection');

        if (empty($settings)) {
            $settings = array(
                'display' => 'default',
                'plans'   => '',
            );
        }

        if ($product_selection == 'selected' && $settings['display'] == 'custom' && empty($settings['plans'])) {
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

    public function isApplicable ()
    {
        $country = $this->session->data['payment_address']['iso_code_2'];
        if ($country != 'GB') {
            return false;
        }

        $plans          = $this->model_payment_divido->getCartPlans($this->cart);
        list($subtotal) = $this->getOrderTotals();
        foreach ($plans as $plan) {
            if ($plan->min_amount <= $subtotal) {
                return true;
            }
        }

        return false;
    }
}
