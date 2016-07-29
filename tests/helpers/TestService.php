<?php

namespace Mrtnzlml\Tests;

class TestService extends \Mrtnzlml\CompilerExtension
{

	public function __construct($a, $b, $c)
	{
		\Tester\Assert::same(159753, $b);
	}

}
