<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace Model\User;


use Everyman\Neo4j\Query\Row;

class ProfileFilterModel extends FilterModel
{
    /**
     * Returns the metadata for editing the profile
     * @param null $locale Locale of the metadata
     * @param bool $filter Filter non public attributes
     * @return array
     */
    public function getMetadata($locale = null, $filter = true)
    {
        $locale = $this->getLocale($locale);
        $choiceOptions = $this->getChoiceOptions($locale);

        $publicMetadata = array();
        foreach ($this->metadata as $name => $values) {
            $publicField = $values;
            $publicField['label'] = $values['label'][$locale];

            if ($values['type'] === 'choice') {
                $publicField['choices'] = array();
                if (isset($choiceOptions[$name])) {
                    $publicField['choices'] = $choiceOptions[$name];
                }
            } elseif ($values['type'] === 'double_choice') {
                $publicField['choices'] = array();
                if (isset($choiceOptions[$name])) {
                    $publicField['choices'] = $choiceOptions[$name];
                    if (isset($values['doubleChoices'])) {
                        foreach ($values['doubleChoices'] as $choice => $doubleChoices) {
                            foreach ($doubleChoices as $doubleChoice => $doubleChoiceValues) {
                                $publicField['doubleChoices'][$choice][$doubleChoice] = $doubleChoiceValues[$locale];
                            }
                        }
                    }
                }
            } elseif ($values['type'] === 'multiple_choices') {
                $publicField['choices'] = array();
                if (isset($choiceOptions[$name])) {
                    $publicField['choices'] = $choiceOptions[$name];
                }
                if (isset($values['max_choices'])) {
                    $publicField['max_choices'] = $values['max_choices'];
                }
            } elseif ($values['type'] === 'tags_and_choice') {
                $publicField['choices'] = array();
                if (isset($values['choices'])) {
                    foreach ($values['choices'] as $choice => $description) {
                        $publicField['choices'][$choice] = $description[$locale];
                    }
                }
                $publicField['top'] = $this->getTopProfileTags($name);
            } elseif ($values['type'] === 'tags') {
                $publicField['top'] = $this->getTopProfileTags($name);
            }

            $publicMetadata[$name] = $publicField;
        }

        if ($filter) {
            foreach ($publicMetadata as &$item) {
                if (isset($item['labelFilter'])) {
                    unset($item['labelFilter']);
                }
                if (isset($item['filterable'])) {
                    unset($item['filterable']);
                }
            }
        }

        return $publicMetadata;
    }

    /**
     * Returns the metadata for creating search filters
     * @param null $locale
     * @return array
     */
    public function getFilters($locale = null)
    {

        $locale = $this->getLocale($locale);
        $metadata = $this->getMetadata($locale, false);
        $labels = array();
        foreach ($metadata as $key => &$item) {
            if (isset($item['labelFilter'])) {
                $item['label'] = $item['labelFilter'][$locale];
                unset($item['labelFilter']);
            }
            if (isset($item['filterable']) && $item['filterable'] === false) {
                unset($metadata[$key]);
            } else {
                $labels[] = $item['label'];
            }
        }

        if (!empty($labels)) {
            array_multisort($labels, SORT_ASC, $metadata);
        }

        return $metadata;
    }

    protected function getChoiceOptions($locale)
    {
        $translationField = 'name_' . $locale;

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(option:ProfileOption)')
            ->returns("head(filter(x IN labels(option) WHERE x <> 'ProfileOption')) AS labelName, option.id AS id, option." . $translationField . " AS name")
            ->orderBy('labelName');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $choiceOptions = array();
        /** @var Row $row */
        foreach ($result as $row) {
            $typeName = $this->labelToType($row->offsetGet('labelName'));
            $optionId = $row->offsetGet('id');
            $optionName = $row->offsetGet('name');

            $choiceOptions[$typeName][$optionId] = $optionName;
        }

        return $choiceOptions;
    }

    public function labelToType($labelName)
    {

        return lcfirst($labelName);
    }

    public function typeToLabel($typeName)
    {
        return ucfirst($typeName);
    }

    public function getAgeRangeFromBirthdayRange(array $birthday)
    {
        $min = $this->getYearsFromDate($birthday['min']);
        $max = $this->getYearsFromDate($birthday['max']);

        return array('min' => $max, 'max' => $min);
    }

    private function getYearsFromDate($birthday)
    {
        $minDate = new \DateTime($birthday);
        $minInterval = $minDate->diff(new \DateTime());
        return $minInterval->y;
    }

    public function getBirthdayRangeFromAgeRange($min = null, $max = null)
    {
        $return = array();
        if ($min){
            $now = new \DateTime();
            $maxBirthday = $now->modify('-'.$min.' years')->format('Y-m-d');
            $return ['max'] = $maxBirthday;
        }
        if ($max){
            $now = new \DateTime();
            $minBirthday = $now->modify('-'.$max.' years')->format('Y-m-d');
            $return['min'] = $minBirthday;
        }

        return $return;
    }

    protected function getTopProfileTags($tagType)
    {

        $tagLabelName = $this->typeToLabel($tagType);

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(tag:' . $tagLabelName . ')-[tagged:TAGGED]-(profile:Profile)')
            ->returns('tag.name AS tag, count(*) as count')
            ->limit(5);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $tags = array();
        foreach ($result as $row) {
            /* @var $row Row */
            $tags[] = $row->offsetGet('tag');
        }

        return $tags;
    }
}