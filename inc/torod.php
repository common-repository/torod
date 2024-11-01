<?php
namespace Torod;

use GuzzleHttp\Client;

class torod
{
	public function get_aws_label($tracking_id)
	{
		$url = torodurl . '/en/api/order/track';
		$headers = ['KEY' => 'Torod@123*', 'Authorization' => 'Bearer ' . $this->getToken(),];
		$body = [
			'tracking_id' => $tracking_id,
		];
		$args = ['method' => 'POST', 'headers' => $headers, 'body' => $body,];
		$response = wp_remote_post($url, $args);
		if (is_wp_error($response)) {
			return false;
		}
		$response_body = json_decode(wp_remote_retrieve_body($response), true);
		if (
			isset($response_body['status']) && $response_body['status'] && isset($response_body['code']) && $response_body['code'] == 200 && isset($response_body['data']) &&
			isset($response_body['data']['aws_label'])
		) {
			return $response_body['data']['aws_label'];
		}
		return false;
	}
	public function getToken()
	{
		$token = get_option('torod_token');
		$n = "";
		if (!isset($token) or empty($token)) {
			$veri = $this->createToken();
			if ($veri != false) {
				update_option('torod_token', $veri->data);
				$n = $veri->data->bearer_token;
				$this->plugin_log("getToken status : true : options is empty create new Token  ");
			} else {
				$this->plugin_log("getToken status : false : options is empty failed to create new Token  ");
				return false;
			}
		} else {
			$bitis = strtotime($token->token_generated_date) + 24 * 60 * 60;
			$suan = strtotime("now");
			if ($bitis > $suan) {
				$n = $token->bearer_token;
				$time = $bitis - $suan;
				$this->plugin_log("getToken status : true :  token not expired have time: $time");
			} else {
				$veri = $this->createToken();
				if ($veri) {
					update_option('torod_token', $veri->data);
					$n = $veri->data->bearer_token;
					$this->plugin_log("getToken status : true :  token expired create new Token ");
				} else {
					$this->plugin_log("getToken status : false :  token expried but Failed to create new Token ");
					return false;
				}
			}
		} //createtoken fnish
		if ($this->tokenTest($n)) {
			return $n;
		} else {
			$veri = $this->createToken();
			if ($veri != false) {
				update_option('torod_token', $veri->data);
				$n = $veri->data->bearer_token;
				return $n;
			} else {
				return false;
			}
		}
	}
	public function createToken()
	{
		$settings = get_option('torod_wp_all_settings');
		if (!isset($settings) || empty($settings)) {
			$this->plugin_log("createToken status : false : torod_wp_all_settings option is empty");
			return false;
		}
		$url = torodurl . '/en/api/token';
		$body = ['client_id' => $settings['client_id'], 'client_secret' => $settings['client_secret'],];
		$args = ['method' => 'POST', 'body' => $body,];
		$response = wp_remote_post($url, $args);
		if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
			$data = wp_remote_retrieve_body($response);
			$dataj = json_decode($data);
			if ($dataj->status) {
				$this->plugin_log("createToken status : true : create new token and return token code");
				return $dataj;
			} else {
				$error = is_array($dataj->message) ? end($dataj->message) : $dataj->message;
				$this->plugin_log("createToken data result status : false : because : " . $error);
				return false;
			}
		} else {
			$error_message = "create token problem 93852535";
			$this->plugin_log("createToken error: " . $error_message);
			return false;
		}
	}
	public function plugin_log($entry, $mode = 'a', $file = 'torod')
	{
		if (TOROD_LOGMODE == 1) {
			$date = date("Y-m-d h:i:s A");
			$upload_dir = wp_upload_dir();
			$upload_dir = $upload_dir['basedir'];
			if (is_array($entry)) {
				$entry = json_encode($entry);
			}
			$file = TOROD_PLUGIN_PATH . $file . '.log';
			$file = fopen($file, $mode);
			$bytes = fwrite($file, $date . "::" . $entry . "\n");
			fclose($file);
		}
	}
	public function tokenTest($token)
	{
		$url = torodurl . "/en/api/get-all/cities?region_id=1;";
		$args = ['headers' => ['Authorization' => 'Bearer ' . $token,],];
		$response = wp_remote_get($url, $args);
		if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
			$data = wp_remote_retrieve_body($response);
			$dataj = json_decode($data);
			if ($dataj->status) {
				$this->plugin_log("tokenTest status : true : token test status true");
				$result = true;
			} else {
				$error = is_array($dataj->message) ? end($dataj->message) : $dataj->message;
				$this->plugin_log("tokenTest status : false : token test status false because : " . $error);
				$result = false;
			}
		} else {
			$this->plugin_log("tokenTest error: bir hata var");
			$result = false;
		}
		return $result;
	}
	public function loginUser($email, $password)
	{
		$url = torodurl . '/en/api/login-plugin';
		$siteurl = get_site_url();
		$site_title = get_bloginfo('name');
		$body = [
			'email' => $email,
			'password' => $password,
			'plugin' => 'woocommerce',
			'webhook_url' => $siteurl . '/Torod/shipment/webhook',
			'site_url' => $siteurl,
			'site_name' => $site_title,
		];
		$args = ['method' => 'POST', 'headers' => ['KEY' => 'Torod@123*'], 'body' => $body];
		$response = wp_remote_post($url, $args);
		if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
			$dataj = json_decode(wp_remote_retrieve_body($response));
			if ($dataj->status == true) {
				$plugin_data = $dataj->plugin_data;
				$user_data = $dataj->data;
				if ($this->userRegData($plugin_data->client_id, $plugin_data->client_secret_key, $user_data->user_id, $plugin_data->app_id, $user_data->email)) {
					$this->updateAdress();
					$result = ['status' => 1, 'message' => 'Login Success'];
					return $result;
				}
			} else {
				$error_message = $this->messageFilter($dataj->message);
				$result = ['status' => 0, 'message' => $error_message];
				return $result;
			}
		} else {
			$data = wp_remote_retrieve_body($response);
			$dataj = json_decode($data);
			$error_code = $dataj->code;
			$error_message = $this->messageFilter($dataj->message);
			$this->plugin_log("order_create error:  " . $error_code . " - " . $error_message);
			$result = ['status' => 0, 'message' => $error_message];
			return $result;
		}
	}
	public function userRegData($a, $b, $c, $d, $e)
	{
		$data = [];
		$data['client_id'] = $a;
		$data['client_secret'] = $b;
		$data['user_id'] = $c;
		$data['app_id'] = $d;
		$data['email'] = $e;
		if (!empty($data)):
			update_option('torod_wp_all_settings', $data);
			$this->plugin_log("userRegData status : true user_id : " . $c);
			return true;
		else:
			$this->plugin_log("userRegData status : false empty data ");
			return false;
		endif;
	}
	public function updateAdress()
	{
		$this->updateDataFromApi();
	} // order create fnish
	public function messageFilter($message)
	{
		if (is_object($message) && $message instanceof stdClass) {
			// stdClass nesnesinin ilk özelliğini alın
			$property_names = array_keys((array) $message);
			$first_property_name = $property_names[0];
			// İlk özelliğin değerini alın
			$result = $message->$first_property_name;
		} elseif (is_array($message)) {
			$result = end($message);
		} else {
			$result = (array) $message;
			rsort($result);
			$result = $result[0];
		}
		return $result;
	}
	public function updateDataFromApi()
	{
		$tables_exist = $this->checkAndCreateTables();
		if (!$tables_exist) {
			$this->checkAndCreateTables();
		}
		/*$url = torodurl."/en/api/get-all/regions?country_id=1;";*/
		$url = torodurl . "/en/api/get-all/regions-access";
		$headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $this->getToken(),];
		$args = ['headers' => $headers,];
		$response = wp_remote_get($url, $args);
		if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
			$body = wp_remote_retrieve_body($response);
			$dataJson = json_decode($body);
			if ($dataJson->status == true) {
				$this->updateRegionsInDatabase($dataJson->data);
				$regions = $dataJson->data;
			}
		}
		$client = new Client(['defaults' => ['headers' => $headers]]);
		$retryAfterSeconds = 5;
		$url = torodurl . "/en/api/get-all/cities-access";
		$response = $client->get($url);
		$body = $response->getBody();
		$dataJson = json_decode($body);
		if ($dataJson->status == true) {
			$this->updateCitiesInDatabase($dataJson->data, '');
		}
		$client_countries = new Client(['defaults' => ['headers' => $headers]]);
		$retryAfterSeconds = 5;
		$url_countries = torodurl . "/en/api/get-all/countries";
		$response_countries = $client_countries->get($url_countries);
		$body_countries = $response_countries->getBody();
		$dataJson_countries = json_decode($body_countries);
		if ($dataJson_countries->status == true) {
			$this->updateCountriesInDatabase($dataJson_countries->data);
		}
	}
	public function checkAndCreateTables()
	{
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$torod_countries_table = $wpdb->prefix . 'torod_countries';
		$torod_regions_table = $wpdb->prefix . 'torod_regions';
		$torod_cities_table = $wpdb->prefix . 'torod_cities';
		$torod_order_log_table = $wpdb->prefix . 'torod_order_log';
		// torod_countries_table create
		if ($wpdb->get_var("SHOW TABLES LIKE '$torod_countries_table'") !== $torod_countries_table) {
			$sql_countries = "CREATE TABLE $torod_countries_table (
			country_id BIGINT UNSIGNED NOT NULL,
			country_name VARCHAR(255) NOT NULL,
			country_name_ar VARCHAR(255) NOT NULL,
			country_code VARCHAR(255) NOT NULL,
			currency_code VARCHAR(255) NOT NULL,
			country_iso_code VARCHAR(255) NOT NULL,
			country_phone_code VARCHAR(255) NOT NULL,
			PRIMARY KEY (country_id)
				) $charset_collate;";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql_countries);
			$this->plugin_log("create DB Torod countries");
		}
		// Bölge tablosunu kontrol et ve gerekirse oluştur
		if ($wpdb->get_var("SHOW TABLES LIKE '$torod_regions_table'") !== $torod_regions_table) {
			$sql_regions = "CREATE TABLE $torod_regions_table (
			region_id BIGINT UNSIGNED NOT NULL,
			country_id BIGINT UNSIGNED NOT NULL,
			region_name VARCHAR(255) NOT NULL,
			region_name_ar VARCHAR(255) NOT NULL,
			PRIMARY KEY (region_id)
				) $charset_collate;";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql_regions);
			$this->plugin_log("create DB Torod Regions table");
		}
		// Şehir tablosunu kontrol et ve gerekirse oluştur
		if ($wpdb->get_var("SHOW TABLES LIKE '$torod_cities_table'") !== $torod_cities_table) {
			$sql_cities = "CREATE TABLE $torod_cities_table (
			city_id BIGINT UNSIGNED NOT NULL,
			region_id BIGINT UNSIGNED NOT NULL,
			city_name VARCHAR(255) NOT NULL,
			city_name_ar VARCHAR(255) NOT NULL,
			PRIMARY KEY (city_id),
			UNIQUE KEY city_id_unique (city_id),
			FOREIGN KEY (region_id) REFERENCES $torod_regions_table(region_id) ON DELETE CASCADE
		) $charset_collate;";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql_cities);
			$this->plugin_log("create DB Torod City table");
		}
		// Create order_log Table
		if ($wpdb->get_var("SHOW TABLES LIKE '$torod_order_log_table'") !== $torod_order_log_table) {
			$sql_order_log = "CREATE TABLE $torod_order_log_table (
				id INT NOT NULL AUTO_INCREMENT,
				order_id BIGINT NOT NULL,
				error_code VARCHAR(255) NOT NULL,
				error_message VARCHAR(255) NOT NULL,
				PRIMARY KEY (id)
			) $charset_collate;";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql_order_log);
			$this->plugin_log("create DB Torod Order Log table");
		}

	}
	public function updateCountriesInDatabase($countriesData)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'torod_countries';
		foreach ($countriesData as $countries) {
			$country_id = $countries->id;
			$country_name = $countries->country_name;
			$country_name_ar = $countries->country_name_ar;
			$country_code = $countries->country_code;
			$currency_code = $countries->currency_code;
			$country_phone_code = $countries->country_phone_code;
			$country_iso_code = $countries->country_iso_code;
			// Check if the region_id already exists in the database
			$exists = $wpdb->get_row("SELECT * FROM $table_name WHERE country_id = $country_id");
			if (!$exists) {
				// Insert the new record
				$wpdb->replace($table_name, ['country_id' => $country_id, 'country_name' => $country_name, 'country_name_ar' => $country_name_ar, 'country_code' => $country_code, 'currency_code' => $currency_code, 'country_iso_code' => $country_iso_code, 'country_phone_code' => $country_phone_code,], ['%d', '%s', '%s', '%s', '%s', '%s', '%s']);
				$last_error = $wpdb->last_error;
				if (!empty($last_error)) {
					$this->plugin_log("updateCountriesInDatabase update error $last_error");
				}
			}
		}
		$this->plugin_log("updateCountriesInDatabase success update");
	}
	private function updateRegionsInDatabase($regionsData)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'torod_regions';
		foreach ($regionsData as $region) {
			$region_id = $region->region_id;
			$country_id = $region->country_id;
			$region_name = $region->region_name;
			$region_name_ar = $region->region_name_ar;
			// Check if the region_id already exists in the database
			$exists = $wpdb->get_row("SELECT * FROM $table_name WHERE region_id = $region_id");
			if (!$exists) {
				// Insert the new record
				$wpdb->replace($table_name, ['region_id' => $region_id, 'country_id' => $country_id, 'region_name' => $region_name, 'region_name_ar' => $region_name_ar,], ['%d', '%d', '%s', '%s']);
				$last_error = $wpdb->last_error;
				if (!empty($last_error)) {
					$this->plugin_log("updateCitiesInDatabase update error $last_error");
				}
			}
			if ($exists) {
				$column_name = 'country_id';
				$column = $wpdb->get_results($wpdb->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ", DB_NAME, $table_name, $column_name));
				if (!$column) {
					$wpdb->query("ALTER TABLE $table_name ADD country_id bigINT(20) NOT NULL");
				}
				$wpdb->query($wpdb->prepare("UPDATE $table_name SET country_id=%d WHERE region_id=%d",$country_id,$region_id));
				$last_error = $wpdb->last_error;
				if (!empty($last_error)) {
					$this->plugin_log("updateCitiesInDatabase update error $last_error");
				}
			}
		}
		$this->plugin_log("updateRegionsInDatabase success update");
	}
	public function updateCitiesInDatabase($citiesData, $region_id)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'torod_cities';
		foreach ($citiesData as $city) {
			if (isset($city->cities_id)) {
				$city_id = $city->cities_id;
				$region_id = $city->region_id;
				$city_name = $city->city_name;
				$city_name_ar = $city->city_name_ar;
				// Check if city_id exists in the database
				$city_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE city_id = %d", $city_id));
				// If city_id does not exist, insert the record
				if (!$city_exists) {
					$data = [
						'city_id' => $city_id,
						'region_id' => $region_id,
						'city_name' => $city_name,
						'city_name_ar' => $city_name_ar,
					];
					$wpdb->replace($table_name, $data);
					$last_error = $wpdb->last_error;
					if (!empty($last_error)) {
						$this->plugin_log("updateCitiesInDatabase update error $last_error");
					}
				}
			}
		}
		$this->plugin_log("updateCitiesInDatabase success region id : $region_id");
	}
	public function userregister($first_name, $last_name, $store_name, $email, $phone_number)
	{
		$siteurl = get_site_url();
		$site_title = get_bloginfo('name');
		$url = torodurl . '/en/api/install-plugin';
		$headers = ['KEY' => 'Torod@123*',];
		$body = [
			'first_name' => $first_name,
			'last_name' => $last_name,
			'store_name' => $store_name,
			'email' => $email,
			'phone_number' => $phone_number,
			'plugin' => 'woocommerce',
			'webhook_url' => $siteurl . '/Torod/shipment/webhook',
			'site_url' => $siteurl,
			'site_name' => $site_title,
		];
		$args = ['method' => 'POST', 'headers' => $headers, 'body' => $body,];
		$response = wp_remote_post($url, $args);
		if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
			$dataj = json_decode(wp_remote_retrieve_body($response));
			if ($dataj->status == true) {
				$plugin_data = $dataj->plugin_data;
				$user_data = $dataj->data;
				if ($this->userRegData($plugin_data->client_id, $plugin_data->client_secret_key, $user_data->user_id, $plugin_data->app_id, $user_data->email)) {
					$this->updateAdress();
					$result = ['status' => 1, 'message' => 'Register Success'];
					return $result;
				}
			} else {
				$error_message = $this->messageFilter($dataj->message);
				$result = ['status' => 0, 'message' => $error_message];
				return $result;
			}
		} else {
			$error_message = "userregister false error 646513133113";
			$this->plugin_log("userregister error: " . $error_message);
			return false;
		}
	}
	public function torod_disconnect($app_id, $user_id)
	{
		$url = torodurl . '/en/api/uninstall-plugin';
		$headers = ['KEY' => 'Torod@123*',];
		$body = ['user_id' => $user_id, 'app_id' => $app_id, 'plugin' => 'woocommerce'];
		$args = ['method' => 'POST', 'headers' => $headers, 'body' => $body,];
		$response = wp_remote_post($url, $args);
		if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
			$data = wp_remote_retrieve_body($response);
			$dataj = json_decode($data);
			if ($dataj->status == true) {
				update_option('torod_wp_all_settings', []);
				$this->plugin_log("torod_disconnect data result status : true : user_id $user_id");
				return 1;
			} else {
				$result = is_array($dataj->message) ? end($dataj->message) : $dataj->message;
				$this->plugin_log("torod_disconnect data result status : false : " . $result);
				update_option('torod_wp_all_settings', []);
				return 0;
			}
		} else {
			update_option('torod_wp_all_settings', []);
			$error_message = "torod disconnect error 346136436";
			$this->plugin_log("torod_disconnect error: " . $error_message);
			return 0;
		}
	}
	public function order_create_torod($order, $id, $status = null)
	{
		global $wpdb;
		$torod_order_log_table = $wpdb->prefix . 'torod_order_log';
		if ($this->checkuser() == true) {
			if (empty($id)) {
				$this->plugin_log("order_create_torod status : false : dont have order id  ");
				return;
			}
			if ($status !== null) {
				$issetStatusOrder = $status;
				$this->plugin_log("order_create_torod send custom order status  ");
			} else {
				$issetStatusOrder = $this->issetOrderInStatus($order->get_Status());
				$this->plugin_log("order_create_torod send custom order status " . $order->get_Status() . " gönderildi ");
			}
			if ($issetStatusOrder) {
				$country_code = "";
				if ($order->get_billing_country() != $order->get_shipping_country() && $order->get_shipping_country() != '') {
					$country_code = $order->get_shipping_country();
				} else {
					$country_code = $order->get_billing_country();
				}
				$country_data = $this->getCountryEnableData($country_code);
				if ($country_data["is_country_enable"]) {
					if ($order->get_billing_address_1() != $order->get_shipping_address_1()) {
						$zip = $order->get_shipping_postcode() ? $order->get_shipping_postcode() : '';
						$address = $order->get_shipping_address_1() . " " . $order->get_shipping_address_2() . "" . $zip;
					} else {
						$zip = $order->get_billing_postcode() ? $order->get_billing_postcode() : '';
						$address = $order->get_billing_address_1() . " " . $order->get_billing_address_2() . " " . $zip;
					}
					if ($order->get_billing_city() != $order->get_shipping_city() && $order->get_shipping_city() != '') {
						$cityname = $order->get_shipping_city();
						$city = $this->cityNameRid($cityname);
					} else {
						$cityname = $order->get_billing_city();
						$city = $this->cityNameRid($cityname);
					}
					$first_name = '';
					$last_name = '';
					if (!empty($order->get_shipping_first_name())) {
						$first_name = $order->get_shipping_first_name();
						$last_name = $order->get_shipping_last_name() ?? '';
					} else {
						$first_name = $order->get_billing_first_name();
						$last_name = $order->get_billing_last_name() ?? '';
					}
					$name = trim($first_name . ' ' . $last_name);
					$paymentname = $this->issetPaymentMethod($order->get_payment_method());
					$merchantinfo = get_option('torod_wp_all_settings');
					$email = (!empty($order->get_billing_email())) ? $order->get_billing_email() : $merchantinfo['email'];
					/*$phone = $this->phoneNumberFix($order->get_billing_phone());*/
					$phone = str_replace(" ","",$order->get_billing_phone());
					$detailsorder = $this->orderDetails($order);

					if (!$city) {
						$check_order_log = $this->getAllOrderLog($id);
						$torod_order_log_table = $wpdb->prefix . 'torod_order_log';
						if(empty($check_order_log)) {
							$wpdb->replace($torod_order_log_table, ['order_id' => $id, 'error_code' => 422, 'error_message' => 'City not found']);
						}else{
							$wpdb->query($wpdb->prepare("UPDATE $torod_order_log_table SET error_message=%s WHERE order_id=%d",'City not found',$id));
						}
						$last_error = $wpdb->last_error;
						if (!empty($last_error)) {
							$this->plugin_log("Insert In Order Log error $last_error");
						}
						$this->plugin_log("order_create_torod order City not found ");
						$result = ['status' => 0, 'message' => 'order City not found'];
						return $result;
					} else {
						$cityid = $city;
						$weight_json = json_decode($this->orderDetails($order), true);
						$weight_array = array_column($weight_json, 'weight');
						$total_weight = array_sum($weight_array);

						$data = [
							"name" => $name,
							"email" => $email,
							"phone_number" => $phone,
							"item_description" => $detailsorder,
							"order_total" => $order->get_total(),
							"payment" => $paymentname,
							"weight" => $total_weight,
							"no_of_box" => 1,
							"type" => "address_city",
							"city_id" => $cityid,
							"address" => $address,
							"reference_id" => $id,
						];
						$returndata = $this->order_create($data);
						return $returndata;
					}			
				} else {
					$check_order_log = $this->getAllOrderLog($id);
					if(empty($check_order_log)) {
						$wpdb->replace($torod_order_log_table, ['order_id' => $id, 'error_code' => 422, 'error_message' => 'countries not selected']);
					}else{
						$wpdb->query($wpdb->prepare("UPDATE $torod_order_log_table SET error_message=%s WHERE order_id=%d",'countries not selected',$id));
					}
					$last_error = $wpdb->last_error;
					if (!empty($last_error)) {
						$this->plugin_log("Insert In Order Log error $last_error");
					}
					$this->plugin_log("order_create_torod order countries not selected ");
					$result = ['status' => 0, 'message' => 'order countries not in selected'];
					return $result;
				}
			} else {
				$this->plugin_log("order_create_torod order status not selected ");
				$result = ['status' => 0, 'message' => 'order status not in selected'];
				$check_order_log = $this->getAllOrderLog($id);
				if(empty($check_order_log)) {
					$wpdb->replace($torod_order_log_table, ['order_id' => $id, 'error_code' => 422, 'error_message' => 'order status not selected']);
				}else{
					$wpdb->query($wpdb->prepare("UPDATE $torod_order_log_table SET error_message=%s WHERE order_id=%d",'order status not selected',$id));
				}
				$last_error = $wpdb->last_error;
				if (!empty($last_error)) {
					$this->plugin_log("Insert In Order Log error $last_error");
				}
				return $result;
			}
		} else { // check user fnish
			$this->plugin_log("order_create_torod status :false :  merchant not logged in ");
			$result = ['status' => 0, 'message' => 'merchant not logged in'];
			$check_order_log = $this->getAllOrderLog($id);
			if(empty($check_order_log)) {
				$wpdb->replace($torod_order_log_table, ['order_id' => $id, 'error_code' => 422, 'error_message' => 'order_create_torod merchant not logged in']);
			}else{
				$wpdb->query($wpdb->prepare("UPDATE $torod_order_log_table SET error_message=%s WHERE order_id=%d",'order_create_torod merchant not logged in',$id));
			}
			$last_error = $wpdb->last_error;
			if (!empty($last_error)) {
				$this->plugin_log("Insert In Order Log error $last_error");
			}
			return $result;
		}
	}
	public function checkuser()
	{
		$settings = get_option('torod_wp_all_settings');
		if (!empty($settings)) {
			if (!empty($settings['client_id'] && !empty($settings['client_secret']))) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	public function issetOrderInStatus($status)
	{
		$status = "wc-" . $status;
		$veri = get_option("torod_status_settings");
		return in_array($status, $veri, true);
	}
	public function cityNameRid($cityname)
	{
		global $wpdb;
		// Şehir adını küçük harfe dönüştür
		$cityname = strtolower($cityname);
		// Şehir ID'sini veritabanından al
		$city_table = $wpdb->prefix . 'torod_cities';
		$city = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$city_table} WHERE LOWER(city_name) = %s OR LOWER(city_name_ar) = %s", $cityname, $cityname));
		if ($city) {
			return $city->city_id;
		}
		return false;
	}
	public function issetPaymentMethod($method)
	{
		$veri = get_option("torod_payment_gateway");
		$checkpyment = in_array($method, $veri, true);
		$data = ($checkpyment) ? "COD" : "Prepaid";
		return $data;
	}
	public function phoneNumberFix($number)
	{
		$number = preg_replace("/[^0-9]/", "", $number);
		$ct = strlen((string) $number);
		if ($ct > 12) {
			if ($number[0] == 9 and $number[1] == 6 and $number[2] == 6) {
				$sondokuz = substr($number, 12);
				$no = "966" . $sondokuz;
			} else {
				$sondokuz = substr($number, -9);
				$no = "966" . $sondokuz;
			}
		} else {
			if ($ct == 12) {
				if ($number[0] == 9 and $number[1] == 6 and $number[2] == 6) {
					$no = $number;
				}
			} else {
				if ($ct == 11) {
					if ($number[0] == 0) {
						$string = (string) $number;
						$sondokuz = substr($string, -9);
						$no = "966" . $sondokuz;
					} else {
						$ilkdokuz = substr((string) $number, 0, 9);
						$no = "966" . $ilkdokuz;
					}
				} else {
					if ($ct == 10) {
						if ($number[0] == 0) {
							$string = (string) $number;
							$sondokuz = substr($string, -9);
							$no = "966" . $sondokuz;
						} elseif ($number[0] == 5) {
							$string = (string) $number;
							$sondokuz = substr((string) $number, 0, 9);
							$no = "966" . $sondokuz;
						} else {
							$ilkdokuz = substr((string) $number, 0, 9);
							$no = "966" . $ilkdokuz;
						}
					} elseif ($ct == 9) {
						if ($number[0] == 0) {
							$string = (string) $number;
							$sondokuz = substr($string, -8);
							$no = "966" . $sondokuz . "0";
						} elseif ($number[0] == 5) {
							$string = (string) $number;
							$sondokuz = substr((string) $number, 0, 9);
							$no = "966" . $sondokuz;
						} else {
							$ilkdokuz = substr((string) $number, 0, 9);
							$no = "966" . $ilkdokuz;
						}
					} elseif ($ct == 8) {
						$no = "966$number" . "0";
					} elseif ($ct == 7) {
						$no = "966$number" . "00";
					} elseif ($ct == 6) {
						$no = "966$number" . "000";
					} elseif ($ct == 5) {
						$no = "966$number" . "0000";
					} elseif ($ct == 4) {
						$no = "966$number" . "00000";
					} elseif ($ct == 3) {
						$no = "966$number" . "000000";
					} elseif ($ct == 2) {
						$no = "966$number" . "0000000";
					} else {
						$no = "966$number" . "00000000";
					}
				}
			}
		}
		$this->plugin_log("phoneNumberFix run return phone number :  $no ");
		return $no;
	}
	public function orderDetails($order)
	{
		$order_items = $order->get_items(); // Get order items array of objects
		$items_count = count($order_items); // Get order items count
		$items_data = [];
		// Initializing
		foreach ($order->get_items() as $item_id => $item) {
			$variation_id = $item->get_variation_id();
			$product_id = $variation_id > 0 ? $variation_id : $item->get_product_id();
			$product = $item->get_product();
			//$weight = $this->order_total_weight($product->get_weight())*$item->get_quantity();
			// Set specific data for each item in the array
			$items_data[] = [
				'id' => $item->get_product_id(),
				'name' => $item->get_name(),
				'sku' => $product->get_sku(),
				'weight' => $this->order_total_weight($product->get_weight()),
				'quantity' => $item->get_quantity(),
				'price' => $item->get_subtotal(),
			];
		}
		return json_encode($items_data);
	}
	public function order_create($orderdata)
	{
		global $wpdb;
		$url = torodurl . '/en/api/order/create';
		$headers = ['KEY' => 'Torod@123*', 'Authorization' => 'Bearer ' . $this->getToken(),];
		$body = array_merge($orderdata, ['plugin' => 'woocommerce']);
		$args = ['method' => 'POST', 'headers' => $headers, 'body' => $body,];
		$response = wp_remote_post($url, $args);
		if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
			$data = wp_remote_retrieve_body($response);
			$dataj = json_decode($data);
			if ($dataj->status == true) {
				$id = $orderdata['reference_id'];
				$oid = $dataj->data->order_id;
				update_post_meta($id, 'torod_order_id', $oid);
				$this->plugin_log("order_create status : true : create order id : $oid");
				$check_order_log = $this->getAllOrderLog($id);
				$torod_order_log_table = $wpdb->prefix . 'torod_order_log';
				if(!empty($check_order_log)) {
					$wpdb->query(
					              'DELETE  FROM '.$torod_order_log_table.'
					               WHERE order_id = "'.$id.'"'
					);
				}
				return ['status' => 1, 'message' => 'Başarılı!'];
			} else {
				$this->plugin_log("status false döndü");
				$error = is_array($dataj->message) ? end($dataj->message) : $dataj->message;
				$this->plugin_log("order_create status : false : because : $error");
				return ['status' => 0, 'message' => $error];
			}
		} else {
			$data = wp_remote_retrieve_body($response);
			$dataj = json_decode($data);
			$id = $orderdata['reference_id'];
			$error_code = $dataj->code;
			$error_message = $this->messageFilter($dataj->message);
			$this->plugin_log("order_create error:  " . $error_code . " - " . $error_message);
			$torod_order_id = get_post_meta($id, "torod_order_id", true);
			if (!$torod_order_id) {
				$torod_order_log_table = $wpdb->prefix . 'torod_order_log';
				$check_order_log = $this->getAllOrderLog($id);
				if(empty($check_order_log)) {
					$wpdb->replace($torod_order_log_table, ['order_id' => $id, 'error_code' => $error_code, 'error_message' => $error_message,], ['%s', '%s', '%s']);
					$last_error = $wpdb->last_error;
					if (!empty($last_error)) {
						$this->plugin_log("Insert In Order Log error $last_error");
					}
				}else{
					$wpdb->query($wpdb->prepare("UPDATE $torod_order_log_table SET error_message=%s WHERE order_id=%d",$error_message,$id));
					$last_error = $wpdb->last_error;
					if (!empty($last_error)) {
						$this->plugin_log("update In Order Log error $last_error");
					}
				}
			}
			$result = ['status' => 0, 'message' => $error_message];
			return $result;
		}
	}
	public function order_total_weight($weight)
	{
		$wtype = get_option('woocommerce_weight_unit');
		switch ($wtype) {
			case "g":
				return (float) $weight * 0.001;
				break;
			case "lbs":
				return (float) $weight / 2.20462;
				break;
			case "oz":
				return (float) $weight * 0.02834952;
				break;
			default:
				return (float) $weight;
				break;
		}
		return $weight;
	}
	public function order_update_torod($order_id)
	{
		global $wpdb;
		$searchorderid = $order_id;
		$orderids = $this->allOrderlist();
		$torod_order_log_table = $wpdb->prefix . 'torod_order_log';
		if (in_array($searchorderid, $orderids ?? [])) {
			$this->plugin_log("id torodda var işleme devam ");
			if ($this->checkuser() == true) {
				if (empty($order_id)) {
					$this->plugin_log("order_update_torod status :false :  order_id dont have ");
					return;
				}
				$order = wc_get_order($order_id);
				$id = get_post_meta($order_id, "torod_order_id", true);
				$issetStatusOrder = $this->issetOrderInStatus($order->get_Status());
				if ($issetStatusOrder) {
					$country_code = "";
					if ($order->get_billing_country() != $order->get_shipping_country() && $order->get_shipping_country() != '') {
						$country_code = $order->get_shipping_country();
					} else {
						$country_code = $order->get_billing_country();
					}
					$country_data = $this->getCountryEnableData($country_code);

					if ($country_data["is_country_enable"]) {
						if ($order->get_billing_address_1() != $order->get_shipping_address_1()) {
						$zip = $order->get_shipping_postcode() ? $order->get_shipping_postcode() : '';
						$address = $order->get_shipping_address_1() . " " . $order->get_shipping_address_2() . " " . $zip;
						} else {
							$zip = $order->get_billing_postcode() ? $order->get_billing_postcode() : '';
							$address = $order->get_billing_address_1() . " " . $order->get_billing_address_2() . " " . $zip;
						}
						if ($order->get_billing_city() != $order->get_shipping_city() && $order->get_shipping_city() != '') {
							$statename = $order->get_shipping_state();
							$cityname = $order->get_shipping_city();
							$city = $this->cityNameRid($cityname, $statename);
						} else {
							$statename = $order->get_billing_state();
							$cityname = $order->get_billing_city();
							$city = $this->cityNameRid($cityname, $statename);
						}
						if ($order->get_shipping_first_name() !== null) {
							$first_name = $order->get_shipping_first_name();
							$last_name = $order->get_shipping_last_name() ?? '';
							if (empty($first_name)) {
								$first_name = $order->get_billing_first_name();
							}
							if (empty($last_name)) {
								$last_name = $order->get_billing_last_name() ?? '';
							}
						} else {
							$first_name = $order->get_billing_first_name();
							$last_name = $order->get_billing_last_name() ?? '';
						}
						$name = trim($first_name . ' ' . $last_name);
						$paymentname = $this->issetPaymentMethod($order->get_payment_method());
						$email = $order->get_billing_email();
						/*$phone = $this->phoneNumberFix($order->get_billing_phone());*/
						$phone = str_replace(" ","",$order->get_billing_phone());
						$detailsorder = $this->orderDetails($order);
						$cityid = ($city && is_numeric($city)) ? $city : 3;

						$weight_json = json_decode($this->orderDetails($order), true);
						$weight_array = array_column($weight_json, 'weight');
						$total_weight = array_sum($weight_array);
						if (!$city) {
							$torod_order_log_table = $wpdb->prefix . 'torod_order_log';
							$check_order_log = $this->getAllOrderLog($id);
							if(empty($check_order_log)) {
								$wpdb->replace($torod_order_log_table, ['order_id' => $id, 'error_code' => 422, 'error_message' => 'City not found']);
							}else {
								$wpdb->query($wpdb->prepare("UPDATE $torod_order_log_table SET error_message=%s WHERE order_id=%d",'City not found',$id));
							}
							$last_error = $wpdb->last_error;
							if (!empty($last_error)) {
								$this->plugin_log("Insert In Order Log error $last_error");
							}
							$this->plugin_log("order_create_torod order City not found ");
							$result = ['status' => 0, 'message' => 'order City not found'];
							return $result;
						} else {
							$cityid = $city;
							$data = [
								"name" => $name,
								"email" => $email,
								"phone_number" => $phone,
								"item_description" => $detailsorder,
								"order_total" => $order->get_total(),
								"payment" => $paymentname,
								"weight" => $total_weight,
								"no_of_box" => 1,
								"type" => "address_city",
								"city_id" => $cityid,
								"address" => $address,
								"order_id" => $id,
							];
							$returndata = $this->update_order($data);
							if ($returndata['status'] == true) {
								$this->plugin_log("order_update_torod status : true : update order_id : " . $id);
								return true;
							} else {
								$result = $returndata['message'];
								$this->plugin_log("order_update_torod status : false : order update status false because : " . $result);
								return false;
							}
						}
					} else {
						$check_order_log = $this->getAllOrderLog($id);
						if(empty($check_order_log)) {
							$wpdb->replace($torod_order_log_table, ['order_id' => $id, 'error_code' => 422, 'error_message' => 'countries not selected']);
						}else{
							$wpdb->query($wpdb->prepare("UPDATE $torod_order_log_table SET error_message=%s WHERE order_id=%d",'countries not selected',$id));
						}
						$last_error = $wpdb->last_error;
						if (!empty($last_error)) {
							$this->plugin_log("Insert In Order Log error $last_error");
						}
						$this->plugin_log("order_Update_torod order countries not selected ");
						$result = ['status' => 0, 'message' => 'order countries not in selected'];
						return $result;
					}
				} else {
					$this->plugin_log("order_Update_torod order status not selected ");
					$result = ['status' => 0, 'message' => 'order status not in selected'];
					$check_order_log = $this->getAllOrderLog($id);
					if(empty($check_order_log)) {
						$wpdb->replace($torod_order_log_table, ['order_id' => $id, 'error_code' => 422, 'error_message' => 'order status not in selected']);
					}else{
						$wpdb->query($wpdb->prepare("UPDATE $torod_order_log_table SET error_message=%s WHERE order_id=%d", 'order status not in selected',$id));
					}
					$last_error = $wpdb->last_error;
					if (!empty($last_error)) {
						$this->plugin_log("Insert In Order Log error $last_error");
					}
					return $result;
				}
			} else { // check user fnish
				$this->plugin_log("order_update_torod status :false :  merchant not logged in ");
				$check_order_log = $this->getAllOrderLog($id);
				if(empty($check_order_log)) {
					$wpdb->replace($torod_order_log_table, ['order_id' => $id, 'error_code' => 422, 'error_message' => 'merchant not logged in']);
				}else{
					$wpdb->query($wpdb->prepare("UPDATE $torod_order_log_table SET error_message=%s WHERE order_id=%d",'merchant not logged in',$id));
				}
				$last_error = $wpdb->last_error;
				if (!empty($last_error)) {
					$this->plugin_log("Insert In Order Log error $last_error");
				}
				return false;
			}
		} else {
			$this->plugin_log("order_update_torod status :false : order id dont have !! ");
			$check_order_log = $this->getAllOrderLog($id);
			if(empty($check_order_log)) {
				$wpdb->replace($torod_order_log_table, ['order_id' => $id, 'error_code' => 422, 'error_message' => 'order id dont have']);
			}else{
				$wpdb->query($wpdb->prepare("UPDATE $torod_order_log_table SET error_message=%s WHERE order_id=%d",'order id dont have',$id));
			}
			$last_error = $wpdb->last_error;
			if (!empty($last_error)) {
				$this->plugin_log("Insert In Order Log error $last_error");
			}
			return false;
		}
	} // regionNameRid bitti
	public function allOrderlist()
	{
		$url = torodurl . "/en/api/order/list";
		$headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $this->getToken(),];
		$args = ['headers' => $headers,];
		$response = wp_remote_get($url, $args);
		$orderids = [];
		if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
			$data = wp_remote_retrieve_body($response);
			$dataj = json_decode($data);
			if ($dataj->status == true) {
				foreach ($dataj->data as $item) {
					$orderids[] = $item->order_id;
				}
				$this->plugin_log("allOrderlist status : true : all order send list: ");
				return $orderids;
			} else {
				$this->plugin_log("allOrderlist status : false : something wrong all order list get: ");
				return $orderids;
			}
		} else {
			$error_message = "all order list error 464946466464";
			$this->plugin_log("allOrderlist error: " . $error_message);
			return $orderids;
		}
	}
	public function update_order($orderdata)
	{
		$url = torodurl . '/en/api/order/update';
		$headers = ['KEY' => 'Torod@123*', 'Authorization' => 'Bearer ' . $this->getToken(),];
		$body = [
			'order_id' => $orderdata['order_id'],
			'name' => $orderdata['name'],
			'email' => $orderdata['email'],
			'phone_number' => $orderdata['phone_number'],
			'item_description' => $orderdata['item_description'],
			'order_total' => $orderdata['order_total'],
			'payment' => $orderdata['payment'],
			'weight' => $orderdata['weight'],
			'no_of_box' => $orderdata['no_of_box'],
			'type' => $orderdata['type'],
			'city_id' => $orderdata['city_id'],
			'address' => $orderdata['address'],
			'reference_id' => $orderdata['order_id'],
			'plugin' => 'woocommerce',
		];
		$args = ['method' => 'POST', 'headers' => $headers, 'body' => $body,];
		$response = wp_remote_post($url, $args);
		if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
			$data = wp_remote_retrieve_body($response);
			$dataj = json_decode($data);
			$result = ['status' => true, 'data' => $dataj];
		} else {
			$data = json_decode($response['body'], true);
			if (isset($data['message']) && is_array($data['message'])) {
				$error_messages = [];
				foreach ($data['message'] as $key => $message) {
					$error_messages[] = $key . ': ' . $message;
				}
				$error_message = implode(', ', $error_messages);
			} else {
				$error_message = "An unexpected error occurred 74589633";
			}
			$result = ['status' => false, 'message' => $error_message];
		}
		return $result;
	}
	public function getCityId($cityName, $regionname)
	{
		$rid = $this->regionNameRid($regionname);
		if (is_numeric($rid)) {
			$cid = $this->cityNameRid($cityName, $rid);
			return $cid;
		} else {
			return null;
		}
	}
	public function regionNameRid($search_value)
	{
		$torod = new torod;
		$data = $torod->getRegions();
		foreach ($data as $id => $item) {
			if ($item['en'] === $search_value || $item['ar'] === $search_value) {
				return $id;
			}
		}
		return null;
	}
	public function getRegions($country_id = 1)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'torod_regions';
		$results = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
		$regions = [];
		foreach ($results as $item) {
			$regions[$item['region_id']] = ["en" => $item['region_name'], "ar" => $item['region_name_ar'], "country_id" => $item['country_id']];
		}
		return $regions;
	}
	public function getRegionsForadmin($country_id = 1)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'torod_regions';
		$results = $wpdb->get_results("SELECT * FROM $table_name WHERE country_id = $country_id", ARRAY_A);
		$regions = [];
		foreach ($results as $item) {
			$regions[$item['region_id']] = ["en" => $item['region_name'], "ar" => $item['region_name_ar'], "country_id" => $item['country_id']];
		}
		return $regions;
	}
	//Custom country get
	public function getCountryEnableData($country_code = "SA")
	{
		global $wpdb;
		$returnArray = ["is_country_enable" => false, "country_id" => 0];
		$country_table_name = $wpdb->prefix . 'torod_countries';
		$country_data = $wpdb->get_row("SELECT * FROM $country_table_name WHERE country_code = '$country_code'", ARRAY_A);
		if (!empty($country_data)) {
			$enabled_countries = get_option('torod_enabled_countries', []);
			if (in_array($country_data["country_id"], $enabled_countries)) {
				$returnArray = ["is_country_enable" => true, "country_id" => $country_data["country_id"]];
			}
		}
		return $returnArray;
	}
	//Custom country get
	public function getCountryRegions($country_id = 1)
	{
		global $wpdb;
		$regions = [];
		$region_table_name = $wpdb->prefix . 'torod_regions';
		$results = $wpdb->get_results("SELECT * FROM $region_table_name WHERE country_id = '$country_id'", ARRAY_A);
		foreach ($results as $item) {
			$regions[$item['region_id']] = ["en" => $item['region_name'], "ar" => $item['region_name_ar'], "country_id" => $item['country_id']];
		}
		return $regions;
	}
	//Custom country get
	public function getcountries()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'torod_countries';
		$results = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
		$countries = [];
		/*print_r($results);*/
		foreach ($results as $item) {
			$countries[$item['country_id']] = ["country_id" => $item['country_id'], "en" => $item['country_name'], "ar" => $item['country_name_ar'], "country_code" => $item['country_code']];
		}
		/*print_r($countries);*/
		return $countries;
	}
	public function userinfo($email)
	{
		$user_data = get_user_by('email', $email);
		return $user_data;
	}
	public function getAllCity($data)
	{
		if (is_numeric($data)) {
			$region_id = $data;
		} else {
			$region_id = $this->regionNameRid($data);
		}
		global $wpdb;
		$table_name = $wpdb->prefix . 'torod_cities';
		$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE region_id = %d", $region_id), ARRAY_A);
		$cities = [];
		foreach ($results as $item) {
			$cities[$item['city_id']] = ["en" => $item['city_name'], "ar" => $item['city_name_ar']];
		}
		return $cities;
	}
	/* Get Order Log */
	public function getAllOrderLog($oid = '')
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'torod_order_log';
		if ($oid !='') {
			$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE order_id = ".$oid .""), ARRAY_A);
		}else{
			$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name"), ARRAY_A);
		}
		return $results;
	}
}