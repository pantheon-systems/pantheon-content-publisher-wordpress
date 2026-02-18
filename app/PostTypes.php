<?php

namespace Pantheon\ContentPublisher;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Utility class for working with WordPress post types in the plugin context.
 */
class PostTypes
{
	/**
	 * Post types excluded from the available list.
	 */
	private const EXCLUDED_TYPES = ['attachment'];

	/**
	 * Get all available public post types (excluding attachments).
	 *
	 * @return array<int, array{name: string, label: string}>
	 */
	public static function getAvailable(): array
	{
		$postTypes = get_post_types(['public' => true], 'objects');
		$result = [];

		foreach ($postTypes as $postType) {
			if (in_array($postType->name, self::EXCLUDED_TYPES, true)) {
				continue;
			}
			$result[] = [
				'name' => $postType->name,
				'label' => $postType->labels->singular_name,
			];
		}

		return $result;
	}

	/**
	 * Check whether a given post type is valid (public and not excluded).
	 *
	 * @param string $postType The post type slug to validate.
	 * @return bool
	 */
	public static function isValid(string $postType): bool
	{
		$validTypes = get_post_types(['public' => true], 'names');
		return isset($validTypes[$postType])
			&& !in_array($postType, self::EXCLUDED_TYPES, true);
	}

	/**
	 * Validate a post type, returning the validated type or a fallback.
	 *
	 * @param string $postType The post type to validate.
	 * @param string $fallback The fallback post type if validation fails.
	 * @return string The validated post type or the fallback.
	 */
	public static function validated(string $postType, string $fallback = 'post'): string
	{
		return self::isValid($postType) ? $postType : $fallback;
	}
}
