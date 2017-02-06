<?php declare(strict_types = 1);

namespace Adeira\Tests;

use Tester\Assert;
use Tester\FileMock;

require dirname(__DIR__) . '/bootstrap.php';

/**
 * @testCase
 */
class ExtensionsManipulation extends \Tester\TestCase
{

	use \Nette\SmartObject;

	public function testDeprecatedMessage()
	{
		$compiler = new \Nette\DI\Compiler;
		$compiler->addExtension('extensions', new \Adeira\ConfigurableExtensionsExtension);
		$compiler->addConfig([
			'extensions' => [
				new class extends \Nette\DI\CompilerExtension {

					public function provideConfig()
					{
						$config = <<<NEON
extensions:
	key: value
NEON;
						return FileMock::create($config, 'neon');
					}

				}
			]
		]);
		Assert::exception(function () use ($compiler) {
			$compiler->compile();
		}, \Nette\NotSupportedException::class, 'You cannot manipulate original extensions. This operation is not supported.');
	}

}

(new ExtensionsManipulation)->run();
