<?php

/**
 *  Governorates of Kuwait
 * - 6 governorates
 * 
 * Source: 
 * - https://en.wikipedia.org/wiki/Governorates_of_Kuwait
 * 
 * @author  3Lahoonk <bofro7@gmail.com> | https://twitter.com/3LaHoonK
 * @version 1.0.0
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

global $states;

$torod = new \Torod\torod();

$data = [];

$country_data = $torod->getCountryEnableData("KW");
if ($country_data["is_country_enable"]) {
	$data = $torod->getCountryRegions($country_data["country_id"]);
	$current_language = get_locale();

	$states['KW'] = [];

	$enabled_states = get_option('torod_enabled_states', []);

	$regionsayi = count($enabled_states);

	foreach ($data as $key => $region) {
		$region_id = str_pad($key, 2, '0', STR_PAD_LEFT);
		if ($regionsayi > 0) {
			if (in_array($region_id, $enabled_states)) {
				$region_name = $region['en'];
				if ($current_language == 'ar') {
					$region_name = $region['ar'];
				}
				$states['KW']["KW-{$region_id}"] =
					_x($region_name, 'Governorate of Kuwait', 'states-cities-and-places-for-woocommerce');
			}
		} else {
			$region_name = $region['en'];
			if ($current_language == 'ar') {
				$region_name = $region['ar'];
			}
			$states['KW']["KW-{$region_id}"] =
				_x($region_name, 'Governorate of Kuwait', 'states-cities-and-places-for-woocommerce');
		}

	}
} else {
	$states['KW'] = array(
		'AA' => _x('Al Asimah', 'Governorate of Kuwait', 'states-cities-and-places-for-woocommerce'),
		'HA' => _x('Hawalli', 'Governorate of Kuwait', 'states-cities-and-places-for-woocommerce'),
		'AF' => _x('Al Farwaniyah', 'Governorate of Kuwait', 'states-cities-and-places-for-woocommerce'),
		'MK' => _x('Mubarak Al-Kabeer', 'Governorate of Kuwait', 'states-cities-and-places-for-woocommerce'),
		'AJ' => _x('Al Jahra', 'Governorate of Kuwait', 'states-cities-and-places-for-woocommerce'),
		'AH' => _x('Al Ahmadi', 'Governorate of Kuwait', 'states-cities-and-places-for-woocommerce'),
	);
}

// Use this filter to handle the Governorates of Kuwait
$states['KW'] = apply_filters('scpwoo_custom_states_kw', $states['KW']);