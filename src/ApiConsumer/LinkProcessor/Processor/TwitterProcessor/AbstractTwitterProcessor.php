<?php

namespace ApiConsumer\LinkProcessor\Processor\TwitterProcessor;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\AbstractAPIProcessor;
use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use ApiConsumer\ResourceOwner\TwitterResourceOwner;

abstract class AbstractTwitterProcessor extends AbstractAPIProcessor
{
    const TWITTER_LABEL = 'LinkTwitter';

    /** @var  TwitterUrlParser */
    protected $parser;

    /** @var  TwitterResourceOwner */
    protected $resourceOwner;

    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $link = $preprocessedLink->getFirstLink();
        $link->addAdditionalLabels(self::TWITTER_LABEL);
    }

}