<?php

namespace ApiConsumer\LinkProcessor\Processor\YoutubeProcessor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\AbstractProcessor;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;
use ApiConsumer\ResourceOwner\GoogleResourceOwner;

abstract class AbstractYoutubeProcessor extends AbstractProcessor
{
    /**
     * @var YoutubeUrlParser
     */
    protected $parser;

    /**
     * @var GoogleResourceOwner
     */
    protected $resourceOwner;

    protected $itemApiUrl;
    protected $itemApiParts;

    public function requestItem(PreprocessedLink $preprocessedLink)
    {
        $itemId = $this->getItemId($preprocessedLink->getCanonical());
        $preprocessedLink->setResourceItemId(reset($itemId));

        $response = $this->requestSpecificItem($itemId);

        if (!((isset($response['items']) && is_array($response['items']) && count($response['items']) > 0 && isset($response['items'][0]['snippet'])))) {
            throw new CannotProcessException($preprocessedLink->getCanonical());
        }

        return $response['items'][0];
    }

    function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $link = $preprocessedLink->getLink();

        $snippet = $data['snippet'];
        $link->setTitle($snippet['title']);
        $link->setDescription($snippet['description']);
    }

    abstract protected function requestSpecificItem($id);

}