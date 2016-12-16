<?php

namespace ApiConsumer\LinkProcessor\Processor\TwitterProcessor;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\AbstractProcessor;
use ApiConsumer\LinkProcessor\Processor\BatchProcessorInterface;
use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use ApiConsumer\ResourceOwner\TwitterResourceOwner;
use Model\Creator;
use Model\User\TokensModel;

abstract class AbstractTwitterProfileProcessor extends AbstractProcessor implements BatchProcessorInterface
{
    /**
     * @var TwitterResourceOwner
     */
    protected $resourceOwner;

    /**
     * @var TwitterUrlParser
     */
    protected $parser;

    /**
     * @var PreprocessedLink[]
     */
    protected $batch = array();

    function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $preprocessedLink->addLink(Creator::buildFromArray($this->resourceOwner->buildProfileFromLookup($data)));
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

    public function addToBatch(PreprocessedLink $preprocessedLink)
    {
        $this->batch[] = $preprocessedLink;
    }

}