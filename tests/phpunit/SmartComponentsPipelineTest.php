<?php

/**
 * Tests for SmartComponents placeholder replacement and full processing pipeline.
 */

namespace Pantheon\ContentPublisher\Tests;

use Pantheon\ContentPublisher\SmartComponents;
use WP_UnitTestCase;

class SmartComponentsPipelineTest extends WP_UnitTestCase
{
	private SmartComponents $registry;

	protected function setUp(): void
	{
		parent::setUp();
		$this->registry = new SmartComponents();
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

	public function testReplaceMatchesByIdEvenWhenOutOfOrder(): void
	{
		// Placeholder order is reversed relative to the extracted components
		// array, simulating the two independent parsing passes diverging.
		$processed = '<component id="c2"></component><component id="c1"></component>';
		$components = [
			['id' => 'c1', 'type' => 'MEDIA_EMBED', 'attrs' => ['url' => 'https://example.com/first']],
			['id' => 'c2', 'type' => 'MEDIA_EMBED', 'attrs' => ['url' => 'https://example.com/second']],
		];

		$result = $this->registry->replaceComponentPlaceholders($processed, $components);

		$secondPos = strpos($result, 'example.com/second');
		$firstPos = strpos($result, 'example.com/first');
		$this->assertNotFalse($secondPos);
		$this->assertNotFalse($firstPos);
		$this->assertLessThan($firstPos, $secondPos);
	}

	public function testReplaceFallsBackToPositionalWhenNoIds(): void
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

	public function testReplaceFallsBackToPositionalWhenIdNotFound(): void
	{
		$processed = '<component id="unknown"></component>';
		$components = [
			['id' => 'c1', 'type' => 'MEDIA_EMBED', 'attrs' => ['url' => 'https://example.com/only']],
		];

		$result = $this->registry->replaceComponentPlaceholders($processed, $components);

		$this->assertStringContainsString('example.com/only', $result);
	}

	// ── extractFromRawContent() ──────────────────────────────────────

	public function testExtractFromRawContentCapturesId(): void
	{
		$attrs = base64_encode(wp_json_encode(['url' => 'https://example.com/video']));
		$rawContent = '<pcc-component id="c1" type="MEDIA_EMBED" attrs="' . $attrs . '"></pcc-component>';

		$components = $this->registry->extractFromRawContent($rawContent);

		$this->assertCount(1, $components);
		$this->assertSame('c1', $components[0]['id']);
		$this->assertSame('MEDIA_EMBED', $components[0]['type']);
	}

	public function testExtractFromRawContentWithoutIdReturnsNullId(): void
	{
		$attrs = base64_encode(wp_json_encode(['url' => 'https://example.com/video']));
		$rawContent = '<pcc-component type="MEDIA_EMBED" attrs="' . $attrs . '"></pcc-component>';

		$components = $this->registry->extractFromRawContent($rawContent);

		$this->assertCount(1, $components);
		$this->assertNull($components[0]['id']);
	}

	// ── processContent() ────────────────────────────────────────────

	public function testProcessContentEndToEndMatchesById(): void
	{
		$attrsOne = base64_encode(wp_json_encode(['url' => 'https://example.com/first']));
		$attrsTwo = base64_encode(wp_json_encode(['url' => 'https://example.com/second']));
		$rawContent = '<pcc-component id="c1" type="MEDIA_EMBED" attrs="' . $attrsOne . '"></pcc-component>'
			. '<pcc-component id="c2" type="MEDIA_EMBED" attrs="' . $attrsTwo . '"></pcc-component>';
		// Placeholders arrive in the opposite order from the raw metadata,
		// as can happen when the two parsing passes diverge.
		$processedContent = '<component id="c2"></component><component id="c1"></component>';

		$result = $this->registry->processContent($processedContent, $rawContent);

		$secondPos = strpos($result, 'example.com/second');
		$firstPos = strpos($result, 'example.com/first');
		$this->assertNotFalse($secondPos);
		$this->assertNotFalse($firstPos);
		$this->assertLessThan($firstPos, $secondPos);
	}

	public function testProcessContentEndToEnd(): void
	{
		$attrs = base64_encode(wp_json_encode([
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
