<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Php74\BuiltinExt;

class DateTime
{
    public string $date;
    public string $time;

    public function __construct(string $date = '', string $time = '')
    {
        $this->date = $date;
        $this->time = $time;
    }
}
