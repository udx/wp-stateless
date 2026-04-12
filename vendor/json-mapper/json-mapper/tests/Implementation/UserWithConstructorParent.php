<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation;

use JsonMapper\Tests\Implementation\Models\UserWithConstructor;

class UserWithConstructorParent
{
    /** @var UserWithConstructor */
    public $user;

    /** @var UserWithConstructor[][] */
    public $userHistory;
}
