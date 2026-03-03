<?php

/**
 * PHPUnit tests for the ACF Field Mapper — CRUD and validation.
 */

namespace Pantheon\ContentPublisher\Tests;

use Pantheon\ContentPublisher\AcfFieldMapper;
use WP_UnitTestCase;

/**
 * Verifies mapping storage, retrieval, validation, and sanitisation.
 */
class AcfFieldMapperCrudTest extends WP_UnitTestCase
{
	private AcfFieldMapper $mapper;

	protected function setUp(): void
	{
		parent::setUp();
		$this->mapper = new AcfFieldMapper();
		$GLOBALS['_acf_test_field_types'] = [];
		delete_option(CPUB_ACF_FIELD_MAPPINGS_OPTION_KEY);
		delete_option(CPUB_ACF_USER_MATCH_BY_OPTION_KEY);
		delete_transient('cpub_acf_mapping_errors');
	}

	public function testGetMappingsReturnsEmptyWhenNoOption(): void
	{
		$this->assertSame([], $this->mapper->getMappings());
	}

	public function testSaveMappingsPersistsValidMappings(): void
	{
		$mappings = [
			['post_type' => 'post', 'acf_field' => 'assigned_editor', 'cpub_field' => 'Assigned Editor'],
			['post_type' => 'page', 'acf_field' => 'page_subtitle', 'cpub_field' => 'subtitle'],
		];
		$this->mapper->saveMappings($mappings);
		$stored = $this->mapper->getMappings();
		$this->assertCount(2, $stored);
		$this->assertSame('assigned_editor', $stored[0]['acf_field']);
		$this->assertSame('Assigned Editor', $stored[0]['cpub_field']);
	}

	public function testSaveMappingsSanitisesFields(): void
	{
		$this->mapper->saveMappings([['post_type' => 'POST TYPE', 'acf_field' => 'my_field', 'cpub_field' => '  author  ']]);
		$stored = $this->mapper->getMappings();
		$this->assertSame('posttype', $stored[0]['post_type']);
		$this->assertSame('author', $stored[0]['cpub_field']);
	}

	public function testSaveMappingsThrowsOnNonArrayItem(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->mapper->saveMappings(['not-an-array']);
	}

	public function testSaveMappingsThrowsOnMissingKey(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->mapper->saveMappings([['post_type' => 'post', 'acf_field' => 'field']]);
	}

	public function testSaveMappingsThrowsOnEmptyValue(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->mapper->saveMappings([['post_type' => 'post', 'acf_field' => '', 'cpub_field' => 'author']]);
	}

	public function testGetMappingsFiltersCorruptedOptionData(): void
	{
		update_option(CPUB_ACF_FIELD_MAPPINGS_OPTION_KEY, [
			['post_type' => 'post', 'acf_field' => 'field', 'cpub_field' => 'ok'],
			['post_type' => '', 'acf_field' => 'field', 'cpub_field' => 'bad'],
			'not-an-array',
		]);
		$stored = $this->mapper->getMappings();
		$this->assertCount(1, $stored);
		$this->assertSame('ok', $stored[0]['cpub_field']);
	}

	public function testSaveMappingsOverwritesExisting(): void
	{
		$this->mapper->saveMappings([['post_type' => 'post', 'acf_field' => 'old', 'cpub_field' => 'old']]);
		$this->mapper->saveMappings([['post_type' => 'page', 'acf_field' => 'new', 'cpub_field' => 'new']]);
		$stored = $this->mapper->getMappings();
		$this->assertCount(1, $stored);
		$this->assertSame('page', $stored[0]['post_type']);
	}

	public function testGetUserMatchByDefaultsToLogin(): void
	{
		$this->assertSame('login', $this->mapper->getUserMatchBy());
	}

	public function testSaveMappingsPersistsUserMatchByEmail(): void
	{
		$this->mapper->saveMappings([], 'email');
		$this->assertSame('email', $this->mapper->getUserMatchBy());
	}

	public function testSaveMappingsRejectsInvalidUserMatchBy(): void
	{
		$this->mapper->saveMappings([], 'invalid');
		$this->assertSame('login', $this->mapper->getUserMatchBy());
	}
}
