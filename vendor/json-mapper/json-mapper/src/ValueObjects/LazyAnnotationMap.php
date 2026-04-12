<?php

declare(strict_types=1);

namespace JsonMapper\ValueObjects;

use JsonMapper\Builders\PropertyBuilder;
use JsonMapper\Parser\Import;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\PseudoType;
use phpDocumentor\Reflection\PseudoTypes;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types;
use phpDocumentor\Reflection\Types\Context;

class LazyAnnotationMap
{
    private string $input;
    /** @var Import[] */
    private array $imports;
    private ?DocBlock $docBlock = null;
    private string $namespace;

    /**
     * @param Import[] $imports
     */
    public function __construct(
        string $input,
        string $namespace = '',
        array $imports = []
    ) {
        $this->input = $input;
        $this->namespace = $namespace;
        $this->imports = $imports;
    }

    public function hasVar(): bool
    {
        $this->initialize();

        return !is_null($this->getFirstTagByNameAndVariableName('var'));
    }

    public function hasParam(string $variableName): bool
    {
        $this->initialize();

        return !is_null($this->getFirstTagByNameAndVariableName('param', $variableName));
    }

    public function tagToPropertyBuilder(string $tagName, ?string $variableName = null): PropertyBuilder
    {
        $this->initialize();
        $tag = $this->getFirstTagByNameAndVariableName($tagName, $variableName);

        if (\is_null($tag)) {
            throw new \RuntimeException('Missing tag with name ' . $tagName . ' for variable ' . $variableName);
        }

        $type = $tag->getType();
        if (is_null($type)) {
            throw new \RuntimeException('Tag has no type');
        }

        $builder = PropertyBuilder::new()
            ->setIsNullable($type instanceof Types\Nullable);

        // Unpack nullable type (non-compound type prefixed with question mark)
        if ($type instanceof Types\Nullable) {
            $type = $type->getActualType();
        }
        // Unpack pseudo type
        if ($type instanceof PseudoType && ! $type instanceof PseudoTypes\List_) {
            $type = $type->underlyingType();
        }
        if ($type instanceof Types\Compound) {
            $types = $type->getIterator()->getArrayCopy();
        } else {
            $types = [$type];
        }

        foreach ($types as $type) {
            switch (get_class($type)) {
                case Types\Null_::class:
                    $builder->setIsNullable(true);
                    break;
                case Types\String_::class:
                case Types\Boolean::class:
                case Types\Float_::class:
                case Types\Integer::class:
                case Types\Object_::class:
                case Types\Mixed_::class:
                case Types\Array_::class:
                case PseudoTypes\List_::class:
                case PseudoTypes\ArrayShape::class:
                    $builder->addType($this->mapDocBlockTypeClassToPropertyType($type), $this->mapDoCBlockTypeToArrayDimension($type));
                    break;
                default:
                    throw new \RuntimeException('Unexpected type ' . get_class($type));
            }
        }

        return $builder;
    }

    private function initialize(): void
    {
        if (!\is_null($this->docBlock)) {
            return;
        }

        $factory = DocBlockFactory::createInstance();

        $namespaceAliases = [];
        foreach ($this->imports as $import) {
            if ($import->hasAlias()) {
                $namespaceAliases[$import->getAlias()] = $import->getImport();
                continue;
            }

            $farMostRightNamespaceSeparator = strrpos($import->getImport(), '\\');
            if ($farMostRightNamespaceSeparator === false) {
                $namespaceAliases[$import->getImport()] = $import->getImport();
                continue;
            }

            $key = substr($import->getImport(), $farMostRightNamespaceSeparator + 1);
            $namespaceAliases[$key] = $import->getImport();
        }
        $this->docBlock = $factory->create(
            $this->input,
            new Context(
                $this->namespace,
                $namespaceAliases,
            )
        );
    }

    private function getFirstTagByNameAndVariableName(string $name, ?string $variableName = null): ?DocBlock\Tags\TagWithType
    {
        $tags = $this->docBlock->getTagsWithTypeByName($name);

        if (\is_null($variableName)) {
            return array_shift($tags);
        }

        $matches = array_filter($tags, static function (DocBlock\Tags\TagWithType $tag) use ($variableName): bool {
            if (!$tag instanceof DocBlock\Tags\Param && !$tag instanceof DocBlock\Tags\Var_) {
                return false;
            }
            return $tag->getVariableName() === $variableName;
        });

        return array_shift($matches);
    }

    private function mapDocBlockTypeClassToPropertyType(Type $type): string
    {
        switch (get_class($type)) {
            case Types\String_::class:
            case Types\ClassString::class:
                return 'string';
            case Types\Boolean::class:
                return 'bool';
            case Types\Float_::class:
                return 'float';
            case Types\Integer::class:
                return 'int';
            case Types\Mixed_::class:
                return 'mixed';
            case Types\Object_::class:
                return ltrim($type->__toString(), '\\');
            case Types\Array_::class:
            case PseudoTypes\List_::class:
                return $this->mapDocBlockTypeClassToPropertyType($type->getValueType());
            case PseudoTypes\ArrayShape::class:
                return $this->mapDocBlockTypeClassToPropertyType($type->underlyingType());
            default:
                throw new \RuntimeException('Unexpected type ' . get_class($type));
        }
    }

    private function mapDoCBlockTypeToArrayDimension(Type $type): ArrayInformation
    {
        $dimensions = 0;
        while ($type instanceof Types\Array_) {
            $dimensions++;
            $type = $type->getValueType();
        }

        if ($dimensions === 0) {
            return ArrayInformation::notAnArray();
        }

        return ArrayInformation::multiDimension($dimensions);
    }
}
