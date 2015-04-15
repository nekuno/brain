<?php


namespace Event;

class ProcessLinksEvent extends FetchEvent
{

    protected $links;

    public function __construct($user, $resourceOwner, $links)
    {

        parent::__construct($user, $resourceOwner);
        $this->links = $links;
    }

    public function getLinks()
    {
        return $this->links;
    }

}
