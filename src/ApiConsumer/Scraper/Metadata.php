<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 9/07/14
 * Time: 11:35
 */

namespace ApiConsumer\Scraper;

use Symfony\Component\DomCrawler\Crawler;

class Metadata
{

    private $crawler;

    private $validMetaRel = array(
        'author',
        'description'
    );

    protected $validMetaName = array(
        'description',
        'author',
        'keywords',
        'title'
    );

    public function __construct(Crawler $crawler)
    {

        $this->crawler = $crawler;

    }

    public function getMetaTags()
    {

        $metaTagsData = $this->crawler->each(function (Crawler $node) {

            return array(
                'rel'      => $node->attr('rel'),
                'name'     => $node->attr('name'),
                'property' => $node->attr('property'),
                'content'  => $node->attr('content'),
            );

        });

        $this->trimUseLessTags($metaTagsData);

        return $metaTagsData;
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
     * @param $metadata
     */
    protected function trimUseLessTags($metadata)
    {

        foreach ($metadata as $index => $data) {

            if (null === $data['rel'] && null === $data['name'] && null === $data['property']) {
                unset($metadata[$index]);
            }

            if (null !== $data['name'] && !in_array($data['name'], $this->validMetaName)) {
                unset($metadata[$index]);
            }

            if (null !== $data['rel'] && !in_array($data['rel'], $this->validMetaRel)) {
                unset($metadata[$index]);
            }

            if (null === $data['content']) {
                unset($metadata[$index]);
            }

        }

        return $metadata;

    }

}
