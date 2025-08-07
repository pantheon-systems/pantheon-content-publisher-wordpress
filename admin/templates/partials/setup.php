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
			<div class="welcome-page">
				<?php require CONTENT_PUB_PLUGIN_DIR . 'admin/templates/partials/error-message.php'; ?>
				<div class="page-grid mt-6">
					<div class="col-span-8">
						<div class="w-[80%]">
							<h1 class="page-header">
								<?php esc_html_e('Connect Google Workspace to your WordPress site', 'pantheon-content-publisher') ?>
							</h1>
							<p class="page-description">
								<?php
								esc_html_e(
									'Effortlessly publish content from Google Docs to your WordPress site using 
									Pantheon Content Publisher.',
									'pantheon-content-publisher'
								) ?>
							</p>
							<div class="mt-8 mb-0.5">
							<span class="font-semibold text-[0.83rem]">
								<?php esc_html_e('Management token', 'pantheon-content-publisher') ?>
							</span>
								<img class="scale-110 ms-1 pb-2.5 inline"
									 src="<?php
										echo esc_url(CONTENT_PUB_PLUGIN_DIR_URL . 'assets/images/red-dot.svg') ?>"
									 alt="Red Dot Icon">
								<div class="tooltip inline">
									<img class="scale-110 ms-2 pb-1 inline"
										 src="<?php echo
											esc_url(CONTENT_PUB_PLUGIN_DIR_URL . 'assets/images/circle-info.svg') ?>"
										 alt="Circle Info">
									<span class="tooltip-text">
									<?php
									esc_html_e('Enter the management token obtained from
                                    the Pantheon Content Publisher dashboard', 'pantheon-content-publisher') ?>
								</span>
								</div>

							</div>
							<input type="password"
								   placeholder="***************"
								   id="access-token"
								   name="access_token" class="input-with-border mb-2" required/>
							<button id="pcc-app-authenticate" class="primary-button">
								<?php esc_html_e('Connect', 'pantheon-content-publisher') ?>
							</button>
						</div>
						<p class="text-base mt-8 mb-10">
							<?php
							echo wp_kses_post(
								sprintf(
									// Translators: %s is the contents of the a tag
									// making it link to the Pantheon Content Publisher dashboard.
									__(
										"Don't have a token yet? Go to the " .
										"<a %s>Pantheon Content Publisher dashboard</a> to generate one.",
										'pantheon-content-publisher'
									),
									'class="pantheon-link  hover:text-secondary" target="_blank" ' .
									'href="https://content.pantheon.io/dashboard/settings/tokens?tab=1"'
								)
							)
							?>
						</p>
					</div>
					<div class="col-span-4 self-start justify-self-end">
						<img src="<?php echo esc_url(CONTENT_PUB_PLUGIN_DIR_URL . 'assets/images/multi-icons.png') ?>"
							 alt="Pantheon Logo">
					</div>
				</div>
				<?php
				require 'footer.php';
				?>
			</div>
		</div>
	</div>
</div>
