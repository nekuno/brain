<?php


namespace Tests\ApiConsumer\LinkProcessor\MetadataParser;


use ApiConsumer\LinkProcessor\MetadataParser\MetadataParser;

class AuxMetadataParser extends MetadataParser
{

    public function setValidNameAttributeValues(array $names)
    {
        $this->validNameAttributeValues = $names;
    }

    public function setValidRelAttributeValues(array $names)
    {
        $this->validRelAttributeValues = $names;
    }

    public function keysAndValuesNotContentToLowercase($data)
    {
        return parent::keysAndValuesNotContentToLowercase($data);
    }

    public function isValidNameAttribute($data)
    {
        return parent::isValidNameAttribute($data);
    }

    public function isValidRelAttribute($data)
    {
        return parent::isValidRelAttribute($data);
    }

    public function removeUselessTags($tags)
    {
        return parent::removeUseLessTags($tags);
    }

    public function hasOneUsefulAttributeAtLeast(array $data)
    {
        return parent::hasOneUsefulAttributeAtLeast($data
        );
    }

    public function isValidContentAttribute($tag)
    {
        return parent::isValidContentAttribute($tag);
    }

    public function removeTagsSorterThanNWords($tags, $wordLimit = 2)
    {
        return parent::removeTagsSorterThanNWords($tags, $wordLimit);
    }
}
