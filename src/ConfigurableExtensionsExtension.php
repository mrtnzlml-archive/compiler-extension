<?php

namespace Adeira;

use Nette;

class ConfigurableExtensionsExtension extends \Nette\DI\Extensions\ExtensionsExtension
{

	private $experimental;

	public function __construct($experimental = FALSE)
	{
		$this->experimental = $experimental;
	}

	public function loadFromFile($file)
	{
		$loader = new \Nette\DI\Config\Loader;
		if ($this->experimental === TRUE) {
			$loader->addAdapter('neon', GroupedNeonAdapter::class);
		}
		$res = $loader->load($file);
		$this->compiler->addDependencies($loader->getDependencies());
		return $res;
	}

	public function loadConfiguration()
	{
		$ceeConfig = $this->getConfig(); // configuration of this extension (list of extensions)

		foreach ($ceeConfig as $name => $class) {
			if ($class instanceof Nette\DI\Statement) {
				$rc = new \ReflectionClass($class->getEntity());
				$this->compiler->addExtension($name, $extension = $rc->newInstanceArgs($class->arguments));
			} else {
				$this->compiler->addExtension($name, $extension = new $class);
			}

			$builder = $this->getContainerBuilder();
			$extensionConfigFile = FALSE;
			if (method_exists($extension, 'provideConfig')) {
				$extensionConfigFile = $extension->provideConfig();
			}

			if ($extensionConfigFile) {
				$extensionConfig = $this->loadFromFile($extensionConfigFile); //TODO: addConfig jako pole + pole konfiguračních souborů
				if (isset($extensionConfig['parameters'])) {
					$builder->parameters = \Nette\DI\Config\Helpers::merge(
						\Nette\DI\Helpers::expand($extensionConfig['parameters'], $extensionConfig['parameters'], TRUE),
						$builder->parameters
					);
				}
				if (isset($extensionConfig['services'])) {
					$services = $this->expandExtensionParametersInServices(
						$extensionConfig['services'],
						$this->compiler->getConfig()[$name] ?? []
					);
					$extensionConfig['services'] = $services;
				}
				$this->compiler->addConfig($extensionConfig);
			}
			//TODO: exception když se snažím konfigurovat něco co neexistuje (?)
		}
	}

	/**
	 * Expands %%variables%% in extensions scope.
	 *
	 * @throws \Nette\OutOfRangeException
	 * @throws \Nette\InvalidArgumentException
	 */
	private function expandExtensionParametersInServices($services, array $config)
	{
		return self::expand($services, $config, TRUE);
	}

	/**
	 * Expands %%placeholders%%
	 *
	 * @return mixed
	 *
	 * @throws Nette\InvalidArgumentException
	 * @throws Nette\OutOfRangeException
	 *
	 * This is basically copy of \Nette\DI\Helpers::expand
	 */
	public static function expand($var, array $params, $recursive = FALSE)
	{
		if (is_array($var)) {
			$res = [];
			foreach ($var as $key => $val) {
				$res[$key] = self::expand($val, $params, $recursive);
			}
			return $res;
		} elseif ($var instanceof Nette\DI\Statement) {
			return new Nette\DI\Statement(
				self::expand($var->getEntity(), $params, $recursive),
				self::expand($var->arguments, $params, $recursive)
			);
		} elseif (!is_string($var)) {
			return $var;
		}

		$parts = preg_split('#%%([\w.-]*)%%#i', $var, -1, PREG_SPLIT_DELIM_CAPTURE);
		$res = '';
		foreach ($parts as $n => $part) {
			if ($n % 2 === 0) {
				$res .= $part;
			} elseif ($part === '') {
				$res .= '%';
			} elseif (isset($recursive[$part])) {
				throw new \Nette\InvalidArgumentException(
					sprintf('Circular reference detected for variables: %s.', implode(', ', array_keys($recursive)))
				);
			} else {
				try {
					$val = Nette\Utils\Arrays::get($params, explode('.', $part));
				} catch (\Nette\InvalidArgumentException $exc) {
					//FIXME: OutOfRangeException only because of BC
					throw new \Nette\OutOfRangeException(
						"Cannot replace %%$part%% because parameter does not exist.",
						0,
						$exc
					);
				}
				if ($recursive) {
					$val = self::expand($val, $params, (is_array($recursive) ? $recursive : []) + [$part => 1]);
				}
				if (strlen($part) + 4 === strlen($var)) {
					return $val;
				}
				if (!is_scalar($val) && $val !== NULL) {
					throw new \Nette\InvalidArgumentException("Unable to concatenate non-scalar parameter '$part' into '$var'.");
				}
				$res .= $val;
			}
		}
		return $res;
	}

}
