<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Models\Wrappers;

use JsonMapper\Tests\Implementation\Models\IShape;

class IShapeWrapper implements IShapeAware
{
    /** @var IShape */
    public $shape;

    public function getShape(): IShape
    {
        return $this->shape;
    }
}
