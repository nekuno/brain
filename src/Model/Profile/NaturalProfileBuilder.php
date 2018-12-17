<?php

namespace Model\Profile;

use Model\Metadata\CategoryMetadataManager;
use Model\Metadata\ProfileMetadataManager;

class NaturalProfileBuilder
{
    /**
     * @var ProfileMetadataManager
     */
    protected $profileMetadataManager;

    /**
     * @var CategoryMetadataManager
     */
    protected $categoryMetadataManager;

    protected $metadata;

    protected $result = array();

    /**
     * NaturalProfileBuilder constructor.
     * @param ProfileMetadataManager $profileMetadataManager
     * @param CategoryMetadataManager $categoryMetadataManager
     */
    public function __construct(ProfileMetadataManager $profileMetadataManager, CategoryMetadataManager $categoryMetadataManager)
    {
        $this->profileMetadataManager = $profileMetadataManager;
        $this->categoryMetadataManager = $categoryMetadataManager;
    }

    public function buildNaturalProfile(Profile $profile)
    {
        $interfaceLanguage = $profile->get('interfaceLanguage');
        $this->metadata = $this->profileMetadataManager->getMetadata($interfaceLanguage);

        foreach ($profile->getValues() as $fieldName => $profileValue) {
            if (!$this->findCategory($fieldName)) {
                continue;
            }

            $metadatum = $this->metadata[$fieldName];

            $naturalText = $this->getNaturalText($profileValue, $metadatum);

            $this->addToResult($fieldName, $naturalText);
        }

        return $this->buildResult();
    }

    protected function getNaturalText($value, $metadatum)
    {
        $natural = $metadatum['natural'];

        $naturalValue2 = '';

        switch ($metadatum['type']) {
            case 'choice':
            case 'textArea':
                $naturalValue1 = $value;
                break;
            case 'multiple_choices':
                $naturalValue1 = implode(', ', $value);
                break;
            case 'double_choice':
                $naturalValue1 = $value['choice'];
                $naturalValue2 = $value['detail'];
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
                $naturalValue1 = $this->getTagsAndChoiceNaturalValue($value, $natural);
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
    protected function getTagsAndChoiceNaturalValue($profileValue, $natural)
    {
        $languages = array();

        foreach ($profileValue as $tagAndChoice) {
            $tag = $tagAndChoice['tag']['name'];
            $choice = $tagAndChoice['choice'];

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
            $finalResult[$category] = implode('; ', $categoryValues) . '.';
        }

        return $finalResult;
    }

}