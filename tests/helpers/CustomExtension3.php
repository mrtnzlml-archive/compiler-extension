<?php declare(strict_types = 1);

namespace Adeira\Tests;

use Tester\FileMock;

class CustomExtension3 extends \Nette\DI\CompilerExtension
{

	public function provideConfig()
	{
		$config = <<<CONFIG
services:
	- Adeira\Tests\Service3('a', %%thisExtensionParameterDoesNotExist%%, 'c')

thisDoesntExist: true
CONFIG;
		return FileMock::create($config, 'neon');
	}

}
