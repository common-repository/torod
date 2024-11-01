<?php
class torod_OrderLog
{
	public function display_orderlog_page()
	{
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		$torod = new \Torod\torod();
		$order_log_data = $torod->getAllOrderLog();
		?>
		<div class="wrap">
			<h1>
				<?php _e('Torod Order Log', 'torod'); ?>
			</h1>
			<?php 
					if(!empty($order_log_data)) {
			?>
			<a class="order-log-syncall button" href="javascript:void(0)"><?php _e('Sync All Orders', 'torod'); ?></a><img class="lodinggif-syncall" width="30" height="30" src="<?php echo TOROD_LOADING_IMG_URL ?>"
								style="display: none; margin-left: 10px;">
								<div class="syncall-result"></div>
			<table class="form-table order-log-table wp-list-table widefat fixed striped table-view-list posts">
				<thead>
				<tr>
					<th scope="col">
						<?php _e('Order ID', 'torod'); ?>
					</th>
					<th scope="col">
						<?php _e('Error Message', 'torod'); ?>
					</th>
					<th scope="col">
						<?php _e('Action', 'torod'); ?>
					</th>
				</tr>
				</thead>
				<?php 
					foreach($order_log_data as $key => $value) { 
				?>
					<tr>
						<td>
							<?php echo $value['order_id']; ?>
						</td>
						<td>
							<?php echo $value['error_message']; ?>
						</td>
						<td scope="row">
							<a class="button" href="<?php echo admin_url('post.php?post='.$value['order_id'].'&action=edit'); ?>" target = "_blank"><?php _e('Update', 'torod'); ?></a>
							<a class="button custom-sync-order-button" href="javascript:void(0)" data-order-id="<?php echo $value['order_id']; ?>"><?php _e('Sync Order', 'torod'); ?></a><img class="lodinggif-<?php echo $value['order_id']; ?>" width="30" height="30" src="<?php echo TOROD_LOADING_IMG_URL ?>"
								style="display: none; margin-left: 10px;">
						</td>
					</tr>
				<?php } ?>
			</table>
			<?php
				} else {
					echo "<h3>"; _e('No Order Log Found', 'torod'); echo "</h3>";
				}
			?>
		</div>
<?php }
 }