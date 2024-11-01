<?php

/*
Plugin Name:Torod
Plugin URI: https://torod.co
Description: A web platform that enables you to compare shipping prices, print shipping labels, track orders, and manage returns from a single place. No Contracting, no coding required.
Version: 1.6
Author: Torod Holding LTD
Text Domain: torod
Domain Path: /languages/
*/
if (!defined('ABSPATH')) {
	exit;
}
define('TOROD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('TOROD_IMG_PATH', plugin_dir_path(__FILE__) . 'assets/img/');
define('TOROD_LOADING_IMG_URL', plugins_url('assets/img/loading.gif', __FILE__));
define('TOROD_ADMIN_URL', admin_url('admin.php?page=torodpage'));
define('TOROD_VERSION', '1.6');
require_once(TOROD_PLUGIN_PATH . 'inc/init.php');

$demo_account = get_option('torod_demo_account', 'yes');
$log_mode = get_option('torod_log_mode', 'enabled');

$torod_url = ($demo_account === 'yes') ? "https://demo.stage.torod.co" : "https://torod.co";
define('torodurl', $torod_url);

$torod_log_mode = ($log_mode === 'enabled') ? 1 : 0;
define('TOROD_LOGMODE', $torod_log_mode);

add_action('admin_enqueue_scripts', 'files_js');
function files_js()
{

	if (isset($GLOBALS['wc_states_places'])) {
		$wc_states_places_instance = $GLOBALS['wc_states_places'];
		$places = json_encode($wc_states_places_instance->get_places());
	} else {
		$places = '';
	}

	$dil = get_locale();
	// Select2 CSS dosyasını ekle
	wp_enqueue_style('select2-css', plugin_dir_url(__FILE__) . 'assets/css/select2.min.css', [], '1.0', 'all');
	// Select2 JavaScript dosyasını ekle
	wp_enqueue_script('select2-js', plugin_dir_url(__FILE__) . 'assets/js/select2.min.js', ['jquery'], '1.0', true);
	// Özel AJAX işlemleri için JavaScript dosyasını ekle
	wp_enqueue_script('ajax-script', plugin_dir_url(__FILE__) . 'assets/js/torod_script.js?' . time(), ['jquery'], '1.0', true);
	// AJAX işlemleri için yerel değişkenlerin ayarlanması
	wp_enqueue_style('torod-settings-style', plugin_dir_url(__FILE__) . 'assets/css/torod_style.css');
	wp_localize_script(
		'ajax-script',
		'torod',
		[
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('ajax-nonce'),
			'cities' => $places,
			'dlang' => $dil
		]
	);
}

function add_menu()
{

	$logo_svg_path = TOROD_IMG_PATH . 'torodlogo.svg';
	$logo_svg_content = wp_remote_get($logo_svg_path);
	add_menu_page(
		__('Torod', 'torod'),
		'Torod',
		'edit_pages',
		'torodpage',
		'torod_login',
		'data:image/svg+xml;base64,' . base64_encode(file_get_contents(TOROD_IMG_PATH . 'torodlogo.svg')),
		56
	);

	add_submenu_page(
		'torodpage',
		// Parent slug
		__('Settings', 'torod'),
		// Page title
		__('Settings', 'torod'),
		// Menu title
		'edit_pages',
		// Capability
		'torod-settings',
		// Menu slug
		'torod_settings_callback' // Callback function
	);
	/* Failed Order Log Sub Menu*/
	add_submenu_page(
		'torodpage',
		// Parent slug
		__('Order Log', 'torod'),
		// Page title
		__('Order Log', 'torod'),
		// Menu title
		'edit_pages',
		// Capability
		'torod-order-log',
		// Menu slug
		'torod_order_log_callback' // Callback function
	);
}

add_action('admin_menu', 'add_menu');

// Callback function for the Settings submenu
function torod_settings_callback()
{

	require_once __DIR__ . '/inc/torod_Settings.php';
	$settings = new torod_Settings();
	$settings->display_settings_page();
}
// Callback function for the Order Log submenu
function torod_order_log_callback()
{

	require_once __DIR__ . '/inc/torod_orderlog.php';
	$orderLog = new torod_OrderLog();
	$orderLog->display_orderlog_page();
}
/**
	* Display torod login page
	*/
function torod_login()
{

	$screen = new screen();
	$screen->firstscreen();
}

add_action("init", "deleteoption");
function deleteoption()
{

	if (isset($_GET['option_delete_torod'])) {
		$data = [];
		update_option('torod_wp_all_settings', $data);
	}
}

add_action('init', 'custom_rewrite_rule', 10, 0);

function custom_rewrite_rule()
{

	add_rewrite_rule('^Torod/shipment/webhook/?', 'index.php?custom_page=torod_shipment_webhook', 'top');
}

register_activation_hook(__FILE__, 'torod_plugin_activate');
function torod_plugin_activate()
{

	$torod = new Torod\torod();
	$torod->checkAndCreateTables();
	/* Add Plugin Verison in DB */
	$old_ver = get_option( 'torod_version', '0' );
	$new_ver = TOROD_VERSION;
	$requires_update = version_compare( $old_ver, $new_ver, '<' );

	add_option('torod_version', TOROD_VERSION);
	add_option('torod_wp_all_settings',[]);
	add_option('torod_token','');
	add_option('torod_status_settings',[]);
	add_option('torod_payment_gateway',[]);
	add_option('torod_log_mode','enabled');
	add_option('torod_enabled_states',[]);
	add_option('torod_enabled_cities',[]);
	add_option('torod_enabled_countries',[]);
	add_option('torod_demo_account','yes');

	register_uninstall_hook( __FILE__, 'torod_plugin_uninstall' );
	custom_rewrite_rule();
	flush_rewrite_rules();
}
/* And here goes the uninstallation function: */
function torod_plugin_uninstall(){
	global $wpdb;
	$torod_countries_table = $wpdb->prefix . 'torod_countries';
	$torod_regions_table = $wpdb->prefix . 'torod_regions';
	$torod_cities_table = $wpdb->prefix . 'torod_cities';
	$torod_order_log_table = $wpdb->prefix . 'torod_order_log';
	$tableArray = [   
		$torod_countries_table,
		$torod_regions_table,
		$torod_cities_table,
		$torod_order_log_table,
	];

	foreach ($tableArray as $tablename) {
		$wpdb->query("DROP TABLE IF EXISTS $tablename");
	}
	
	delete_option('torod_wp_all_settings');
	delete_option('torod_token');
	delete_option('torod_status_settings');
	delete_option('torod_payment_gateway');
	delete_option('torod_log_mode');
	delete_option('torod_enabled_states');
	delete_option('torod_enabled_cities');
	delete_option('torod_enabled_countries');
	delete_option('torod_version');
	delete_option('torod_demo_account','');
}
register_deactivation_hook(__FILE__, 'torod_plugin_deactivate');
function torod_plugin_deactivate()
{

	flush_rewrite_rules();
}

function torod_mmar_main_file()
{

	return __FILE__;
}

$torod = new \Torod\torod();
if ($torod->checkuser()) {
	require_once plugin_dir_path(__FILE__) . 'inc/adress.php';
}

function hide_admin_notices_on_plugin_page()
{
	global $wpdb;
	// Eklenti sayfasının belirleyicisini buraya girin
	$plugin_page_slug = 'torodpage';
	$plugin_pagesettings_slug = 'torodpage-settings';

	if ((isset($_GET['page']) && $_GET['page'] == $plugin_page_slug) || (isset($_GET['page']) && $_GET['page'] == $plugin_pagesettings_slug)) {
		remove_all_actions('admin_notices');
		remove_all_actions('all_admin_notices');
		$table_name = $wpdb->prefix . 'options';
		$results = $wpdb->get_results("SELECT * FROM $table_name WHERE option_name = 'torod_enabled_countries'");
		$results_torod_version = $wpdb->get_results("SELECT * FROM $table_name WHERE option_name = 'torod_version'");
		if (empty($results)) {
			update_option('torod_enabled_countries', array("01"));
		}
		if (empty($results_torod_version)) {
			$torod = new \Torod\torod();
			$torod->updateDataFromApi();
			update_option('torod_version', TOROD_VERSION);
		}
	}
}

add_action('admin_head', 'hide_admin_notices_on_plugin_page');

add_filter('plugin_action_links', 'add_settings_link', 10, 2);
function add_settings_link($links, $file)
{

	if ($file == plugin_basename(TOROD_PLUGIN_PATH . '/torod-mmar.php')) {
		$links[] = '<a href="' . esc_url(TOROD_ADMIN_URL) . '">' . esc_html__('Settings', 'torod') . '</a>';
	}

	return $links;
}

if (!wp_next_scheduled('torod_daily_event')) {
	wp_schedule_event(time(), 'daily', 'torod_daily_event');
}

add_action('torod_daily_event', 'updateDBAdress');

function updateDBAdress()
{

	$torod = new \Torod\torod();
	$torod->updateDataFromApi();
}