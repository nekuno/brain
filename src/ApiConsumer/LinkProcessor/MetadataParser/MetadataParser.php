<?php

namespace ApiConsumer\LinkProcessor\MetadataParser;

class MetadataParser
{

    /**
     * @var array
     */
    protected $validNameAttributeValues = array();

    /**
     * @var array
     */
    protected $validRelAttributeValues = array();

    /**
     * @param $metaTagsData
     * @return mixed
     */
    protected function keysAndValuesNotContentToLowercase($metaTagsData)
    {

        foreach ($metaTagsData as $index => &$tag) {
            $metaTagsData[$index] = array_change_key_case($tag);
            foreach ($tag as $type => $value) {
                $tag[$type] = $type != 'content' ? strtolower($value) : $value;
            }
        }

        return $metaTagsData;
    }

    /**
     * @param $metadataTags
     * @return mixed
     */
    protected function sanitizeMetadataTags($metadataTags)
    {

        $metadataTags = $this->keysAndValuesNotContentToLowercase($metadataTags);

        $metadataTags = $this->removeUseLessTags($metadataTags);

        return $metadataTags;
    }

    /**
     * @param $tags
     * @param $wordLimit
     */
    protected function removeTagsSorterThanNWords($tags, $wordLimit = 2)
    {
        foreach ($tags as $index => &$tag) {
            $tag = strtolower($tag);

            if (str_word_count($tag, 0) > $wordLimit) {
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

            if (false === $this->hasOneUsefulAttributeAtLeast($data)) {
                unset($metaTagsData[$index]);
                continue;
            }

            if (
                $this->isValidNameAttribute($data)
                || $this->isValidRelAttribute($data)
                || $this->isValidPropertyAttribute($data)
            ) {
                unset($metaTagsData[$index]);
            }

            if (!$this->isValidContentAttribute($data)) {
                unset($metaTagsData[$index]);
            }
        }

        return $metaTagsData;
    }

    /**
     * @param $data
     * @return bool
     */
    protected function hasOneUsefulAttributeAtLeast(array $data)
    {
        return null !== (array_key_exists('rel', $data) ? $data['rel'] : null)
        || null !== (array_key_exists('name', $data) ? $data['name'] : null)
        || null !== (array_key_exists('property', $data) ? $data['property'] : null);
    }

    /**
     * @param $data
     * @return bool
     */
    protected function isValidNameAttribute($data)
    {
        return in_array($data['name'], $this->validNameAttributeValues);
    }

    /**
     * @param $data
     * @return bool
     */
    protected function isValidRelAttribute($data)
    {

        return in_array($data['rel'], $this->validRelAttributeValues);
    }

    protected function isValidPropertyAttribute($data)
    {
        if (array_key_exists('property', $data)) {
            return null !== $data['property'] && '' !== trim($data['property']);
        } else {
            return false;
        }
    }

    /**
     * @param $data
     * @return bool
     */
    protected function isValidContentAttribute($data)
    {
        if (array_key_exists('content', $data)) {
            return null !== $data['content'] && '' !== trim($data['content']);
        } else {
            return false;
        }
    }

}
