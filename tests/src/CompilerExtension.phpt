<?php

namespace Adeira\Tests;

use Nette;
use Tester;
use Tester\Assert;

require dirname(__DIR__) . '/bootstrap.php';

/**
 * @testCase
 */
class CompilerExtension extends \Tester\TestCase
{

	/** @var \Nette\DI\Compiler */
	private $compiler;

	/** @var \Nette\DI\Container */
	private $generatedContainer;

	public function setUp()
	{
		Tester\Helpers::purge($tempDir = __DIR__ . '/../temp/thread_' . getenv(Tester\Environment::THREAD));

		$configurator = new Nette\Configurator;
		$configurator->setTempDirectory($tempDir);
		$configurator->addConfig(__DIR__ . '/config.neon');
		$configurator->onCompile[] = function (Nette\Configurator $sender, Nette\DI\Compiler $compiler) {
			$this->compiler = $compiler;
		};
		$dic = $configurator->createContainer();
		$this->generatedContainer = $dic;
	}

	/**
	 * Original parameters are added in config.neon:
	 *
	 * parameters:
	 *     k1: v1
	 *     k2: v2
	 *
	 * These parameters are overridden by CustomExtension1 using addConfig method.
	 */
	public function testAddConfigParameters()
	{
		$parameters = $this->generatedContainer->getParameters();
		Assert::same('v1', $parameters['k1']);
		Assert::same('overridden', $parameters['k2']);
		Assert::same('v3', $parameters['k3']);
	}

	public function testExtensionParametersExpand()
	{
		//there is test in constructor of Service3
		$this->generatedContainer->getByType(\Adeira\Tests\Service3::class);
		//do not add another asserts so it will fail when the test forgets to execute an assertion
	}

	public function testExtensionParametersExpandFactory()
	{
		//there is test in constructor of Service5
		$this->generatedContainer->getByType(\Adeira\Tests\IService5Factory::class)->create();
		//do not add another asserts so it will fail when the test forgets to execute an assertion
	}

	public function testAddConfigExtensions()
	{
		Assert::same([
			'php' => 'Nette\\DI\\Extensions\\PhpExtension',
			'constants' => 'Nette\\DI\\Extensions\\ConstantsExtension',
			'extensions' => 'Nette\\DI\\Extensions\\ExtensionsExtension',
			'application' => 'Nette\\Bridges\\ApplicationDI\\ApplicationExtension',
			'decorator' => 'Nette\\DI\\Extensions\\DecoratorExtension',
			'cache' => 'Nette\\Bridges\\CacheDI\\CacheExtension',
			'di' => 'Nette\\DI\\Extensions\\DIExtension',
			'http' => 'Nette\\Bridges\\HttpDI\\HttpExtension',
			'latte' => 'Nette\\Bridges\\ApplicationDI\\LatteExtension',
			'routing' => 'Nette\\Bridges\\ApplicationDI\\RoutingExtension',
			'session' => 'Nette\\Bridges\\HttpDI\\SessionExtension',
			'ext1' => 'Adeira\\Tests\\CustomExtension1',
			'ext2' => 'Adeira\\Tests\\CustomExtension2',
			'ext3' => 'Adeira\\Tests\\ExtensionEmptyConfig',
			'inject' => 'Nette\\DI\\Extensions\\InjectExtension',
		], array_map(function ($item) {
			return get_class($item);
		}, $this->compiler->getExtensions()));

		/** @var CustomExtension2 $extension */
		$extension = $this->compiler->getExtensions('Adeira\Tests\CustomExtension2')['ext2'];
		Assert::same([
			'ek1' => 'ev1',
			'ek2' => 'overridden',
			'ek3' => 'ev3',
		], $extension->getConfig());
	}

	public function testReplaceUnknownExtensionParameter()
	{
		$compiler = new \Nette\DI\Compiler;
		$compiler->addExtension('ext3', new \Adeira\Tests\CustomExtension3);
		Assert::throws(function () use ($compiler) {
			$compiler->compile();
		}, \OutOfRangeException::class, 'Cannot replace %%%%thisExtensionParameterDoesNotExist%%%% because parameter does not exist.');
	}

	public function testAddConfigServices()
	{
		$builder = $this->compiler->getContainerBuilder();
		Assert::same([
			'Nette\\Application\\Application',
			'Nette\\Application\\PresenterFactory',
			'Nette\\Application\\LinkGenerator',
			'Nette\\Caching\\Storages\\SQLiteJournal',
			'Nette\\Caching\\Storages\\FileStorage',
			'Nette\\Http\\RequestFactory',
			['@http.requestFactory', 'createHttpRequest'],
			'Nette\\Http\\Response',
			'Nette\\Http\\Context',
			'Latte\\Engine',
			'Nette\\Bridges\\ApplicationLatte\\TemplateFactory',
			'Nette\\Application\\Routers\\RouteList',
			'Nette\\Http\\Session',
			'Adeira\\Tests\\Definition',
			'Adeira\\Tests\\Service2', //overridden (named service)
			'Adeira\\Tests\\Service4', //registered in config.neon
			'Adeira\\Tests\\Service5', //registered later in extension
			'Adeira\\Tests\\Service3', //registered later in extension
			'NetteModule\\ErrorPresenter',
			'NetteModule\\MicroPresenter',
			'Nette\\DI\\Container',
		], array_map(function (\Nette\DI\ServiceDefinition $item) {
			return $item->getFactory()->getEntity();
		}, array_values($builder->getDefinitions())));
	}

	public function testSetMapping()
	{
		/** @var \Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = $this->generatedContainer->getService('application.presenterFactory');
		Assert::type('Nette\Application\PresenterFactory', $presenterFactory);

		$reflectionClass = new \ReflectionClass($presenterFactory);
		$reflectionProperty = $reflectionClass->getProperty('mapping');
		$reflectionProperty->setAccessible(TRUE);
		Assert::same([
			'*' => ['a\\', '*b\\', '*c'],
			'Nette' => ['NetteModule\\', '*\\', '*Presenter'],
			'Module' => ['App\\', '*Module\\', 'Controllers\\*Controller'],
		], $reflectionProperty->getValue($presenterFactory));
	}

}

(new CompilerExtension)->run();
