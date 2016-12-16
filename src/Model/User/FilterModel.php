<?php

namespace Model\User;


use Model\Neo4j\GraphManager;

abstract class FilterModel
{
    protected $gm;
    protected $metadata;
    protected $socialMetadata;
    protected $defaultLocale;

    public function __construct(GraphManager $gm, array $metadata, array $socialMetadata, $defaultLocale)
    {
        $this->gm = $gm;
        $this->metadata = $metadata;
        $this->socialMetadata = $socialMetadata;
        $this->defaultLocale = $defaultLocale;
    }

    public function getLocale($locale)
    {

        if (!$locale || !in_array($locale, array('en', 'es'))) {
            $locale = $this->defaultLocale;
        }

        return $locale;
    }

    /**
     * Returns the metadata for creating search filters
     * @param null $locale
     * @return array
     */
    public function getFilters($locale = null)
    {
        $locale = $this->getLocale($locale);
        $metadata = $this->getMetadata($locale, false);
        $labels = array();
        foreach ($metadata as $key => &$item) {
            if (isset($item['labelFilter'])) {
                $item['label'] = $item['labelFilter'][$locale];
                unset($item['labelFilter']);
            }
            if (isset($item['filterable']) && $item['filterable'] === false) {
                unset($metadata[$key]);
            } else {
                $labels[] = $item['label'];
            }
        }

        if (!empty($labels)) {
            array_multisort($labels, SORT_ASC, $metadata);
        }

        return $metadata;
    }

    /**
     * Returns the metadata for filtering users
     * @param null $locale Locale of the metadata
     * @param bool $filter Filter non public attributes
     * @return array
     */
    public function getMetadata($locale = null, $filter = true)
    {
        $locale = $this->getLocale($locale);

        $publicMetadata = array();
        foreach ($this->metadata as $name => $values) {
            $publicField = $values;
            $publicField['label'] = $values['label'][$locale];

            $publicField = $this->modifyPublicFieldByType($publicField, $name, $values, $locale);

            $publicMetadata[$name] = $publicField;
        }

        if ($filter) {
            foreach ($publicMetadata as &$item) {
                if (isset($item['labelFilter'])) {
                    unset($item['labelFilter']);
                }
                if (isset($item['filterable'])) {
                    unset($item['filterable']);
                }
            }
        }

        return $publicMetadata;
    }

    public function getSocialFilters($locale)
    {
        $locale = $this->getLocale($locale);
        $metadata = $this->getSocialMetadata($locale);
        $labels = array();
        foreach ($metadata as $key => &$item) {
            if (isset($item['labelFilter'])) {
                $item['label'] = $item['labelFilter'][$locale];
                unset($item['labelFilter']);
            }
            if (isset($item['filterable']) && $item['filterable'] === false) {
                unset($metadata[$key]);
            } else {
                $labels[] = $item['label'];
            }
        }

        if (!empty($labels)) {
            array_multisort($labels, SORT_ASC, $metadata);
        }

        return $metadata;
    }

    public function getSocialMetadata($locale)
    {
        $locale = $this->getLocale($locale);

        $publicMetadata = array();
        foreach ($this->socialMetadata as $name => $values) {
            $publicField = $values;
            $publicField['label'] = $values['label'][$locale];

            $publicField = $this->modifyPublicFieldByType($publicField, $name, $values, $locale);

            $publicMetadata[$name] = $publicField;
        }

        foreach ($publicMetadata as &$item) {
            if (isset($item['labelFilter'])) {
                unset($item['labelFilter']);
            }
            if (isset($item['filterable'])) {
                unset($item['filterable']);
            }
        }

        return $publicMetadata;
    }

    protected function modifyPublicFieldByType($publicField, $name, $values, $locale)
    {
        return $publicField;
    }
}