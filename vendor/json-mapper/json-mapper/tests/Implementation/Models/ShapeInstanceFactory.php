<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Models;

class ShapeInstanceFactory
{
    public function __invoke(\stdClass $data): IShape
    {
        switch ($data->type) {
            case 'square':
                return new Square();
            case 'circle':
                return new Circle();
            default:
                throw new \RuntimeException("Unable to create shape for type {$data->type}");
        }
    }
}
