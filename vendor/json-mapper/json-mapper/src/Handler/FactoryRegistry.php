<?php

declare(strict_types=1);

namespace JsonMapper\Handler;

use JsonMapper\Exception\ClassFactoryException;

class FactoryRegistry
{
    /** @var callable[] */
    private $factories = [];

    public static function withNativePhpClassesAdded(): self
    {
        $factory = new self();
        $factory->addFactory(\DateTime::class, static function (string $value) {
            return new \DateTime($value);
        });
        $factory->addFactory(\DateTimeImmutable::class, static function (string $value) {
            return new \DateTimeImmutable($value);
        });
        $factory->addFactory(\stdClass::class, static function ($value) {
            return (object) $value;
        });

        return $factory;
    }

    public function addFactory(string $className, callable $factory): self
    {
        if ($this->hasFactory($className)) {
            throw ClassFactoryException::forDuplicateClassname($className);
        }

        $this->factories[$this->sanitiseClassName($className)] = $factory;

        return $this;
    }

    public function hasFactory(string $className): bool
    {
        return \array_key_exists($this->sanitiseClassName($className), $this->factories);
    }

    /**
     * @param mixed $params
     * @return mixed
     */
    public function create(string $className, $params)
    {
        if (!$this->hasFactory($className)) {
            throw ClassFactoryException::forMissingClassname($className);
        }

        $factory = $this->factories[$this->sanitiseClassName($className)];

        return $factory($params);
    }

    private function sanitiseClassName(string $className): string
    {
        /* Erase leading slash as ::class doesn't contain leading slash */
        if (\strpos($className, '\\') === 0) {
            $className = \substr($className, 1);
        }

        return $className;
    }
}
