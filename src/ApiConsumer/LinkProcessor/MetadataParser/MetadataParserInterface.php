<?php
/**
 * @author adrian.web.dev@gmail.com
 */

namespace ApiConsumer\LinkProcessor\MetadataParser;


interface MetadataParserInterface {

    public function extractMetadataTags(array $metaTags);

    public function extractMetadata(array $metaTags);
    
}
