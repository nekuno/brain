<?php


namespace Event;

class ProcessLinksEvent extends FetchingEvent
{

    protected $links;

    public function __construct($user, $resourceOwner, $fetcher, $links)
    {

        parent::__construct($user, $resourceOwner, $fetcher);
        $this->links = $links;
    }

    public function getLinks()
    {
        return $this->links;
    }

}
