<?php

namespace Mitchdav\SNS;

use Mitchdav\SNS\Contracts\NameFormer;

class SimpleNameFormer implements NameFormer
{
	const PREFIX_SERVICE_NAME = '$SERVICE_NAME$';

	public function formName($service, $label, $config)
	{
		if (isset($config['name'])) {
			return $config['name'];
		}

		if (isset($config['label'])) {
			$name = $config['label'];
		} else {
			if (strpos($label, '@') !== FALSE) {
				$name = substr($label, 0, strpos($label, '@'));
			} else {
				$name = $label;
			}
		}

		if (isset($config['prefix'], $config['joiner'])) {
			$prefix = $config['prefix'];
			$joiner = $config['joiner'];

			if ($prefix === self::PREFIX_SERVICE_NAME) {
				$prefix = $service;
			}

			$name = $prefix . $joiner . $name;
		}

		return $name;
	}
}