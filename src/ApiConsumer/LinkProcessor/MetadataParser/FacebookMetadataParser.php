<?php


namespace ApiConsumer\LinkProcessor\MetadataParser;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Class FacebookMetadataParser
 * @package ApiConsumer\LinkProcessor\MetadataParser
 */
class FacebookMetadataParser implements MetadataParserInterface
{

    /**
     *
     */
    const MAX_WORDS = 2;

    /**
     * { @inheritdoc }
     */
    public function extractMetadata(Crawler $crawler)
    {
        $facebookMetadata = array();

        $facebookMetadata['title'] = $this->getOgTitleText($crawler);
        $facebookMetadata['description'] = $this->getOgDescriptionText($crawler);
        $facebookMetadata['language'] = $this->getLanguage($crawler);
        $facebookMetadata['thumbnail'] = $this->getOgImage($crawler);

        return $facebookMetadata;
    }

    /**
     * @param Crawler $crawler
     * @return null|string
     */
    private function getOgTitleText(Crawler $crawler)
    {
        try {
            $title = $crawler->filterXPath('//meta[@property="og:title"]')->attr('content');
        } catch (\InvalidArgumentException $e) {
            $title = null;
        }

        return '' !== trim($title) ? $title : null;
    }

    /**
     * @param Crawler $crawler
     * @return null|string
     */
    private function getOgDescriptionText(Crawler $crawler)
    {
        try {
            $description = $crawler->filterXPath('//meta[@property="og:description"]')->attr('content');
        } catch (\InvalidArgumentException $e) {
            $description = null;
        }

        return '' !== trim($description) ? $description : null;
    }

    private function getOgImage(Crawler $crawler)
    {
        try {
            $image = $crawler->filterXPath('//meta[@property="og:image"]')->attr('content');
        } catch (\InvalidArgumentException $e) {
            $image = null;
        }

        return '' !== trim($image) ? $image : null;
    }

    /**
     * @param Crawler $crawler
     * @return null|string
     */
    private function getLanguage(Crawler $crawler)
    {
        try {
            $language = strtolower(substr($crawler->filterXPath('//meta[@property="og:locale"]')->attr('content'), 0, 2));
        } catch (\InvalidArgumentException $e) {
            $language = null;
        }

        return '' !== trim($language) ? $language : null;
    }

    /**
     * { @inheritdoc }
     */
    public function extractTags(Crawler $crawler)
    {
        $tags = $crawler->filterXPath('//meta[@property="article:tag"]');

        $scrapedTags = $tags->each(
            function (Crawler $node) {

                $tag = $node->attr('content');

                return array('name' => trim(mb_strtolower($tag, 'UTF-8')));
            }
        );

        if (!is_array($scrapedTags) || !count($scrapedTags)) {
            return array();
        }

        $this->filterTags($scrapedTags);

        return null !== $scrapedTags ? $scrapedTags : array();
    }

    /**
     * @param $scrapedTags
     */
    private function filterTags(array &$scrapedTags)
    {
        foreach ($scrapedTags as $index => $tag) {
            if (null === $tag['name'] || '' === $tag['name'] || str_word_count($tag['name']) > self::MAX_WORDS) {
                unset($scrapedTags[$index]);
            }
        }
    }

}
