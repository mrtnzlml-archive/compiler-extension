<?php declare(strict_types = 1);

namespace Adeira\Tests;

use Adeira\GroupedNeonAdapter;
use Tester\Assert;
use Tester\FileMock;

require dirname(__DIR__) . '/bootstrap.php';

/**
 * @testCase
 */
class GroupedNeonAdapterTest extends \Tester\TestCase
{

	public function testThatProcessExpandsEntities()
	{
		$loader = new \Nette\DI\Config\Loader;
		$loader->addAdapter($extension = 'neon', GroupedNeonAdapter::class);
		$configuration = <<<NEON
section:
	- Namespace\ClassA
	B: Namespace\ClassB
	- Namespace\(Class_1)
	- Namespace\ClassC
	- Namespace\(
		Class_2
		Class_3
		Class_4
	)
NEON;
		Assert::same([
			'section' => [
				0 => 'Namespace\\ClassA',
				'B' => 'Namespace\\ClassB',
				2 => 'Namespace\\ClassC', // it doesn't cleanup indices but who cares
				4 => 'Namespace\\Class_1',
				5 => 'Namespace\\Class_2',
				6 => 'Namespace\\Class_3',
				7 => 'Namespace\\Class_4',
			],
		], $loader->load(FileMock::create($configuration, $extension)));
	}

	public function testThatClassMustBeAnonymous()
	{
		$loader = new \Nette\DI\Config\Loader;
		$loader->addAdapter($extension = 'neon', GroupedNeonAdapter::class);
		$configuration = <<<NEON
section:
	A: Namespace\ClassA
	B: Namespace\(
		Class_2
		Class_3
		Class_4
	)
NEON;
		Assert::exception(function () use ($loader, $configuration, $extension) {
			$loader->load(FileMock::create($configuration, $extension));
		}, \Nette\Neon\Exception::class, 'Service with grouped classes must be anonymous. You have to remove key \'B\' to use this feature.');
	}

	public function testThatExpandedClassMustBeAnonymous()
	{
		$loader = new \Nette\DI\Config\Loader;
		$loader->addAdapter($extension = 'neon', GroupedNeonAdapter::class);
		$configuration = <<<NEON
section:
	A: Namespace\ClassA
	- Namespace\(
		Class_2,
		c3: Class_3,
		Class_4,
	)
NEON;
		Assert::exception(function () use ($loader, $configuration, $extension) {
			$loader->load(FileMock::create($configuration, $extension));
		}, \Nette\Neon\Exception::class, 'Grouped classes in service definition must be anonymous. Please remove key \'c3\'.');
	}

}

(new GroupedNeonAdapterTest)->run();
