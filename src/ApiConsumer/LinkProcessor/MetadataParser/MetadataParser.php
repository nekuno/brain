<?php

namespace ApiConsumer\LinkProcessor\MetadataParser;

class MetadataParser
{


    /**
     * @var array
     */
    protected $validMetaName = array();

    /**
     * @var array
     */
    protected $validMetaRel = array();

    /**
     * @param $metaTagsData
     * @return mixed
     */
    protected function keysToLowercase($metaTagsData)
    {

        foreach ($metaTagsData as &$tag) {

            foreach ($tag as $type => $value) {
                if ($type !== "content" && null !== $value) {
                    $tag[$type] = strtolower($value);
                }
            }
        }

        return $metaTagsData;
    }


    /**
     * @param $tags
     * @param $wordLimit
     */
    protected function filterTagsByNumOfWords($tags, $wordLimit = 2)
    {
        foreach ($tags as $index => &$tag) {
            $tag = strtolower($tag);

            $words = explode(' ', $tag);
            if (count($words) > $wordLimit) {
                unset($tags[$index]);
            }
        }

        return $tags;
    }

    /**
     * @param $metaTagsData
     */
    protected function removeUseLessTags($metaTagsData)
    {

        foreach ($metaTagsData as $index => $data) {

            if (false === $this->hasOneUsefulMetaAtLeast($data)) {
                unset($metaTagsData[$index]);
                continue;
            }

            if (null !== $data['rel'] && !$this->isValidRel($data)) {
                unset($metaTagsData[$index]);
                continue;
            }

            if (null !== $data['name'] && !$this->isValidName($data)) {
                unset($metaTagsData[$index]);
                continue;
            }

            if (null === $data['content']) {
                unset($metaTagsData[$index]);
            }
        }

        return $metaTagsData;
    }

    /**
     * @param $data
     * @return bool
     */
    protected function hasOneUsefulMetaAtLeast(array $data)
    {

        return null !== $data['rel'] || null !== $data['name'] || null !== $data['property'];
    }

    /**
     * @param $data
     * @return bool
     */
    protected function isValidRel($data)
    {

        return in_array($data['rel'], $this->validMetaRel);
    }

    /**
     * @param $data
     * @return bool
     */
    protected function isValidName($data)
    {

        return in_array($data['name'], $this->validMetaName);
    }

}
