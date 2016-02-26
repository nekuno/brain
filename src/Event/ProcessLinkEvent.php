<?php


namespace Event;

use ApiConsumer\LinkProcessor\PreprocessedLink;

class ProcessLinkEvent extends FetchEvent
{
    /** @var  PreprocessedLink */
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
