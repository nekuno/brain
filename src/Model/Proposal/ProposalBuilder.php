<?php

namespace Model\Proposal;

use Model\Availability\Availability;
use Model\Metadata\ProposalMetadataManager;
use Model\Profile\ProfileFields\AbstractField;
use Model\Profile\ProfileFields\FieldAvailability;
use Model\Profile\ProfileFields\FieldBoolean;
use Model\Profile\ProfileFields\FieldChoice;
use Model\Profile\ProfileFields\FieldInteger;
use Model\Profile\ProfileFields\FieldString;
use Model\Profile\ProfileFields\FieldTag;

class ProposalBuilder
{
    protected $metadataManager;

    /**
     * @var string
     */
    protected $base;

    /**
     * @var string
     */
    protected $host;

    /**
     * ProposalFieldBuilder constructor.
     * @param $metadataManager
     */
    public function __construct(ProposalMetadataManager $metadataManager, $base, $host)
    {
        $this->metadataManager = $metadataManager;
        $this->base = $base;
        $this->host = $host;
    }

    /**
     * @param $proposalType
     * @param $proposalData
     * @return Proposal
     */
    public function buildFromData($proposalType, $proposalData)
    {
        $metadata = $this->metadataManager->getMetadata();
        $metadatum = $metadata[$proposalType];
        $dataFields = $proposalData['fields'];
        $fields = array();
        foreach ($metadatum AS $fieldName => $fieldMetadata) {
            if (!array_key_exists($fieldName, $dataFields)) {
                continue;
            }
            $value = $dataFields[$fieldName];
            $proposalField = $this->buildField($fieldMetadata);
            $proposalField->setName($fieldName);
            $proposalField->setValue($value);

            if ($proposalField instanceof FieldAvailability && ($value instanceof Availability)) {
                $proposalField->setAvailability($value);
            }

            $fields[] = $proposalField;
        }

        $proposal = new Proposal($proposalType, $fields, $this->base, $this->host);
        if (isset($proposalData['proposalId'])) {
            $proposal->setId($proposalData['proposalId']);
        }

        return $proposal;
    }

    public function buildEmpty($proposalType)
    {
        $metadata = $this->metadataManager->getMetadata();
        $metadatum = $metadata[$proposalType];

        $fields = array();
        foreach ($metadatum AS $fieldName => $fieldMetadata) {
            $proposalField = $this->buildField($fieldMetadata);
            $proposalField->setName(($fieldName));
            $proposalField->setNodeName('proposal');

            $fields[] = $proposalField;
        }

        return new Proposal($proposalType, $fields, $this->base, $this->host);
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
            case 'integer':
                $proposalField = new FieldInteger();
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