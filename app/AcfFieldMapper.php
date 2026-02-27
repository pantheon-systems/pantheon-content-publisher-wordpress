<?php

namespace Pantheon\ContentPublisher;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Stores and applies mappings between Content Publisher metadata and ACF fields.
 */
class AcfFieldMapper
{
	private const ERROR_TRANSIENT_KEY = 'cpub_acf_mapping_errors';

	/**
	 * Return all stored mappings.
	 *
	 * @return array
	 */
	public function getMappings(): array
	{
		$raw = get_option(CPUB_ACF_FIELD_MAPPINGS_OPTION_KEY, []);
		if (!is_array($raw)) {
			return [];
		}
		return array_values(array_filter($raw, [$this, 'isValidMapping']));
	}

	/**
	 * Validate and persist a new mapping set.
	 *
	 * @param array $mappings
	 * @param string $userMatchBy
	 * @return bool
	 * @throws \InvalidArgumentException
	 */
	public function saveMappings(array $mappings, string $userMatchBy = 'login'): bool
	{
		$clean = [];
		foreach ($mappings as $i => $item) {
			if (!is_array($item)) {
				throw new \InvalidArgumentException(
					sprintf('Mapping at index %d is not an object.', (int) $i)
				);
			}
			if (!$this->isValidMapping($item)) {
				throw new \InvalidArgumentException(
					sprintf(
						'Mapping at index %d is missing required keys (post_type, acf_field, cpub_field)' .
						' or contains empty values.',
						(int) $i
					)
				);
			}
			$clean[] = [
				'post_type' => sanitize_key($item['post_type']),
				'acf_field' => sanitize_text_field($item['acf_field']),
				'cpub_field' => sanitize_text_field($item['cpub_field']),
			];
		}

		update_option(CPUB_ACF_FIELD_MAPPINGS_OPTION_KEY, $clean);

		$userMatchBy = in_array($userMatchBy, ['login', 'email'], true) ? $userMatchBy : 'login';
		update_option(CPUB_ACF_USER_MATCH_BY_OPTION_KEY, $userMatchBy);

		return true;
	}

	/**
	 * Return the user-field match strategy.
	 *
	 * @return string
	 */
	public function getUserMatchBy(): string
	{
		$value = get_option(CPUB_ACF_USER_MATCH_BY_OPTION_KEY, 'login');
		return in_array($value, ['login', 'email'], true) ? $value : 'login';
	}

	/**
	 * Apply ACF field mappings for a synced post.
	 *
	 * @param int $postId
	 * @param string $postType
	 * @param array $metadata
	 * @return void
	 */
	public function applyMappings(int $postId, string $postType, array $metadata): void
	{
		$mappings = $this->getMappingsForPostType($postType);
		if (empty($mappings)) {
			return;
		}

		if (!$this->isAcfActive()) {
			error_log(sprintf(
				'[Content Publisher / ACF] post_id=%d: ACF field mappings are configured but ACF is not active.',
				$postId
			));
			$this->storeErrors([
				sprintf(
					'ACF is not active (post %d, post_type: %s). Mappings were skipped.',
					$postId,
					$postType
				),
			]);
			return;
		}

		$errors = [];
		$userMatchBy = $this->getUserMatchBy();

		foreach ($mappings as $mapping) {
			$acfField = $mapping['acf_field'];
			$cpubField = $mapping['cpub_field'];

			if (!array_key_exists($cpubField, $metadata)) {
				$errors[] = sprintf(
					'ACF mapping skipped: Content Publisher field %s not present in document metadata ' .
					'(post %d, acf_field: %s).',
					$cpubField,
					$postId,
					$acfField
				);
				continue;
			}

			$value = $metadata[$cpubField];

			// Detect the ACF field type so we can apply special handling.
			$fieldObj = function_exists('get_field_object')
				? get_field_object($acfField, $postId)
				: null;

			$fieldType = is_array($fieldObj) ? ($fieldObj['type'] ?? '') : '';

			if ($fieldType === 'user') {
				$resolved = $this->resolveUserField((string) $value, $userMatchBy);
				if ($resolved === null) {
					$errors[] = sprintf(
						'ACF user field %s: could not find WordPress user matching %s %s (post %d).',
						$acfField,
						$userMatchBy,
						$value,
						$postId
					);
					continue;
				}
				// ACF stores user fields as the user ID integer.
				update_post_meta($postId, $acfField, $resolved);
			} elseif (function_exists('update_field')) {
				update_field($acfField, $value, $postId);
			} else {
				update_post_meta($postId, $acfField, $value);
			}
		}

		if (!empty($errors)) {
			foreach ($errors as $msg) {
				error_log(sprintf('[Content Publisher / ACF] post_id=%d: %s', $postId, $msg));
			}
			$this->storeErrors($errors);
		}
	}

