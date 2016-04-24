# Enhanced CompilerExtension for Nette Framework

[![Build Status](https://travis-ci.org/mrtnzlml/compiler-extension.svg?branch=master)](https://travis-ci.org/mrtnzlml/compiler-extension)

If you have more complicated project structure with a lot of bundles (DIC extensions), it's very common that you have to setup a lot of thinks and it may be quite difficult. But not with this `CompilerExtension`:

```php
<?php

namespace Ant\Articles\DI;

class ArticlesExtension extends \Mrtnzlml\CompilerExtension
{

	public function loadConfiguration()
	{
		$this->addConfig(__DIR__ . '/config.neon');
	}

	public function beforeCompile()
	{
		$this->setMapping(['Articles' => 'Ant\Articles\*Module\Presenters\*Presenter']);
	}

}
```

There are two main helpers - `addConfig()` and `setMapping()`. Second helper is quite straightforward. It just setup custom presenter mapping. `addConfig()` is much more interesting. Imagine you have this config in your application:

```
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

And now you'll add another config in your DIC extension using `addConfig()`:

```
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
		- Ant\Grid\Latte\Macros
```

What is the result? Now there are three global parameters:

```
parameters:
  key1: value1
  key2: overridden
  key3: value3
```

As you can see your custom DIC extension has priority. Extensions parameters (`ext2`) behaves exactly the same. What about services? As you can expect there will be three services:

```
- DefaultService
named: Service2
- Tests\TestService
```

You can also play with other extensions (`latte` in this example). This is however the most problematic part, because it's needed to remove definitions and aliases from DIC, but it's not easy to figure out which one. This library is trying to figure out what to do, but it's not silver bullet. However you can specifiy regular expression of service names to be reloaded using `\Mrtnzlml\CompilerExtension::reloadDefinition()` method.
