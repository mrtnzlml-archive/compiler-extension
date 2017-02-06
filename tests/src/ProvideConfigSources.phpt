<?php declare(strict_types = 1);

namespace Adeira\Tests;

use Tester\Assert;

require dirname(__DIR__) . '/bootstrap.php';

/**
 * @testCase
 */
class ProvideConfigSources extends \Tester\TestCase
{

	use \Nette\SmartObject;

	public function testThatArrayWorks()
	{
		$compiler = new \Nette\DI\Compiler;
		$compiler->addExtension('extensions', new \Adeira\ConfigurableExtensionsExtension);
		$compiler->addExtension('ext', new \Adeira\Tests\CustomExtension3);
		$compiler->addConfig([
			'extensions' => [
				new class extends \Nette\DI\CompilerExtension {

					public function provideConfig()
					{
						return ['ext' => ['tadá' => 'tudum']];
					}

				},
			],
		]);
		$compiler->compile();
		Assert::same([
			'tadá' => 'tudum',
		], $compiler->getExtensions(\Adeira\Tests\CustomExtension3::class)['ext']->getConfig());
	}

	public function testThatStringThrowsException()
	{
		$compiler = new \Nette\DI\Compiler;
		$compiler->addExtension('extensions', new \Adeira\ConfigurableExtensionsExtension);
		$compiler->addExtension('ext', new \Adeira\Tests\CustomExtension3);
		$compiler->addConfig([
			'extensions' => [
				new class extends \Nette\DI\CompilerExtension {

					public function provideConfig()
					{
						return 'string';
					}

				},
			],
		]);
		Assert::exception(function() use ($compiler) {
			$compiler->compile();
		}, \Nette\UnexpectedValueException::class, "Method 'provideConfig' should return file name or array with configuration but 'string' given.");
	}

}

(new ProvideConfigSources)->run();
