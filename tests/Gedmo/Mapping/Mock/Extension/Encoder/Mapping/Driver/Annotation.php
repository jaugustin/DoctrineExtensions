<?php

// file: vendor/Extension/Encoder/Mapping/Driver/Annotation.php

namespace Gedmo\Mapping\Mock\Extension\Encoder\Mapping\Driver;

use Gedmo\Mapping\Driver;
use Doctrine\Common\Annotations\AnnotationReader;

class Annotation implements Driver
{
    public function validateFullMetadata($meta, array $config)
    {
        // in our case values are independant from each other
    }

    public function readExtendedMetadata($meta, array &$config) {
        // load our available annotations
        require_once __DIR__ . '/../Annotations.php';
        $reader = new AnnotationReader();
        // set annotation namespace and alias
        $reader->setAnnotationNamespaceAlias('Gedmo\Mapping\Mock\Extension\Encoder\Mapping\\', 'ext');

        $class = $meta->getReflectionClass();
        // check only property annotations
        foreach ($class->getProperties() as $property) {
            // skip inherited properties
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            // now lets check if property has our annotation
            if ($encode = $reader->getPropertyAnnotation($property, 'Gedmo\Mapping\Mock\Extension\Encoder\Mapping\Encode')) {
                $field = $property->getName();
                // check if field is mapped
                if (!$meta->hasField($field)) {
                    throw new \Exception("Field is not mapped as object property");
                }
                // allow encoding only strings
                if (!in_array($encode->type, array('sha1', 'md5'))) {
                    throw new \Exception("Invalid encoding type supplied");
                }
                // validate encoding type
                $mapping = $meta->getFieldMapping($field);
                if ($mapping['type'] != 'string') {
                    throw new \Exception("Only strings can be encoded");
                }
                // store the metadata
                $config['encode'][$field] = array(
                    'type' => $encode->type,
                    'secret' => $encode->secret
                );
            }
        }
    }
}
