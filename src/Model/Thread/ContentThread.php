<?php

namespace Model\Thread;


use Model\Filters\FilterContent;

class ContentThread extends Thread
{
    /**
     * @var FilterContent
     */
    protected $filterContent;

    /**
     * @return FilterContent
     */
    public function getFilterContent()
    {
        return $this->filterContent;
    }

    /**
     * @param FilterContent $filterContent
     */
    public function setFilterContent($filterContent)
    {
        $this->filterContent = $filterContent;
    }



    function jsonSerialize()
    {
        $array = parent::jsonSerialize();

        $array += array(
            'category' => ThreadManager::LABEL_THREAD_CONTENT,
            'filters' => $this->getFilterContent(),
        );

        return $array;
    }

}