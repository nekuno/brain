<?php


namespace ApiConsumer\Event;

use Symfony\Component\EventDispatcher\Event;

class LinkEvent extends Event
{

    protected $data;

    /**
     * @return mixed
     */
    public function getData()
    {

        return $this->data;
    }

    public function __construct(array $data)
    {

        $this->data = $data;
    }
}
