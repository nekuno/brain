<?php

namespace ApiConsumer\LinkProcessor\Processor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\SynonymousParameters;
use Symfony\Component\DomCrawler\Crawler;

interface ProcessorInterface
{
    /**
     * @param PreprocessedLink $preprocessedLink
     * @return array|Crawler
     * @throws CannotProcessException
     */
    function requestItem(PreprocessedLink $preprocessedLink);

    function hydrateLink(PreprocessedLink $preprocessedLink, array $data);

    function addTags(PreprocessedLink $preprocessedLink, array $data);

    /**
     * @param PreprocessedLink $preprocessedLink
     * @param array $data
     */
    function getSynonymousParameters(PreprocessedLink $preprocessedLink, array $data);

} 