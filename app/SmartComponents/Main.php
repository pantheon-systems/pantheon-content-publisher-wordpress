<?php

namespace Pantheon\ContentPublisher\SmartComponents;

/**
 * Main SmartComponents controller
 */
class Main
{

	public function __construct()
	{
		$this->init();
	}

	/**
	 * Initialize smart components
	 *
	 * @return void
	 */
	private function init(): void
	{
		// Register REST API endpoints
		new ComponentSchemaEndpoint();
		new ComponentPreviewEndpoint();

		// Register block styles handler
		new BlockStylesHandler();
	}
}
