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

    protected $tags;

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
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param mixed $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    function jsonSerialize()
    {
        $array = parent::jsonSerialize();

        $array += array(
            'category' => ThreadManager::LABEL_THREAD_CONTENT,
            'type' => $this->getType(),
            'tags' => $this->getTags(),
        );

        return $array;
    }

}