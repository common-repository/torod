<?php
use Torod\torod;

class screen
{
	public function firstscreen()
	{
		$this->loginform();
	}
	public function loginform()
	{
		$torod = new torod;
		wp_enqueue_style('bootstrap-min-css', plugins_url('assets/css/bootstrap.min.css', dirname(__FILE__)));
		wp_enqueue_style('torod_style', plugins_url('assets/css/torod_style.css', dirname(__FILE__)));
		wp_enqueue_script('bootstrap-min-js', plugins_url('assets/js/bootstrap.min.js', dirname(__FILE__)), array('jquery'));
		?>
		<div class="torodarea mb-5">
			<div class="container text-center mt-5">
				<div id="multi-step-form-container">
					<ul class="form-stepper form-stepper-horizontal text-center mx-auto pl-0">
						<li class="form-stepper-active text-center form-stepper-list" step="1">
							<a class="mx-2">
								<span class="form-stepper-circle">
									<span>1</span>
								</span>
								<div class="label">Connect Store</div>
							</a>
						</li>
						<li class="form-stepper-unfinished text-center form-stepper-list" step="2">
							<a class="mx-2">
								<span class="form-stepper-circle text-muted">
									<span>2</span>
								</span>
								<div class="label text-muted">Sync Preferences</div>
							</a>
						</li>
						<li class="form-stepper-unfinished text-center form-stepper-list" step="3">
							<a class="mx-2">
								<span class="form-stepper-circle text-muted">
									<span>3</span>
								</span>
								<div class="label text-muted">Order Mapping Status</div>
							</a>
						</li>
					</ul>
					<section id="step-1" class="form-step">
						<h2 class="font-normal mb-5">Connect Store</h2>
					<?php
					if (!empty($_POST['u_email'])) {
						$user_email = sanitize_email($_POST['u_email']);
						$sonuc = $torod->loginUser($user_email, $_POST['u_password']);
						if ($sonuc['status'] == 0) {
							echo '<p style="color: #fff;background-color: #d70909;width:100%;padding: 15px;" >' . 'Someting wrong<br>' . esc_attr($sonuc['message']) . '</p> ';
						}
					}
					if (!empty($_POST['first_name'])) {
						$first_name = sanitize_text_field($_POST['first_name']);
						$last_name = sanitize_text_field($_POST['last_name']);
						$store_name = sanitize_text_field($_POST['store_name']);
						$user_email = sanitize_email($_POST['user_email']);
						$phone_number = sanitize_text_field($_POST['phone_number']);
						if (!empty($first_name) and !empty($last_name) and !empty($store_name) and !empty($user_email) and !empty($phone_number)) {
							$sonuc2 = $torod->userregister($first_name, $last_name, $store_name, $user_email, $phone_number);
						} else {
							echo '<p style="color: #fff;background-color: #d70909;width:100%;padding: 15px;" >' . 'All fields required</p > ';
							echo '<br>';
						}
					}
					if ($torod->checkuser()): ?>
						<svg xmlns="http://www.w3.org/2000/svg" width="250" height="250" fill="#20c997" class="bi bi-check-circle-fill" viewbox="0 0 16 16">
							<path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
						</svg>
						<h1>Your Store is Connected Successfully!</h1>
						<br>
						<button class="btn btn-lg btn-warning baglantikes" type="button">Disconnect
						</button>
						<button class="btn btn-lg btn-success btn-navigate-form-step btn-yemliha" type="button" step_number="2">Next
						</button>
						<br>
				<?php else: ?>
					<div class="row">
						<div class="col-12 col-md-6">
							<h3>Existing Torod Merchant?</h3>
							<hr>
							<br>
							<h5 class="text-start">Login directly using your Torod Account</h5>
							<br>
							<form id="userAccountSetupForm" name="userAccountSetupForm" enctype="multipart/form-data" method="POST">
								<div class="mb-3 row">
									<label for="staticEmail" class="col-sm-2 col-form-label">Email</label>
									<div class="col-sm-8">
										<input name="u_email" type="text" class="form-control-plaintext" id="torod_email" placeholder="email@example.com">
									</div>
								</div>
								<div class="mb-3 row">
									<label for="torod_Password" class="col-sm-2 col-form-label">Password</label>
									<div class="col-sm-8">
										<input name="u_password" type="password" class="form-control" id="torod_Password">
									</div>
								</div>
								<div class="d-grid gap-2 col-6 mx-auto text-end">
									<button class="btn btn-primary btn-yemliha" type="submit">Connect
									</button>
								</div>
							</form>
						</div>

						<div class="col-12 col-md-6">
							<h3>Don’t Have Torod Account?</h3>
							<hr>
							<br>
							<h5 class="text-start">No worries, we will create you a free account</h5>
							<div class="mt-4">
								<form id="userRegister" name="userRegister" enctype="multipart/form-data" method="POST">
									<div class="mb-3 row">
										<label for="first_name" class="col-sm-4 col-form-label text-start">First Name</label>
										<div class="col-sm-8">
											<input value="<?php
											echo $torod->userinfo(get_bloginfo('admin_email'))->first_name; ?>" name="first_name" type="text" class="form-control-plaintext" id="first_name">
										</div>
									</div>
									<div class="mb-3 row">
										<label for="last_name" class="col-sm-4 col-form-label text-start">Last Name</label>
										<div class="col-sm-8">
											<input value="<?php
											echo $torod->userinfo(get_bloginfo('admin_email'))->last_name; ?>" name="last_name" type="text" class="form-control-plaintext" id="last_name">
										</div>
									</div>
									<div class="mb-3 row">
										<label for="store_name" class="col-sm-4 col-form-label text-start">Store Name</label>
										<div class="col-sm-8">
											<input name="store_name" type="text" class="form-control-plaintext" id="store_name">
										</div>
									</div>
									<div class="mb-3 row">
										<label for="user_email" class="col-sm-4 col-form-label text-start">Email</label>
										<div class="col-sm-8">
											<input value="<?php
											echo get_bloginfo('admin_email') ?>" name="user_email" type="text" class="form-control-plaintext" id="user_email">
										</div>
									</div>
									<div class="mb-3 row">
										<label for="phone_number" class="col-sm-4 col-form-label text-start">Phone Number</label>
										<div class="col-sm-8">
											<input value="<?php
											echo $torod->userinfo(get_bloginfo('admin_email'))->shipping_phone; ?>" name="phone_number" type="tel" class="form-control-plaintext" id="phone_number">
										</div>
									</div>
									<div class="d-grid gap-2 col-6 mx-auto text-end">
										<button class="btn btn-primary btn-yemliha" type="submit">Create Free Account
										</button>
									</div>
								</form>
							</div>

							<div class="text-start">
								<br>
								<h5 class="fw-normal">
									Your account Email will be (<?php
									echo get_bloginfo('admin_email') ?>)</h5>
							</div>
							<p class="text-start fs-6 fw-bold m-0 p-0">Note:</p>
							<p class="text-start fs-6 m-0 p-0">If you wish to use different email, you can create a free account on Torod then connect it to your store!</p>
						</div>
					</div>
				<?php endif; ?>
					</section>
					<section id="step-2" class="form-step d-none">
						<h2 class="font-normal mb-5">Sync Preferences</h2>
						<h4 class=" text-start mt-5">Which order status do you want to push to Torod automatically?</h4>
						<p class="text-start">Note: These orders will be pushed to Torod as (New) – and waiting for you to select a shipper
						</p>
						<div class="row pt-5">
							<div class="col-md-12">
								<div class="col-12">
									<p class="font-bold fs-5">Order Status</p>
									<select class="select_status_order form-control" id="select_status_order" multiple="multiple">
										<option value="">Select options</option>
									</select>
								</div>
							</div>
							<div class="col-md-12">
								<div class="col-12">
									<p class="font-bold fs-5 mt-3">COD Payment Methods</p>
									<select class="select_payment_method form-control" id="select_payment_method" multiple="multiple">
										<option value="">Select options</option>
									</select>
								</div>
							</div>
						</div>
						<style>
							.form-check .form-check-input {
								float: none !important;
								margin-left: 0 !important;
							}
						</style>
						<div class="row text-start" style="display: none;">
							<?php $veri = get_option('status_radio'); ?>
							<p class="row-gap-3"></p>
							<p class="fs-5 text-start">Which orders would you like to sync?</p>
							<div class="form-check">
								<input class="form-check-input" type="radio" name="statusradio" value="onlynew" <?php echo ($veri == "onlynew") ? "checked" : ""; ?>/>
								<label class="form-check-label" for="flexRadioDefault1">Sync orders from now on</label>
							</div>
							<div class="form-check ">
								<input class="form-check-input" type="radio" name="statusradio" value="newandold" <?php echo ($veri == "newandold") ? "checked" : ""; ?>/>
								<label class="form-check-label" for="flexRadioDefault2">Sync new and old orders as well</label>
							</div>
						</div>
						<div class="mt-3">
							<button class="btn btn-lg btn-warning btn-navigate-form-step" type="button" step_number="1">Prev</button>
							<button class="btn btn-lg btn-success btn-yemliha statusregister" type="button">SAVE</button>
							<img class="lodinggif" width="30" height="30" src="<?php echo TOROD_LOADING_IMG_URL ?>"
								style="display: none; margin-left: 10px;">
								<button class="btn btn-lg btn-success btn-navigate-form-step btn-yemliha" type="button" step_number="3">Next
						</button>
						</div>
					</section>
					<section id="step-3" class="form-step d-none">
						<h2 class="font-normal mb-3"><?php _e('Torod Order Mapping Status', 'torod'); ?></h2>
						<div class="row pt-3">
							<?php 
								$order_statusess = wc_get_order_statuses(); 
								$selected_omapping = get_option("torod_ordermappingstatus");
							?>
								<div class="col-md-6 mb-4">
									<p class="text-start"><?php _e('Created Status', 'torod'); ?></p>
									<select name="torod_order_mapping_status[]" class="d-block w-100 torod_webhook_status" data-wp-status="created">
										<option value="">Select option</option>
										<?php foreach ($order_statusess as $a => $b) { ?>
										<option <?php echo ($selected_omapping['created'] ==  $b) ? ' selected="selected"' : '';?>><?php _e($b, 'torod'); ?></option>
										<?php } ?>	
									</select>
								</div>
								<div class="col-md-6 mb-4">
									<p class="text-start"><?php _e('Cancelled Status', 'torod'); ?></p>
									<select name="torod_order_mapping_status[]" class="d-block w-100 torod_webhook_status" data-wp-status="cancelled">
										<option value="">Select option</option>
										<?php foreach ($order_statusess as $a => $b) { ?>
										<option <?php echo ($selected_omapping['cancelled'] ==  $b) ? ' selected="selected"' : '';?>><?php _e($b, 'torod'); ?></option>
										<?php } ?>	
									</select>
								</div>
								<div class="col-md-6 mb-4">
									<p class="text-start"><?php _e('Shipped Status', 'torod'); ?></p>
									<select name="torod_order_mapping_status[]" class="d-block w-100 torod_webhook_status" data-wp-status="Shipped">
										<option value="">Select option</option>
										<?php foreach ($order_statusess as $a => $b) { ?>
										<option <?php echo ($selected_omapping['Shipped'] ==  $b) ? ' selected="selected"' : '';?>><?php _e($b, 'torod'); ?></option>
										<?php } ?>	
									</select>
								</div>
								<div class="col-md-6 mb-4">
									<p class="text-start"><?php _e('Delivered Status', 'torod'); ?></p>
									<select name="torod_order_mapping_status[]" class="d-block w-100 torod_webhook_status" data-wp-status="Delivered">
										<option value="">Select option</option>
										<?php foreach ($order_statusess as $a => $b) { ?>
										<option <?php echo ($selected_omapping['Delivered'] ==  $b) ? ' selected="selected"' : '';?>><?php _e($b, 'torod'); ?></option>
										<?php } ?>	
									</select>
								</div>
								<div class="col-md-6 mb-4">
									<p class="text-start"><?php _e('Failed Status', 'torod'); ?></p>
									<select name="torod_order_mapping_status[]" class="d-block w-100 torod_webhook_status" data-wp-status="failed">
										<option value="">Select option</option>
										<?php foreach ($order_statusess as $a => $b) { ?>
										<option <?php echo ($selected_omapping['failed'] ==  $b) ? ' selected="selected"' : '';?>><?php _e($b, 'torod'); ?></option>
										<?php } ?>	
									</select>
								</div>
								<div class="col-md-6 mb-4">
									<p class="text-start"><?php _e('RTO Status', 'torod'); ?></p>
									<select name="torod_order_mapping_status[]" class="d-block w-100 torod_webhook_status" data-wp-status="rto">
										<option value="">Select option</option>
										<?php foreach ($order_statusess as $a => $b) { ?>
										<option <?php echo ($selected_omapping['rto'] ==  $b) ? ' selected="selected"' : '';?>><?php _e($b, 'torod'); ?></option>
										<?php } ?>	
									</select>
								</div>
								<div class="col-md-6 mb-4">
									<p class="text-start"><?php _e('Lost Status', 'torod'); ?></p>
									<select name="torod_order_mapping_status[]" class="d-block w-100 torod_webhook_status" data-wp-status="lost">
										<option value="">Select option</option>
										<?php foreach ($order_statusess as $a => $b) { ?>
										<option <?php echo ($selected_omapping['lost'] ==  $b) ? ' selected="selected"' : '';?>><?php _e($b, 'torod'); ?></option>
										<?php } ?>	
									</select>
								</div>
								<div class="col-md-6 mb-4">
									<p class="text-start"><?php _e('Damage Status', 'torod'); ?></p>
									<select name="torod_order_mapping_status[]" class="d-block w-100 torod_webhook_status" data-wp-status="damage">
										<option value="">Select option</option>
										<?php foreach ($order_statusess as $a => $b) { ?>
										<option <?php echo ($selected_omapping['damage'] ==  $b) ? ' selected="selected"' : '';?>><?php _e($b, 'torod'); ?></option>
										<?php } ?>	
									</select>
								</div>
						</div>
						<div class="mt-3">
							<button class="btn btn-lg btn-warning btn-navigate-form-step" type="button" step_number="2">Prev</button>
							<button class="btn btn-lg btn-primary btn-yemliha webhook_o_status_save" type="button">SAVE</button>
							<img class="lodinggif" width="30" height="30" src="<?php echo TOROD_LOADING_IMG_URL ?>"
								style="display: none; margin-left: 10px;">
						</div>
					</section>
				</div>
			</div>
		</div><?php
	}
}