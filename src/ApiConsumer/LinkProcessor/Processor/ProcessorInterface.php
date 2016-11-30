<?php

namespace ApiConsumer\LinkProcessor\Processor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\Exception\UrlChangedException;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\SynonymousParameters;
use Symfony\Component\DomCrawler\Crawler;

interface ProcessorInterface
{
    /**
     * @param PreprocessedLink $preprocessedLink
     * @return array|Crawler
     * @throws CannotProcessException|UrlChangedException
     */
    function requestItem(PreprocessedLink $preprocessedLink);

    function hydrateLink(PreprocessedLink $preprocessedLink, array $data);

    function addTags(PreprocessedLink $preprocessedLink, array $data);

    function getSynonymousParameters(PreprocessedLink $preprocessedLink, array $data);

    function getImages(array $data);

} 