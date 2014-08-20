<?php


namespace ApiConsumer\LinkProcessor\MetadataParser;

use Symfony\Component\DomCrawler\Crawler;

class FacebookMetadataParser extends MetadataParser
{

    /**
     * @var Crawler
     */
    private $crawler;

    /**
     * @param Crawler $crawler
     */
    public function __construct(Crawler $crawler)
    {

        $this->crawler = $crawler;
    }

    /**
     * @return array
     */
    public function getMetaTags()
    {

        $metaTagsData = $this->crawler->each(
            function (Crawler $node) {

                return array(
                    'property' => $node->attr('property'),
                    'content' => $node->attr('content'),
                );
            }
        );


        $metaTagsData = $this->keysToLowercase($metaTagsData);

        $metaTagsData = $this->removeUseLessTags($metaTagsData);

        return $metaTagsData;
    }

    /**
     * @param $metaTags
     * @return array
     */
    public function extractOgMetadata(array $metaTags)
    {

        $ogMetadata = array();

        foreach ($metaTags as $nodeMetadata) {

            if (null === $nodeMetadata['property']) {
                continue;
            }

            if (strstr($nodeMetadata['property'], 'og:')) {
                $ogMetadata[] = array(ltrim($nodeMetadata['property'], 'og:') => $nodeMetadata['content']);
            }
        }

        return $ogMetadata;
    }

    /**
     * @param array $metaTags
     * @return array
     */
    public function extractTagsFromFacebookMetadata(array $metaTags)
    {

        $tags = array();

        foreach ($metaTags as $nodeMetadata) {

            if (null === $nodeMetadata['property']) {
                continue;
            }

            if (strstr($nodeMetadata['property'], 'article:tag')) {
                $tags[]['name'] = $nodeMetadata['content'];
            }
        }

        return $tags;
    }

    /**
     * @param $metaTagsData
     */
    private function removeUseLessTags($metaTagsData)
    {

        foreach ($metaTagsData as $index => $data) {

            if (false === $this->hasOneUsefulMetaAtLeast($data)) {
                unset($metaTagsData[$index]);
                continue;
            }

            if (null === $data['content']) {
                unset($metaTagsData[$index]);
            }
        }

        return $metaTagsData;
    }

    /**
     * @param $data
     * @return bool
     */
    private function hasOneUsefulMetaAtLeast(array $data)
    {

        return null !== $data['property'];
    }
}
