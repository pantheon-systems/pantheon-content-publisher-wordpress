<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}
?>
<div class="pcc-content notice pcc-notice is-dismissible">
	<div class="page-content">
		<div class="continue-setup">
			<div class="header">
				<img src="<?php
									echo esc_url(PCC_PLUGIN_DIR_URL . 'assets/images/pantheon-logo.svg') ?>"
					alt="Pantheon Logo">
				<span class="header-title">
					<?php
					esc_html_e('Content Publisher', 'pantheon-content-publisher-for-wordpress') ?>
				</span>
			</div>
			<div class="">
				<p class="page-description">
					<?php
					esc_html_e(
						'Plugin installed! To create and publish content,
					complete the setup by connecting Google Workspace to your WordPress site.',
						'pantheon-content-publisher-for-wordpress'
					) ?>
				</p>
				<a href="<?php
									echo esc_url(add_query_arg(
										['page' => 'pantheon-content-publisher-for-wordpress'],
										admin_url('admin.php')
									)) ?>"
					class="primary-button me-auto"><?php
					esc_html_e('Complete setup', 'pantheon-content-publisher-for-wordpress') ?>
				</a>
			</div>
		</div>
	</div>
</div>
