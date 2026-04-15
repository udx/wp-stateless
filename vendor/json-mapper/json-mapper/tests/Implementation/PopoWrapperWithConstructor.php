<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation;

class PopoWrapperWithConstructor
{
    /** @var Popo */
    private $popo;

    public function __construct(Popo $popo)
    {
        $this->popo = $popo;
    }

    public function getPopo(): Popo
    {
        return $this->popo;
    }
}
