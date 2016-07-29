<?php

namespace Mrtnzlml\Tests;

class ExtensionEmptyConfig extends \Mrtnzlml\CompilerExtension
{

	public function loadConfiguration()
	{
		$this->addConfig(\Tester\FileMock::create('', 'neon'));
	}

}
