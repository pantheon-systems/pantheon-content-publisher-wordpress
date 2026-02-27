<?php

/**
 * Tests for SmartComponents content detection and raw content extraction.
 */

namespace Pantheon\ContentPublisher\Tests;

use Pantheon\ContentPublisher\SmartComponents;
use WP_UnitTestCase;

class SmartComponentsDetectionTest extends WP_UnitTestCase
{
	private SmartComponents $registry;

	protected function setUp(): void
	{
		parent::setUp();
		$this->registry = new SmartComponents();
	}

	// ── contentHasComponents() ──────────────────────────────────────

	/**
	 * @dataProvider contentHasComponentsProvider
	 */
	public function testContentHasComponents(string $content, bool $expected): void
	{
		$this->assertSame($expected, $this->registry->contentHasComponents($content));
	}

	public function contentHasComponentsProvider(): array
	{
		return [
			'present' => ['<p>Hello</p><component></component><p>World</p>', true],
			'with attributes' => ['<p>Hello</p><component id="c1" data-type="test"></component>', true],
			'absent' => ['<p>Hello World</p>', false],
			'empty string' => ['', false],
			'substring only' => ['<mycomponent>test</mycomponent>', false],
		];
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
}
