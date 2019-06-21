<?php

namespace Mitchdav\SNS\Contracts;

interface NameFormer
{
	/**
	 * @param string $service
	 * @param string $label
	 * @param array  $config
	 *
	 * @return string
	 */
	public function formName($service, $label, $config);
}