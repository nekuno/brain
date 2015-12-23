<?php
/**
 * Created by PhpStorm.
 * User: yawmoght
 * Date: 07/12/2015
 * Time: 14:21
 */

namespace Model\User\Thread;


class ContentThread extends Thread
{
    protected $type;

    protected $tag;

    public function __construct($id, $name, $type)
    {
        parent::__construct($id, $name);

        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param mixed $tags
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    function jsonSerialize()
    {
        $array = parent::jsonSerialize();

        $array += array(
            'category' => ThreadManager::LABEL_THREAD_CONTENT,
            'type' => $this->getType(),
            'tag' => $this->getTag(),
        );

        return $array;
    }

}