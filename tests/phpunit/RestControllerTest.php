<?php

/**
 * Tests that pantheoncloud REST endpoints send Cache-Control: no-store.
 */

namespace Pantheon\ContentPublisher\Tests;

use Pantheon\ContentPublisher\RestController;
use WP_UnitTestCase;

class RestControllerTest extends WP_UnitTestCase
{
	public function testStatusCheckHasNoCacheHeader(): void
	{
		$controller = new RestController();
		$response = $controller->pantheonCloudStatusCheck();
		$headers = $response->get_headers();

		$this->assertArrayHasKey('Cache-Control', $headers);
		$this->assertStringContainsString('no-store', $headers['Cache-Control']);
	}
}
