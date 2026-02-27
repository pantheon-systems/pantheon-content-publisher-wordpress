<?php

namespace Pantheon\ContentPublisher\Components;

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols -- WordPress direct access guard, consistent with rest of codebase.
if (!defined('ABSPATH')) {
	exit;
}

use Pantheon\ContentPublisher\Interfaces\SmartComponentInterface;

/**
 * Media Embed smart component.
 *
 * Renders embedded media (YouTube, Vimeo, etc.) using WordPress oEmbed
 * with an iframe fallback for unsupported providers.
 */
class MediaEmbed implements SmartComponentInterface
{
	private const DEFAULT_WIDTH = '100%';
	private const DEFAULT_HEIGHT = '400px';

	/**
	 * {@inheritDoc}
	 */
	public function type(): string
	{
		return 'MEDIA_EMBED';
	}

	/**
	 * {@inheritDoc}
	 */
	public function schema(): array
	{
		return [
			'title' => 'Media Embed',
			'iconUrl' => null,
			'fields' => [
				'url' => [
					'displayName' => 'URL',
					'type' => 'string',
					'required' => true,
				],
				'width' => [
					'displayName' => 'Width',
					'type' => 'string',
					'required' => false,
				],
				'height' => [
					'displayName' => 'Height',
					'type' => 'string',
					'required' => false,
				],
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function allowedHtmlTags(): array
	{
		return [
			'iframe' => [
				'src' => true,
				'width' => true,
				'height' => true,
				'style' => true,
				'allowfullscreen' => true,
				'loading' => true,
				'frameborder' => true,
				'allow' => true,
				'title' => true,
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function render(array $attrs): string
	{
		$url = $attrs['url'] ?? '';
		if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
			return '';
		}

		$width = !empty($attrs['width']) ? $attrs['width'] : self::DEFAULT_WIDTH;
		$height = !empty($attrs['height']) ? $attrs['height'] : self::DEFAULT_HEIGHT;

		return $this->renderOembed($url, $width, $height)
			?? $this->renderIframe($url, $width, $height);
	}

	/**
	 * Render using WordPress oEmbed for supported providers (YouTube, Vimeo, etc.).
	 *
	 * Overrides the iframe dimensions in the oEmbed response to match the
	 * author's requested values.
	 *
	 * @return string|null Rendered HTML, or null if the provider is not supported.
	 */
	private function renderOembed(string $url, string $width, string $height): ?string
	{
		$oembedArgs = [];
		$numericWidth = (int) $width;
		if ($numericWidth > 0 && $width !== '100%') {
			$oembedArgs['width'] = $numericWidth;
		}
		$numericHeight = (int) $height;
		if ($numericHeight > 0) {
			$oembedArgs['height'] = $numericHeight;
		}

		$html = wp_oembed_get($url, $oembedArgs);
		if (!$html) {
			return null;
		}

		// Override iframe dimensions to match the author's values.
		$doc = new \DOMDocument();
		@$doc->loadHTML('<body>' . $html . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

		$iframes = $doc->getElementsByTagName('iframe');
		if ($iframes->length > 0) {
			$iframe = $iframes->item(0);
			$iframe->setAttribute('width', '100%');
			$iframe->setAttribute('height', $height);
		}

		$body = $doc->getElementsByTagName('body')->item(0);
		$html = '';
		if ($body) {
			foreach ($body->childNodes as $child) {
				$html .= $doc->saveHTML($child);
			}
		}

		return sprintf(
			'<div class="cpub-media-embed" style="width:%s;margin:1.5em 0;">%s</div>',
			esc_attr($width),
			$html
		);
	}

	/**
	 * Render a plain iframe for providers not supported by WordPress oEmbed.
	 *
	 * @return string Rendered HTML.
	 */
	private function renderIframe(string $url, string $width, string $height): string
	{
		return sprintf(
			'<div class="cpub-media-embed" style="width:%s;margin:1.5em 0;">'
			. '<iframe src="%s" style="width:100%%;height:%s;border:0;" '
			. 'allowfullscreen loading="lazy"></iframe></div>',
			esc_attr($width),
			esc_url($url),
			esc_attr($height)
		);
	}
}
