<?php

namespace Base;

class Object
{
	private static $logger;
	public static $config;

	public function __construct($config)
	{
		$this->config = $config;
		if (!isset($this->logger)) {
			$loggerConfig = [];
			if (!empty($config['\Base\Log'])) {
				$loggerConfig = $config['\Base\Log'];
			}
			$this->logger = new \Base\Log($loggerConfig);
		}
	}

	public function log($message, $params = [], $level = \Base\Log::INFO)
	{
		if (empty($this->logger)) {
			return;
		}

	}
}