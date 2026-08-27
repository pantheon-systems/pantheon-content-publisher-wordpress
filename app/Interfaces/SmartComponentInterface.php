<?php

namespace Pantheon\ContentPublisher\Interfaces;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Interface for smart components.
 *
 * Each smart component (e.g. Media Embed) implements this interface
 * and is registered with the SmartComponents registry.
 */
interface SmartComponentInterface
{
	/**
	 * Return the component type identifier (e.g. 'MEDIA_EMBED').
	 *
	 * @return string
	 */
	public function type(): string;

	/**
	 * Return the component schema for the Google Docs add-on.
	 *
	 * @return array
	 */
	public function schema(): array;

	/**
	 * Render the component to HTML.
	 *
	 * @param array $attrs Component attributes.
	 * @return string
	 */
	public function render(array $attrs): string;

	/**
	 * Return HTML tags and attributes required by this component
	 * for wp_kses_allowed_html.
	 *
	 * Format: ['tag' => ['attribute' => true, ...], ...]
	 *
	 * @return array
	 */
	public function allowedHtmlTags(): array;
}
