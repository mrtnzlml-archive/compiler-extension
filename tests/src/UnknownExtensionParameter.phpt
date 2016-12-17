<?php

namespace Adeira\Tests;

use Tester\Assert;
use Tester\FileMock;

require dirname(__DIR__) . '/bootstrap.php';

/**
 * @testCase
 */
class UnknownExtensionParameter extends \Tester\TestCase
{

	public function testReplaceUnknownExtensionParameter()
	{
		$compiler = new \Nette\DI\Compiler;
		$compiler->addExtension('extensions', new \Adeira\ConfigurableExtensionsExtension);
		$config = <<<NEON
extensions:
	ext3: Adeira\Tests\CustomExtension3
NEON;
		$compiler->loadConfig(FileMock::create($config, 'neon'));
		Assert::throws(
			function () use ($compiler) {
				$compiler->compile();
			},
			\OutOfRangeException::class,
			'Cannot replace %%%%thisExtensionParameterDoesNotExist%%%% because parameter does not exist.'
		);
	}

}

(new UnknownExtensionParameter)->run();
