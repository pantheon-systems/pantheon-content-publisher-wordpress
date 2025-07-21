<?php
// Exit if accessed directly.
if (!\defined('ABSPATH')) {
	exit;
}
?>
<div class="pcc-content">
	<?php
	require 'header.php';
	?>
	<div class="page-content">
		<div class="connected-collection-page">
			<?php require CONTENT_PUB_PLUGIN_DIR . 'admin/templates/partials/error-message.php'; ?>
			<div class="py-2.5">
				<h3 class="text-grey font-bold text-sm mb-[0.5rem]">
					<?php esc_html_e('Connected content collection', 'pantheon-content-publisher-for-wordpress') ?>
				</h3>
				<div class="page-grid mb-9">
					<div class="col-span-8 justify-self-start break-all">
						<h1 class="page-header">
							<?php echo esc_url(site_url()) ?>
						</h1>
						<p class="font-bold text-sm mt-2">
							<span class="text-grey font-normal">COLLECTION ID:</span>
							<?php echo esc_html(get_option(CONTENT_PUB_SITE_ID_OPTION_KEY)) ?>
						</p>
					</div>
					<a class="secondary-button self-start col-span-4 justify-self-end" 
						href="<?php echo esc_url(add_query_arg([
						'page' => 'pantheon-content-publisher-for-wordpress',
						'view' => 'disconnect-confirmation'
					], admin_url('admin.php'))) ?>">
						<span>
							<?php esc_html_e('Disconnect collection', 'pantheon-content-publisher-for-wordpress') ?>
						</span>
					</a>
				</div>
				<div>
					<div class="divider-border"></div>
					<p class="text-lg font-bold mt-10 mb-[1.25rem]">
						<?php esc_html_e('Publish your document as:', 'pantheon-content-publisher-for-wordpress') ?>
					</p>
					<div class="inputs-container">
						<div class='input-wrapper'>
							<input class='radio-input' name='post_type' type='radio' value='post' id='radio-post' <?php
							checked(get_option(CONTENT_PUB_INTEGRATION_POST_TYPE_OPTION_KEY), 'post');
							?>>
							<label class="text-base" for="radio-post">
								<?php
								$labels = get_post_type_labels(get_post_type_object('post'));
								echo esc_html($labels->singular_name);
								?>
							</label>
						</div>
						<div class='input-wrapper'>
							<input class='radio-input' name='post_type' type='radio' value='page' id='radio-page' <?php
							checked(get_option(CONTENT_PUB_INTEGRATION_POST_TYPE_OPTION_KEY), 'page');
							?>>
							<label class="text-base" for="radio-page">
								<?php
								$labels = get_post_type_labels(get_post_type_object('page'));
								echo esc_html($labels->singular_name);
								?>
							</label>
						</div>
					</div>
					<a class="primary-button" id="pcc-update-collection" href="#">
						<?php esc_html_e('Save configuration', 'pantheon-content-publisher-for-wordpress') ?>
					</a>
				</div>
			</div>
			<?php
			require 'footer.php';
			?>
		</div>
	</div>
</div>