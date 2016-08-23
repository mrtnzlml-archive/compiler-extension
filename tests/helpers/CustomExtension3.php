<?php

namespace Mrtnzlml\Tests;

use Tester\FileMock;

class CustomExtension3 extends \Mrtnzlml\CompilerExtension
{

	public function loadConfiguration()
	{
		$config = <<<CONFIG
services:
	- Mrtnzlml\Tests\Service3('a', %%thisExtensionParameterDoesNotExist%%, 'c')
CONFIG;
		$this->addConfig(FileMock::create($config, 'neon'));
	}

}
