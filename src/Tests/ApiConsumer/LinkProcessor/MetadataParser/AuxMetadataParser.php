<?php


namespace Tests\ApiConsumer\LinkProcessor\MetadataParser;


use ApiConsumer\LinkProcessor\MetadataParser\MetadataParser;

class AuxMetadataParser extends MetadataParser {

    public function setValidNameAttributeValues(array $names){
        $this->validNameAttributeValues = $names;
    }

    public function setValidRelAttributeValues(array $names){
        $this->validRelAttributeValues = $names;
    }

    public function keysAndValuesNotContentToLowercase(array $data){
        return parent::keysAndValuesNotContentToLowercase($data);
    }

    public function isValidNameAttribute(array $data){
        return parent::isValidNameAttribute($data);
    }

    public function isValidRelAttribute(array $data){
        return parent::isValidRelAttribute($data);
    }

    public function removeUselessTags(array $tags)
    {
        return parent::removeUseLessTags($tags);
    }

    public function hasOneUsefulAttributeAtLeast(array $tag){
        return parent::hasOneUsefulAttributeAtLeast($tag);
    }

    public function isValidContentAttribute(array $tag){
        return parent::isValidContentAttribute($tag);
    }

    public function removeTagsSorterThanNWords(array $tags, $numWords){
        return parent::removeTagsSorterThanNWords($tags, $numWords);
    }
}
