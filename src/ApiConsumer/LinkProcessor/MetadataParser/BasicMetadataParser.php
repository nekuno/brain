<?php


namespace ApiConsumer\LinkProcessor\MetadataParser;

class BasicMetadataParser extends MetadataParser
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
     * @param $metaTags
     * @return array
     */
    public function extractMetadata(array $metaTags)
    {

        $this->sanitizeMetadataTags($metaTags);

        $validKeywords = array('author', 'title', 'description', 'canonical');

        $metadata = array();

        foreach ($metaTags as $nodeMetadata) {

            if (null === $nodeMetadata['name']) {
                continue;
            }

            if (in_array($nodeMetadata['name'], $validKeywords) && null !== $nodeMetadata['content']) {
                $metadata[] = array($nodeMetadata['name'] => $nodeMetadata['content']);
            }
        }

        return $metadata;
    }

    /**
     * @param array $metaTags
     * @return array
     */
    public function extractMetadataTags(array $metaTags)
    {

        $tags = array();

        foreach ($metaTags as $nodeMetadata) {
            if ("keywords" === $nodeMetadata['name'] && null !== $nodeMetadata['content']) {
                $tags = explode(',', $nodeMetadata['content']);
            }
        }

        $tags = $this->filterTagsByNumOfWords($tags);

        $resultTags = array();
        foreach ($tags as $tag) {
            $resultTags[]['name'] = $tag;
        }

        return $resultTags;
    }


    /**
     * @param $metadataTags
     * @return mixed
     */
    public function sanitizeMetadataTags($metadataTags)
    {

        $metadataTags = $this->keysToLowercase($metadataTags);

        $metadataTags = $this->removeUseLessTags($metadataTags);

        return $metadataTags;
    }

}
