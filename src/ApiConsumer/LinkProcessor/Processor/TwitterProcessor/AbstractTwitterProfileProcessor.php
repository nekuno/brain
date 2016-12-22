<?php

namespace ApiConsumer\LinkProcessor\Processor\TwitterProcessor;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\AbstractProcessor;
use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use ApiConsumer\ResourceOwner\TwitterResourceOwner;
use Model\Creator;
use Model\User\TokensModel;

abstract class AbstractTwitterProfileProcessor extends AbstractProcessor
{
    /**
     * @var TwitterResourceOwner
     */
    protected $resourceOwner;

    /**
     * @var TwitterUrlParser
     */
    protected $parser;


    function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $preprocessedLink->setLink(Creator::buildFromArray($this->resourceOwner->buildProfileFromLookup($data[0])));
    }

    public function getImages(PreprocessedLink $preprocessedLink, array $data)
    {
        return isset($data['profile_image_url']) ? array(str_replace('_normal', '', $data['profile_image_url'])) : array();
    }

    protected function getItemIdFromParser($url)
    {
        $bla = $this->parser->getProfileId($url);
        return $bla;
    }

}