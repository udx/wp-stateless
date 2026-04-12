<?php

declare(strict_types=1);

namespace JsonMapper\Helpers;

use JsonMapper\Enums\ScalarType;

interface IScalarCaster
{
    /** @return string|bool|int|float */
    public function cast(ScalarType $scalarType, $value);
}
