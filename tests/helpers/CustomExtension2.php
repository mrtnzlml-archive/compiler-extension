<?php

namespace Adeira\Tests;

class CustomExtension2 extends \Adeira\CompilerExtension
{

	public function loadConfiguration()
	{
		$this->getContainerBuilder()
			->addDefinition($this->prefix('definition'))
			->setClass(\Adeira\Tests\Definition::class);
	}

}
