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
	 * @column
	 */
	public string|null $shopUrl;

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

	public function getIconImageSrc(): string|null
	{
		if ($this->icon === null) {
			return null;
		}

		return 'data:image/png;base64,' . $this->icon;
	}

	public function getIconImageFormAdmin(): string|null
	{
		return $this->icon ? "<img
                        width=\"24\"
                        height=\"24\"
                        src=\"data:image/png;base64,$this->icon\"
                        alt=\"\"
                        title=\"Specifické nastavení pro obchod: $this->name\"
                    />" : $this->name . ': ';
	}
}
