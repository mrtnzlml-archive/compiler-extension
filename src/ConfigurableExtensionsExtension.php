<?php

namespace Adeira;

use Nette;

class ConfigurableExtensionsExtension extends \Nette\DI\Extensions\ExtensionsExtension
{

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
	 * @throws \Adeira\CannotBeReplacedException
	 */
	private function expandExtensionParametersInServices($services, array $config)
	{
		$replacePlaceholder = function ($stringWithPlaceholder) use ($config) {
			if (is_string($stringWithPlaceholder) && preg_match('~%%([^,)]+)%%~', $stringWithPlaceholder, $matches)) {
				$parameterName = $matches[1];
				if (!array_key_exists($parameterName, $config)) {
					throw new \OutOfRangeException("Cannot replace %%$parameterName%% because parameter does not exist.");
				}
				return $config[$parameterName];
			}
			throw new \Adeira\CannotBeReplacedException;
		};
		foreach ($services as $serviceName => &$def) {
			if ($def instanceof \Nette\DI\Statement) {
				foreach ($def->arguments as &$argumentRef) {
					try {
						$argumentRef = $replacePlaceholder($argumentRef);
					} catch (\Adeira\CannotBeReplacedException $exc) {
						//never mind
					}
				}
			} elseif (is_array($def) && array_key_exists('arguments', $def)) {
				$replacedArguments = $def['arguments'];
				foreach ($def['arguments'] as $key => $argument) {
					try {
						$replacedArguments[$key] = $replacePlaceholder($argument);
					} catch (\Adeira\CannotBeReplacedException $exc) {
						//never mind
					}
				}
				$def['arguments'] = $replacedArguments;
			}
		}
		return $services;
	}

}
