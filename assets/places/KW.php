<?php

/** 
 * Districts of Kuwait
 * - 129 districts
 * 
 * Source: 
 * - [Please add the source(s) link(s) to check the list of places]
 * 
 * @author  3Lahoonk <bofro7@gmail.com> | https://twitter.com/3LaHoonK
 * @version 1.0.0
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
global $places;

$current_language = get_locale();

$torod = new \Torod\torod();

$places['KW'] = [];

$enabled_cities = get_option('torod_enabled_cities', []);
$placessayi = count($enabled_cities);
for ($i = 14; $i <= 19; $i++) {
	$data = $torod->getAllCity($i);
	$region_code = 'KW-' . str_pad($i, 2, '0', STR_PAD_LEFT);
	$region_cities = [];

	foreach ($data as $key => $city) {
		if ($placessayi > 0) {
			if (in_array($key, $enabled_cities)) {
				$city_name_en = $city['en'];
				$city_name_ar = $city['ar'];
				$region_cities[] = [
					'en' => _x(
						$city_name_en,
						'Governorates of Saudi Arabia',
						'states-cities-and-places-for-woocommerce'
					),
					'ar' => _x(
						$city_name_ar,
						'Governorates of Saudi Arabia',
						'states-cities-and-places-for-woocommerce'
					)
				];
			}
		} else {
			$city_name_en = $city['en'];
			$city_name_ar = $city['ar'];
			$region_cities[] =
				[
					'en' => _x($city_name_en, 'Governorates of Saudi Arabia', 'states-cities-and-places-for-woocommerce'),
					'ar' => _x($city_name_ar, 'Governorates of Saudi Arabia', 'states-cities-and-places-for-woocommerce')
				];
		}

	}

	$places['KW'][$region_code] = $region_cities;
}


// Use this filter to handle the Districts of Kuwait
$places['KW'] = apply_filters('scpwoo_custom_places_kw', $places['KW']);