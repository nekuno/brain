<?php


namespace ApiConsumer\LinkProcessor\MetadataParser;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Class BasicMetadataParser
 * @package ApiConsumer\LinkProcessor\MetadataParser
 */
class BasicMetadataParser implements MetadataParserInterface
{

    const MAX_WORDS = 2;

    /**
     *{ @inheritdoc }
     */
    public function extractMetadata(Crawler $crawler)
    {

        $htmlTagsWithValidMetadata = array();

        $htmlTagsWithValidMetadata['author'] = $this->getMetaAuthorText($crawler);
        $htmlTagsWithValidMetadata['title'] = $this->getTitleTagText($crawler);
        $htmlTagsWithValidMetadata['description'] = $this->getMetaDescriptionText($crawler);

        return $htmlTagsWithValidMetadata;
    }

    /**
     * @param Crawler $crawler
     * @return null|string
     */
    private function getMetaAuthorText(Crawler $crawler)
    {

        try {
            $author = $crawler->filterXPath('//meta[@name="author"]')->attr('content');
        } catch (\InvalidArgumentException $e) {
            $author = null;
        }

        return '' !== trim($author) ? $author : null;
    }

    /**
     * @param Crawler $crawler
     * @return null|string
     */
    private function getTitleTagText(Crawler $crawler)
    {

        try {
            $title = $crawler->filterXPath('//title')->text();
        } catch (\InvalidArgumentException $e) {
            $title = null;
        }

        return '' !== trim($title) ? $title : null;
    }

    /**
     * @param Crawler $crawler
     * @return null|string
     */
    private function getMetaDescriptionText(Crawler $crawler)
    {

        try {
            $description = $crawler->filterXPath('//meta[@name="description"]')->attr('content');
        } catch (\InvalidArgumentException $e) {
            $description = null;
        }

        return '' !== trim($description) ? $description : null;
    }

    /**
     * { @inheritdoc }
     */
    public function extractTags(Crawler $crawler)
    {

        $tags = array();

        try {
            $keywords = $crawler->filterXPath('//meta[@name="keywords"]')->attr('content');
        } catch (\InvalidArgumentException $e) {
            return $tags;
        }

        if ('' === trim($keywords)) {
            return $tags;
        }

        $keywords = explode(',', $keywords);

        array_walk(
            $keywords,
            function (&$keyword, $index) {

                $keyword = strtolower(trim($keyword));
            }
        );

        foreach ($keywords as $keyword) {
            if (false === $this->isLongTag($keyword)) {
                $tags[]['name'] = $keyword;
            }
        }

        return $tags;
    }

    private function isLongTag($tag)
    {

        return str_word_count($tag) > self::MAX_WORDS;
    }
}
