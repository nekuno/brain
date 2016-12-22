<?php

namespace Model;

class Audio extends Link
{
    protected $embed_type;
    protected $embed_id;

    public static function buildFromLink(Link $link) {
        $array = $link->toArray();

        /** @var Audio $me */
        $me = parent::buildFromArray($array);

        if (isset($array['embed_type'])){
            $me->setEmbedType($array['embed_type']);
        }
        if (isset($array['embed_id'])){
            $me->setEmbedId($array['embed_id']);
        }

        return $me;
    }

    public function toArray()
    {
        $array = parent::toArray();
        $array['additionalLabels'] = array('Audio');
        return $array;
    }

    /**
     * @return mixed
     */
    public function getEmbedType()
    {
        return $this->embed_type;
    }

    /**
     * @param mixed $embed_type
     */
    public function setEmbedType($embed_type)
    {
        $this->embed_type = $embed_type;
    }

    /**
     * @return mixed
     */
    public function getEmbedId()
    {
        return $this->embed_id;
    }

    /**
     * @param mixed $embed_id
     */
    public function setEmbedId($embed_id)
    {
        $this->embed_id = $embed_id;
    }
}