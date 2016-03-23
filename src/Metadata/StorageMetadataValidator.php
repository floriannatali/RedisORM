<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 21/03/16
 * Time: 10:31
 */

namespace Sportnco\RedisORM\Metadata;


use Sportnco\RedisORM\Exception\InvalidMetadataAnnotationException;

class StorageMetadataValidator implements ValidatorInterface
{
    CONST REGEX_TO_VALIDATE_ENTITY_NAME='/^[a-zA-Z0-9-_]+$/';

    public function validate(EntityMetadata $entityMetadata)
    {
        if(0 === preg_match(self::REGEX_TO_VALIDATE_ENTITY_NAME ,$entityMetadata->getEntityName())) {
            throw new InvalidMetadataAnnotationException($entityMetadata->getEntityClass() . ': Entity(name) annotation does not respect pattern: ' . self::REGEX_TO_VALIDATE_ENTITY_NAME);
        }

        if(count($entityMetadata->getIdsProperties()) === 0) {
            throw new InvalidMetadataAnnotationException($entityMetadata->getEntityClass() . ': with "storage" mode, you must define at least one property as "ID" with the annotation');
        }

        if(count($entityMetadata->getIdsProperties()) > 1 && $entityMetadata->isAutoIncrement()) {
            throw new InvalidMetadataAnnotationException($entityMetadata->getEntityClass() . ': Entity(autoIncrement) activation is not compatible with multi ID annotation');
        }

        return $entityMetadata;
    }
}