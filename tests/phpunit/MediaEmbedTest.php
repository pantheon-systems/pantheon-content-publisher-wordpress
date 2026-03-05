<?php

/**
 * PHPUnit tests for the MediaEmbed smart component.
 *
 * Covers the render() method: URL validation, default and custom
 * dimensions, oEmbed and iframe fallback.
 */

namespace Pantheon\ContentPublisher\Tests;

use Pantheon\ContentPublisher\Components\MediaEmbed;
use WP_UnitTestCase;

class MediaEmbedTest extends WP_UnitTestCase
{
	private MediaEmbed $mediaEmbed;

	protected function setUp(): void
	{
		parent::setUp();
		$this->mediaEmbed = new MediaEmbed();
	}

	// ── Interface compliance ────────────────────────────────────────

	public function testTypeReturnsMediaEmbed(): void
	{
		$this->assertSame('MEDIA_EMBED', $this->mediaEmbed->type());
	}

	public function testSchemaHasRequiredFields(): void
	{
		$schema = $this->mediaEmbed->schema();

		$this->assertArrayHasKey('title', $schema);
		$this->assertArrayHasKey('fields', $schema);
		$this->assertArrayHasKey('url', $schema['fields']);
		$this->assertArrayHasKey('width', $schema['fields']);
		$this->assertArrayHasKey('height', $schema['fields']);
		$this->assertTrue($schema['fields']['url']['required']);
		$this->assertFalse($schema['fields']['width']['required']);
	}

	// ── render() ────────────────────────────────────────────────────

	public function testRenderReturnsEmptyForMissingUrl(): void
	{
		$this->assertSame('', $this->mediaEmbed->render([]));
	}

	public function testRenderReturnsEmptyForEmptyUrl(): void
	{
		$this->assertSame('', $this->mediaEmbed->render(['url' => '']));
	}

	public function testRenderReturnsEmptyForInvalidUrl(): void
	{
		$this->assertSame('', $this->mediaEmbed->render(['url' => 'not-a-url']));
	}

	public function testRenderProducesEmbedWithDefaultDimensions(): void
	{
		$html = $this->mediaEmbed->render(['url' => 'https://example.com/video']);

		$this->assertStringContainsString('class="cpub-media-embed"', $html);
		$this->assertStringContainsString('width:100%', $html);
		$this->assertStringContainsString('height:400px', $html);
		$this->assertStringContainsString('example.com/video', $html);
	}

	public function testRenderRespectsCustomDimensions(): void
	{
		$html = $this->mediaEmbed->render([
			'url' => 'https://example.com/video',
			'width' => '600px',
			'height' => '300px',
		]);

		$this->assertStringContainsString('width:600px', $html);
		$this->assertStringContainsString('height:300px', $html);
	}

	public function testRenderFallsBackToIframe(): void
	{
		// Use an unusual URL that wp_oembed_get() won't support.
		$html = $this->mediaEmbed->render(['url' => 'https://example.com/custom-video']);

		$this->assertStringContainsString('<iframe', $html);
		$this->assertStringContainsString('src="https://example.com/custom-video"', $html);
		$this->assertStringContainsString('allowfullscreen', $html);
		$this->assertStringContainsString('loading="lazy"', $html);
	}
}
