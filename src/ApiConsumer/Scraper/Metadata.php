<?php

namespace ApiConsumer\Scraper;

use Symfony\Component\DomCrawler\Crawler;

class Metadata
{

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
                    'rel'      => $node->attr('rel'),
                    'name'     => $node->attr('name'),
                    'property' => $node->attr('property'),
                    'content'  => $node->attr('content'),
                );
            }
        );

        $this->trimUseLessTags($metaTagsData);

        return $metaTagsData;
    }

    /**
     * @param $metadata
     */
    protected function trimUseLessTags($metadata)
    {

        foreach ($metadata as $index => $data) {

            if ($this->haveOneUsefulMetaAtLeast($data)) {
                unset($metadata[$index]);
            }

            if (null !== $data['name'] && !$this->isValidName($data)) {
                unset($metadata[$index]);
            }

            if (null !== $data['rel'] && !$this->isValidRel($data)) {
                unset($metadata[$index]);
            }

            if (null === $data['content']) {
                unset($metadata[$index]);
            }
        }

        return $metadata;
    }

    /**
     * @param $data
     * @return bool
     */
    protected function haveOneUsefulMetaAtLeast($data)
    {

        return null === $data['rel'] && null === $data['name'] && null === $data['property'];
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
     * @param $data
     * @return bool
     */
    protected function isValidRel($data)
    {

        return in_array($data['rel'], $this->validMetaRel);
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
