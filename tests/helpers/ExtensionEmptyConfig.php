<?php

namespace Adeira\Tests;

class ExtensionEmptyConfig extends \Adeira\CompilerExtension
{

	public function loadConfiguration()
	{
		$this->addConfig(\Tester\FileMock::create('', 'neon'));
	}

}
