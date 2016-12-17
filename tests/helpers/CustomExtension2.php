<?php

namespace Adeira\Tests;

use Tester\FileMock;

class CustomExtension2 extends \Nette\DI\CompilerExtension
{

	public function provideConfig()
	{
		$config = <<<NEON
ext1:
	commands:
		- 'com1_ext2'
		- 'com2_ext2'
NEON;
		return FileMock::create($config, 'neon');
	}

	public function loadConfiguration()
	{
		$this->getContainerBuilder()
			->addDefinition($this->prefix('definition'))
			->setClass(\Adeira\Tests\Definition::class);
	}

}
