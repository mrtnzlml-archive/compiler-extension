# Enhanced CompilerExtension for Nette Framework

[![Build Status](https://travis-ci.org/adeira/compiler-extension.svg?branch=master)](https://travis-ci.org/adeira/compiler-extension)

If you have more complicated project structure with a lot of bundles (DIC extensions), it's very common that you have to setup a lot of things and it may be quite difficult. But not with this extension. All you need is to use `Adeira\ConfigurableExtensionsExtension` instead of default `ExtensionsExtension` like this (probably in `bootstrap.php`):

```php
$configurator->defaultExtensions['extensions'] = \Adeira\ConfigurableExtensionsExtension::class;
```

This new extension will take care of configuration files in your bundles. Next if you want to use custom config for extension, just use `provideConfig` method:

```php
<?php

namespace App\Articles\DI;

class ArticlesExtension extends \Adeira\CompilerExtension
{

  public function provideConfig()
  {
    return __DIR__ . '/config.neon';
  }

  public function beforeCompile()
  {
    $this->setMapping(['Articles' => 'App\Articles\*Module\Presenters\*Presenter']);
  }

}
```

You don't have to extend `Adeira\CompilerExtension` but there is useful helper `setMapping()` (it just setups custom presenter mapping). But `provideConfig` metod will work with `Nette\DI\CompilerExtension` descendants as well. **And why is this so interesting?** Imagine you have this config in your application (added in bootstrap via `Nette\DI\Compiler::addConfig`):

```yaml
parameters:
  key1: value1
  key2: value2

services:
  - DefaultService
  named: Tests\Service

extensions:
  ext2: CustomExtension2

ext2:
  ext_key1: ext_value1
  ext_key2: ext_value2

application:
  mapping:
    *: *
```

And now you'll add another config in your DIC extension using `provideConfig` method:

```yaml
parameters:
  key2: overridden
  key3: value3

services:
  - Tests\TestService
  named: Service2

ext2:
  ext_key2: overridden
  ext_key3: ext_value3

latte:
  macros:
    - App\Grid\Latte\Macros
```

What is the result? Now there are three global parameters:

```yaml
parameters:
  key1: value1
  key2: overridden
  key3: value3
```

As you can see your custom DIC extension has priority. Extensions parameters (`ext2`) behaves exactly the same. What about services? As you can expect there will be three services:

```yaml
- DefaultService
named: Service2
- Tests\TestService
```

And here comes the most interesting part. If you have a lot of extensions it's good idea to use custom config files (it's simple and easy to understand). But it may be hard to get extension configuration from neon file. In `Nette\DI\CompilerExtension` descendant class you could do simply `$this->getConfig()` to get configuration related to the extension, but there is no equivalent for doing this in neon. This extension adds special syntax for this case. From the previous examples there are three options related to the `ext2` extension:

```yaml
ext2:
  ext_key1: ext_value1
  ext_key2: overridden
  ext_key3: ext_value3
```

To get second parameter into service use this:

```yaml
services:
  - Tests\TestService(%%ext_key2%%)
```

Remember that this is possible only if you are using custom config added by `provideConfig` method. It will not work in configs added in bootstrap file (via `Nette\DI\Compiler::addConfig`). This is because only under extension it's possible to get key from the right extension section (`ext2.ext_key2` in this case).

### Experimental features
These features are not enabled by default now (but may be enabled by default in the future). To enable experimental features now you have to register this extension differently:

```php
 $configurator->defaultExtensions['extensions'] = [\Adeira\ConfigurableExtensionsExtension::class, [TRUE]]; // Become superhero!
```

At this moment there is so called `GroupedNeonAdapter`. It allows you to write service definitions in NEON with grouped syntax. Before:

```php
graphql:
  types:
  - Adeira\Connector\Devices\Infrastructure\Delivery\API\GraphQL\Type\WeatherStationRecordType
  - Adeira\Connector\Devices\Infrastructure\Delivery\API\GraphQL\Type\WeatherStationsConnectionType
  - Adeira\Connector\Devices\Infrastructure\Delivery\API\GraphQL\Type\WeatherStationsEdgeType
  - Adeira\Connector\Devices\Infrastructure\Delivery\API\GraphQL\Type\WeatherStationType
```

After:

```php
graphql:
  types:
    - Adeira\Connector\Devices\Infrastructure\Delivery\API\GraphQL\Type\( # namespace must end with backslash
      WeatherStationRecordType
      WeatherStationsConnectionType
      WeatherStationsEdgeType
      WeatherStationType
    )
```

This feature is optional and works only in NEON files provided via `provideConfig` method. All classes must be registered anonymously. If it's not possible just don't use this feature.
