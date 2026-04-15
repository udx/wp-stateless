<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Models\Wrappers;

use JsonMapper\Tests\Implementation\Models\IShape;

interface IShapeAware
{
    public function getShape(): IShape;
}
