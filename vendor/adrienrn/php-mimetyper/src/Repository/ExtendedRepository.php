<?php

namespace MimeTyper\Repository;

/**
 * This shim repository aggregates all repositories to get the most complete
 * mapping.
 *
 * @since 0.1.0
 */
class ExtendedRepository extends CompositeRepository
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $repositories = array())
    {
        parent::__construct(
            array_merge(
                array(
                    new MimeDbRepository(dirname(dirname(__DIR__)) . "/resources/custom-types.json"),
                    new MimeDbRepository(),
                ),
                $repositories
            )
        );
    }
}