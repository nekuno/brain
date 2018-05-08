<?php

namespace Service;

use Model\Metadata\MetadataManagerFactory;
use Model\Metadata\MetadataManagerInterface;
use Model\Metadata\MetadataUtilities;
use Model\Group\GroupManager;
use Model\Profile\ProfileOptionManager;

class MetadataService
{
    protected $metadataManagerFactory;
    protected $groupModel;
    protected $profileOptionManager;
    protected $metadataUtilities;

    protected $managers = array();

    /**
     * MetadataService constructor.
     * @param MetadataManagerFactory $metadataManagerFactory
     * @param GroupManager $groupModel
     * @param ProfileOptionManager $profileOptionManager
     * @param MetadataUtilities $metadataUtilities
     */
    public function __construct(MetadataManagerFactory $metadataManagerFactory, GroupManager $groupModel, ProfileOptionManager $profileOptionManager, MetadataUtilities $metadataUtilities)
    {
        $this->metadataManagerFactory = $metadataManagerFactory;
        $this->groupModel = $groupModel;
        $this->profileOptionManager = $profileOptionManager;
        $this->metadataUtilities = $metadataUtilities;
    }

    public function getUserFilterMetadata($locale, $userId = null)
    {
        $metadata = $this->getBasicMetadata($locale, 'user_filter');

        $choices = $this->profileOptionManager->getLocaleOptions($locale);
        $metadata = $this->addChoices($metadata, $choices, $locale);

        if ($userId) {
            $groupChoices = $this->getGroupChoices($userId);
            if (!empty($groupChoices)) {
                $metadata['groups']['choices'] = $groupChoices;
            }
        }

        return $metadata;
    }

    public function getProfileMetadata($locale = null)
    {
        $metadata = $this->getBasicMetadata($locale, 'profile');

        return $metadata;
    }

    public function getProfileMetadataWithChoices($locale = null)
    {
        $metadata = $this->getProfileMetadata($locale);

        $choices = $this->profileOptionManager->getLocaleOptions($locale);
        $metadata = $this->addChoices($metadata, $choices, $locale);

        return $metadata;
    }

    public function getCategoriesMetadata($locale = null)
    {
        $metadata = $this->getBasicMetadata($locale, 'categories');

        return $metadata;
    }

    public function getContentFilterMetadata($locale = null)
    {
        $metadata = $this->getBasicMetadata($locale, 'content_filter');

        return $metadata;
    }

    public function getGroupChoices($userId)
    {
        $groups = $this->groupModel->getAllByUserId($userId);
        $choices = $this->groupModel->buildGroupNames($groups);

        return $choices;
    }

    protected function getMetadataManager($name)
    {
        if (isset($this->managers[$name])) {
            return $this->managers[$name];
        }

        $manager = $this->metadataManagerFactory->build($name);
        $this->managers[$name] = $manager;

        return $manager;
    }

    protected function addChoices(array $metadata, array $choices, $locale)
    {
        foreach ($metadata as $name => &$field) {
            switch ($field['type']) {
                case 'choice':
                    $field = $this->mergeSingleChoices($field, $name, $choices);
                    break;
                case 'double_choice':
                case 'double_multiple_choices':
                case 'choice_and_multiple_choices':
                    $field = $this->mergeSingleChoices($field, $name, $choices);
                    $field = $this->fixDoubleChoicesLocale($field, $locale);
                    break;
                case 'multiple_choices':
                    $field = $this->mergeSingleChoices($field, $name, $choices);
                    $field['max'] = isset($field['max']) ? $field['max'] : 999;
                    $field['min'] = isset($field['min']) ? $field['min'] : 0;
                    break;
                case 'multiple_fields':
                    $field['metadata'] = $this->addChoices($field['metadata'], $choices, $locale);
                    $field['max'] = isset($field['max']) ? $field['max'] : 999;
                    $field['min'] = isset($field['min']) ? $field['min'] : 0;
                    break;
                case 'tags_and_choice':
                    $field = $this->mergeSingleChoices($field, $name, $choices);
                    $field = $this->fixSingleChoicesLocale($field, $locale);

                    $field['top'] = $this->profileOptionManager->getTopProfileTags($name);
                    break;
                case 'tags':
                    $field['top'] = $this->profileOptionManager->getTopProfileTags($name);
                    break;
                default:
                    break;
            }
        }

        return $metadata;
    }

    /**
     * @param $field
     * @param $name
     * @param $choices
     * @return array
     */
    protected function mergeSingleChoices(array $field, $name, $choices)
    {
        $configChoices = isset($field['choices']) && is_array($field['choices']) ? $field['choices'] : array();
        $databaseChoices = isset($choices[$name]) ? $choices[$name] : array();
        $allChoices = $configChoices + $databaseChoices;
//        if (!empty($allChoices)) {
        $field['choices'] = $allChoices;
//        }

        return $field;
    }

    /**
     * @param $field
     * @param $locale
     * @return mixed
     */
    protected function fixDoubleChoicesLocale($field, $locale)
    {
        $valueDoubleChoices = isset($field['doubleChoices']) ? $field['doubleChoices'] : array();
        foreach ($valueDoubleChoices as $choice => $doubleChoices) {
            foreach ($doubleChoices as $doubleChoice => $doubleChoiceValues) {
                $field['doubleChoices'][$choice][$doubleChoice] = $this->metadataUtilities->getLocaleString($doubleChoiceValues, $locale);
            }
        }

        return $field;
    }

    protected function fixSingleChoicesLocale($field, $locale)
    {
        if (isset($field['choices'])) {
            foreach ($field['choices'] as $choice => $description) {
                $field['choices'][$choice] = $this->metadataUtilities->getLocaleString($description, $locale);
            }
        }

        return $field;
    }

    public function getBasicMetadata($locale, $name)
    {
        /** @var MetadataManagerInterface $metadataManager */
        $metadataManager = $this->getMetadataManager($name);
        $metadata = $metadataManager->getMetadata($locale);

        return $metadata;
    }

    public function changeChoicesToIds(array $metadata)
    {
        foreach ($metadata as &$field) {
            if (isset($field['choices']) && is_array($field['choices'])) {
                foreach ($field['choices'] as $id => $choice) {
                    if(isset($choice['id'])){
                        $field['choices'][$id] = $choice['id'];
                    }
                }
            }
        }

        return $metadata;
    }

}