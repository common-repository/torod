<?php

require_once(TOROD_PLUGIN_PATH . 'inc/torod.php');
require_once(TOROD_PLUGIN_PATH . 'inc/screen.php');
require_once(TOROD_PLUGIN_PATH . 'inc/ajaxyk.php');
require_once(TOROD_PLUGIN_PATH . 'inc/wc_torod.php');
require_once(TOROD_PLUGIN_PATH . 'inc/vendor/autoload.php');

use Torod\torod;

class init
{

	public function __construct()
	{

		add_action("woocommerce_thankyou", [$this, 'order_send_torod']);
		add_action("woocommerce_update_order", [$this, "order_update_torod"]);

	}

	public function order_send_torod($order_id)
	{

		$torod = new torod();
		$order = wc_get_order($order_id);
		$orid = $order_id;
		$create = $torod->order_create_torod($order, $orid);

	}

	public function order_update_torod($order_id)
	{

		$torod = new torod();
		$create = $torod->order_update_torod($order_id);
	}

}

new init();