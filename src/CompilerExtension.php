<?php

namespace Mrtnzlml;

use Nette\Application\IPresenterFactory;

class CompilerExtension extends \Nette\DI\CompilerExtension
{

	/**
	 * How to do it better? I need to reload extension configuration and it means remove old definitions and aliases from container and
	 * call ->loadConfiguration() again. But I don't know what to remove without naming it here...
	 */
	private $removeDefinitions = [
		'latte\..+',
		'nette\.latte',
	];

	/**
	 * Should be called in loadConfiguration().
	 *
	 * @return array
	 */
	protected function addConfig($configFile)
	{
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

	protected function reloadDefinition($regex)
	{
		if (is_string($regex)) {
			array_push($this->removeDefinitions, $regex);
		} elseif (is_array($regex)) {
			foreach ($regex as $r) {
				array_push($this->removeDefinitions, $r);
			}
		} else {
			throw new \Nette\InvalidArgumentException('Definition regex should be string name or array od string names.');
		}
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

			$builder = $this->getContainerBuilder();
			$aliases = $builder->getAliases();
			foreach ($builder->getDefinitions() as $defName => $definition) {
				foreach ($this->removeDefinitions as $regex) {
					if (preg_match('~' . $regex . '~i', $defName)) {
						$builder->removeAlias(array_search($defName, $aliases));
						$builder->removeDefinition($defName);
					}
				}
			}
			$extension->loadConfiguration();
		}
	}

	/**
	 * Expands %%variables%% in extensions scope.
	 */
	private function expandExtensionParametersInServices($services)
	{
		$replacePlaceholder = function ($stringWithPlaceholder) {
			if (is_string($stringWithPlaceholder) && preg_match('~%%([^,)]+)%%~', $stringWithPlaceholder, $matches)) {
				return $this->getConfig()[$matches[1]];
			}
			throw new \Mrtnzlml\CannotBeReplacedException;
		};
		foreach ($services as $_ => &$def) {
			if ($def instanceof \Nette\DI\Statement) {
				foreach ($def->arguments as &$argumentRef) {
					try {
						$argumentRef = $replacePlaceholder($argumentRef);
					} catch (\Mrtnzlml\CannotBeReplacedException $exc) {
						//never mind
					}
				}
			} elseif (is_array($def) && array_key_exists('arguments', $def)) {
				$replacedArguments = $def['arguments'];
				foreach ($def['arguments'] as $key => $argument) {
					try {
						$replacedArguments[$key] = $replacePlaceholder($argument);
					} catch (\Mrtnzlml\CannotBeReplacedException $exc) {
						//never mind
					}
				}
				$def['arguments'] = $replacedArguments;
			}
		}
		return $services;
	}

}
