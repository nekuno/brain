<?php


namespace Event;

class ProcessLinkEvent extends FetchingEvent
{

    protected $link;

    public function __construct($user, $resourceOwner, $fetcher, $link)
    {

        parent::__construct($user, $resourceOwner, $fetcher);
        $this->link = $link;
    }

    public function getLink()
    {
        return $this->link;
    }

}
