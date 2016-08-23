<?php

namespace Mrtnzlml\Tests;

class Service5 extends \Mrtnzlml\CompilerExtension
{

	public function __construct($xyz)
	{
		\Tester\Assert::same(159753, $xyz);
	}

}
