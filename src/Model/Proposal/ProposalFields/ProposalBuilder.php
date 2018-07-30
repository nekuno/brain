<?php

namespace Model\Proposal\ProposalFields;

use Model\Metadata\ProposalMetadataManager;
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
    //TODO: Add locale
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

            if ($proposalField instanceof ProposalFieldAvailability && $value) {
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
     * @return AbstractProposalField
     */
    protected function buildField(array $fieldMetadata)
    {
        $type = $fieldMetadata['type'];
        switch ($type) {
            case 'string':
                $proposalField = new ProposalFieldString();
                break;
            case 'tag':
            case 'tag_and_suggestion':
                $proposalField = new ProposalFieldTag();
                break;
            case 'choice':
                $proposalField = new ProposalFieldChoice();
                break;
            case 'boolean':
                $proposalField = new ProposalFieldBoolean();
                break;
            case 'availability':
                $proposalField = new ProposalFieldAvailability();
                break;
            default:
                $proposalField = new ProposalFieldString();
                break;
        }
        $proposalField->setType($type);

        return $proposalField;
    }
}