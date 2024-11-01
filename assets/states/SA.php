<?php

/**
 * Regions of Saudi Arabia
 * - 13 administrative regions
 * Source:
 * - https://en.wikipedia.org/wiki/Regions_of_Saudi_Arabia
 * - https://en.wikipedia.org/wiki/ISO_3166-2:SA
 * @author  Yordan Soares <contacto@yordansoar.es> | https://yordansoar.es/
 * @version 1.0.0
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

global $states;

$torod = new \Torod\torod();

$country_data = $torod->getCountryEnableData("SA");
if ($country_data["is_country_enable"]) {
	$data = $torod->getCountryRegions($country_data["country_id"]);

	$current_language = get_locale();

	$states['SA'] = [];

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
				$states['SA']["SA-{$region_id}"] =
					_x($region_name, 'Regions of Saudi Arabia', 'states-cities-and-places-for-woocommerce');
			}
		} else {
			$region_name = $region['en'];
			if ($current_language == 'ar') {
				$region_name = $region['ar'];
			}
			$states['SA']["SA-{$region_id}"] =
				_x($region_name, 'Regions of Saudi Arabia', 'states-cities-and-places-for-woocommerce');
		}

	}
} else {
	$states['SA'] = array(
		'SA-01' => _x('Riyadh', 'Regions of Saudi Arabia', 'states-cities-and-places-for-woocommerce'),
		'SA-02' => _x('Makkah', 'Regions of Saudi Arabia', 'states-cities-and-places-for-woocommerce'),
		'SA-03' => _x('Al Madinah', 'Regions of Saudi Arabia', 'states-cities-and-places-for-woocommerce'),
		'SA-04' => _x('Eastern Province', 'Regions of Saudi Arabia', 'states-cities-and-places-for-woocommerce'),
		'SA-05' => _x('Al-Qassim', 'Regions of Saudi Arabia', 'states-cities-and-places-for-woocommerce'),
		'SA-06' => _x('Ha\'il', 'Regions of Saudi Arabia', 'states-cities-and-places-for-woocommerce'),
		'SA-07' => _x('Tabuk', 'Regions of Saudi Arabia', 'states-cities-and-places-for-woocommerce'),
		'SA-08' => _x('Northern Borders', 'Regions of Saudi Arabia', 'states-cities-and-places-for-woocommerce'),
		'SA-09' => _x('Jazan', 'Regions of Saudi Arabia', 'states-cities-and-places-for-woocommerce'),
		'SA-10' => _x('Najran', 'Regions of Saudi Arabia', 'states-cities-and-places-for-woocommerce'),
		'SA-11' => _x('Al-Bahah Region', 'Regions of Saudi Arabia', 'states-cities-and-places-for-woocommerce'),
		'SA-12' => _x('Al-Jawf', 'Regions of Saudi Arabia', 'states-cities-and-places-for-woocommerce'),
		'SA-14' => _x('\'Asir', 'Regions of Saudi Arabia', 'states-cities-and-places-for-woocommerce'),
	);
}

// Use this filter to handle the Regions of Saudi Arabia
$states['SA'] = apply_filters('scpwoo_custom_states_sa', $states['SA']);