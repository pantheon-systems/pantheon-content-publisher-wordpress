<?php

namespace Pantheon\ContentPublisher\SmartComponents;

/**
 * Handles enqueuing block styles for smart components in post content
 */
class BlockStylesHandler
{
	public function __construct()
	{
		add_filter('the_content', [$this, 'enqueueBlockStyles'], 8);
	}

	/**
	 * Parse content for blocks and enqueue their styles.
	 *
	 * @param string $content Post content
	 * @return string Unchanged content
	 */
	public function enqueueBlockStyles(string $content): string
	{
		// Early return if not in main query or not singular
		if (!is_singular() || !in_the_loop() || !is_main_query()) {
			return $content;
		}

		// Extract block names from rendered HTML classes
		$blocks = $this->extractBlocksFromContent($content);

		// Enqueue styles for each block type
		foreach ($blocks as $blockName) {
			$this->enqueueBlockAssets($blockName);
		}

		return $content;
	}

	/**
	 * Extract block types from rendered HTML content.
	 *
	 * @param string $content HTML content
	 * @return array Array of unique block names
	 */
	private function extractBlocksFromContent(string $content): array
	{
		$blocks = [];

		// Match wp-block-* classes
		if (preg_match_all('/wp-block-([a-z0-9-]+)/', $content, $matches)) {
			foreach ($matches[1] as $blockSlug) {
				// Convert wp-block-quote to core/quote
				// Convert wp-block-embed to core/embed
				$blockName = 'core/' . str_replace('-', '-', $blockSlug);
				$blocks[] = $blockName;
			}
		}

		return array_unique($blocks);
	}

	/**
	 * Enqueue styles and scripts for a specific block.
	 *
	 * @param string $blockName Block name (e.g., 'core/quote')
	 * @return void
	 */
	private function enqueueBlockAssets(string $blockName): void
	{
		$registry = \WP_Block_Type_Registry::get_instance();
		$blockType = $registry->get_registered($blockName);

		if (!$blockType) {
			return;
		}

		// Enqueue block's style
		if (!empty($blockType->style)) {
			wp_enqueue_style($blockType->style);
		}

		// Enqueue block's editor style (some blocks use this on frontend too)
		if (!empty($blockType->editor_style)) {
			wp_enqueue_style($blockType->editor_style);
		}

		// Enqueue block's script
		if (!empty($blockType->script)) {
			wp_enqueue_script($blockType->script);
		}

		// Enqueue block's view script
		if (!empty($blockType->view_script)) {
			wp_enqueue_script($blockType->view_script);
		}
	}
}
