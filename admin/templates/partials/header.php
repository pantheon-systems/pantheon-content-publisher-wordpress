<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}
?>
<div class="header">
	<img src="<?php
	echo esc_url(PCC_PLUGIN_DIR_URL . 'assets/images/pantheon-logo.svg') ?>" alt="Pantheon Logo">
	<span class="header-title"><?php
		esc_html_e('Content Publisher', 'pantheon-content-publisher-for-wordpress') ?></span>
</div>
