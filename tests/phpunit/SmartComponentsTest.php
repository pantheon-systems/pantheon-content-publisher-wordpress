<?php

/**
 * PHPUnit tests for the SmartComponents registry and pipeline.
 *
 * Covers registration, schema generation, component rendering delegation,
 * content detection, raw content extraction, placeholder replacement,
 * and the full processing pipeline.
 */

namespace Pantheon\ContentPublisher\Tests;

use Pantheon\ContentPublisher\Interfaces\SmartComponentInterface;
use Pantheon\ContentPublisher\SmartComponents;
use Pantheon\ContentPublisher\Components\MediaEmbed;
use WP_UnitTestCase;

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

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SmartComponentsTest extends WP_UnitTestCase
{
	private SmartComponents $registry;

	protected function setUp(): void
	{
		parent::setUp();
		// Create a fresh instance (bypass singleton to isolate tests).
		$this->registry = new SmartComponents();
	}

	// ── Registration ────────────────────────────────────────────────

	public function testRegistersMediaEmbedByDefault(): void
	{
		$this->assertGreaterThanOrEqual(1, $this->registry->count());
		$schema = $this->registry->getSchema();
		$this->assertArrayHasKey('MEDIA_EMBED', $schema);
	}

	public function testRegisterAddsComponent(): void
	{
		$initialCount = $this->registry->count();
		$this->registry->register(new StubComponent());

		$this->assertSame($initialCount + 1, $this->registry->count());
	}

	public function testThirdPartyRegistrationViaAction(): void
	{
		// Simulate third-party registration via the action hook.
		add_action('cpub_register_smart_components', function ($registry) {
			$registry->register(new StubComponent());
		});

		$registry = new SmartComponents();
		$schema = $registry->getSchema();

		$this->assertArrayHasKey('STUB_COMPONENT', $schema);
		$this->assertArrayHasKey('MEDIA_EMBED', $schema);

		// Clean up.
		remove_all_actions('cpub_register_smart_components');
	}

	// ── Schema ──────────────────────────────────────────────────────

	public function testGetSchemaReturnsAllComponents(): void
	{
		$this->registry->register(new StubComponent());
		$schema = $this->registry->getSchema();

		$this->assertArrayHasKey('MEDIA_EMBED', $schema);
		$this->assertArrayHasKey('STUB_COMPONENT', $schema);
		$this->assertSame('Media Embed', $schema['MEDIA_EMBED']['title']);
		$this->assertSame('Stub', $schema['STUB_COMPONENT']['title']);
	}

	// ── renderComponent ─────────────────────────────────────────────

	public function testRenderComponentDelegatesToRegistered(): void
	{
		$this->registry->register(new StubComponent());
		$html = $this->registry->renderComponent('STUB_COMPONENT', ['text' => 'Hello']);

		$this->assertStringContainsString('stub-component', $html);
		$this->assertStringContainsString('Hello', $html);
	}

	public function testRenderComponentReturnsEmptyForUnknownType(): void
	{
		$this->assertSame('', $this->registry->renderComponent('NONEXISTENT', []));
	}

	public function testRenderComponentIsCaseInsensitive(): void
	{
		$this->registry->register(new StubComponent());
		$html = $this->registry->renderComponent('stub_component', ['text' => 'test']);

		$this->assertStringContainsString('stub-component', $html);
	}

	// ── contentHasComponents() ──────────────────────────────────────

	public function testContentHasComponentsReturnsTrueWhenPresent(): void
	{
		$content = '<p>Hello</p><component></component><p>World</p>';
		$this->assertTrue($this->registry->contentHasComponents($content));
	}

	public function testContentHasComponentsReturnsTrueWithAttributes(): void
	{
		$content = '<p>Hello</p><component id="c1" data-type="test"></component>';
		$this->assertTrue($this->registry->contentHasComponents($content));
	}

	public function testContentHasComponentsReturnsFalseWhenAbsent(): void
	{
		$this->assertFalse($this->registry->contentHasComponents('<p>Hello World</p>'));
	}

	public function testContentHasComponentsReturnsFalseForEmptyString(): void
	{
		$this->assertFalse($this->registry->contentHasComponents(''));
	}

	public function testContentHasComponentsReturnsFalseForSubstring(): void
	{
		$content = '<mycomponent>test</mycomponent>';
		$this->assertFalse($this->registry->contentHasComponents($content));
	}

	// ── extractFromRawContent() ─────────────────────────────────────

	public function testExtractSingleComponent(): void
	{
		$attrs = base64_encode(json_encode(['url' => 'https://youtube.com/watch?v=abc']));
		$raw = '<p>Text</p>'
			. '<pcc-component id="c1" type="MEDIA_EMBED" attrs="' . $attrs . '"></pcc-component>';

		$components = $this->registry->extractFromRawContent($raw);

		$this->assertCount(1, $components);
		$this->assertSame('MEDIA_EMBED', $components[0]['type']);
		$this->assertSame('https://youtube.com/watch?v=abc', $components[0]['attrs']['url']);
	}

	public function testExtractMultipleComponents(): void
	{
		$attrs1 = base64_encode(json_encode(['url' => 'https://youtube.com/watch?v=1']));
		$attrs2 = base64_encode(json_encode(['url' => 'https://vimeo.com/123']));
		$raw = '<pcc-component id="c1" type="MEDIA_EMBED" attrs="' . $attrs1 . '"></pcc-component>'
			. '<p>Middle</p>'
			. '<pcc-component id="c2" type="MEDIA_EMBED" attrs="' . $attrs2 . '"></pcc-component>';

		$components = $this->registry->extractFromRawContent($raw);

		$this->assertCount(2, $components);
		$this->assertSame('https://youtube.com/watch?v=1', $components[0]['attrs']['url']);
		$this->assertSame('https://vimeo.com/123', $components[1]['attrs']['url']);
	}

	public function testExtractReturnsEmptyForNoComponents(): void
	{
		$this->assertEmpty($this->registry->extractFromRawContent('<p>No components here</p>'));
	}

	public function testExtractHandlesInvalidBase64(): void
	{
		$raw = '<pcc-component id="c1" type="MEDIA_EMBED" attrs="not-valid-base64!!!"></pcc-component>';
		$components = $this->registry->extractFromRawContent($raw);

		$this->assertCount(1, $components);
		$this->assertSame('MEDIA_EMBED', $components[0]['type']);
		$this->assertEmpty($components[0]['attrs']);
	}

	public function testExtractHandlesInvalidJson(): void
	{
		$attrs = base64_encode('not json');
		$raw = '<pcc-component id="c1" type="MEDIA_EMBED" attrs="' . $attrs . '"></pcc-component>';
		$components = $this->registry->extractFromRawContent($raw);

		$this->assertCount(1, $components);
		$this->assertEmpty($components[0]['attrs']);
	}

	public function testExtractPreservesWidthAndHeight(): void
	{
		$attrs = base64_encode(json_encode([
			'url' => 'https://example.com/video',
			'width' => '80%',
			'height' => '350px',
		]));
		$raw = '<pcc-component id="c1" type="MEDIA_EMBED" attrs="' . $attrs . '"></pcc-component>';

		$components = $this->registry->extractFromRawContent($raw);

		$this->assertSame('80%', $components[0]['attrs']['width']);
		$this->assertSame('350px', $components[0]['attrs']['height']);
	}

	// ── replaceComponentPlaceholders() ──────────────────────────────

	public function testReplaceSubstitutesMediaEmbedPlaceholder(): void
	{
		$processed = '<p>Before</p><component></component><p>After</p>';
		$components = [
			[
				'type' => 'MEDIA_EMBED',
				'attrs' => ['url' => 'https://example.com/embed'],
			],
		];

		$result = $this->registry->replaceComponentPlaceholders($processed, $components);

		$this->assertStringNotContainsString('<component></component>', $result);
		$this->assertStringContainsString('cpub-media-embed', $result);
		$this->assertStringContainsString('example.com/embed', $result);
		$this->assertStringContainsString('<p>Before</p>', $result);
		$this->assertStringContainsString('<p>After</p>', $result);
	}

	public function testReplaceHandlesMultiplePlaceholdersInOrder(): void
	{
		$processed = '<component></component><component></component>';
		$components = [
			['type' => 'MEDIA_EMBED', 'attrs' => ['url' => 'https://example.com/first']],
			['type' => 'MEDIA_EMBED', 'attrs' => ['url' => 'https://example.com/second']],
		];

		$result = $this->registry->replaceComponentPlaceholders($processed, $components);

		$firstPos = strpos($result, 'example.com/first');
		$secondPos = strpos($result, 'example.com/second');
		$this->assertNotFalse($firstPos);
		$this->assertNotFalse($secondPos);
		$this->assertLessThan($secondPos, $firstPos);
	}

	public function testReplaceUnsupportedComponentLeavesComment(): void
	{
		$processed = '<component></component>';
		$components = [
			['type' => 'SOME_OTHER_TYPE', 'attrs' => ['key' => 'value']],
		];

		$result = $this->registry->replaceComponentPlaceholders($processed, $components);

		$this->assertStringContainsString('unsupported smart component', $result);
		$this->assertStringContainsString('SOME_OTHER_TYPE', $result);
	}

	public function testReplaceWithEmptyComponentsReturnsOriginal(): void
	{
		$processed = '<p>Content</p><component></component>';
		$result = $this->registry->replaceComponentPlaceholders($processed, []);

		$this->assertSame($processed, $result);
	}

	public function testReplaceExtraPlaceholdersPreserved(): void
	{
		$processed = '<component></component><component></component>';
		$components = [
			['type' => 'MEDIA_EMBED', 'attrs' => ['url' => 'https://example.com/only']],
		];

		$result = $this->registry->replaceComponentPlaceholders($processed, $components);

		$this->assertStringContainsString('example.com/only', $result);
		$this->assertStringContainsString('<component></component>', $result);
	}

	public function testReplaceWithRegisteredThirdPartyComponent(): void
	{
		$this->registry->register(new StubComponent());

		$processed = '<component></component>';
		$components = [
			['type' => 'STUB_COMPONENT', 'attrs' => ['text' => 'Custom']],
		];

		$result = $this->registry->replaceComponentPlaceholders($processed, $components);

		$this->assertStringContainsString('stub-component', $result);
		$this->assertStringContainsString('Custom', $result);
	}

	// ── processContent() ────────────────────────────────────────────

	public function testProcessContentEndToEnd(): void
	{
		$attrs = base64_encode(json_encode([
			'url' => 'https://example.com/video',
			'width' => '80%',
			'height' => '350px',
		]));
		$rawContent = '<p>Hello</p>'
			. '<pcc-component id="c1" type="MEDIA_EMBED" attrs="' . $attrs . '"></pcc-component>';
		$processedContent = '<p>Hello</p><component></component>';

		$result = $this->registry->processContent($processedContent, $rawContent);

		$this->assertStringNotContainsString('<component>', $result);
		$this->assertStringContainsString('cpub-media-embed', $result);
		$this->assertStringContainsString('width:80%', $result);
	}

	public function testProcessContentWithNullRawReturnsOriginal(): void
	{
		$processed = '<p>Normal content</p>';
		$this->assertSame($processed, $this->registry->processContent($processed, null));
	}

	public function testProcessContentWithNoComponentsInRawReturnsOriginal(): void
	{
		$processed = '<p>Content</p><component></component>';
		$raw = '<p>Content without components</p>';

		$this->assertSame($processed, $this->registry->processContent($processed, $raw));
	}
}
