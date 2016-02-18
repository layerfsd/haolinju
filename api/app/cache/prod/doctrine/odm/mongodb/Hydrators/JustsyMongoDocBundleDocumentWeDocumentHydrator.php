<?php

namespace Hydrators;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Hydrator\HydratorInterface;
use Doctrine\ODM\MongoDB\UnitOfWork;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ODM. DO NOT EDIT THIS FILE.
 */
class JustsyMongoDocBundleDocumentWeDocumentHydrator implements HydratorInterface
{
    private $dm;
    private $unitOfWork;
    private $class;

    public function __construct(DocumentManager $dm, UnitOfWork $uow, ClassMetadata $class)
    {
        $this->dm = $dm;
        $this->unitOfWork = $uow;
        $this->class = $class;
    }

    public function hydrate($document, $data, array $hints = array())
    {
        $hydratedData = array();

        /** @Field(type="id") */
        if (isset($data['_id'])) {
            $value = $data['_id'];
            $return = (string) $value;
            $this->class->reflFields['id']->setValue($document, $return);
            $hydratedData['id'] = $return;
        }

        /** @Field(type="string") */
        if (isset($data['name'])) {
            $value = $data['name'];
            $return = (string) $value;
            $this->class->reflFields['name']->setValue($document, $return);
            $hydratedData['name'] = $return;
        }

        /** @Field(type="int") */
        if (isset($data['length'])) {
            $value = $data['length'];
            $return = (int) $value;
            $this->class->reflFields['length']->setValue($document, $return);
            $hydratedData['length'] = $return;
        }

        /** @Field(type="date") */
        if (isset($data['uploadDate'])) {
            $value = $data['uploadDate'];
            if ($value instanceof \MongoDate) { $date = new \DateTime(); $date->setTimestamp($value->sec); $return = $date; } else { $return = new \DateTime($value); }
            $this->class->reflFields['uploadDate']->setValue($document, clone $return);
            $hydratedData['uploadDate'] = $return;
        }

        /** @Field(type="int") */
        if (isset($data['chunkSize'])) {
            $value = $data['chunkSize'];
            $return = (int) $value;
            $this->class->reflFields['chunkSize']->setValue($document, $return);
            $hydratedData['chunkSize'] = $return;
        }

        /** @Field(type="string") */
        if (isset($data['md5'])) {
            $value = $data['md5'];
            $return = (string) $value;
            $this->class->reflFields['md5']->setValue($document, $return);
            $hydratedData['md5'] = $return;
        }

        /** @Field(type="file") */
        if (isset($data['file'])) {
            $value = $data['file'];
            $return = $value;
            $this->class->reflFields['file']->setValue($document, $return);
            $hydratedData['file'] = $return;
        }
        return $hydratedData;
    }
}