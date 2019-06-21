<?php

namespace Mitchdav\SNS;

class SNS
{
	const ACCOUNT_DEFAULT         = 'default';
	const ARN_FORMER_DEFAULT      = '$DEFAULT$';

	/**
	 * @var \Mitchdav\SNS\Models\Config
	 */
	private $config;

	public function __construct($configFileName = 'sns')
	{
		$this->config = ConfigParser::parse(config($configFileName));
	}

	/**
	 * @return \Mitchdav\SNS\Models\Config
	 */
	public function getConfig()
	{
		return $this->config;
	}
}