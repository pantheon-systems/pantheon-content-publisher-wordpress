<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}
?>
<div class="pcc-content">
	<?php
	require 'header.php';
	?>
	<div class="page-content">
		<?php require CPUB_PLUGIN_DIR . 'admin/templates/partials/spinner.php'; ?>
		<div id="pcc-content">
			<div class="create-collection-page">
				<?php require CPUB_PLUGIN_DIR . 'admin/templates/partials/error-message.php'; ?>
				<div class="page-grid">
					<div class="col-span-7 justify-self-start">
						<h1 class="page-header mt-[1.875rem]">
							<?php
							esc_html_e(
								'Create your first content collection',
								'pantheon-content-publisher-for-wordpress'
							)
							?>
						</h1>
						<p class="page-description mb-8">
							<?php esc_html_e(
								'Just one more step!
						A content collection is a set of content for your WordPress site.
						Connected to Google Workspace,
						it helps you organize and manage your site content in Google Docs.',
								'pantheon-content-publisher-for-wordpress'
							) ?>
						</p>
						<p class="text-with-border mb-[1.875rem]"><?php echo esc_url(site_url()) ?></p>
						<p class="text-lg font-bold mb-[1.25rem]">
							<?php esc_html_e('Publish your document as:', 'pantheon-content-publisher-for-wordpress') ?>
						</p>
						<div class="inputs-container">
							<div class='input-wrapper'>
								<input class='radio-input' name='post_type' type='radio'
									value="post"
									id="radio-post"
									<?php
									checked(get_option(CPUB_INTEGRATION_POST_TYPE_OPTION_KEY), 'post');
									?>>
								<label class="text-base" for="radio-post">
									<?php
									$labels = get_post_type_labels(get_post_type_object('post'));
									echo esc_html($labels->singular_name);
									?>
								</label>
							</div>
							<div class='input-wrapper'>
								<input class='radio-input' name='post_type' type='radio'
									value='page'
									id='radio-page'
									<?php
									checked(get_option(CPUB_INTEGRATION_POST_TYPE_OPTION_KEY), 'page');
									?>>
								<label class="text-base" for="radio-page">
									<?php
									$labels = get_post_type_labels(get_post_type_object('page'));
									echo esc_html($labels->singular_name);
									?>
								</label>
							</div>
						</div>
						<a class="primary-button" id="pcc-create-site" href="#">
							<?php esc_html_e('Create collection', 'pantheon-content-publisher') ?>
						</a>
						<div class="mb-10">
							<a class="secondary-button self-start justify-self-end" id="pcc-disconnect" href="#">
								<?php
								esc_html_e(
									'Reset your Google Workspace authentication',
									'pantheon-content-publisher-for-wordpress'
								)
								?>
							</a>
						</div>
					</div>
					<div class="col-span-5 justify-self-end">
						<img src="<?php echo esc_url(CPUB_PLUGIN_DIR_URL . 'assets/images/collection-image.png') ?>"
							alt="collection-image">
					</div>
				</div>
				<?php
				require 'footer.php';
				?>
			</div>
		</div>
	</div>
</div>
