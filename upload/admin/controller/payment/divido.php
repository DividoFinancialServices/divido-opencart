<?php

require_once DIR_SYSTEM . '/library/divido/Divido.php';

class ControllerPaymentDivido extends Controller
{
    const CACHE_KEY_PLANS = 'divido_plans';

    public function __construct ($registry)
    {
        parent::__construct($registry);

        $this->load->language('payment/divido');
        $this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');
		$this->load->model('localisation/order_status');

        $this->setStrings();
        $this->setBreadcrumbs();

        $this->cache = new Cache('file');

        $this->api_key = $this->getVal('divido_api_key');
        if ($this->api_key) {
            Divido::setMerchant($this->api_key);
        }
    }

    public function index ()
    {
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('divido', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->redirect($this->url->link('payment/divido', 'token=' . $this->session->data['token'], 'SSL'));
		}

        $this->setData();

        $this->template = 'payment/divido.tpl';
        $this->children = array('common/header', 'common/footer');

        $output      = $this->render(true);
        $compression = $this->config->get('config_compression');
        $this->response->setOutput($output, $compression);
    }

    protected function setData ()
    {

        $this->data['divido_api_key']          = $this->api_key;
        $this->data['divido_order_status_id']  = $this->getVal('divido_order_status_id');
        $this->data['divido_status']           = $this->getVal('divido_status');
        $this->data['divido_sort_order']       = $this->getVal('divido_sort_order');
        $this->data['divido_title']            = $this->getVal('divido_title');
        $this->data['divido_description']      = $this->getVal('divido_description');
        $this->data['divido_prependtoprice']   = $this->getVal('divido_prependtoprice');
        $this->data['divido_calc_layout']      = $this->getVal('divido_calc_layout');
        $this->data['divido_productselection'] = $this->getVal('divido_productselection');
        $this->data['divido_price_threshold']  = $this->getVal('divido_price_threshold');
        $this->data['divido_planselection']    = $this->getVal('divido_planselection');
        xdebug_break();
        $this->data['divido_plans_selected']   = $this->getVal('divido_plans_selected') ?: array();
        $this->data['divido_plans']            = $this->getPlans();
    }

    protected function getPlans ()
    {
        if (! $this->api_key) {
            return null;
        }

        if ($plans = $this->cache->get(self::CACHE_KEY_PLANS)) {
            return $plans;
        }

        $response = Divido_Finances::all();
        if ($response->status != 'ok') {
            $this->error['warning'] = "Can't get list of finance plans from Divido!";
            return null;
        }

        $plans = $response->finances;
        $this->cache->set(self::CACHE_KEY_PLANS, $plans);

        return $plans;
    }

    protected function setBreadcrumbs ()
    {
        $this->data['breadcrumbs'] = array(
            array(
                'text'      => $this->language->get('text_home'),
                'href'      =>  $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
                'separator' => false
            ),
            array(
                'text'      => $this->language->get('text_payment'),
                'href'      => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),       		
                'separator' => ' :: '
            ),
            array(
                'text'      => $this->language->get('heading_title'),
                'href'      => $this->url->link('payment/divido', 'token=' . $this->session->data['token'], 'SSL'),
                'separator' => ' :: '
            )
        );
    }

    protected function setStrings ()
    {
        $this->data['heading_title']           = $this->language->get('heading_title');
		$this->data['button_save']             = $this->language->get('button_save');
		$this->data['button_cancel']           = $this->language->get('button_cancel');
		$this->data['text_enabled']            = $this->language->get('text_enabled');
		$this->data['text_disabled']           = $this->language->get('text_disabled');
		$this->data['entry_order_status']      = $this->language->get('entry_order_status');
		$this->data['entry_status']            = $this->language->get('entry_status');
		$this->data['entry_sort_order']        = $this->language->get('entry_sort_order');
        $this->data['entry_api_key']           = $this->language->get('entry_api_key');
        $this->data['entry_title']             = $this->language->get('entry_title');
        $this->data['entry_description']       = $this->language->get('entry_description');
        $this->data['entry_prependtoprice']    = $this->language->get('entry_prependtoprice');
        $this->data['entry_calc_layout']  = $this->language->get('entry_calc_layout');
        $this->data['entry_productselection']  = $this->language->get('entry_productselection');
        $this->data['entry_planselection']     = $this->language->get('entry_planselection');
        $this->data['entry_planlist']          = $this->language->get('entry_planlist');
        $this->data['entry_price_threshold']    = $this->language->get('entry_price_threshold');

        $this->data['entry_plans_options']     = array(
            'all'      => $this->language->get('entry_plans_options_all'),
            'selected' => $this->language->get('entry_plans_options_selected'),
        );

        $this->data['entry_products_options']  = array(
            'all'       => $this->language->get('entry_products_options_all'),
            'selected'  => $this->language->get('entry_products_options_selected'),
            'threshold' => $this->language->get('entry_products_options_threshold'),
        );

        $this->data['entry_calc_options'] = array(
            'default' => $this->language->get('entry_calc_layout_default'),
            'custom'  => $this->language->get('entry_calc_layout_custom'),
        );

		$this->data['action']                  = $this->url->link('payment/divido', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['cancel']                  = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['error_warning']           = isset($this->error['warning']) ? $this->error['warning'] : '';
		$this->data['order_statuses']          = $this->model_localisation_order_status->getOrderStatuses();
    }

    protected function getVal ($key) {
        $post = $this->request->post;

        return !empty($post[$key]) ? $post[$key] : $this->config->get($key);
    }

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'payment/divido')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return (!$this->error);
	}

}
