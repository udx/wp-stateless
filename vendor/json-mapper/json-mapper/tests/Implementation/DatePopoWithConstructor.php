<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation;

use DateTimeImmutable;

class DatePopoWithConstructor
{
    private $date;

    public function __construct(?DateTimeImmutable $date)
    {
        $this->date = $date;
    }

    public function getDate(): ?DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(?DateTimeImmutable $date): void
    {
        $this->date = $date;
    }
}
