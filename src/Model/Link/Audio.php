<?php

namespace Model\Link;

class Audio extends Link
{
    const AUDIO_LABEL = 'Audio';

    protected $embed_type;
    protected $embed_id;

    public function toArray()
    {
        $array = parent::toArray();
        $array['additionalLabels'][] = self::AUDIO_LABEL;
        $array['additionalFields'] = array(
            'embed_id' => $this->getEmbedId(),
            'embed_type' => $this->getEmbedType()
        );

        unset($array['embed_id']);
        unset($array['embed_type']);

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

    public function isComplete()
    {
        return parent::isComplete() && $this->getEmbedId() && $this->getEmbedType();
    }
}