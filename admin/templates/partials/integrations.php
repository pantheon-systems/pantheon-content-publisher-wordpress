<?php
// Exit if accessed directly.
if (!\defined('ABSPATH')) {
	exit;
}
$activePlugins = apply_filters('active_plugins', get_option('active_plugins'));
?>
<div class="connected-collection-page integrations text-lg">
	<div class="py-2.5">
		<h3 class="font-bold mb-[0.5rem]">Advanced Custom Fields</h3>
		<p class="text-lg text-grey">Metadata fields defined in the Pantheon Content Publisher collection can be synced to Advanced Custom Fields.</p>

		<?php if (in_array('advanced-custom-fields/acf.php', $activePlugins)) : ?>
		<?php
			// Kludgy way to get all ACF fields, we need a post so use the first one.
			$wp_query = new WP_Query(array('post_type' => 'post'));
			if ( $wp_query->have_posts() ) : ?>
			<?php
				$field_groups = acf_get_field_groups(array('post_id' => $wp_query->posts[0]->ID));
				foreach ( $field_groups as $group ) : ?>
					<table class="widefat fixed mt-10" cellspacing="0">
						<thead>
							<tr>
								<th width="30%" scope="col">Advanced Custom Fields</th>
								<th width="10%" scope="col">Type</th>
								<th width="60%" scope="col">Content Publisher</th>
							</tr>
						</thead>
						<tfoot>
							<th colspan="3">ACF field group: <?php echo $group['title']; ?></th>
						</tfoot>
						<tbody>
						<?php 
							$fields = acf_get_fields($group['ID']);
							$metadata_map = get_option(PCC_INTEGRATION_METADATA_MAP, []);
							$metadata_user_map = get_option(PCC_INTEGRATION_METADATA_USER_MAP, 'login');
							$i = 0;
							foreach ($fields as $field) : ?>
								<tr class="<?php if ($i++ % 2) echo 'alternate'; ?>">
									<td class="text-lg"><label for="acf--<?php echo $field['name']; ?>"><?php echo $field['label']; ?></label></td>
									<td class="text-lg"><?php echo  $field['type']; ?></td>
									<td class="text-lg">
										<input  type="text"
												value="<?php if (!is_null($metadata_map) && array_key_exists($field['name'], $metadata_map)) echo $metadata_map[$field['name']]; ?>"
												placeholder="Content Publisher field name"										
												data-integration="acf"
												id="acf--<?php echo $field['name']; ?>"
												name="<?php echo $field['name']; ?>" 
												class="input-with-border" />
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endforeach; 
			endif; //end have posts
		?>
		<div class="inputs-container mt-10">
			<p class="text-lg">Map ACF user fields by matching:</p>
			<div class="input-wrapper">
				<input class="radio-input" type="radio" id="map-user-login" name="acf-user-map" value="login"<?php if ($metadata_user_map == 'login') echo ' checked="checked"'; ?>>
				<label class="text-base" for="map-user-login">User login</label><br>
			</div>
			<div class="input-wrapper">
				<input class="radio-input" type="radio" id="map-user-email" name="acf-user-map" value="email"<?php if ($metadata_user_map == 'email') echo ' checked="checked"'; ?>>
				<label class="text-base" for="map-user-email">User email</label><br>		
			</div>
		</div>
		<button id="pcc-app-integration-acf" class="primary-button">
			<?php esc_html_e('Update Metadata Mapping', 'pantheon-content-publisher-for-wordpress') ?>
		</button>
		<?php endif; //end acf active ?>
	</div>
</div>