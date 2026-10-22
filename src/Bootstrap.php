<?php

declare(strict_types=1);

namespace Base;

use Nette\Utils\FileSystem;

class Bootstrap
{
	public static function boot(): Configurator
	{
		$dir = \dirname((new \ReflectionClass(static::class))->getFileName());
		
		$environment = static::loadEnvironment($dir . '/../config/environments.neon', $dir . '/../temp/cache');
		
		$configurator = new Configurator();
		$configurator->setDebugMode($environment['parameters']['access']['debug'] ?? []);
		$configurator->setTimeZone('Europe/Prague');
		
		$trustedMode = $configurator->isDebugMode() || Configurator::detectDebugMode($environment['parameters']['access']['trusted']);
		$debugMode = $trustedMode ? static::getDebugModeByCookie($configurator->isDebugMode()) : $configurator->isDebugMode();
		
		$configurator->addStaticParameters([
			'trustedMode' => $trustedMode,
			'appDir' => $dir,
			'debugMode' => $debugMode,
			'productionMode' => !$debugMode,
		]);
		
		if ($debugMode) {
			static::setTracySessionDirectory($dir . '/../temp/tracy-session');
		}
		
		$configurator->enableTracy($dir . '/../temp/log');
		$configurator->setTempDirectory($dir . '/../temp');
		$configurator->addConfig($dir . '/../config/general.neon');
		
		if (\is_file($dir . '/../config/general.production.neon')) {
			$configurator->addConfig($dir . '/../config/general.production.neon');
		} elseif (\is_file($dir . '/../config/general.local.neon')) {
			$configurator->addConfig($dir . '/../config/general.local.neon');
		} else {
			throw new \RuntimeException('Please run "composer init-devel or init-production"');
		}
		
		return $configurator;
	}
	
	public static function getDebugModeByCookie(bool $default): bool
	{
		return (bool) (\filter_input(\INPUT_COOKIE, 'debug') ?? $default);
	}
	
	/**
	 * Rozparsovaný `environments.neon` z cache — čte se v každém requestu ještě před DI kontejnerem a parsování
	 * NEONu stojí ~1 ms. Cache je PHP soubor s polem (přijde z OPcache) a platí, dokud se nezmění mtime souboru.
	 * @return array<mixed>
	 */
	protected static function loadEnvironment(string $file, string $cacheDir): array
	{
		// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- chybějící soubor ohlásí až Loader níže
		$modified = @\filemtime($file);
		$cacheFile = $cacheDir . '/environments.' . \md5($file) . '.php';
		
		if ($modified !== false && \is_file($cacheFile)) {
			$cached = include $cacheFile;
			
			if (\is_array($cached) && ($cached['modified'] ?? null) === $modified && \is_array($cached['data'] ?? null)) {
				return $cached['data'];
			}
		}
		
		$data = (new \Nette\DI\Config\Loader())->load($file);
		
		if ($modified !== false && self::isExportable($data)) {
			self::writeEnvironmentCache($cacheFile, ['modified' => $modified, 'data' => $data]);
		}
		
		return $data;
	}
	
	/**
	 * Tracy si jinak ukládá session debug baru (`tracy-*`) do `session.save_path` a ve 3 % debug requestů v něm dělá
	 * `glob()` přes celý adresář — se stovkami tisíc session souborů to jsou sekundy.
	 */
	protected static function setTracySessionDirectory(string $directory): void
	{
		if (!\class_exists(\Tracy\FileSession::class)) {
			return;
		}
		
		try {
			FileSystem::createDir($directory, 0770);
			\Tracy\Debugger::setSessionStorage(new \Tracy\FileSession($directory));
		} catch (\Throwable) {
			// Adresář nejde založit, nebo storage už nastavil dřívější boot ve stejném procesu (testy, CLI skripty) —
			// Tracy zůstane u dosavadního úložiště.
		}
	}
	
	/**
	 * @param array<mixed> $data
	 */
	private static function isExportable(array $data): bool
	{
		$exportable = true;
		
		\array_walk_recursive($data, static function ($value) use (&$exportable): void {
			$exportable = $exportable && ($value === null || \is_scalar($value));
		});
		
		return $exportable;
	}
	
	/**
	 * Zápis přes dočasný soubor a rename — souběžný request nikdy neincluduje rozepsaný soubor.
	 * @param array<mixed> $payload
	 */
	private static function writeEnvironmentCache(string $cacheFile, array $payload): void
	{
		$temporary = $cacheFile . '.' . \bin2hex(\random_bytes(4)) . '.tmp';
		
		try {
			// Bez chmod (null): práva podle umask procesu jako u DI kontejneru, ne zapisovatelné pro všechny.
			FileSystem::write($temporary, '<?php return ' . \var_export($payload, true) . ";\n", null);
			
			// Nativní rename (ne FileSystem::rename, ten cíl napřed smaže) — nahrazení je atomické.
			// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- selhání řeší řádek níže
			if (@\rename($temporary, $cacheFile)) {
				return;
			}
			
			FileSystem::delete($temporary);
		} catch (\Throwable) {
			// Cache je jen zrychlení — bez ní se NEON čte dál.
		}
	}
}
