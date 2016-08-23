<?php

namespace Mrtnzlml\Tests;

class Service5 extends \Mrtnzlml\CompilerExtension
{

	public function __construct($abc, $xyz, $false, $null)
	{
		\Tester\Assert::same('test', $abc);
		\Tester\Assert::same(159753, $xyz);
		\Tester\Assert::same(FALSE, $false);
		\Tester\Assert::null($null);
	}

}
