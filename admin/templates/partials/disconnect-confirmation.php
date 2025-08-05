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
		<?php require CONTENT_PUB_PLUGIN_DIR . 'admin/templates/partials/spinner.php'; ?>
		<div id="pcc-content">
			<div class="disconnect-confirm-page">
				<?php require CONTENT_PUB_PLUGIN_DIR . 'admin/templates/partials/error-message.php'; ?>
				<div class="page-grid">
					<div class="col-span-7">
						<h1 class="page-header">
							<?php
							esc_html_e(
								'Are you sure you want to disconnect the content collection?',
								'pantheon-content-publisher-for-wordpress'
							)
							?>
						</h1>
						<p class="page-description mb-5">
							<?php esc_html_e(
								'All Google Docs in the collection will be disconnected from your site
                            , and you wont be able to update site content in Google Docs anymore.',
								'pantheon-content-publisher-for-wordpress'
							) ?>
						</p>
						<p class="page-description">
							<?php esc_html_e(
								'The content will remain on the site, manageable using the WordPress admin interface.',
								'pantheon-content-publisher-for-wordpress'
							) ?>
						</p>
						<div class="flex gap-4 mt-[1.875rem]">
							<a class="secondary-button"
							   href="<?php echo esc_url(add_query_arg([
								   'page' => 'pantheon-content-publisher-for-wordpress',
								   'view' => 'connected-collection'], admin_url('admin.php'))) ?>">
								<?php esc_html_e('Stay connected', 'pantheon-content-publisher-for-wordpress') ?>
							</a>
							<a class="danger-button" id="pcc-disconnect" href="#">
								<?php esc_html_e('Disconnect', 'pantheon-content-publisher-for-wordpress') ?>
							</a>
						</div>
					</div>
					<div class="col-span-5 justify-self-end">
						<img src="<?php echo esc_url(CONTENT_PUB_PLUGIN_DIR_URL . 'assets/images/Inspection.png') ?>"
							 alt="Inspection images"
						>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
