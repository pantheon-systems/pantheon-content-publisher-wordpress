<?php

namespace Pantheon\ContentPublisher\SmartComponents\Blocks;

/**
 * Pullquote block innerHTML builder
 */
class Pullquote
{
	/**
	 * Build innerHTML for pullquote blocks.
	 *
	 * Pullquote blocks require a <figure> wrapper to match WordPress core structure.
	 *
	 * @param array $attrs Pullquote block attributes
	 * @return string Inner HTML with pullquote structure
	 */
	public static function buildInnerHtml(array $attrs): string
	{
		$value = $attrs['value'] ?? '';
		$citation = $attrs['citation'] ?? '';

		// Pullquote uses a <blockquote> inside the block wrapper
		// The wrapper gets added by render_block(), so we only return the inner content
		$html = '<blockquote>';

		if ($value) {
			$html .= '<p>' . esc_html($value) . '</p>';
		}

		if ($citation) {
			$html .= '<cite>' . esc_html($citation) . '</cite>';
		}

		$html .= '</blockquote>';

		return $html;
	}

	/**
	 * Get the wrapper HTML for pullquote blocks.
	 *
	 * @param string $innerHTML Inner HTML content
	 * @param array $attrs Block attributes
	 * @return string HTML with figure wrapper
	 */
	public static function getWrapper(string $innerHTML, array $attrs): string
	{
		return '<figure class="wp-block-pullquote">' . $innerHTML . '</figure>';
	}
}
