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
	 * Hosty shopu rozsekané ze středníkem oddělené hodnoty `baseUrl`.
	 *
	 * Prázdné části se zahazují: `getSelectedShopByDomain()` porovnává `str_contains($host, $baseUrl)`
	 * a `str_contains($cokoli, '')` je vždy `true`, takže jediný prázdný segment (koncový `;`, `;;`
	 * nebo prázdný `baseUrl`) by z tohohle shopu udělal match na **libovolnou** doménu a stáhl by na
	 * něj provoz všech ostatních shopů. `trim()` je ze stejného soudku — `abel.cz; b2b.abel.sk`
	 * s mezerou za středníkem by jinak nematchlo nikdy.
	 *
	 * @return list<string>
	 */
	public function getBaseUrls(): array
	{
		$parts = \array_map(
			static fn (string $part): string => \trim($part),
			\explode(';', Strings::lower($this->baseUrl)),
		);

		return \array_values(\array_filter($parts, static fn (string $part): bool => $part !== ''));
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
