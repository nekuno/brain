<?php

namespace Model\Profile;

use Model\Metadata\MetadataUtilities;
use Model\Metadata\ProfileMetadataManager;
use Model\Neo4j\GraphManager;

class ProfileOptionManager extends AbstractProfileOptionManager
{
    public function __construct(GraphManager $graphManager, MetadataUtilities $metadataUtilities, ProfileMetadataManager $metadata)
    {
        parent::__construct($graphManager, $metadataUtilities, $metadata);
    }

    public function getUserProfileOptions($id)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(option:ProfileOption)-[optionOf:OPTION_OF]->(profile:Profile)-[:PROFILE_OF]->(user:User)')
            ->where('user.qnoow_id = { id }')
            ->setParameter('id', $id)
            ->returns('profile, collect(distinct {option: option, detail: (CASE WHEN EXISTS(optionOf.detail) THEN optionOf.detail ELSE null END)}) AS options');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $options = array();
        foreach ($result as $row) {
            $options += $this->buildOptions($row->offsetGet('options'));
        }

        return $options;
    }
}