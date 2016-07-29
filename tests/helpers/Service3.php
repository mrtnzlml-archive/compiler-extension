<?php

namespace Mrtnzlml\Tests;

class Service3 extends \Mrtnzlml\CompilerExtension
{

	public function __construct($a, $b, $c)
	{
		\Tester\Assert::type(\Mrtnzlml\Tests\Service2::class, $a);
		\Tester\Assert::same(159753, $b);
		\Tester\Assert::same('%', $c);
	}

}
