<?php

namespace Pantheon\ContentPublisher\SmartComponents\Blocks;

/**
 * Embed block innerHTML builder
 */
class Embed
{
	/**
	 * Build innerHTML for embed blocks.
	 *
	 * Fetches the actual oEmbed HTML from the provider so it displays properly
	 * in the Block Editor and Classic Editor.
	 *
	 * @param array $attrs Embed block attributes
	 * @return string Inner HTML with oEmbed content
	 */
	public static function buildInnerHtml(array $attrs): string
	{
		$url = $attrs['url'] ?? '';
		if (!$url) {
			return '';
		}

		$oembed_html = wp_oembed_get($url) ?: $url;

		$caption = $attrs['caption'] ?? '';
		$html = '<figure class="wp-block-embed"><div class="wp-block-embed__wrapper">' . "\n";
		$html .= $oembed_html . "\n";
		$html .= '</div>';

		if ($caption) {
			$html .= '<figcaption class="wp-element-caption">' . esc_html($caption) . '</figcaption>';
		}

		$html .= '</figure>';

		return $html;
	}
}
