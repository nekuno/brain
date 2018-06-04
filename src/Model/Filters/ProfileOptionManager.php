<?php

namespace Model\Filters;

use Model\Metadata\MetadataUtilities;
use Model\Metadata\UserFilterMetadataManager;
use Model\Neo4j\GraphManager;
use Model\Profile\AbstractProfileOptionManager;

class ProfileOptionManager extends AbstractProfileOptionManager
{
    public function __construct(GraphManager $graphManager, MetadataUtilities $metadataUtilities, UserFilterMetadataManager $metadataManager)
    {
        parent::__construct($graphManager, $metadataUtilities, $metadataManager);
    }
}