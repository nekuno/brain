<?php

namespace Model\Profile;

use Everyman\Neo4j\Label;
use Everyman\Neo4j\Query\Row;
use Model\Metadata\MetadataManagerInterface;
use Model\Metadata\MetadataUtilities;
use Model\Neo4j\GraphManager;

abstract class AbstractProfileOptionManager
{
    protected $graphManager;
    protected $metadataManager;
    protected $metadataUtilities;

    protected $options = array();
    protected $tags = array();

    public function __construct(GraphManager $graphManager, MetadataUtilities $metadataUtilities, MetadataManagerInterface $metadataManager)
    {
        $this->graphManager = $graphManager;
        $this->metadataManager = $metadataManager;
        $this->metadataUtilities = $metadataUtilities;
    }

    /**
     * Output choice options independently of locale
     * @return array
     */
    public function getOptions()
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(option:ProfileOption)')
            ->returns("head(filter(x IN labels(option) WHERE x <> 'ProfileOption')) AS labelName, option.id AS id")
            ->orderBy('option.order');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $choiceOptions = array();
        /** @var Row $row */
        foreach ($result as $row) {
            $typeName = $this->metadataUtilities->labelToType($row->offsetGet('labelName'));
            $optionId = $row->offsetGet('id');

            $choiceOptions[$typeName][] = $optionId;
        }

        return $choiceOptions;
    }

    /**
     * Output  choice options according to user language
     * @param $locale
     * @return array
     */
    public function getLocaleOptions($locale)
    {
        $translationField = $this->getTranslationField($locale);
        if (isset($this->options[$translationField])) {
            return $this->options[$translationField];
        }
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(option:ProfileOption)')
            ->returns("head(filter(x IN labels(option) WHERE x <> 'ProfileOption')) AS labelName, option.id AS id, option." . $translationField . " AS name")
            ->orderBy('option.order');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $choiceOptions = array();
        /** @var Row $row */
        foreach ($result as $row) {
            $typeName = $this->metadataUtilities->labelToType($row->offsetGet('labelName'));
            $optionId = $row->offsetGet('id');
            $optionName = $row->offsetGet('name');

            $choiceOptions[$typeName][] = array(
                'id' => $optionId,
                'text' => $optionName
            );
        }

        $this->options[$translationField] = $choiceOptions;

        return $choiceOptions;
    }

    protected function getTranslationField($locale)
    {
        return 'name_' . $locale;
    }

    public function buildOptions(\ArrayAccess $options)
    {
        $optionsResult = array();
        $metadata = $this->metadataManager->getMetadata();

        /** @var Row $optionData */
        foreach ($options as $optionData) {

            list($optionId, $labels, $detail, $details) = $this->getOptionData($optionData);
            /** @var Label[] $labels */
            foreach ($labels as $label) {

                $typeName = $this->metadataUtilities->labelToType($label->getName());
                switch ($metadata[$typeName]['type']) {
                    case 'multiple_choices':
                        $result = $this->getOptionArrayResult($optionsResult, $typeName, $optionId);
                        break;
                    case 'choice_and_multiple_choices':
                        $result = $this->getChoiceAndMultipleChoicesResult($optionId, $details);
                        break;
                    case 'double_multiple_choices':
                        $result = $this->getDoubleMultipleChoicesResult($optionsResult, $typeName, $optionId, $details);
                        break;
                    case 'double_choice':
                    case 'tags_and_choice':
                        $result = $this->getOptionDetailResult($optionId, $detail);
                        break;
                    default:
                        $result = $optionId;
                        break;
                }

                $optionsResult[$typeName] = $result;
            }
        }

        return $optionsResult;
    }

    protected function getOptionData(Row $optionData = null)
    {
        if (!$optionData->offsetExists('option')) {
            return array(null, array(), null, array());
        }
        $optionNode = $optionData->offsetGet('option');
        $optionId = $optionNode->getProperty('id');

        /** @var Label[] $labels */
        $labels = $optionNode->getLabels();
        foreach ($labels as $key => $label) {
            if ($label->getName() === 'ProfileOption') {
                unset($labels[$key]);
            }
        }

        $detail = $optionData->offsetExists('detail') ? $optionData->offsetGet('detail') : '';

        $details = array();
        $detailsRow = $optionData->offsetExists('details') ? $optionData->offsetGet('details') : [];
        foreach ($detailsRow as $detailsSingle) {
            $details[] = $detailsSingle;
        }

        return array($optionId, $labels, $detail, $details);
    }

    protected function getOptionArrayResult($optionsResult, $typeName, $optionId)
    {
        if (isset($optionsResult[$typeName])) {
            $currentResult = is_array($optionsResult[$typeName]) ? $optionsResult[$typeName] : array($optionsResult[$typeName]);
            $currentResult[] = $optionId;
        } else {
            $currentResult = array($optionId);
        }

        sort($currentResult);

        return $currentResult;
    }

    protected function getOptionDetailResult($optionId, $detail)
    {
        return array('choice' => $optionId, 'detail' => $detail);
    }

    protected function getDoubleMultipleChoicesResult($optionsResult, $typeName, $optionId, $details)
    {
        if (isset($optionsResult[$typeName]) && is_array($optionsResult[$typeName])) {
            $currentResult = $optionsResult[$typeName];
        } else {
            $currentResult = array('choices' => array(), 'details' => array());
        }

        $currentResult['choices'][] = $optionId;
        $currentResult['details'] = $details; //are the same for each optionId

        $choices = $currentResult['choices'];
        sort($choices);
        $currentResult['choices'] = $choices;

        $details = $currentResult['details'];
        sort($details);
        $currentResult['details'] = $details;

        return $currentResult;
    }

    protected function getChoiceAndMultipleChoicesResult($optionId, $details)
    {
        $currentResult = array('choice' => $optionId, 'details' => $details);

        $details = $currentResult['details'];
        sort($details);
        $currentResult['details'] = $details;

        return $currentResult;
    }

    public function getTopProfileTags($tagType)
    {
        $tagLabelName = $this->metadataUtilities->typeToLabel($tagType);
        if (isset($this->tags[$tagLabelName])) {
            return $this->tags[$tagLabelName];
        }
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(tag:' . $tagLabelName . ')-[tagged:TAGGED]->(profile:Profile)')
            ->returns('tag.name AS tag, count(*) as count')
            ->limit(5);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $tags = array();
        foreach ($result as $row) {
            /* @var $row Row */
            $tags[] = $row->offsetGet('tag');
        }

        $this->tags[$tagLabelName] = $tags;

        return $tags;
    }

}