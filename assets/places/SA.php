<?php

/**
 * Governorates of Saudi Arabia
 * - 118 governorates
 * Source:
 * - https://en.wikipedia.org/wiki/List_of_governorates_of_Saudi_Arabia
 * - https://www.citypopulation.de/en/saudiarabia/
 * @author  Yordan Soares <contacto@yordansoar.es> | https://yordansoar.es/
 * @version 1.0.0
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

global $places;

$current_language = get_locale();

$torod = new \Torod\torod();

$places['SA'] = [];

$enabled_cities = get_option('torod_enabled_cities', []);
$placessayi = count($enabled_cities);

for ($i = 1; $i <= 13; $i++) {
	$data = $torod->getAllCity($i);
	$region_code = 'SA-' . str_pad($i, 2, '0', STR_PAD_LEFT);
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

	$places['SA'][$region_code] = $region_cities;
}

// Use this filter to handle the Governorates of Saudi Arabia
$places['SA'] = apply_filters('scpwoo_custom_places_sa', $places['SA']);