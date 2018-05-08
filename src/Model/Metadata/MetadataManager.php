<?php

namespace Model\Metadata;

use Symfony\Component\Translation\TranslatorInterface;

class MetadataManager implements MetadataManagerInterface
{
    protected $translator;
    protected $metadataUtilities;
    protected $metadata;
    protected $defaultLocale;

    static public $validLocales = array('en', 'es');

    public function __construct(TranslatorInterface $translator, MetadataUtilities $metadataUtilities, array $metadata, $defaultLocale)
    {
        $this->translator = $translator;
        $this->metadataUtilities = $metadataUtilities;
        $this->metadata = $metadata;
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * @param null $locale Locale of the metadata
     * @return array
     */
    public function getMetadata($locale = null)
    {
        $this->setLocale($locale);

        $metadata = array();
        foreach ($this->metadata as $name => $values) {
            $publicField = $values;
            $publicField['label'] = $this->getLabel($values);

            $publicField = $this->modifyPublicField($publicField, $name, $values);

            $metadata[$name] = $publicField;
        }

        $metadata = $this->orderByLabel($metadata);

        return $metadata;
    }

    protected function setLocale($locale)
    {
        $locale = $this->sanitizeLocale($locale);
        $this->translator->setLocale($locale);
    }

    protected function sanitizeLocale($locale)
    {
        if (!$locale || !in_array($locale, self::$validLocales)) {
            $locale = $this->defaultLocale;
        }

        return $locale;
    }

    protected function getLabel($field)
    {
        $labelField = isset($field['label']) ? $field['label'] : null;

        $locale = $this->translator->getLocale();
        return $labelField ? $this->metadataUtilities->getLocaleString($labelField, $locale) : null;
    }

    protected function orderByLabel($metadata) {
        $labels = $this->getLabels($metadata);

        if (!empty($labels)) {
            array_multisort($labels, SORT_ASC, $metadata);
        }

        return $metadata;
    }

    protected function getLabels($metadata) {
        $labels = array();
        foreach ($metadata as $key => &$item) {
            $labels[] = $item['label'];
        }

        return $labels;
    }

    protected function modifyPublicField($publicField, $name, $values)
    {
        return $publicField;
    }
}