<?php

namespace Pantheon\ContentPublisher\SmartComponents;

/**
 * Handles component schema generation for WordPress blocks
 */
class ComponentSchema
{
	/**
	 * Get allowed block types for smart components
	 *
	 * @return array
	 */
	public function getAllowedBlocks(): array
	{
		$blocks = [
			// Special components not natively supported in Google Docs
			'core/quote',
			'core/pullquote',
			'core/code',
			'core/embed',
			'core/shortcode',
			'core/site-logo',
		];

		return apply_filters('pantheon_publisher_allowed_smart_blocks', $blocks);
	}

	/**
	 * Generate component schema for all allowed blocks
	 *
	 * @return array
	 */
	public function generateSchema(): array
	{
		$allowedBlocks = $this->getAllowedBlocks();
		$components = [];

		foreach ($allowedBlocks as $blockName) {
			$blockType = \WP_Block_Type_Registry::get_instance()->get_registered($blockName);

			if (!$blockType) {
				continue;
			}

			$componentSchema = $this->convertBlockToComponent($blockName, $blockType);
			if ($componentSchema) {
				$componentId = str_replace('/', '_', $blockName);
				$components[$componentId] = $componentSchema;
			}
		}

		return apply_filters('cpub_smart_components', $components);
	}

	/**
	 * Convert a WordPress block to a smart component schema.
	 *
	 * @param string $blockName Block name
	 * @param \WP_Block_Type $blockType Block type object
	 * @return array|null Component schema or null if conversion fails
	 */
	private function convertBlockToComponent(string $blockName, \WP_Block_Type $blockType): ?array
	{
		$component = [
			'title' => $blockType->title ?: $this->formatBlockTitle($blockName),
			'iconUrl' => null,
			'fields' => [],
		];

		if (!empty($blockType->attributes)) {
			foreach ($blockType->attributes as $attrName => $attrConfig) {
				$type = $attrConfig['type'] ?? 'string';
				$hasEnum = !empty($attrConfig['enum']);

				if (!$hasEnum && in_array($type, ['array', 'object'])) {
					continue;
				}

				$field = $this->convertAttributeToField($attrName, $attrConfig);
				if ($field) {
					$field['required'] = false;
					$component['fields'][$attrName] = $field;
				}
			}
		}

		return $component;
	}

	/**
	 * Convert a block attribute to a component field.
	 *
	 * @param string $attrName Attribute name
	 * @param array $attrConfig Attribute configuration
	 * @return array|null Field schema or null if should be skipped
	 */
	private function convertAttributeToField(string $attrName, array $attrConfig): ?array
	{
		$type = $attrConfig['type'] ?? 'string';

		$field = [
			'displayName' => $this->formatFieldName($attrName),
			'required' => !isset($attrConfig['default']),
		];

		// Map WordPress attribute types to component field types
		switch ($type) {
			case 'string':
				$field['type'] = 'string';
				break;

			case 'number':
			case 'integer':
				$field['type'] = 'number';
				break;

			case 'boolean':
				$field['type'] = 'boolean';
				break;

			case 'array':
			case 'object':
				// Skip complex types
				return null;

			default:
				$field['type'] = 'string';
		}

		// Handle enum values
		if (!empty($attrConfig['enum'])) {
			$field['type'] = 'enum';
			$field['options'] = array_map(function($value) {
				return [
					'label' => $this->formatFieldName($value),
					'value' => $value,
				];
			}, $attrConfig['enum']);
		}

		return $field;
	}

	/**
	 * Format block name into readable title.
	 *
	 * @param string $blockName Block name
	 * @return string Formatted title
	 */
	private function formatBlockTitle(string $blockName): string
	{
		$parts = explode('/', $blockName);
		$name = end($parts);
		$name = str_replace(['-', '_'], ' ', $name);
		return ucwords($name);
	}

	/**
	 * Format field name into readable display name.
	 *
	 * @param string $fieldName Field name
	 * @return string Formatted display name
	 */
	private function formatFieldName(string $fieldName): string
	{
		// Convert camelCase to spaces
		$name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $fieldName);
		// Convert snake_case and kebab-case to spaces
		$name = str_replace(['-', '_'], ' ', $name);
		return ucwords($name);
	}
}
