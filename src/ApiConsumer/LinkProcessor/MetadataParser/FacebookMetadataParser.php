<?php


namespace ApiConsumer\LinkProcessor\MetadataParser;

class FacebookMetadataParser extends MetadataParser implements MetadataParserInterface
{

    /**
     * { @inheritdoc }
     */
    public function extractMetadata(array $metaTags)
    {

        $this->sanitizeMetadataTags($metaTags);

        $metadata = array();

        foreach ($metaTags as $nodeMetadata) {

            if (null === $nodeMetadata['property']) {
                continue;
            }

            if (strstr($nodeMetadata['property'], 'og:')) {
                $metadata[] = array(ltrim($nodeMetadata['property'], 'og:') => $nodeMetadata['content']);
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

            if (null === $nodeMetadata['property']) {
                continue;
            }

            if (strstr($nodeMetadata['property'], 'article:tag')) {
                $tags[]['name'] = $nodeMetadata['content'];
            }
        }

        return $tags;
    }

}
