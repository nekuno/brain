<?php

namespace Model\Profile;

use Http\Discovery\Exception\NotFoundException;
use Model\Metadata\CategoryMetadataManager;

class NaturalProfileBuilder
{
    /**
     * @var CategoryMetadataManager
     */
    protected $categoryMetadataManager;

    protected $metadata;

    protected $result = array();

    /**
     * NaturalProfileBuilder constructor.
     * @param CategoryMetadataManager $categoryMetadataManager
     */
    public function __construct(CategoryMetadataManager $categoryMetadataManager)
    {
        $this->categoryMetadataManager = $categoryMetadataManager;
        $this->initializeResult();
    }

    protected function initializeResult()
    {
        $categoryMetadata = $this->categoryMetadataManager->getMetadata()['otherProfile'];
        foreach ( $categoryMetadata as $categoryMetadatum)
        {
            $this->result[$categoryMetadatum['label']] = array();
        }
    }

    /**
     * @param mixed $metadata
     */
    public function setMetadata($metadata): void
    {
        $this->metadata = $metadata;
    }

    public function buildNaturalProfile(Profile $profile)
    {
        if (empty($this->metadata)){
            throw new NotFoundException('Metadata not found for natural profile building');
        }

        foreach ($profile->getValues() as $fieldName => $profileValue) {
            if (!$this->findCategory($fieldName)) {
                continue;
            }

            $metadatum = $this->metadata[$fieldName];

            $naturalText = $this->getNaturalText($profileValue, $metadatum);
//            var_dump('----');
//var_dump($fieldName);
//var_dump($naturalText);
            $this->addToResult($fieldName, $naturalText);
        }

        return $this->buildResult();
    }

    protected function getNaturalText($value, $metadatum)
    {
        $natural = $metadatum['natural'];

        $naturalValue2 = '';

        switch ($metadatum['type']) {

            case 'textArea':
                $naturalValue1 = $value;
                break;
            case 'choice':
                $naturalValue1 = $this->getChoiceText($value, $metadatum);
                break;
            case 'multiple_choices':
                $choices = $this->getMultipleChoicesTexts($value, $metadatum);
                $naturalValue1 = implode(', ', $choices);
                break;
            case 'double_choice':
                $naturalValue1 = $this->getDoubleChoicesTexts($value, $metadatum);
                break;
            case 'tags':
                $labelValue = array_map(
                    function ($tag) {
                        return $tag['name'];
                    },
                    $value
                );
                $naturalValue1 = implode(', ', $labelValue);
                break;
            case 'tags_and_choice':
                $naturalValue1 = $this->getTagsAndChoiceNaturalValue($value, $natural, $metadatum);
                $natural['interfix'] = '';
                break;
            default:
                $naturalValue1 = $value;
                break;
        }

        $naturalText = $this->buildNaturalText($natural, $naturalValue1, $naturalValue2);

        return $naturalText;
    }

    //Japonés: básico, inglés: nativo
    protected function getTagsAndChoiceNaturalValue($profileValue, $natural, $metadatum)
    {
        $languages = array();

        foreach ($profileValue as $tagAndChoice) {
            $tag = $tagAndChoice['tag']['name'];
            $choice = $this->getTextFromKeys($tagAndChoice['choice'], $metadatum['choices']);

            $languages[] = $tag . ' ' . $natural['interfix'] . ' ' . $choice;
        }

        return implode(', ', $languages);
    }

    protected function buildNaturalText($natural, $value1, $value2)
    {
        $string = '';
        if (!empty($natural['prefix'])) {
            $string .= $natural['prefix'] . ' ';
        }
        $string .= $value1;
        if (!empty($natural['interfix'])) {
            $string .= $natural['interfix'] . ' ';
        }
        $string .= $value2;
        $string .= $natural['suffix'];

        return $string;
    }

    protected function findCategory($name)
    {
        $categories = $this->categoryMetadataManager->getMetadata();
        $otherProfileCategories = $categories['otherProfile'];

        foreach ($otherProfileCategories as $otherProfileCategory) {
            if (in_array($name, $otherProfileCategory['fields'])) {
                return $otherProfileCategory['label'];
            }
        }

        return false;
    }

    protected function addToResult($fieldName, $naturalText)
    {
        $category = $this->findCategory($fieldName);

        $this->result[$category][] = $naturalText;
    }

    protected function buildResult()
    {
        $finalResult = array();
        foreach ($this->result as $category => $categoryValues) {
            if (empty($categoryValues)){
                continue;
            }
            $finalResult[$category] = implode('; ', $categoryValues) . '.';
        }

        return $finalResult;
    }

    protected function getChoiceText($choiceId, $metadatum)
    {
        $choices = $metadatum['choices'];
        foreach ($choices as $choice)
        {
            if ($choice['id'] == $choiceId){
                return $choice['text'];
            }
        }

        return $choiceId;
    }

    protected function getMultipleChoicesTexts($choicesIds, $metadatum)
    {
        $choicesTexts = array();
        foreach ($choicesIds as $choicesId)
        {
            $choicesTexts[] = $this->getChoiceText($choicesId, $metadatum);
        }

        return $choicesTexts;
    }

    protected function getDoubleChoicesTexts($values, $metadatum)
    {
        $choice = $this->getChoiceText($values['choice'], $metadatum);
        $detail = $this->getDoubleChoiceText($values, $metadatum);

        return $choice . ' ' . $detail;
    }

    protected function getDoubleChoiceText($values, $metadatum)
    {
        $mainChoiceId = $values['choice'];
        $choices = $metadatum['doubleChoices'][$mainChoiceId];
        $secondChoiceId = $values['detail'];

        return $this->getTextFromKeys($secondChoiceId, $choices);
    }

    protected function getTextFromKeys($targetId, $choices)
    {
        foreach ($choices as $id => $choice)
        {
            if ($id == $targetId){
                return $choice;
            }
        }

        return $targetId;
    }

}