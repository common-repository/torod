<?php
class torod_Settings
{
	public function display_settings_page()
	{
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		if (isset($_POST['torod_settings_submit'])) {
			$this->save_settings();
		}

		$demo_account = get_option('torod_demo_account', 'yes');
		$log_mode = get_option('torod_log_mode', 'enabled');

		$torod = new \Torod\torod();
		$data = $torod->getRegions();
		$data_countries = $torod->getcountries();
		$enabled_countries = get_option('torod_enabled_countries', []);
		$enabled_states = get_option('torod_enabled_states', []);
		$enabled_cities = get_option('torod_enabled_cities', []);

		$current_language = get_locale();
		?>
		<div class="wrap">
			<h1>
				<?php _e('Torod Settings', 'torod'); ?>
			</h1>
			<form method="post" action="">
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<?php _e('Demo Account', 'torod'); ?>
						</th>
						<td>
							<select name="torod_demo_account">
								<option value="yes" <?php selected($demo_account, 'yes'); ?>><?php _e('Yes', 'torod'); ?></option>
								<option value="no" <?php selected($demo_account, 'no'); ?>><?php _e('No', 'torod'); ?></option>
							</select>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e('Log Mode', 'torod'); ?>
						</th>
						<td>
							<select name="torod_log_mode">
								<option value="enabled" <?php selected($log_mode, 'enabled'); ?>><?php _e('Enabled', 'torod'); ?>
								</option>
								<option value="disabled" <?php selected($log_mode, 'disabled'); ?>><?php _e('Disabled', 'torod'); ?></option>
							</select>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e('Fetch region and cities', 'torod'); ?>
						</th>
						<td style="display: flex;">
							<button class="updatedbadmin">
								<?php _e('Sync', 'torod'); ?>
							</button><img class="lodinggif" width="30" height="30" src="<?php echo TOROD_LOADING_IMG_URL ?>"
								style="display: none; margin-left: 10px;">
							<p class="resultajax"></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e('Enabled Country', 'torod'); ?>
						</th>
						<td>
							<select class="allowscountry" multiple id="torod_enabled_country" name="torod_enabled_countries[]">
								<?php foreach ($data_countries as $key => $country) {
									$country_id = str_pad($key, 2, '0', STR_PAD_LEFT);
									$country_name = $current_language == 'ar' ? $country['ar'] : $country['en'];
									if ($enabled_countries) {
										$selected = in_array($country_id, $enabled_countries) ? 'selected ' : '';
									} else {
										$selected = '';
									}
									?>
									<option value="<?php echo esc_attr($country_id); ?>" <?php echo $selected; ?>> <?php echo esc_attr($country_name); ?></option>
								<?php } ?>
							</select>
							<button type="button" class="select-all">
								<?php _e('Select All', 'torod'); ?>
							</button>
							<button type="button" class="clear">
								<?php _e('Clear', 'torod'); ?>
							</button>
						</td>
					</tr>
					<?php
					foreach ($data_countries as $key => $country) {
						$country_id = str_pad($key, 2, '0', STR_PAD_LEFT);
						$data_region = $torod->getRegionsForadmin($key);
						?>
						<tr valign="top">
							<th scope="row">
								<?php _e('Enabled States for ' . $country['en'], 'torod'); ?>
							</th>
							<td>
								<select class="allowstates" multiple id="" name="torod_enabled_states[]"
									onChange="updateCitySelect()">
									<?php foreach ($data_region as $key => $region) {
										$region_id = str_pad($key, 2, '0', STR_PAD_LEFT);
										$region_name = $current_language == 'ar' ? $region['ar'] : $region['en'];
										?>
										<option value="<?php echo esc_attr($region_id); ?>" <?php echo in_array($region_id, $enabled_states) ? 'selected ' : ''; ?>> <?php echo esc_attr($region_name); ?></option>
									<?php } ?>
								</select>
								<button type="button" class="select-all">
									<?php _e('Select All', 'torod'); ?>
								</button>
								<button type="button" class="clear">
									<?php _e('Clear', 'torod'); ?>
								</button>
							</td>
						</tr>
					<?php }
					foreach ($data as $key => $region) {
						$region_id = str_pad($key, 2, '0', STR_PAD_LEFT);
						$cities = $torod->getAllCity($key);
						?>
						<tr valign="top">
							<th scope="row">
								<?php _e('Enabled Cities for ' . $region['en'], 'torod'); ?>
							</th>
							<td>
								<?php $random_number = rand(11111, 99999); ?>
								<input type="hidden" id="torod_enabled_cities_<?php echo $random_number; ?>"
									name="torod_enabled_cities_<?php echo $random_number; ?>" /> <br />
								<select class="allowcities" data-random_id="<?php echo $random_number; ?>" multiple>
									<?php foreach ($cities as $cityid => $city) { ?>
										<option value="<?php echo esc_attr($cityid); ?>" <?php echo in_array($cityid, $enabled_cities) ? 'selected ' : ''; ?>> <?php echo esc_attr($city['en']); ?></option>
									<?php } ?>
								</select>
								<button type="button" class="select-all">
									<?php _e('Select All', 'torod'); ?>
								</button>
								<button type="button" class="clear">
									<?php _e('Clear', 'torod'); ?>
								</button>
							</td>
						</tr>
					<?php } ?>
				</table>
				<p class="submit">
					<input type="submit" name="torod_settings_submit" class="button-primary"
						value="<?php _e('Save Changes', 'torod'); ?>" />
				</p>
			</form>
		</div>
	<?php }

	private function save_settings()
	{
		if (isset($_POST['torod_demo_account'])) {
			update_option('torod_demo_account', sanitize_text_field($_POST['torod_demo_account']));
		}

		if (isset($_POST['torod_log_mode'])) {
			update_option('torod_log_mode', sanitize_text_field($_POST['torod_log_mode']));
		}

		$enabled_states = isset($_POST['torod_enabled_states']) ? array_map('sanitize_text_field', (array) $_POST['torod_enabled_states']) : [];
		update_option('torod_enabled_states', $enabled_states);

		$enabled_countries = isset($_POST['torod_enabled_countries']) ? array_map('sanitize_text_field', (array) $_POST['torod_enabled_countries']) : [];
		update_option('torod_enabled_countries', $enabled_countries);

		$enabled_cities = [];
		foreach ($_POST as $key => $value) {
			if (strpos($key, 'torod_enabled_cities') === 0) {
				$current_value_array = explode(",", $value);
				$enabled_cities = array_merge($enabled_cities, array_map('sanitize_text_field', (array) $current_value_array));
			}
		}
		update_option('torod_enabled_cities', $enabled_cities);
	}
}