<?php

namespace ApiConsumer\LinkProcessor\MetadataParser;

class MetadataParser
{

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
     * @return string
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

}
