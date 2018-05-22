<?php

namespace ApiConsumer\LinkProcessor\Processor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\UrlParserInterface;
use ApiConsumer\ResourceOwner\AbstractResourceOwnerTrait;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;

abstract class AbstractAPIProcessor implements ProcessorInterface
{
    /** @var  ResourceOwnerInterface | AbstractResourceOwnerTrait */
    protected $resourceOwner;
    protected $parser;
    protected $brainBaseUrl;

    public function __construct(ResourceOwnerInterface $resourceOwner, UrlParserInterface $urlParser, $brainBaseUrl)
    {
        $this->resourceOwner = $resourceOwner;
        $this->parser = $urlParser;
        $this->brainBaseUrl = $brainBaseUrl;
    }

    public function getResourceOwner()
    {
        return $this->resourceOwner;
    }

    public function getResponse(PreprocessedLink $preprocessedLink)
    {
        $response = $this->requestItem($preprocessedLink);

        $this->checkValidResponse($preprocessedLink, $response);
        return $response;
    }

    protected function checkValidResponse(PreprocessedLink $preprocessedLink, array $response)
    {
        if (!$this->isValidResponse($response)){
            throw new CannotProcessException($preprocessedLink->getUrl(), sprintf('Response for url %s is not valid', $preprocessedLink->getUrl()));
        }
    }

    abstract protected function requestItem(PreprocessedLink $preprocessedLink);

    protected function isValidResponse(array $response){
        return !empty($response);
    }

    public function addTags(PreprocessedLink $preprocessedLink, array $data)
    {
    }

    public function getSynonymousParameters(PreprocessedLink $preprocessedLink, array $data)
    {
    }

    public function getImages(PreprocessedLink $preprocessedLink, array $data)
    {
        return array();
    }

    protected function getItemId($url)
    {
        try {
            $id = $this->getItemIdFromParser($url);
        } catch (UrlNotValidException $e) {
            throw new CannotProcessException($url);
        }

        return $id;
    }

    protected function getItemIdFromParser($url)
    {
        return $url;
    }

    public function isLinkWorking($url)
    {
        return true;
    }

}