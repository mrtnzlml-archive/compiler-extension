<?php declare(strict_types = 1);

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
		$configurator->defaultExtensions = [
			'extensions' => [\Adeira\ConfigurableExtensionsExtension::class, [TRUE]],
			'application' => [Nette\Bridges\ApplicationDI\ApplicationExtension::class, ['%debugMode%', ['%appDir%'], '%tempDir%/cache']],
			'http' => [Nette\Bridges\HttpDI\HttpExtension::class, ['%consoleMode%']],
			'latte' => [Nette\Bridges\ApplicationDI\LatteExtension::class, ['%tempDir%/cache/latte', '%debugMode%']],
			'routing' => [Nette\Bridges\ApplicationDI\RoutingExtension::class, ['%debugMode%']],
		];
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
			'extensions' => 'Adeira\\ConfigurableExtensionsExtension',
			'application' => 'Nette\\Bridges\\ApplicationDI\\ApplicationExtension',
			'http' => 'Nette\\Bridges\\HttpDI\\HttpExtension',
			'latte' => 'Nette\\Bridges\\ApplicationDI\\LatteExtension',
			'routing' => 'Nette\\Bridges\\ApplicationDI\\RoutingExtension',
			'ext1' => 'Adeira\\Tests\\CustomExtension1',
			'ext2' => 'Adeira\\Tests\\CustomExtension2',
			'ext3' => 'Adeira\\Tests\\ExtensionEmptyConfig',
			'ext4' => 'Adeira\\Tests\\CustomExtension4',
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

	public function testAddConfigServices()
	{
		$builder = $this->compiler->getContainerBuilder();
		Assert::same([
			'Nette\\Application\\Application',
			'Nette\\Application\\PresenterFactory',
			'Nette\\Application\\LinkGenerator',
			'Nette\\Http\\RequestFactory',
			['@http.requestFactory', 'createHttpRequest'],
			'Nette\\Http\\Response',
			'Nette\\Http\\Context',
			'Latte\\Engine',
			'Nette\\Bridges\\ApplicationLatte\\TemplateFactory',
			'Nette\\Application\\Routers\\RouteList',
			'Adeira\\Tests\\CommandsStack',
			'Adeira\\Tests\\Definition',
			'Adeira\\Tests\\Service2', //overridden (named service)
			'Adeira\\Tests\\Service4', //registered in config.neon
			'Adeira\\Tests\\Service5', //registered later in extension
			'Adeira\\Tests\\Service3', //registered later in extension
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

	public function testRegisteredCommands()
	{
		$stack = $this->generatedContainer->getService('ext1.commands.stack');
		Assert::same([
			'com1_ext1',
			'com2_ext1',
			'com3_ext1',
			'com1_ext2',
			'com2_ext2',
		], $stack->commands);
	}

}

(new CompilerExtension)->run();
