<?php

namespace Model\Profile;

use Everyman\Neo4j\Label;
use Everyman\Neo4j\Query\Row;
use Model\Metadata\MetadataUtilities;
use Model\Metadata\ProfileMetadataManager;
use Model\Neo4j\GraphManager;

class ProfileOptionManager
{
    protected $graphManager;
    protected $profileMetadataManager;
    protected $metadataUtilities;

    protected $options = array();
    protected $tags = array();

    public function __construct(GraphManager $graphManager, ProfileMetadataManager $profileMetadataManager, MetadataUtilities $metadataUtilities)
    {
        $this->graphManager = $graphManager;
        $this->profileMetadataManager = $profileMetadataManager;
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

    public function getUserProfileOptions($id)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(option:ProfileOption)-[optionOf:OPTION_OF]->(profile:Profile)-[:PROFILE_OF]->(user:User)')
            ->where('user.qnoow_id = { id }')
            ->setParameter('id', $id)
            ->returns('profile, collect(distinct {option: option, detail: (CASE WHEN EXISTS(optionOf.detail) THEN optionOf.detail ELSE null END)}) AS options');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $options = array();
        foreach ($result as $row) {
            $options += $this->buildOptions($row->offsetGet('options'));
        }

        return $options;
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
        $metadata = $this->profileMetadataManager->getMetadata();

        /** @var Row $optionData */
        foreach ($options as $optionData) {

            list($optionId, $labels, $detail) = $this->getOptionData($optionData);
            /** @var Label[] $labels */
            foreach ($labels as $label) {

                $typeName = $this->metadataUtilities->labelToType($label->getName());

                switch ($metadata[$typeName]['type']) {
                    case 'multiple_choices':
                        $result = $this->getOptionArrayResult($optionsResult, $typeName, $optionId);
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
            return array(null, array(), null);
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

        return array($optionId, $labels, $detail);
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