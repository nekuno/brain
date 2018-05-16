<?php

namespace Model\Metadata;

use Model\Link\LinkManager;

class ContentFilterMetadataManager extends MetadataManager
{
    protected function modifyPublicField($publicField, $name, $values)
    {
        $publicField = parent::modifyPublicField($publicField, $name, $values);

        $choiceOptions = $this->getChoiceOptions();

        switch ($values['type']) {
            case 'multiple_choices':
                $publicField['choices'] = array();
                if (isset($choiceOptions[$name])) {
                    $publicField['choices'] = $choiceOptions[$name];
                }
                if (isset($values['max'])) {
                    $publicField['max'] = $values['max'];
                };
                break;
            case 'tags':
                $publicField['top'] = $this->getTopContentTags($name);
                break;
        }

        return $publicField;
    }

    protected function getChoiceOptions()
    {
        return array(
            'type' => $this->getValidTypesLabels()
        );
    }

    protected function getValidTypesLabels()
    {
        $types = array();
        $keyTypes = LinkManager::getValidTypes();

        foreach ($keyTypes as $type) {
            $types[$type] = $this->translator->trans('types.' . lcfirst($type));
        };

        return $types;
    }

    protected function getTopContentTags($name)
    {
        return array();
    }

}