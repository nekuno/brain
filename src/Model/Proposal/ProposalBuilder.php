<?php

namespace Model\Proposal;

use Model\Availability\Availability;
use Model\Metadata\ProposalMetadataManager;
use Model\Profile\ProfileFields\AbstractField;
use Model\Profile\ProfileFields\FieldAvailability;
use Model\Profile\ProfileFields\FieldBoolean;
use Model\Profile\ProfileFields\FieldChoice;
use Model\Profile\ProfileFields\FieldString;
use Model\Profile\ProfileFields\FieldTag;
use Model\Proposal\Proposal;

class ProposalBuilder
{
    protected $metadataManager;

    /**
     * ProposalFieldBuilder constructor.
     * @param $metadataManager
     */
    public function __construct(ProposalMetadataManager $metadataManager)
    {
        $this->metadataManager = $metadataManager;
    }

    /**
     * @param $proposalName
     * @param $proposalData
     * @return Proposal
     */
    public function buildFromData($proposalName, $proposalData)
    {
        $metadata = $this->metadataManager->getMetadata();
        $metadatum = $metadata[$proposalName];

        $fields = array();
        foreach ($metadatum AS $fieldName => $fieldMetadata) {
            if (!isset($proposalData[$fieldName])) {
                continue;
            }
            $value = $proposalData[$fieldName];
            $proposalField = $this->buildField($fieldMetadata);

            $proposalField->setName($fieldName);
            $proposalField->setValue($value);

            if ($proposalField instanceof FieldAvailability && ($value instanceof Availability)) {
                $proposalField->setAvailability($value);
            }

            $fields[] = $proposalField;
        }

        $proposal = new Proposal($proposalName, $fields);
        if (isset($proposalData['proposalId'])) {
            $proposal->setId($proposalData['proposalId']);
        }

        return new Proposal($proposalName, $fields);
    }

    public function buildEmpty($proposalName)
    {
        $metadata = $this->metadataManager->getMetadata();
        $metadatum = $metadata[$proposalName];

        $fields = array();
        foreach ($metadatum AS $fieldName => $fieldMetadata) {
            $proposalField = $this->buildField($fieldMetadata);
            $proposalField->setName(($fieldName));

            $fields[] = $proposalField;
        }

        return new Proposal($proposalName, $fields);
    }

    /**
     * @param $fieldMetadata
     * @return AbstractField
     */
    protected function buildField(array $fieldMetadata)
    {
        $type = $fieldMetadata['type'];
        switch ($type) {
            case 'string':
                $proposalField = new FieldString();
                break;
            case 'tag':
            case 'tag_and_suggestion':
                $proposalField = new FieldTag();
                break;
            case 'choice':
                $proposalField = new FieldChoice();
                break;
            case 'boolean':
                $proposalField = new FieldBoolean();
                break;
            case 'availability':
                $proposalField = new FieldAvailability();
                break;
            default:
                $proposalField = new FieldString();
                break;
        }
        $proposalField->setType($type);
        $proposalField->setNodeName('proposal');

        return $proposalField;
    }
}