<?php

namespace Adeira\Tests;

use Tester\FileMock;

class CustomExtension3 extends \Adeira\CompilerExtension
{

	public function loadConfiguration()
	{
		$config = <<<CONFIG
services:
	- Adeira\Tests\Service3('a', %%thisExtensionParameterDoesNotExist%%, 'c')
CONFIG;
		$this->addConfig(FileMock::create($config, 'neon'));
	}

}
