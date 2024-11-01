<?php
use Torod\torod;

class wc_torod
{
	public function __construct()
	{
		add_action('woocommerce_checkout_update_order_meta', [$this, 'save_weight_torod']);
		add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'add_custom_order_button']);
		add_action('admin_footer', [$this, 'add_custom_order_button_script']);
		add_filter('query_vars', [$this, 'custom_query_vars'], 10, 1);
		add_action('template_redirect', [$this, 'custom_page_template'], 10, 0);
		add_action('add_meta_boxes', [$this, 'torod_order_shipment_pdf'], 1, 0);
	}
	function torod_order_shipment_pdf()
	{
		add_meta_box('torod_order_shipment_pdf', __('Shipment PDF', 'torod'), [$this, 'torod_order_shipment_pdf_callback'], 'shop_order', 'side', 'high');
	}
	function torod_order_shipment_pdf_callback($post)
	{
		echo '<div style="text-align:center; padding:15px;">';
		$tracking_id = get_post_meta($post->ID, 'tracking_id', true);
		$aws_label_url = get_post_meta($post->ID, 'aws_label', true);
		$torod_description = get_post_meta($post->ID, 'torod_description', true);
		$torod_shipment_tracking_url = get_post_meta($post->ID, 'torod_shipment_tracking_url', true);
		if (!empty($tracking_id)) {
			$torod = new torod;
			//$aws_label_url = $torod->get_aws_label($tracking_id);
			if ($aws_label_url) {
				echo '<a href="' . esc_url($aws_label_url) . '" download="' . esc_attr($tracking_id) . '.pdf" target="_blank" class="button download_pdf button-primary">Download PDF</a>';
			} else {
				echo '<button disabled class="button download_pdf button-primary">Download PDF</button>';
			}
			if ($tracking_id) {
				echo '<p><strong>' . __('Tracking ID:', 'torod') . '</strong> ' . esc_html($tracking_id) . '</p>';
			}
			if ($torod_description) {
				echo '<p><strong>' . __('Status:', 'torod') . '</strong> ' . esc_html($torod_description) . '</p>';
			}
			if($torod_shipment_tracking_url) {
				echo '<a href="' . esc_url($torod_shipment_tracking_url) . '" target="_blank" class="button button-primary">Track shipment</a>';
			} else {
				echo '<button disabled class="button button-primary">Track shipment</button>';
			}
		} else {
			echo '<button disabled class="button download_pdf button-primary">Download PDF</button>';
		}
		echo '</div>';
	}
	function custom_query_vars($vars)
	{
		$vars[] = 'custom_page';
		return $vars;
	}
	function custom_page_template()
	{
		global $wp_query;
		if (isset($wp_query->query_vars['custom_page'])) {
			if ($wp_query->query_vars['custom_page'] == 'torod_shipment_webhook') {
				header('Content-Type: application/json; charset=utf-8');
				// Request body content
				$request_body = file_get_contents('php://input');
				$request_data = json_decode($request_body, true);
				set_transient('torod_webhook_test', $request_data, 1 * HOUR_IN_SECONDS);
				// Read client_secret_key from request data
				$client_secret_key = isset($request_data['client_secret_key']) ? $request_data['client_secret_key'] : null;
				$veri = get_option("torod_wp_all_settings");
				$client_secret = $veri['client_secret'];
				// Check if client_secret_key matches the stored client_secret
				if ($client_secret_key === $client_secret) {
					// Read order_id from request data
					$order_id = isset($request_data['order_id']) ? $request_data['order_id'] : null;
					if ($order_id) {
						// Get order by ID
						$order = wc_get_order($order_id);
						if ($order) {
							$status = $request_data['status'];
							$tracking_id = isset($request_data['tracking_id']) ? $request_data['tracking_id'] : null;
							$aws_label_url = isset($request_data['aws_label']) ? $request_data['aws_label'] : null;
							$torod_description = isset($request_data['torod_description']) ? $request_data['torod_description'] : null;
							$torod_shipment_tracking_url = isset($request_data['torod_shipment_tracking_url']) ? $request_data['torod_shipment_tracking_url'] : null;
							// Save tracking_id as custom meta
							if ($tracking_id) {
								$order->update_meta_data('tracking_id', $tracking_id);
								$order->save_meta_data();
							}
							if ($aws_label_url) {
								$order->update_meta_data('aws_label', $aws_label_url);
								$order->save_meta_data();
							}
							if ($torod_description) {
								$order->update_meta_data('torod_description', $torod_description);
								$order->save_meta_data();
							}
							if ($torod_shipment_tracking_url) {
								$order->update_meta_data('torod_shipment_tracking_url', $torod_shipment_tracking_url);
								$order->save_meta_data();
							}
							$status_changed = false;
							$order_note = '';
							$selected_omapping = get_option("torod_ordermappingstatus");
							if(!empty($selected_omapping)){
								$selected_omapping = get_option("torod_ordermappingstatus");
							}else{
								$selected_omapping = [];
							}

							switch ($status) {
								case 'Created':
									$ostatus = ($selected_omapping['created'] != '') ? $selected_omapping['created'] : 'processing';
									$order_note = __('Order status changed to '.$ostatus.' (Torod).', 'torod');
									$order->update_status(str_replace(' ', '-', strtolower($ostatus)));
									$status_changed = true;
									break;
								case 'Shipped':
									$ostatus = ($selected_omapping['Shipped'] != '') ? $selected_omapping['Shipped'] : 'processing';
									$order_note = __('Order status changed to '.$ostatus.' (Torod).', 'torod');
									$order->update_status(str_replace(' ', '-', strtolower($ostatus)));
									$status_changed = true;
									break;
								case 'Delivered':
									$ostatus = ($selected_omapping['Delivered'] != '') ? $selected_omapping['Delivered'] : 'completed';
									$order_note = __('Order status changed to '.$ostatus.' (Torod).', 'torod');
									$order->update_status(str_replace(' ', '-', strtolower($ostatus)));
									$status_changed = true;
									break;
								case 'Cancelled':
									$ostatus = ($selected_omapping['cancelled'] != '') ? $selected_omapping['cancelled'] : 'cancelled';
									$order_note = __('Order status changed to '.$ostatus.' (Torod).', 'torod');
									$order->update_status(str_replace(' ', '-', strtolower($ostatus)));
									$status_changed = true;
									break;
								case 'Failed':
									$ostatus = ($selected_omapping['failed'] != '') ? $selected_omapping['failed'] : 'cancelled';
									$order_note = __('Order status changed to '.$ostatus.' (Torod).', 'torod');
									$order->update_status(str_replace(' ', '-', strtolower($ostatus)));
									$status_changed = true;
									break;
								case 'Lost':
									$ostatus = ($selected_omapping['lost'] != '') ? $selected_omapping['lost'] : 'cancelled';
									$order_note = __('Order status changed to '.$ostatus.' (Torod).', 'torod');
									$order->update_status(str_replace(' ', '-', strtolower($ostatus)));
									$status_changed = true;
									break;
								case 'Damage':
									$ostatus = ($selected_omapping['damage'] != '') ? $selected_omapping['damage'] : 'cancelled';
									$order_note = __('Order status changed to '.$ostatus.' (Torod).', 'torod');
									$order->update_status(str_replace(' ', '-', strtolower($ostatus)));
									$status_changed = true;
									break;
								case 'RTO':
									$ostatus = ($selected_omapping['rto'] != '') ? $selected_omapping['rto'] : 'cancelled';
									$order_note = __('Order status changed to '.$ostatus.' (Torod).', 'torod');
									$order->update_status(str_replace(' ', '-', strtolower($ostatus)));
									$status_changed = true;
									break;
							}
							// Add order note if status changed
							if ($status_changed && !empty($order_note)) {
								$order->add_order_note($order_note);
							}
							echo json_encode(['success' => true, 'message' => 'Webhook processed successfully.', 'order_id' => $order_id]);
						} else {
							echo json_encode(['success' => false, 'message' => 'Order not found.']);
						}
					} else {
						echo json_encode(['success' => false, 'message' => 'order_id is required.']);
					}
				} else {
					echo json_encode(['success' => false, 'message' => 'client_secret_key does not match.']);
				}
				exit;
			}
		}
	}
	public function add_custom_order_button_script()
	{
		global $pagenow;
		if (is_admin() && $pagenow === 'post.php' && get_post_type() === 'shop_order' || (isset($_GET['page']) && $_GET['page'] == 'torod-order-log')) {
			$order_id = get_the_ID();
			echo '<script type="text/javascript">
			jQuery(document).ready(function($){
				
				$("#custom-order-button, .custom-sync-order-button").click(function(){
					var order_id = $(this).data("order-id");
					$.ajax({
						url: torod.ajax_url,
						type: "POST",
						dataType:"json",
						data: {
							action: "send_order_to_api",
							order_id: order_id,
						},
						beforeSend: function () {
							jQuery(".lodinggif-"+order_id).show();
						},
						success: function(response){
							jQuery(".lodinggif-"+order_id).hide();
							if(response.data.status == 1){
								alert("Order Created on Torod Successfully");
								location.reload();
							}
							if(response.data.status == 0){
								alert(response.data.message);
							}
						},
						error: function(error){
							console.log(error);
						}
					});
				});
				$(".order-log-syncall").click(function(){
					$.ajax({
						url: torod.ajax_url,
						type: "POST",
						dataType:"json",
						data: {
							action: "send_multiple_order_to_api"
						},
						beforeSend: function () {
							jQuery(".lodinggif-syncall").show();
						},
						success: function(response){
							jQuery(".lodinggif-syncall").hide();
							console.log(response);
							var errors_msg = "";
							var success_msg = "";
							$.each(response.data, function(k, v) {
							    if(v.status == 0){
							    	errors_msg += v.id + " " + v.message + "\n";
							    }else{
							    	success_msg += "<h5>" + v.id + "=" + v.message + "</h5>";
							    }
							});
							if(errors_msg != "" ){
								alert(errors_msg);
								location.reload();
							}else{
								alert("Orders Created on Torod Successfully");
								location.reload();
							}
						},
						error: function(error){
							console.log(error);
						}
					});
				});
			});
		</script>';
		}
	}
	public function add_custom_order_button($order)
	{
		$order_id = $order->get_id();
		$id = get_post_meta($order_id, "torod_order_id", true);
		if ($id > 0) {
			echo '<button type="button" class="button" disabled>Order Created on Torod</button>';
		} else {
			echo '<button type="button" class="button" id="custom-order-button" data-order-id="' . $order_id . '">Send to Torod</button>';
		}
	}
	public function save_weight_torod($order_id)
	{
		$weight = WC()->cart->get_cart_contents_weight();
		$unit = get_option('woocommerce_weight_unit');
		if (isset($weight)) {
			update_post_meta($order_id, '_cart_weight', $weight);
			update_post_meta($order_id, '_cart_weight_type', $unit);
		}
	}
}
new wc_torod;