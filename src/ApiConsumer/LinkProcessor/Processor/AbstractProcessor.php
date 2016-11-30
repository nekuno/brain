<?php

namespace ApiConsumer\LinkProcessor\Processor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\SynonymousParameters;
use ApiConsumer\LinkProcessor\UrlParser\UrlParserInterface;
use ApiConsumer\ResourceOwner\AbstractResourceOwnerTrait;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;

abstract class AbstractProcessor implements ProcessorInterface
{
    /** @var  ResourceOwnerInterface | AbstractResourceOwnerTrait */
    protected $resourceOwner;
    protected $parser;

    public function __construct(ResourceOwnerInterface $resourceOwner, UrlParserInterface $urlParser)
    {
        $this->resourceOwner = $resourceOwner;
        $this->parser = $urlParser;
    }

    function addTags(PreprocessedLink $preprocessedLink, array $data)
    {
    }

    function getSynonymousParameters(PreprocessedLink $preprocessedLink, array $data)
    {
    }

    protected function getItemId($url)
    {
        try{
            $id = $this->getItemIdFromParser($url);
        } catch (UrlNotValidException $e){
            throw new CannotProcessException($url);
        }

        return $id;
    }

    protected function getItemIdFromParser($url){
        return $url;
    }

    /**
     * {@inheritDoc}
     */
    public function getParser()
    {
        return $this->parser;
    }

}