<?php

namespace Adeira\Tests;

class ExtensionEmptyConfig extends \Adeira\CompilerExtension
{

	public function provideConfig()
	{
		return \Tester\FileMock::create('', 'neon');
	}

}
