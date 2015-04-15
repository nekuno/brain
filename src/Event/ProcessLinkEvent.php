<?php


namespace Event;

class ProcessLinkEvent extends FetchEvent
{

    protected $link;

    public function __construct($user, $resourceOwner, $link)
    {

        parent::__construct($user, $resourceOwner);
        $this->link = $link;
    }

    public function getLink()
    {
        return $this->link;
    }

}
