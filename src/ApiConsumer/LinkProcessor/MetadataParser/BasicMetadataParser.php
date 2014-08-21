<?php


namespace ApiConsumer\LinkProcessor\MetadataParser;

class BasicMetadataParser extends MetadataParser implements MetadataParserInterface
{

    /**
     * @var array
     */
    protected $validNameAttributeValues = array(
        'description',
        'author',
        'keywords',
        'title'
    );

    /**
     * @var array
     */
    protected $validRelAttributeValues = array(
        'author',
        'description'
    );

    /**
     *{ @inheritdoc }
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
     * { @inheritdoc }
     */
    public function extractTags(array $metaTags)
    {

        $tags = array();

        foreach ($metaTags as $nodeMetadata) {
            if ("keywords" === $nodeMetadata['name'] && null !== $nodeMetadata['content']) {
                $tags = explode(',', $nodeMetadata['content']);
            }
        }

        $tags = $this->removeTagsSorterThanNWords($tags);

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

        $metadataTags = $this->keysAndValuesNotContentToLowercase($metadataTags);

        $metadataTags = $this->removeUseLessTags($metadataTags);

        return $metadataTags;
    }

}
