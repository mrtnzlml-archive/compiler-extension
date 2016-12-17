<?php declare(strict_types = 1);

namespace Adeira\Tests;

use Tester\Assert;

require dirname(__DIR__) . '/bootstrap.php';

/**
 * @testCase
 */
class DeprecatedAddConfig extends \Tester\TestCase
{

	use \Nette\SmartObject;

	public function testDeprecatedMessage()
	{
		Assert::error(function () {
			/** @var \Nette\DI\CompilerExtension $extension */
			$extension = new class extends \Adeira\CompilerExtension
			{

				public function loadConfiguration()
				{
					$this->addConfig(\Tester\FileMock::create('', 'neon'));
				}

			};
			$extension->setCompiler(new class extends \Nette\DI\Compiler
			{
				//empty mock
			}, 'compiler');
			$extension->loadConfiguration();
		}, E_USER_NOTICE, 'Adeira\CompilerExtension::addConfig is deprecated. Use Adeira\ConfigurableExtensionsExtension instead.');
	}

}

(new DeprecatedAddConfig)->run();
