<?php

namespace MimeTyper\Repository;

/**
 * Two-ways mapping (type => extensions and extension => types), this class is a
 * PHP wrapper around the awesome jshttp/mime-db db.json mapping.
 *
 * Why jshttp/mime-db? It's the most complete mime type database out there,
 * compiled from IANA, Apache and custom type from community. It also defines a
 * nice format for mime type to extension mapping, including source (IANA,
 * Apache, custom), compressible status, notes, etc.
 *
 * @since 0.1.0
 * @see http://github.com/jshttp/mime-db
 */
class MimeDbRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    public function __construct($filename = null)
    {
        if (null === $filename) {
            $filename = dirname(dirname(__DIR__)) . '/node_modules/mime-db/db.json';
        }
        $this->filename = $filename;
    }

    /**
     * {@inheritdoc}
     */
    protected function internalInit()
    {
        // Parse data from mime db.
        $mimeDb = json_decode(file_get_contents($this->filename), true);

        // Map from mime-db to simple mappping "mimetype" => array(ext1, ext2, ext3)
        $mimeDbExtensions = array_map(
            function ($type) {
                // Format for 'jshttp/mime-db' is as follow:
                //    "application/xml": {
                //        "source": "iana",
                //        "compressible": true,
                //        "extensions": ["xml","xsl","xsd","rng"]
                //    },
                return (isset($type["extensions"])) ? $type["extensions"] : array();
            },
            array_values($mimeDb)
        );

        $this->setFromMap(array_combine(array_keys($mimeDb), $mimeDbExtensions));
    }
}