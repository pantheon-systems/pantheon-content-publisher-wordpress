<?php

namespace Pantheon\ContentPublisher\Tests;

use Pantheon\ContentPublisher\Interfaces\SmartComponentInterface;

/**
 * Stub component for testing third-party registration.
 */
class StubComponent implements SmartComponentInterface
{
	public function type(): string
	{
		return 'STUB_COMPONENT';
	}

	public function schema(): array
	{
		return [
			'title' => 'Stub',
			'iconUrl' => null,
			'fields' => [
				'text' => [
					'displayName' => 'Text',
					'type' => 'string',
					'required' => true,
				],
			],
		];
	}

	public function render(array $attrs): string
	{
		$text = esc_html($attrs['text'] ?? '');
		return '<div class="stub-component">' . $text . '</div>';
	}

	public function allowedHtmlTags(): array
	{
		return [];
	}
}
