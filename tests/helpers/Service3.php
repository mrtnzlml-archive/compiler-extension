<?php

namespace Adeira\Tests;

class Service3 extends \Adeira\CompilerExtension
{

	public function __construct($a, $b, $c)
	{
		\Tester\Assert::type(\Adeira\Tests\Service2::class, $a);
		\Tester\Assert::same(159753, $b);
		\Tester\Assert::same('%', $c);
	}

}
