<?php

namespace Pantheon\ContentPublisher\SmartComponents\Blocks;

/**
 * Code block innerHTML builder
 */
class Code
{
	/**
	 * Build innerHTML for code blocks.
	 *
	 * @param array $attrs Code block attributes
	 * @return string Inner HTML with code structure
	 */
	public static function buildInnerHtml(array $attrs): string
	{
		$content = $attrs['content'] ?? '';

		if (!$content) {
			return '';
		}

		return '<code>' . esc_html($content) . '</code>';
	}

	/**
	 * Get the wrapper HTML for code blocks.
	 *
	 * @param string $innerHTML Inner HTML content
	 * @param array $attrs Block attributes
	 * @return string HTML with pre wrapper
	 */
	public static function getWrapper(string $innerHTML, array $attrs): string
	{
		return '<pre class="wp-block-code">' . $innerHTML . '</pre>';
	}
}
