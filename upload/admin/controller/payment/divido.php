<?php

require_once DIR_SYSTEM . '/library/divido/Divido.php';

class ControllerPaymentDivido extends Controller
{
	private $tpldata = [];

	public function __construct ($registry)
	{
		parent::__construct($registry);

		$this->load->language('payment/divido');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');
		$this->load->model('localisation/order_status');
		$this->load->model('payment/divido');

		$this->setStrings();
		$this->setBreadcrumbs();

		$this->cache = new Cache('file');

		$this->tpldata['header']      = $this->load->controller('common/header');
		$this->tpldata['footer']      = $this->load->controller('common/footer');
		$this->tpldata['column_left'] = $this->load->controller('common/column_left');
	}

	public function index ()
	{
		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
			$this->model_setting_setting->editSetting('divido', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('payment/divido', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$this->setData();

		$template = 'payment/divido.tpl';

		$output = $this->load->view($template, $this->tpldata);
		$this->response->setOutput($output);
	}

	protected function setData ()
	{
		$this->tpldata['divido_api_key']          = $this->getVal('divido_api_key');
		$this->tpldata['divido_order_status_id']  = $this->getVal('divido_order_status_id');
		$this->tpldata['divido_status']           = $this->getVal('divido_status');
		$this->tpldata['divido_sort_order']       = $this->getVal('divido_sort_order');
		$this->tpldata['divido_title']            = $this->getVal('divido_title');
		$this->tpldata['divido_description']      = $this->getVal('divido_description');
		$this->tpldata['divido_prependtoprice']   = $this->getVal('divido_prependtoprice');
		$this->tpldata['divido_calc_layout']      = $this->getVal('divido_calc_layout');
		$this->tpldata['divido_productselection'] = $this->getVal('divido_productselection');
		$this->tpldata['divido_price_threshold']  = $this->getVal('divido_price_threshold');
		$this->tpldata['divido_planselection']    = $this->getVal('divido_planselection');
		$this->tpldata['divido_plans_selected']   = $this->getVal('divido_plans_selected') ? : array();
		$this->tpldata['divido_exclusive']        = $this->getVal('divido_exclusive');

		try {
			$this->tpldata['divido_plans'] = $this->model_payment_divido->getAllPlans();
		} catch (Exception $e) {
			$this->log->write($e->getMessage());
			$this->tpldata['divido_plans'] = array();
		}
	}

	protected function setBreadcrumbs ()
	{
		$this->tpldata['breadcrumbs'] = array(
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
		$this->tpldata['heading_title']           = $this->language->get('heading_title');
		$this->tpldata['button_save']             = $this->language->get('button_save');
		$this->tpldata['button_cancel']           = $this->language->get('button_cancel');
		$this->tpldata['text_edit']               = $this->language->get('text_edit');
		$this->tpldata['text_enabled']            = $this->language->get('text_enabled');
		$this->tpldata['text_disabled']           = $this->language->get('text_disabled');
		$this->tpldata['entry_order_status']      = $this->language->get('entry_order_status');
		$this->tpldata['entry_status']            = $this->language->get('entry_status');
		$this->tpldata['entry_sort_order']        = $this->language->get('entry_sort_order');
		$this->tpldata['entry_api_key']           = $this->language->get('entry_api_key');
		$this->tpldata['entry_title']             = $this->language->get('entry_title');
		$this->tpldata['entry_description']       = $this->language->get('entry_description');
		$this->tpldata['entry_prependtoprice']    = $this->language->get('entry_prependtoprice');
		$this->tpldata['entry_calc_layout']       = $this->language->get('entry_calc_layout');
		$this->tpldata['entry_productselection']  = $this->language->get('entry_productselection');
		$this->tpldata['entry_planselection']     = $this->language->get('entry_planselection');
		$this->tpldata['entry_planlist']          = $this->language->get('entry_planlist');
		$this->tpldata['entry_price_threshold']   = $this->language->get('entry_price_threshold');
		$this->tpldata['entry_exclusive']         = $this->language->get('entry_exclusive');

		$this->tpldata['entry_plans_options']     = array(
			'all'      => $this->language->get('entry_plans_options_all'),
			'selected' => $this->language->get('entry_plans_options_selected'),
		);

		$this->tpldata['entry_products_options']  = array(
			'all'       => $this->language->get('entry_products_options_all'),
			'selected'  => $this->language->get('entry_products_options_selected'),
			'threshold' => $this->language->get('entry_products_options_threshold'),
		);

		$this->tpldata['entry_calc_options'] = array(
			'default' => $this->language->get('entry_calc_layout_default'),
			'custom'  => $this->language->get('entry_calc_layout_custom'),
		);

		$this->tpldata['entry_exclusive_options'] = array(
			'0' => 'No',
			'1' => 'Yes',
		);

		$this->tpldata['action']                  = $this->url->link('payment/divido', 'token=' . $this->session->data['token'], 'SSL');
		$this->tpldata['cancel']                  = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');
		$this->tpldata['error_warning']           = isset($this->error['warning']) ? $this->error['warning'] : '';
		$this->tpldata['order_statuses']          = $this->model_localisation_order_status->getOrderStatuses();
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
