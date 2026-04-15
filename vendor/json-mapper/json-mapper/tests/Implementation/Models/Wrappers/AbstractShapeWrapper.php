<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Models\Wrappers;

use JsonMapper\Tests\Implementation\Models\AbstractShape;
use JsonMapper\Tests\Implementation\Models\IShape;

class AbstractShapeWrapper implements IShapeAware
{
    /** @var AbstractShape */
    public $shape;

    public function getShape(): IShape
    {
        return $this->shape;
    }
}
