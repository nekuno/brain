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

        $values = $profile->getValues();
        $categories = $this->categoryMetadataManager->getMetadata()['otherProfile'];
        $finalResult = array();
        $pronoun = isset($values['gender']) ? $values['gender'] : 'nb';

        foreach ($categories as $category) {
            $categoryValues = array();

            foreach ($category['fields'] as $name) {
                if (!isset($values[$name])) {
                    continue;
                }
                $metadatum = $this->metadata[$name];
                $naturalText = $this->getNaturalText($values[$name], $metadatum, $pronoun);
                if (isset($naturalText)) {
                    $categoryValues[] = $naturalText;
                }
            }

            if (!empty($categoryValues)) {
                $finalResult[$category['label']] = implode('; ', $categoryValues);
            }
        }

        return $finalResult;
    }

    protected function joinValues($values, $natural)
    {
        if (empty($values)) {
            return null;
        }
        if (count($values) == 1) {
            return $values[0];
        }
        $last = $values[count($values)-1];
        $head = array_slice($values, 0, count($values)-1);
        $joiner = $natural['joinerLast'];
        if ($joiner == ' y ' && preg_match('/^\s*h?[ií]/iu', $last)) {
            $joiner = ' e ';
        }
        return implode(', ', $head) . $joiner . $last;
    }

    protected function applyTransform($value, $natural) {
        if ($natural['transform'] == 'lowercase') {
            return strtolower($value); // FIXME: only lowercase first char
        }
        return $value;
    }

    protected function getNaturalText($value, $metadatum, $pronoun)
    {
        $natural = $metadatum['natural'];

        switch ($metadatum['type']) {

            case 'textArea':
                $naturalValue = $value;
                break;
            case 'choice':
                $naturalValue = $this->getChoiceText($value, $metadatum);
                break;
            case 'multiple_choices':
                $choices = $this->getMultipleChoicesTexts($value, $metadatum);
                $naturalValue = $this->joinValues($choices, $natural);
                break;
            case 'double_choice':
                $naturalValue = $this->getDoubleChoicesTexts($value, $metadatum);
                break;
            case 'tags':
                $labelValue = array_map(
                    function ($tag) use ($natural) {
                        return $this->applyTransform($tag['name'], $natural);
                    },
                    $value
                );
                $naturalValue = $this->joinValues($labelValue, $natural);
                break;
            case 'tags_and_choice':
                $naturalValue = $this->getTagsAndChoiceNaturalValue($value, $metadatum, $pronoun);
                break;
            default:
                $naturalValue = $value;
                break;
        }

        if (isset($naturalValue)) {
            return $this->buildNaturalText($natural, $naturalValue, $pronoun);
        }
        return null;
    }

    //Japonés: básico, inglés: nativo
    protected function getTagsAndChoiceNaturalValue($profileValue, $metadatum, $pronoun)
    {
        $natural = $metadatum['natural'];
        $languages = array();
        $interfix = \MessageFormatter::formatMessage('en', $natural['interfix'], [
            'pronoun' => $pronoun,
        ]);

        foreach ($profileValue as $tagAndChoice) {
            $tag = $this->applyTransform($tagAndChoice['tag']['name'], $natural);
            $choice = $this->getTextFromKeys($tagAndChoice['choice'], $metadatum['choices']);

            $languages[] = $tag . ' ' . $interfix . ' ' . $choice;
        }

        return $this->joinValues($languages, $natural);
    }

    protected function buildNaturalText($natural, $value1, $pronoun)
    {
        return \MessageFormatter::formatMessage('en', $natural['format'], [ // FIXME: use correct locale
            'x' => $value1,
            'pronoun' => $pronoun,
        ]);
    }

    protected function getChoiceText($choiceId, $metadatum)
    {
        $natural = $metadatum['natural'];
        $choices = $metadatum['choices'];

        if ($natural['skipOther'] && $choiceId == 'other') {
            return null;
        }

        foreach ($choices as $choice)
        {
            if ($choice['id'] == $choiceId){
                return $this->applyTransform($choice['text'], $natural);
            }
        }

        return $choiceId;
    }

    protected function getMultipleChoicesTexts($choicesIds, $metadatum)
    {
        $choicesTexts = array();
        foreach ($choicesIds as $choicesId)
        {
            $choice = $this->getChoiceText($choicesId, $metadatum);
            if (isset($choice)) {
                $choicesTexts[] = $choice;
            }
        }

        return $choicesTexts;
    }

    protected function getDoubleChoicesTexts($values, $metadatum)
    {
        $choice = $this->getChoiceText($values['choice'], $metadatum);
        $detail = $this->getDoubleChoiceText($values, $metadatum);

        return $detail ? $choice . ' ' . $detail : $choice;
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
