<?php

namespace JsonMapper\Cache;

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Psr16Cache;

class NullCache extends Psr16Cache implements CacheInterface
{
    public function __construct()
    {
        parent::__construct(new NullAdapter());
    }
}
