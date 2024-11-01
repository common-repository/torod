<?php
require_once(TOROD_PLUGIN_PATH . 'inc/torod.php');
use Torod\torod;

class ajaxyk
{
	public function __construct()
	{
		add_action("wp_ajax_torod_disconnect", [$this, "torod_disconnect"]);
		add_action("wp_ajax_nopriv_torod_disconnect", [$this, "torod_disconnect"]);
		add_action("wp_ajax_torod_status_reg", [$this, "torod_status_reg"]);
		add_action("wp_ajax_nopriv_torod_status_reg", [$this, "torod_status_reg"]);
		add_action("wp_ajax_get_torod_status_reg", [$this, "get_torod_status_reg"]);
		add_action("wp_ajax_nopriv_get_torod_status_reg", [$this, "get_torod_status_reg"]);
		add_action("wp_ajax_getPaymentMethod", [$this, "getPaymentMethod"]);
		add_action("wp_ajax_nopriv_getPaymentMethod", [$this, "getPaymentMethod"]);
		add_action("wp_ajax_getAllCity", [$this, "getAllCity"]);
		add_action("wp_ajax_nopriv_getAllCity", [$this, "getAllCity"]);
		add_action('wp_ajax_send_order_to_api', [$this, 'send_order_to_api']);
		add_action('wp_ajax_nopriv_send_order_to_api', [$this, 'send_order_to_api']);
		add_action('wp_ajax_send_multiple_order_to_api', [$this, 'send_multiple_order_to_api']);
		add_action('wp_ajax_nopriv_send_multiple_order_to_api', [$this, 'send_multiple_order_to_api']);
		add_action('wp_ajax_updateDbFromsetting', [$this, 'updateDbFromsetting']);
		add_action('wp_ajax_nopriv_updateDbFromsetting', [$this, 'updateDbFromsetting']);
		add_action("wp_ajax_torod_OrderMappingStatus", [$this, "torod_OrderMappingStatus"]);
		add_action("wp_ajax_nopriv_torod_OrderMappingStatus", [$this, "torod_OrderMappingStatus"]);
	}
	/*
	 * Torod payment method rge
	 */
	public function send_order_to_api()
	{
		$torod = new torod;
		$order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
		if ($order_id === 0) {
			wp_send_json_error(['message' => 'Invalid order ID']);
		}
		$order = wc_get_order($order_id);
		$sendtorod = $torod->order_create_torod($order, $order_id, true);
		$result['status'] = $sendtorod['status'];
		$result['message'] = $sendtorod['message'];
		if ($sendtorod['status']) {
			wp_send_json_success($result);
		} else {
			wp_send_json_error($result);
		}
	}
	/* */
	public function send_multiple_order_to_api()
	{
		$torod = new torod;
		$order_log_data = $torod->getAllOrderLog();
		$result = array();
		if(!empty($order_log_data)) {
			foreach($order_log_data as $key => $value) { 
				$order = wc_get_order($value['order_id']);
				$sendtorod = $torod->order_create_torod($order, $value['order_id'], true);
				$result[$key]['id'] = $value['order_id'];
				$result[$key]['status'] = $sendtorod['status'];
				$result[$key]['message'] = $sendtorod['message'];
			}
			wp_send_json_success($result);
		} else {
			wp_send_json_error(['message' => 'There is no order log found']);
		}
		
	}
	public function getPaymentMethod()
	{
		$pmethods = [];
		$selected_methods = get_option("torod_payment_gateway");
		$payment_methods = WC()->payment_gateways->payment_gateways;
		foreach ($payment_methods as $payment_method) {
			$is_selected = '';
			if(!empty($selected_methods)){
				$is_selected = in_array($payment_method->id, $selected_methods);
			}
			$pmethods[] = [$payment_method->id, $payment_method->title, $is_selected];
		}
		wp_send_json($pmethods);
	}
	/*
	 * Register select payment gateway
	 *
	 * select status register ajax callback
	 * */
	public function torod_status_reg()
	{
		if (!wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
			die('Busted!');
		}
		$data = isset($_POST['data']) ?
			array_map('sanitize_text_field', (array) $_POST['data']) : [];
		$statusradio = sanitize_text_field($_POST['radiobtn']);
		$paymentGateway = isset($_POST['paymentgt']) ?
			array_map('sanitize_text_field', (array) $_POST['paymentgt']) : [];
		if (!empty($statusradio)) {
			update_option("status_radio", $statusradio);
		}
		if (!empty($paymentGateway)) {
			update_option('torod_payment_gateway', $paymentGateway);
		}
		if (!empty($data) and isset($data)):
			update_option('torod_status_settings', $data);
			update_option('torod_payment_gateway', $paymentGateway);
			$result['type'] = "success";
			wp_send_json($result);
		else:
			$result['type'] = "error";
			wp_send_json($result);
		endif;
		die();
	}
	/*
	 * discconect torod user
	 */
	public function torod_disconnect()
	{
		check_ajax_referer('ajax-nonce', 'nonce', true);
		$torod = new torod;
		$veri = get_option("torod_wp_all_settings");
		if (!empty($veri)) {
			if (!isset($veri['app_id']) || !isset($veri['user_id'])) {
				$data = [];
				update_option('torod_wp_all_settings', $data);
				update_option('torod_token', $data);
				$result['type'] = "success";
			} else {
				$dis = $torod->torod_disconnect(sanitize_text_field($veri['app_id']), sanitize_text_field($veri['user_id']));
				if ($dis == 1) {
					$data = [];
					update_option('torod_wp_all_settings', $data);
					update_option('torod_token', $data);
					$result['type'] = "success";
				} else {
					$result['type'] = "error";
				}
			}
		} else {
			$result['type'] = "error";
		}
		wp_send_json($result);
		die();
	}
	/*
	 * selec2 get data order status and selected status
	 */
	public function get_torod_status_reg()
	{
		$veri = get_option("torod_status_settings");
		$statuses = wc_get_order_statuses();
		$array = [];
		foreach ($statuses as $a => $b) {
			if (!empty($veri)) {
				if (in_array($a, $veri)) {
					$array[] = [$a, $b, true];
				} else {
					$array[] = [$a, $b, false];
				}
			} else {
				$array[] = [$a, $b, false];
			}
		}
		$array = json_encode($array);
		echo $array;
		die();
	}
	/*
	 * get all city value from api
	 */
	public function getAllCity()
	{
		$torod = new torod;
		$region_name = sanitize_text_field($_POST['region_id']);
		$veriler = $torod->getAllCity($region_name);
		$array = json_encode($veriler);
		echo $array;
		die();
	}
	/*
	 * Torod Admin update the database
	 */
	public function updateDbFromsetting()
	{
		check_ajax_referer('ajax-nonce', 'nonce', true);
		$torod = new \Torod\torod();
		$updatenew = $torod->updateDataFromApi();
		if ($updatenew) {
			$result['type'] = "success";
		} else {
			$result['type'] = "error";
		}
		wp_send_json($result);
		die();
	}

	public function torod_OrderMappingStatus()
	{
		$omappingstatus = [];
		$selected_omapping = get_option("torod_ordermappingstatus");
		$omappingstatus = $_POST['data'];
		$result = [];
		if(!empty($omappingstatus)){
			if($selected_omapping == ''){
				add_option('torod_ordermappingstatus', $omappingstatus);
				$result['type'] = "success";
			}else{
				update_option('torod_ordermappingstatus', $omappingstatus);
				$result['type'] = "success";
			}
		}else{
			$result['type'] = "error";
		}
		wp_send_json($result);
		exit();
	}

}
new ajaxyk;