<?php

namespace ApiConsumer\Scraper;

use Symfony\Component\DomCrawler\Crawler;

class Metadata
{

    /**
     * @var array
     */
    protected $validMetaName = array(
        'description',
        'author',
        'keywords',
        'title'
    );

    /**
     * @var array
     */
    private $validMetaRel = array(
        'author',
        'description'
    );

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
                    'rel' => $node->attr('rel'),
                    'name' => $node->attr('name'),
                    'property' => $node->attr('property'),
                    'content' => $node->attr('content'),
                );
            }
        );


        $metaTagsData = $this->keysToLowercase($metaTagsData);

        $metaTagsData = $this->trimUseLessTags($metaTagsData);

        return $metaTagsData;
    }

    protected function keysToLowercase($metaTagsData)
    {

        foreach ($metaTagsData as &$tag) {

            foreach ($tag as $type => $value) {
                if ($type !== "content" && null !== $value) {
                    $tag[$type] = strtolower($value);
                }
            }
        }

        return $metaTagsData;
    }

    /**
     * @param $metaTagsData
     */
    protected function trimUseLessTags($metaTagsData)
    {

        foreach ($metaTagsData as $index => $data) {

            if (false === $this->hasOneUsefulMetaAtLeast($data)) {
                unset($metaTagsData[$index]);
                continue;
            }

            if (null !== $data['rel'] && !$this->isValidRel($data)) {
                unset($metaTagsData[$index]);
                continue;
            }

            if (null !== $data['name'] && !$this->isValidName($data)) {
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
    protected function hasOneUsefulMetaAtLeast(array $data)
    {

        return null !== $data['rel'] || null !== $data['name'] || null !== $data['property'];
    }

    /**
     * @param $data
     * @return bool
     */
    protected function isValidRel($data)
    {

        return in_array($data['rel'], $this->validMetaRel);
    }

    /**
     * @param $data
     * @return bool
     */
    protected function isValidName($data)
    {

        return in_array($data['name'], $this->validMetaName);
    }

    /**
     * @param $metaTags
     * @return array
     */
    public function extractDefaultMetadata(array $metaTags)
    {

        $validKeywords = array('author', 'title', 'description', 'canonical');

        $defaultMetadata = array();

        foreach ($metaTags as $nodeMetadata) {
            if (null !== $nodeMetadata['name']) {
                if (in_array($nodeMetadata['name'], $validKeywords) && null !== $nodeMetadata['content']) {
                    $defaultMetadata[] = array($nodeMetadata['name'] => $nodeMetadata['content']);
                }
            }
        }

        return $defaultMetadata;
    }

    /**
     * @param $metaTags
     * @return array
     */
    public function extractOgMetadata(array $metaTags)
    {

        $ogMetadata = array();

        foreach ($metaTags as $nodeMetadata) {
            if (null !== $nodeMetadata['property']) {
                if (strstr($nodeMetadata['property'], 'og:')) {
                    $ogMetadata[] = array(ltrim($nodeMetadata['property'], 'og:') => $nodeMetadata['content']);
                }
            }
        }

        return $ogMetadata;
    }

    /**
     * @param array $metaTags
     * @return array
     */
    public function extractTagsFromKeywords(array $metaTags)
    {

        $tags = array();

        foreach ($metaTags as $nodeMetadata) {
            if ("keywords" === $nodeMetadata['name'] && null !== $nodeMetadata['content']) {
                $tags = explode(',', $nodeMetadata['content']);
            }
        }

        $tags = $this->filterTagsByLength($tags);

        return $tags;
    }

    public function extractTagsFromFacebookMetadata(array $metaTags)
    {
        $tags = array();

        foreach ($metaTags as $nodeMetadata) {
            if (null !== $nodeMetadata['property']) {
                if (strstr($nodeMetadata['property'], 'article:tag')) {
                    $tags[] = $nodeMetadata['content'];
                }
            }
        }

        return $tags;
    }

    /**
     * @param $tags
     * @param $wordLimit
     * @return string
     */
    private function filterTagsByLength($tags, $wordLimit = 2)
    {
        foreach ($tags as $index => &$tag) {
            $tag = strtolower($tag);

            $words = explode(' ', $tag);
            if (count($words) > $wordLimit) {
                unset($tags[$index]);
            }
        }

        return $tags;
    }

    /**
     * @param $scrapedData
     * @param $link
     * @return mixed
     */
    public function mergeLinkMetadata(array $scrapedData, array $link)
    {

        foreach ($scrapedData as $meta) {
            if (array_key_exists('title', $meta) && null !== $meta['title']) {
                $link['title'] = $meta['title'];
            }

            if (array_key_exists('description', $meta) && null !== $meta['description']) {
                $link['description'] = $meta['description'];
            }

            if (array_key_exists('canonical', $meta) && null !== $meta['canonical']) {
                $link['url'] = $meta['canonical'];
            }
        }

        return $link;
    }
}
