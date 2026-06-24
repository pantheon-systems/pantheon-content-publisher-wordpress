<?php

/**
 * Tests for SmartComponents registration, schema, and rendering.
 */

namespace Pantheon\ContentPublisher\Tests;

use Pantheon\ContentPublisher\SmartComponents;
use WP_UnitTestCase;

class SmartComponentsRegistryTest extends WP_UnitTestCase
{
	private SmartComponents $registry;

	protected function setUp(): void
	{
		parent::setUp();
		$this->registry = new SmartComponents();
	}

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
		add_action('cpub_register_smart_components', function ($registry) {
			$registry->register(new StubComponent());
		});

		$registry = new SmartComponents();
		$schema = $registry->getSchema();

		$this->assertArrayHasKey('STUB_COMPONENT', $schema);
		$this->assertArrayHasKey('MEDIA_EMBED', $schema);

		remove_all_actions('cpub_register_smart_components');
	}

	public function testGetSchemaReturnsAllComponents(): void
	{
		$this->registry->register(new StubComponent());
		$schema = $this->registry->getSchema();

		$this->assertArrayHasKey('MEDIA_EMBED', $schema);
		$this->assertArrayHasKey('STUB_COMPONENT', $schema);
		$this->assertSame('Media Embed', $schema['MEDIA_EMBED']['title']);
		$this->assertSame('Stub', $schema['STUB_COMPONENT']['title']);
	}

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
}
