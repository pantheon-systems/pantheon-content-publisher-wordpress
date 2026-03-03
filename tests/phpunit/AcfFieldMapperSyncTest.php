<?php

/**
 * PHPUnit tests for the ACF Field Mapper — sync and apply behavior.
 */

namespace Pantheon\ContentPublisher\Tests;

use Pantheon\ContentPublisher\AcfFieldMapper;
use WP_UnitTestCase;

/**
 * Verifies mapping application, user field resolution, and error handling.
 */
class AcfFieldMapperSyncTest extends WP_UnitTestCase
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

	public function testApplyMappingsDoesNothingWhenNoMappingsForPostType(): void
	{
		$postId = self::factory()->post->create();
		$this->mapper->applyMappings($postId, 'post', ['author' => 'Alice']);
		$this->assertSame([], $this->mapper->consumeErrors());
	}

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

	public function testApplyMappingsIgnoresOtherPostType(): void
	{
		$this->mapper->saveMappings([
			['post_type' => 'page', 'acf_field' => 'subtitle', 'cpub_field' => 'subtitle'],
		]);
		$postId = self::factory()->post->create();
		$this->mapper->applyMappings($postId, 'post', ['subtitle' => 'Hello']);
		$this->assertSame([], $this->mapper->consumeErrors());
	}

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

	public function testConsumeErrorsClearsTransient(): void
	{
		set_transient('cpub_acf_mapping_errors', ['error 1', 'error 2'], HOUR_IN_SECONDS);
		$errors = $this->mapper->consumeErrors();
		$this->assertCount(2, $errors);
		$this->assertSame([], $this->mapper->consumeErrors());
	}

	public function testConsumeErrorsReturnsEmptyWhenNoErrors(): void
	{
		$this->assertSame([], $this->mapper->consumeErrors());
	}

	public function testGetAcfFieldsReturnsEmptyWhenAcfFieldGroupsUnavailable(): void
	{
		$this->assertSame([], $this->mapper->getAcfFields('post'));
	}

	public function testGetAcfFieldsReturnsEmptyForNullPostType(): void
	{
		$this->assertSame([], $this->mapper->getAcfFields(null));
	}
}
