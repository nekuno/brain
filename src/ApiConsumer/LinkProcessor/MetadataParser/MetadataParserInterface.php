<?php
/**
 * @author adrian.web.dev@gmail.com
 */

namespace ApiConsumer\LinkProcessor\MetadataParser;


interface MetadataParserInterface {

    /**
     * Extracts possible tags from keywords an other meta tags
     *
     * @param array $metaTags
     * @return mixed
     */
    public function extractTags(array $metaTags);

    /**
     * Parse meta tags and extracts basic data such as title, description and canonical URL
     *
     * @param array $metaTags
     * @return mixed
     */
    public function extractMetadata(array $metaTags);

}
