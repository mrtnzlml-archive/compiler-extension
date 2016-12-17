<?php

namespace Adeira;

use Nette\Application\IPresenterFactory;

class CompilerExtension extends \Nette\DI\CompilerExtension
{

	public function provideConfig()
	{
	}

	/**
	 * @deprecated Use \Adeira\ConfigurableExtensionsExtension instead.
	 */
	protected function addConfig($configFile)
	{
		trigger_error(__METHOD__ . ' is deprecated');
		return $this->loadFromFile($configFile);
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

}
