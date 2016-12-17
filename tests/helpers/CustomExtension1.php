<?php

namespace Adeira\Tests;

use Tester\FileMock;

class CustomExtension1 extends \Adeira\CompilerExtension
{

	public function provideConfig()
	{
		$config = <<<CONFIG
parameters:
	k2: overridden
	k3: v3
services:
	- Adeira\Tests\Service3(@named(), %%numericExtensionParameter%%, '%%')
	- implement: Adeira\Tests\IService5Factory
	  arguments:
	  	- test
	  	- %%numericExtensionParameter%%
	  	- %%falseExtensionParameter%%
	  	- %%nullExtensionParameter%%
	named: Adeira\Tests\Service2
ext2:
	ek2: overridden
	ek3: ev3
latte:
	macros:
		- Adeira\Tests\FakeLatteMacro
CONFIG;
		return FileMock::create($config, 'neon');
	}

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('commands.stack'))
			->setClass(\Adeira\Tests\CommandsStack::class)
			->addSetup('?->addCommands(?)', [
				'@self',
				$this->config['commands'],
			]);
	}

	public function beforeCompile()
	{
		$this->setMapping(['Module' => 'App\*Module\Controllers\*Controller']);
	}

	public function getExtensions($type = NULL)
	{
		return $this->compiler->getExtensions($type);
	}

}
