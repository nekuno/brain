<?php

namespace Model\Proposal\ProposalFields;

use App\Model\Proposal\ProposalFields\ProposalFieldBoolean;
use Model\Metadata\MetadataManagerInterface;
use Model\Proposal\Proposal;

class ProposalBuilder
{
    protected $metadataManager;

    /**
     * ProposalFieldBuilder constructor.
     * @param $metadataManager
     */
    public function __construct(MetadataManagerInterface $metadataManager)
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
        foreach ($metadatum AS $fieldName => $fieldMetadata){
            $type = $fieldMetadata['type'];
            $value = isset($proposalData[$fieldName])? $proposalData[$fieldName] : null;
            switch($type){
                case 'string':
                    $proposalField = new ProposalFieldString();
                    $proposalField->setName($fieldName);
                    $proposalField->setValue($value);
                    break;
                case 'tag':
                case 'tag_and_suggestion':
                    $proposalField = new ProposalFieldTag();
                    $proposalField->setName($fieldName);
                    $proposalField->setValue($value);
                    break;
                case 'choice':
                    $proposalField = new ProposalFieldChoice();
                    $proposalField->setName($fieldName);
                    $proposalField->setValue($value);
                    break;
                case 'boolean':
                    $proposalField = new ProposalFieldBoolean();
                    $proposalField->setName($fieldName);
                    $proposalField->setValue($value);
                    break;
                case 'availability':
                    $proposalField = new ProposalFieldAvailability();
                    break;
                default:
                    $proposalField = new ProposalFieldString();
                    break;
            }

            $fields[] = $proposalField;
        }

        $proposal = new Proposal($proposalName, $fields);
        if (isset($proposalData['proposalId'])){
            $proposal->setId($proposalData['proposalId']);
        }

        return new Proposal($proposalName, $fields);
    }

}