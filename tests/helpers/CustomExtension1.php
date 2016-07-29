<?php

namespace Mrtnzlml\Tests;

use Tester\FileMock;

class CustomExtension1 extends \Mrtnzlml\CompilerExtension
{

	public function loadConfiguration()
	{
		$config = <<<CONFIG
parameters:
	k2: overridden
	k3: v3
services:
	- Mrtnzlml\Tests\Service3(@named(), %%extensionParameter%%, '%%')
	named: Mrtnzlml\Tests\Service2
ext2:
	ek2: overridden
	ek3: ev3
latte:
	macros:
		- Mrtnzlml\Tests\FakeLatteMacro
CONFIG;
		$this->addConfig(FileMock::create($config, 'neon'));
	}

	public function beforeCompile()
	{
		$this->setMapping(['Module' => 'App\*Module\Controllers\*Controller']);
	}

	public function getExtensions($type = NULL)
	{
		return $this->compiler->getExtensions($type);
	}

	public function reloadDefinition($regex)
	{
		parent::reloadDefinition($regex);
	}

}
