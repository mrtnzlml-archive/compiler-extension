<?php declare(strict_types = 1);

namespace Adeira\Tests;

class ExtensionEmptyConfig extends \Adeira\CompilerExtension
{

	public function provideConfig()
	{
		return \Tester\FileMock::create('', 'neon');
	}

}
