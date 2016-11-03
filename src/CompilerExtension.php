<?php

namespace Adeira;

use Nette\Application\IPresenterFactory;

class CompilerExtension extends \Nette\DI\CompilerExtension
{

	/**
	 * Should be called in loadConfiguration().
	 *
	 * @return array
	 */
	protected function addConfig($configFile)
	{
		//TODO (?) debug_backtrace()[1]['function'] === 'loadConfiguration'

		$builder = $this->getContainerBuilder();
		$config = $this->loadFromFile($configFile);
		if (isset($config['parameters'])) {
			//$config['parameters'] = ['container' => NULL] + $config['parameters'];
			$builder->parameters = \Nette\DI\Config\Helpers::merge(
				\Nette\DI\Helpers::expand($config['parameters'], $config['parameters'], TRUE),
				$builder->parameters
			);
		}
		$this->processExtensions($config);
		if (isset($config['services'])) {
			$services = $this->expandExtensionParametersInServices($config['services']);
			$this->compiler->addConfig(['services' => $services]);
		}
		return $config;
	}

	/**
	 * Should be called in beforeCompile().
	 *
	 * @param array $mapping ['Articles' => 'Ant\Articles\Presenters\*Presenter']
	 */
	protected function setMapping(array $mapping)
	{
		$builder = $this->getContainerBuilder();
		$presenterFactory = $builder->getByType(IPresenterFactory::class);
		if ($presenterFactory === NULL) {
			throw new \Nette\InvalidStateException('Cannot find Nette\Application\IPresenterFactory implementation.');
		}
		$builder->getDefinition($presenterFactory)->addSetup('setMapping', [$mapping]);
	}

	/**
	 * @deprecated
	 */
	protected function reloadDefinition($regex)
	{
		trigger_error(__METHOD__ . ' is deprecated. This should be fully automatic now. Just remove it and you are ready to go.', E_USER_DEPRECATED);
	}

	private function processExtensions($config)
	{
		$extensions = $this->compiler->getExtensions();
		/**
		 * @var string $name
		 * @var \Nette\DI\CompilerExtension $extension
		 */
		foreach (array_intersect_key($extensions, $config) as $name => $extension) {
			$newConfig = \Nette\DI\Config\Helpers::merge($config[$name], $extension->getConfig());
			$extension->setConfig($newConfig);
		}
	}

	/**
	 * Expands %%variables%% in extensions scope.
	 */
	private function expandExtensionParametersInServices($services)
	{
		$replacePlaceholder = function ($stringWithPlaceholder) {
			if (is_string($stringWithPlaceholder) && preg_match('~%%([^,)]+)%%~', $stringWithPlaceholder, $matches)) {
				$config = $this->getConfig();
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
