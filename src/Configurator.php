<?php

namespace Base;

class Configurator extends \Nette\Bootstrap\Configurator
{
	public static function detectDebugMode(array|string|null $list = null): bool
	{
		if (parent::detectDebugMode($list) === true) {
			return true;
		}

		// phpcs:ignore
		$addr = $_SERVER['REMOTE_ADDR'] ?? \php_uname('n');

		return static::isIpInDockerRange($addr);
	}

	public static function isIpInDockerRange(string $ip): bool
	{
		$ipLong = \ip2long($ip);
		$rangeStart = \ip2long('172.16.0.0');
		$rangeEnd = \ip2long('172.31.255.255');

		return $ipLong >= $rangeStart && $ipLong <= $rangeEnd;
	}
}
