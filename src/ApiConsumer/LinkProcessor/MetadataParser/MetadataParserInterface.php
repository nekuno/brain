<?php

namespace ApiConsumer\LinkProcessor\MetadataParser;


use Symfony\Component\DomCrawler\Crawler;

/**
 * Interface MetadataParserInterface
 * @package ApiConsumer\LinkProcessor\MetadataParser
 */
interface MetadataParserInterface {

    /**
     * Extracts possible tags from keywords an other meta tags
     *
     * @param Crawler $crawler
     * @return mixed
     */
    public function extractTags(Crawler $crawler);

    /**
     * Parse meta tags and extracts basic data such as title, description and canonical URL
     *
     * @param Crawler $crawler
     * @return mixed
     */
    public function extractMetadata(Crawler $crawler);

}
