<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Model\User;


class UserFilterModel extends FilterModel
{

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

}