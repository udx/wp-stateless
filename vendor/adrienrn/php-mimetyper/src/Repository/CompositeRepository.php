<?php

namespace MimeTyper\Repository;

use Dflydev\ApacheMimeTypes\CompositeRepository as BaseCompositeRepository;

class CompositeRepository extends BaseCompositeRepository implements RepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function dumpExtensionToType()
    {
        $extensionToType = array();
        foreach ($this->repositories as $repository) {
            $repositoryExtensionToType = $repository->dumpExtensionToType();
            foreach ($repositoryExtensionToType as $extension => $type) {
                if (!isset($extensionToType[$extension])) {
                    $extensionToType[$extension] = $type;
                } else {
                    $extensionToType[$extension] = array_unique(array_merge($extensionToType[$extension], $type));
                }
            }
        }
        return $extensionToType;
    }

    /**
     * {@inheritdoc}
     */
    public function findExtension($type)
    {
        // Get all matching extensions.
        $extensions = $this->findExtensions($type);

        if (count($extensions) > 0) {
            // Return first match.
            return $extensions[0];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasExtension($extension)
    {
        $extensionToTypes = $this->dumpExtensionToType();

        return (isset($extensionToTypes[$extension]));
    }

    /**
     * {@inheritdoc}
     */
    public function findTypes($extension)
    {
        $extensionToTypes = $this->dumpExtensionToType();

        if (isset($extensionToTypes[$extension])) {
            return $extensionToTypes[$extension];
        }

        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function hasType($type)
    {
        $typeToExtensions = $this->dumpTypeToExtensions();

        return (isset($typeToExtensions[$type]) && count($typeToExtensions[$type]) > 0);
    }
}