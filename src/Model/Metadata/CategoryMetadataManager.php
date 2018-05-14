<?php

namespace Model\Metadata;

class CategoryMetadataManager extends MetadataManager
{
    public function getMetadata($locale = null) {
        $this->setLocale($locale);

        $publicCategories = array();
        foreach ($this->metadata as $type => $categories) {
            $publicCategories[$type] = $this->getSingleType($categories);
        }

        return $publicCategories;
    }

    protected function getSingleType($categories) {
        $publicCategories = array();
        foreach ($categories as $category) {
            $publicField = $category;
            $publicField['label'] = $this->getLabel($category);
            $publicCategories[] = $publicField;
        }

        return $publicCategories;
    }
}