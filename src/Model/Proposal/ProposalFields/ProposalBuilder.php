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
     * @param array $data
     * @return Proposal[]
     */
    //TODO: Add locale
    public function buildMany(array $data)
    {
        $proposals = array();
        foreach ($data AS $proposalName => $proposalData)
        {
            $proposals[] = $this->buildOne($proposalName, $proposalData);
        }

        return $proposals;
    }

    /**
     * @param $proposalName
     * @param $proposalData
     * @return Proposal
     */
    //TODO: Add locale
    public function buildOne($proposalName, $proposalData)
    {
        $metadata = $this->metadataManager->getMetadata();
        $metadatum = $metadata[$proposalName];

        $fields = array();
        foreach ($metadatum AS $fieldName => $fieldMetadata){
            $type = $fieldMetadata['type'];
            $value = $proposalData[$fieldName];
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
                default:
                    $proposalField = new ProposalFieldString();
                    break;
            }

            $fields[] = $proposalField;
        }

        return new Proposal($proposalName, $fields);
    }

}