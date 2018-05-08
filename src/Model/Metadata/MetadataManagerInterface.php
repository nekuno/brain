<?php

namespace Model\Metadata;

interface MetadataManagerInterface
{
    public function getMetadata($locale = null);
}