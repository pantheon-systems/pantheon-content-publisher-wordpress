<?php

namespace Pantheon\ContentPublisher\SmartComponents\Blocks;

/**
 * Quote block innerHTML builder
 */
class Quote
{
	/**
	 * Build innerHTML for quote blocks.
	 *
	 * @param array $attrs Quote block attributes
	 * @return string Inner HTML with quote structure
	 */
	public static function buildInnerHtml(array $attrs): string
	{
		$value = $attrs['value'] ?? '';
		$citation = $attrs['citation'] ?? '';

		$html = '';

		if ($value) {
			$html .= '<p>' . esc_html($value) . '</p>';
		}

		if ($citation) {
			$html .= '<cite>' . esc_html($citation) . '</cite>';
		}

		return $html;
	}

	/**
	 * Get the wrapper HTML for quote blocks.
	 *
	 * @param string $innerHTML Inner HTML content
	 * @param array $attrs Block attributes
	 * @return string HTML with blockquote wrapper
	 */
	public static function getWrapper(string $innerHTML, array $attrs): string
	{
		return '<blockquote class="wp-block-quote">' . $innerHTML . '</blockquote>';
	}
}
