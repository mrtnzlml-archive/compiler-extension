<?php

namespace Mrtnzlml\Tests;

class Service5 extends \Mrtnzlml\CompilerExtension
{

	public function __construct($abc, $xyz)
	{
		\Tester\Assert::same('test', $abc);
		\Tester\Assert::same(159753, $xyz);
	}

}
