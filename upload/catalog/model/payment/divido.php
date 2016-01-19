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

        $this->cache->set(self::CACHE_KEY_PLANS, $plans);

        return $plans;
    }
}
