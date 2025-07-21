<?php
// Exit if accessed directly.
if (!\defined('ABSPATH')) {
	exit;
}
?>
<div id="spinner-box" class="hidden">
	<div class="pcc-spinner-container">
		<img class="pcc-spinner" src="<?php echo esc_url(CONTENT_PUB_PLUGIN_DIR_URL . 'assets/images/spinner.svg') ?>"
			 alt="Spinner icon">
		<p id="spinner-text" class="text-base"></p>
	</div>
</div>
