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

        $metadata = array();

        foreach ($metaTags as $nodeMetadata) {

            if (null === $nodeMetadata['name']) {
                continue;
            }else{
                $metadata[] = $nodeMetadata;
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

}
