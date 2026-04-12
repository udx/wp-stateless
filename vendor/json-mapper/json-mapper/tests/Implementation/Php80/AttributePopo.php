<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Php80;

use JsonMapper\Middleware\Attributes\MapFrom;

class AttributePopo
{
    #[MapFrom("Identifier")]
    public int $id;
    #[MapFrom("UserName")]
    public string $name;
    #[MapFrom("email")]
    public string $email;
}
