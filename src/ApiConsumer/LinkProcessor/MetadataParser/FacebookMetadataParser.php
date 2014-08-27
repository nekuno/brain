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

    /**
     * { @inheritdoc }
     */
    public function extractTags(Crawler $crawler)
    {

        $tags = $crawler->filterXPath('//meta[@property="article:tag"]');

        $scrapedTags = $tags->each(
            function (Crawler $node) {

                $tag = $node->attr('content');

                return array('name' => trim(strtolower($tag)));
            }
        );

        if (!count($scrapedTags)) {
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
