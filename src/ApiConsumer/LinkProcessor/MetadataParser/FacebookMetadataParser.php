<?php


namespace ApiConsumer\LinkProcessor\MetadataParser;

class FacebookMetadataParser extends MetadataParser implements MetadataParserInterface
{

    /**
     * @param $metaTags
     * @return array
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
     * @param array $metaTags
     * @return array
     */
    public function extractMetadataTags(array $metaTags)
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
