<?php

namespace Model\Metadata;

use Symfony\Component\Translation\TranslatorInterface;

class ProposalMetadataManager extends MetadataManager
{
    /**
     * ProposalMetadataManager constructor.
     */
    public function __construct(TranslatorInterface $translator, MetadataUtilities $metadataUtilities, array $metadata, $defaultLocale)
    {
        parent::__construct($translator, $metadataUtilities, $metadata, $defaultLocale);

        foreach ($this->metadata as $name => &$proposalData) {
            $proposalData['description'] = array('type' => 'string');
            $proposalData['participantLimit'] = array('type' => 'boolean');
            $proposalData['availability'] = array('type' => 'availability');
        }
    }

    protected function modifyPublicField($publicField, $name, $values)
    {
        unset($publicField['label']);

        return $publicField;
    }

    protected function orderByLabel($metadata)
    {
        return $metadata;
    }

}