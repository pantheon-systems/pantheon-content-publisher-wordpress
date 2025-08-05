<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}
?>
<div id="pcc-error-message" class="pcc-error-message hidden flex justify-between">
	<div class="flex items-center gap-2.5">
		<img src="<?php echo esc_url(CONTENT_PUB_PLUGIN_DIR_URL . 'assets/images/diamond-exclamation.png') ?>"
			 alt="Diamond exclamation icon">
		<div id="pcc-error-text"></div>
	</div>
	<div class="flex items-center gap-4">
		<button id="pcc-error-close-button">
			<img src="<?php echo esc_url(CONTENT_PUB_PLUGIN_DIR_URL . 'assets/images/close-icon.png') ?>"
				 alt="Close icon">
		</button>
	</div>
</div>
