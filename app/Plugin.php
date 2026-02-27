<?php

/**
 * The plugin singleton class.
 *
 */

namespace Pantheon\ContentPublisher;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * The main class
 */
class Plugin
{
	/**
	 * Class instance.
	 *
	 * @access private
	 * @static
	 *
	 * @var ?Plugin
	 */
	private static ?Plugin $instance = null;

	public function __construct()
	{
		$this->init();
	}

	/**
	 * Initialize the plugin.
	 *
	 * @access private
	 *
	 * @return void
	 */
	private function init(): void
	{
		$smartComponents = new SmartComponents();
		new Settings($smartComponents);
		new RestController($smartComponents);
		new ComponentEndpoints($smartComponents);
		new Admin();
	}

	/**
	 * Get instance of the class.
	 *
	 * @access public
	 * @static
	 *
	 * @return Plugin
	 */
	public static function getInstance(): Plugin
	{
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
