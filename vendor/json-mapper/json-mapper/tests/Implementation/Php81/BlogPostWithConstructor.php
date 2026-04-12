<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Php81;

class BlogPostWithConstructor
{
    private Status $status;

    public function __construct(Status $status)
    {
        $this->status = $status;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }
}