	/**
	 * Return ACF field groups and fields, optionally filtered by post type.
	 *
	 * @return array
	 */
	public function getAcfFields(?string $postType = null): array
	{
		if (!$this->isAcfActive() || !function_exists('acf_get_field_groups')) {
			return [];
		}

		$queryArgs = $postType ? ['post_type' => $postType] : [];
		$groups = acf_get_field_groups($queryArgs);

		if (empty($groups)) {
			return [];
		}

		$fields = [];
		foreach ($groups as $group) {
			$groupFields = acf_get_fields($group['key'] ?? '');
			if (!is_array($groupFields)) {
				continue;
			}
			foreach ($groupFields as $field) {
				$fields[] = [
					'key' => $field['key'] ?? '',
					'label' => $field['label'] ?? '',
					'name' => $field['name'] ?? '',
					'type' => $field['type'] ?? '',
					'group' => $group['title'] ?? '',
				];
			}
		}

		return $fields;
	}

	/**
	 * Whether ACF is currently active.
	 *
	 * @return bool
	 */
	public function isAcfActive(): bool
	{
		return function_exists('update_field') || class_exists('ACF');
	}

	/**
	 * Retrieve and clear stored mapping errors.
	 *
	 * @return array
	 */
	public function consumeErrors(): array
	{
		$errors = get_transient(self::ERROR_TRANSIENT_KEY);
		delete_transient(self::ERROR_TRANSIENT_KEY);
		return is_array($errors) ? $errors : [];
	}

	/**
	 * Resolve a user-type ACF field value to a WordPress user ID.
	 *
	 * @param string $value
	 * @param string $matchBy
	 * @return int|null
	 */
	private function resolveUserField(string $value, string $matchBy): ?int
	{
		if (empty($value)) {
			return null;
		}
		$field = $matchBy === 'email' ? 'email' : 'login';
		$user = get_user_by($field, $value);
		return $user instanceof \WP_User ? $user->ID : null;
	}

	/**
	 * Return only mappings for the given post type.
	 *
	 * @return array
	 */
	private function getMappingsForPostType(string $postType): array
	{
		return array_values(array_filter(
			$this->getMappings(),
			static fn(array $m): bool => $m['post_type'] === $postType
		));
	}

	/**
	 * Validate that a mapping has the required keys.
	 *
	 * @return bool
	 */
	private function isValidMapping(mixed $item): bool
	{
		if (!is_array($item)) {
			return false;
		}
		foreach (['post_type', 'acf_field', 'cpub_field'] as $key) {
			if (empty($item[$key]) || !is_string($item[$key])) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Append errors to the transient for later retrieval.
	 *
	 * @param array $errors
	 */
	private function storeErrors(array $errors): void
	{
		$existing = get_transient(self::ERROR_TRANSIENT_KEY);
		if (!is_array($existing)) {
			$existing = [];
		}
		set_transient(
			self::ERROR_TRANSIENT_KEY,
			array_merge($existing, $errors),
			HOUR_IN_SECONDS
		);
	}
}
