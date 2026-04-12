<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Php81;

enum Status: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
}
