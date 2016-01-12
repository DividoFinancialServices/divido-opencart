<?php

require_once DIR_SYSTEM . '/library/divido/Divido.php';

class ModelPaymentDivido extends Model
{
    const CACHE_KEY_PLANS = 'divido_plans';

    public function getSettings ($product_id)
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

    public function editSettings ($product_id, $form)
    {
        $display = $form['divido_display'];
        $plans   = $form['divido_plans'];
        $plans   = implode(',', $plans);
        $query   = sprintf("
            replace into %sproduct_divido (product_id, display, plans)
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
        $all_plans = $this->getAllPlans();
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
        $api_key = $this->config->get('divido_api_key');
        if (!$api_key) {
            throw new Exception("No Divido api-key defined");
        }

        Divido::setMerchant($api_key);

        $plans = array();
        if (! $plans = $this->cache->get(self::CACHE_KEY_PLANS)) {
            $response = Divido_Finances::all();
            if ($response->status != 'ok') {
                throw new Exception("Can't get list of finance plans from Divido!");
            }

            $plans = $response->finances;

            $this->cache->set(self::CACHE_KEY_PLANS, $plans);
        }

        return $plans;
    }
}
