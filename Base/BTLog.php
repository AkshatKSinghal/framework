<?php

namespace Base;

class Log
{
	const DEBUG = 100;
	const INFO = 200;
	const WARN = 300;
	const ERROR = 400;
	const CRITICAL = 500;

	private $messages = [];
	private $level = self::WARN;
	private $path = "/tmp/logs.txt";
	private $delimiter = "\t";
	private $requestID;
	private $requestURL;
	private $startTime;
	private $callingFunction;

	public function __construct($config)
	{
		$this->startTime = microtime(true);
		$this->loadConfig($config);

		if (empty($this->requestID)) {
			$this->$requestID = '#TODO Generate Random Number here';
		}

		if (empty($this->requestURL)) {
			if (!empty($_SERVER['REQUEST_URI'])) {
				$this->$requestURL = $_SERVER['REQUEST_URI'];
			}
			else {
				$this->$requestURL = $_SERVER['SCRIPT_FILENAME'];
			}
		}

		if (empty($this->callingFunction)) {
			$this->callingFunction = [
				'class' => 'WebApp',
				'function' => 'Request',
				'line' => 'N/A'
			];
		}
	}

	public function __destruct()
	{
		$data = [
			'level' => self::INFO,
			'message' => 'Request Completed',
			'params' => [],
			'time' => time(),
			'class' => $this->callingFunction['class'],
			'function' => $this->callingFunction['function'],
			'line' => $this->callingFunction['line']
		];
		$this->writeToFile($data);
	}

	private function loadConfig()
	{
		$mapping = [
			'level' => 'level',
			'requestURL' => 'request_url',
			'requestID' => 'request_id',
			'delimiter' => 'delimiter',
			'path' => 'path',
			'callingFunction' => 'calling_function'
		];
		foreach ($mapping as $key => $value) {
			if (!empty($config[$value])) {
				$this->$key = $config[$value];
			}
		}
		if (empty(constant("self::".$this->level))) {
			error_log("Undefined Log Method $this->level used in setup");
			$this->level = NULL;
		}
		return;
	}

	public function log($message, $params = [], $level = self::INFO, $backtraceAdd = 0)
	{
		if (empty($this->level)) {
			error_log("Undefined Log Method used in setup");
			return;
		}
		$callingFunction = array_pop(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2 + $backtraceAdd));
		$this->pushData($message, $params, $level, $callingFunction);
		if ($level >= $this->level) {
			$this->logMessageToFile();
		}
	}

	public function __call($level, $arguments)
	{
		if (empty(constant('self::'.$level))) {
			error_log("Undefined Log Method $level used");
			return;
		}
		$this->log($arguments['0'], $arguments['1'], $arguments['2'], 1);
	}

	private function logMessageToFile()
	{
		while ($data = $this->popData()) {
			$this->writeToFile($data);
		}
	}

	private function writeToFile($data)
	{
		file_put_contents($this->path, $this->getLogText($data), FILE_APPEND);
	}

	private function getLogText($data)
	{
		$logData = [
			$this->requestID,
			$this->requestURL,
			time(),
			$data['time'],
			$data['level'],
			$data['message'],
			$data['params'],
			"{$data['class']}::{$data['function']}({$data['line']})"
		];
		return implode($this->delimiter, $logData);
	}

	private function pushData($message, $params, $level, $callingFunction)
	{
		$this->messages[] = [
			'message' => $message,
			'level' => $level,
			'time' => time(),
			'params' => $params,
			'class' => $callingFunction['class'],
			'function' => $callingFunction['function'],
			'line' => $callingFunction['line']
		];
	}

	private function popData()
	{
		return array_pop($this->messages);
	}
}