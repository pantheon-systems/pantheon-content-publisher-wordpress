<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php wp_head(); ?>
	<style>
		/* Clean preview styling */
		body {
			margin: 0;
			padding: 16px;
		}
		/* Hide admin bar if shown */
		#wpadminbar {
			display: none !important;
		}
	</style>
</head>
<body <?php body_class('component-preview'); ?>>
	<?php
	global $cpub_preview_content;
	echo $cpub_preview_content;
	?>
	<?php wp_footer(); ?>
</body>
</html>
