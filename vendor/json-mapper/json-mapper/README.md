<picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://jsonmapper.net/images/jsonmapper-light.png">
  <img alt="JsonMapper logo" src="https://jsonmapper.net/images/jsonmapper.png">
</picture>

---
JsonMapper is a PHP library that allows you to map a JSON response to your PHP objects that are either annotated using doc blocks or use typed properties.
For more information see the project website: https://jsonmapper.net/

[![GitHub](https://img.shields.io/github/license/JsonMapper/JsonMapper)](https://choosealicense.com/licenses/mit/)
[![Packagist Version](https://img.shields.io/packagist/v/json-mapper/json-mapper)](https://packagist.org/packages/json-mapper/json-mapper)
![Packagist Downloads](https://img.shields.io/packagist/dm/json-mapper/json-mapper)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/json-mapper/json-mapper)](#)
![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/JsonMapper/JsonMapper/build.yaml)
[![Coverage Status](https://coveralls.io/repos/github/JsonMapper/JsonMapper/badge.svg?branch=develop)](https://coveralls.io/github/JsonMapper/JsonMapper?branch=develop)

# Why use JsonMapper
Continuously mapping your JSON responses to your own objects becomes tedious and is error-prone. Not mentioning the
tests that needs to be written for said mapping.

JsonMapper has been build with the most common usages in mind. In order to allow for those edge cases which are not 
supported by default, it can easily be extended as its core has been designed using middleware.

JsonMapper supports the following features
 * Case conversion
 * Debugging
 * DocBlock annotations
 * Final callback
 * Namespace resolving
 * PHP 7.4 Types properties
  
# Installing JsonMapper
The installation of JsonMapper can easily be done with [Composer](https://getcomposer.org)
```bash
$ composer require json-mapper/json-mapper
```
The example shown above assumes that `composer` is on your `$PATH`.

# How do I use JsonMapper
Given the following class definition
```php
namespace JsonMapper\Tests\Implementation;

class SimpleObject
{
    /** @var string */
    private $name;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
```
Combined with the following JsonMapper code as part of your application
```php
$mapper = (new \JsonMapper\JsonMapperFactory())->default();
$object = new \JsonMapper\Tests\Implementation\SimpleObject();

$mapper->mapObject(json_decode('{ "name": "John Doe" }'), $object);

var_dump($object);
```
The above example will output:
```text
class JsonMapper\Tests\Implementation\SimpleObject#1 (1) {
  private $name =>
  string(8) "John Doe"
}
```  

# Customizing JsonMapper
Writing your own middleware has been made as easy as possible with an `AbstractMiddleware` that can be extended with the functionality 
you need for your project.

```php
use JsonMapper;

$mapper = (new JsonMapper\JsonMapperFactory())->bestFit();
$mapper->push(new class extends JsonMapper\Middleware\AbstractMiddleware {
    public function handle(
        \stdClass $json,
        JsonMapper\Wrapper\ObjectWrapper $object,
        JsonMapper\ValueObjects\PropertyMap $map,
        JsonMapper\JsonMapperInterface $mapper
    ): void {
        /* Custom logic here */
    }
});
```

# Contributing
Please refer to [CONTRIBUTING.md](https://github.com/JsonMapper/JsonMapper/blob/main/CONTRIBUTING.md) for information on how to contribute to JsonMapper.

## List of Contributors
Thanks to everyone who has contributed to JsonMapper! You can find a detailed list of contributors of JsonMapper on [GitHub](https://github.com/JsonMapper/JsonMapper/graphs/contributors).

## Sponsoring
[![JetBrains](https://jsonmapper.net/images/jetbrains-variant-3.png?)](https://www.jetbrains.com/?from=JsonMapper)

This project is sponsored by JetBrains providing an open source license to continue building on JsonMapper without cost.     

# License
The MIT License (MIT). Please see [License File](https://github.com/JsonMapper/JsonMapper/blob/main/LICENSE) for more information.
