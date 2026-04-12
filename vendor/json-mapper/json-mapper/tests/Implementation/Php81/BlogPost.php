<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Php81;

class BlogPost
{
    public Status $status;

    /** @var Status[] */
    public $historicStates;

    /** @var Status[][] */
    public $historicStatesByDate;
}
