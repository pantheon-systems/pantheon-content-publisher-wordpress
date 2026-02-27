<?php

/**
 * PHPUnit tests for the ACF Field Mapper.
 *
 * Covers storing, retrieving, and applying field mappings between
 * Content Publisher metadata and Advanced Custom Fields.
 */

namespace Pantheon\ContentPublisher\Tests;

use Pantheon\ContentPublisher\AcfFieldMapper;
use WP_UnitTestCase;

/**
 * Verifies mapping CRUD, user field resolution, and sync behavior.
 */
class AcfFieldMapperTest extends WP_UnitTestCase
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

	/**
	 * Returns an empty array when no mappings have been saved.
	 */
	public function testGetMappingsReturnsEmptyWhenNoOption(): void
	{
		$this->assertSame([], $this->mapper->getMappings());
	}

	/**
	 * Valid mappings are persisted and retrievable.
	 */
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

	/**
	 * Keys and values are sanitised when saving.
	 */
	public function testSaveMappingsSanitisesFields(): void
	{
		$this->mapper->saveMappings([['post_type' => 'POST TYPE', 'acf_field' => 'my_field', 'cpub_field' => '  author  ']]);
		$stored = $this->mapper->getMappings();
		$this->assertSame('posttype', $stored[0]['post_type']);
		$this->assertSame('author', $stored[0]['cpub_field']);
	}

	/**
	 * Non-array items in the mapping set trigger an exception.
	 */
	public function testSaveMappingsThrowsOnNonArrayItem(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->mapper->saveMappings(['not-an-array']);
	}

	/**
	 * Missing required keys trigger an exception.
	 */
	public function testSaveMappingsThrowsOnMissingKey(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->mapper->saveMappings([['post_type' => 'post', 'acf_field' => 'field']]);
	}

	/**
	 * Empty values in required keys trigger an exception.
	 */
	public function testSaveMappingsThrowsOnEmptyValue(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->mapper->saveMappings([['post_type' => 'post', 'acf_field' => '', 'cpub_field' => 'author']]);
	}

	/**
	 * Corrupted option data is filtered out, keeping only valid entries.
	 */
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

	/**
	 * Saving a new mapping set replaces the previous one entirely.
	 */
	public function testSaveMappingsOverwritesExisting(): void
	{
		$this->mapper->saveMappings([['post_type' => 'post', 'acf_field' => 'old', 'cpub_field' => 'old']]);
		$this->mapper->saveMappings([['post_type' => 'page', 'acf_field' => 'new', 'cpub_field' => 'new']]);
		$stored = $this->mapper->getMappings();
		$this->assertCount(1, $stored);
		$this->assertSame('page', $stored[0]['post_type']);
	}

	/**
	 * User match strategy defaults to 'login' when not set.
	 */
	public function testGetUserMatchByDefaultsToLogin(): void
	{
		$this->assertSame('login', $this->mapper->getUserMatchBy());
	}

	/**
	 * Saving with user_match_by 'email' persists correctly.
	 */
	public function testSaveMappingsPersistsUserMatchByEmail(): void
	{
		$this->mapper->saveMappings([], 'email');
		$this->assertSame('email', $this->mapper->getUserMatchBy());
	}

	/**
	 * Invalid user_match_by values fall back to 'login'.
	 */
	public function testSaveMappingsRejectsInvalidUserMatchBy(): void
	{
		$this->mapper->saveMappings([], 'invalid');
		$this->assertSame('login', $this->mapper->getUserMatchBy());
	}

	/**
	 * No errors are produced when no mappings exist for the given post type.
	 */
	public function testApplyMappingsDoesNothingWhenNoMappingsForPostType(): void
	{
		$postId = self::factory()->post->create();
		$this->mapper->applyMappings($postId, 'post', ['author' => 'Alice']);
		$this->assertSame([], $this->mapper->consumeErrors());
	}

	/**
	 * Missing Content Publisher fields are skipped with an error.
	 */
	public function testApplyMappingsSkipsMissingCpubField(): void
	{
		$this->mapper->saveMappings([
			['post_type' => 'post', 'acf_field' => 'assigned_writer', 'cpub_field' => 'author'],
		]);
		$postId = self::factory()->post->create();
		$this->mapper->applyMappings($postId, 'post', ['title' => 'Some Title']);
		$errors = $this->mapper->consumeErrors();
		$this->assertNotEmpty($errors);
		$this->assertStringContainsString('author', $errors[0]);
		$this->assertStringContainsString('not present', $errors[0]);
	}

	/**
	 * Mappings for a different post type are ignored.
	 */
	public function testApplyMappingsIgnoresOtherPostType(): void
	{
		$this->mapper->saveMappings([
			['post_type' => 'page', 'acf_field' => 'subtitle', 'cpub_field' => 'subtitle'],
		]);
		$postId = self::factory()->post->create();
		$this->mapper->applyMappings($postId, 'post', ['subtitle' => 'Hello']);
		$this->assertSame([], $this->mapper->consumeErrors());
	}

	/**
	 * User-type ACF fields are resolved by login name.
	 */
	public function testUserFieldResolvedByLogin(): void
	{
		$userId = self::factory()->user->create(['user_login' => 'chris_yates', 'user_email' => 'chris@example.com']);
		$this->mapper->saveMappings([
			['post_type' => 'post', 'acf_field' => 'assigned_writer', 'cpub_field' => 'Assigned Writer'],
		], 'login');
		$GLOBALS['_acf_test_field_types']['assigned_writer'] = 'user';
		$postId = self::factory()->post->create();
		$this->mapper->applyMappings($postId, 'post', ['Assigned Writer' => 'chris_yates']);
		$stored = get_post_meta($postId, 'assigned_writer', true);
		$this->assertSame($userId, (int) $stored);
		$this->assertSame([], $this->mapper->consumeErrors());
	}

	/**
	 * User-type ACF fields are resolved by email address.
	 */
	public function testUserFieldResolvedByEmail(): void
	{
		$userId = self::factory()->user->create(['user_login' => 'warren_peace', 'user_email' => 'warren@example.com']);
		$this->mapper->saveMappings([
			['post_type' => 'post', 'acf_field' => 'assigned_editor', 'cpub_field' => 'Assigned Editor'],
		], 'email');
		$GLOBALS['_acf_test_field_types']['assigned_editor'] = 'user';
		$postId = self::factory()->post->create();
		$this->mapper->applyMappings($postId, 'post', ['Assigned Editor' => 'warren@example.com']);
		$stored = get_post_meta($postId, 'assigned_editor', true);
		$this->assertSame($userId, (int) $stored);
	}

	/**
	 * An error is logged when the user lookup returns no match.
	 */
	public function testUserFieldLogsErrorWhenUserNotFound(): void
	{
		$this->mapper->saveMappings([
			['post_type' => 'post', 'acf_field' => 'assigned_writer', 'cpub_field' => 'Assigned Writer'],
		], 'login');
		$GLOBALS['_acf_test_field_types']['assigned_writer'] = 'user';
		$postId = self::factory()->post->create();
		$this->mapper->applyMappings($postId, 'post', ['Assigned Writer' => 'nonexistent_user']);
		$errors = $this->mapper->consumeErrors();
		$this->assertNotEmpty($errors);
		$this->assertStringContainsString('nonexistent_user', $errors[0]);
	}

	/**
	 * Consuming errors clears the transient so they are not returned twice.
	 */
	public function testConsumeErrorsClearsTransient(): void
	{
		set_transient('cpub_acf_mapping_errors', ['error 1', 'error 2'], HOUR_IN_SECONDS);
		$errors = $this->mapper->consumeErrors();
		$this->assertCount(2, $errors);
		$this->assertSame([], $this->mapper->consumeErrors());
	}

	/**
	 * Returns an empty array when no errors have been stored.
	 */
	public function testConsumeErrorsReturnsEmptyWhenNoErrors(): void
	{
		$this->assertSame([], $this->mapper->consumeErrors());
	}

	/**
	 * Returns an empty field list when acf_get_field_groups is not available.
	 */
	public function testGetAcfFieldsReturnsEmptyWhenAcfFieldGroupsUnavailable(): void
	{
		$this->assertSame([], $this->mapper->getAcfFields('post'));
	}

	/**
	 * Returns an empty field list for a null post type.
	 */
	public function testGetAcfFieldsReturnsEmptyForNullPostType(): void
	{
		$this->assertSame([], $this->mapper->getAcfFields(null));
	}
}
