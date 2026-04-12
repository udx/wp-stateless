<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation;

use JsonMapper\Tests\Implementation\Models\User;

class ComplexObject
{
    /** @var SimpleObject|null */
    private $child;
    /** @var SimpleObject[] */
    private $children;
    /** @var User */
    private $user;
    /** @var mixed  */
    public $mixedParam;

    public function getChild(): ?SimpleObject
    {
        return $this->child;
    }

    public function setChild(?SimpleObject $child): void
    {
        $this->child = $child;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
