<?php

namespace Mrtnzlml\Tests;

use Tester\Assert;

require dirname(__DIR__) . '/bootstrap.php';

/**
 * @testCase
 */
class CompilerExtension extends \Tester\TestCase
{

	/** @var CustomExtension1 */
	private $extension;

	/** @var \Nette\DI\Container */
	private $generatedContainer;

	public function setUp()
	{
		$compiler = new \Nette\DI\Compiler;
		$this->extension = (new CustomExtension1)->setCompiler($compiler, 'ext');

		$compiler->addExtension('extensions', new \Nette\DI\Extensions\ExtensionsExtension);
		$compiler->addExtension('latte', new \Nette\Bridges\ApplicationDI\LatteExtension(dirname(__DIR__) . '/temp'));
		$compiler->addExtension('application', new \Nette\Bridges\ApplicationDI\ApplicationExtension);
		$compiler->addExtension('routing', new \Nette\Bridges\ApplicationDI\RoutingExtension);
		$compiler->addExtension('http', new \Nette\Bridges\HttpDI\HttpExtension);
		$compiler->addExtension('ext1', $this->extension);

		$compiler->loadConfig(__DIR__ . '/config.neon');
		$compiler = $compiler->setClassName($className = '_' . md5(mt_rand(100, 999)));
		eval($compiler->compile());
		$this->generatedContainer = new $className;
	}

	public function testAddConfigParameters()
	{
		Assert::same([
			'k1' => 'v1',
			'k2' => 'overridden',
			'k3' => 'v3',
		], $this->generatedContainer->getParameters());
	}

	public function testExtensionParametersExpand()
	{
		//there is test in constructor of Service3
		$this->generatedContainer->getByType(\Mrtnzlml\Tests\Service3::class);
		//do not add another asserts so it will fail when the test forgets to execute an assertion
	}

	public function testExtensionParametersExpandFactory()
	{
		//there is test in constructor of Service5
		$this->generatedContainer->getByType(\Mrtnzlml\Tests\IService5Factory::class)->create();
		//do not add another asserts so it will fail when the test forgets to execute an assertion
	}

	public function testAddConfigExtensions()
	{
		Assert::same([
			'extensions' => 'Nette\\DI\\Extensions\\ExtensionsExtension',
			'latte' => 'Nette\\Bridges\\ApplicationDI\\LatteExtension',
			'application' => 'Nette\\Bridges\\ApplicationDI\\ApplicationExtension',
			'routing' => 'Nette\\Bridges\\ApplicationDI\\RoutingExtension',
			'http' => 'Nette\\Bridges\\HttpDI\\HttpExtension',
			'ext1' => 'Mrtnzlml\\Tests\\CustomExtension1',
			'ext2' => 'Mrtnzlml\\Tests\\CustomExtension2',
			'ext3' => 'Mrtnzlml\\Tests\\ExtensionEmptyConfig',
		], array_map(function ($item) {
			return get_class($item);
		}, $this->extension->getExtensions()));

		/** @var CustomExtension2 $extension */
		$extension = $this->extension->getExtensions('Mrtnzlml\Tests\CustomExtension2')['ext2'];
		Assert::same([
			'ek1' => 'ev1',
			'ek2' => 'overridden',
			'ek3' => 'ev3',
		], $extension->getConfig());
	}

	public function testAddConfigServices()
	{
		$builder = $this->extension->getContainerBuilder();
		Assert::same([
			'Nette\\Application\\Application',
			'Nette\\Application\\PresenterFactory',
			'Nette\\Application\\LinkGenerator',
			'Nette\\Application\\Routers\\RouteList',
			'Nette\\Http\\RequestFactory',
			['@http.requestFactory', 'createHttpRequest'],
			'Nette\\Http\\Response',
			'Nette\\Http\\Context',
			'Mrtnzlml\\Tests\\Service2', //overridden (named service)
			'Mrtnzlml\\Tests\\Service4', //registered in config.neon
			'Mrtnzlml\\Tests\\Service5', //registered later in extension
			'Mrtnzlml\\Tests\\Service3', //registered later in extension
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
			'*' => ['', '*Module\\', '*'],
			'Nette' => ['NetteModule\\', '*\\', '*Presenter'],
			'Module' => ['App\\', '*Module\\', 'Controllers\\*Controller'],
		], $reflectionProperty->getValue($presenterFactory));
	}

	public function testReloadDefinition()
	{
		Assert::exception(function () {
			$this->extension->reloadDefinition(1);
		}, \Nette\InvalidArgumentException::class, 'Definition regex should be string name or array od string names.');
	}

}

(new CompilerExtension)->run();
