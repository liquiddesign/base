<?php

namespace Base\DB;

use Nette\Utils\Strings;
use StORM\Entity;

/**
 * Represents standalone shop using same database
 */
class Shop extends Entity
{
	/**
	 * @column
	 */
	public string $name;

	/**
	 * Values separated by semicolon
	 * @column
	 */
	public string $baseUrl;

	/**
	 * @column{"type":"longtext"}
	 */
	public string|null $icon;

	/**
	 * @return list<string>
	 */
	public function getBaseUrls(): array
	{
		return \explode(';', Strings::lower($this->baseUrl));
	}
}
