<?php


namespace ApiConsumer\Scraper\Metadata;

use Symfony\Component\DomCrawler\Crawler;

class BasicMetadata extends Metadata
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
    protected $validMetaRel = array(
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
                    'rel'     => $node->attr('rel'),
                    'name'    => $node->attr('name'),
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
    public function extractDefaultMetadata(array $metaTags)
    {

        $validKeywords = array('author', 'title', 'description', 'canonical');

        $defaultMetadata = array();

        foreach ($metaTags as $nodeMetadata) {

            if (null === $nodeMetadata['name']) {
                continue;
            }

            if (in_array($nodeMetadata['name'], $validKeywords) && null !== $nodeMetadata['content']) {
                $defaultMetadata[] = array($nodeMetadata['name'] => $nodeMetadata['content']);
            }
        }

        return $defaultMetadata;
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

        $tags = $this->filterTagsByNumOfWords($tags);

        return $tags;
    }

    /**
     * @param $data
     * @return bool
     */
    protected function hasOneUsefulMetaAtLeast(array $data)
    {

        return null !== $data['rel'] || null !== $data['name'];
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
    private function isValidRel($data)
    {

        return in_array($data['rel'], $this->validMetaRel);
    }

    /**
     * @param $data
     * @return bool
     */
    private function isValidName($data)
    {

        return in_array($data['name'], $this->validMetaName);
    }
}
