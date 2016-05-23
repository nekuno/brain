<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace Model\User;


use Model\LinkModel;
use Model\Neo4j\GraphManager;

class ContentFilterModel extends FilterModel
{
    /**
     * @var LinkModel
     */
    protected $linkModel;

    public function __construct(GraphManager $gm, LinkModel $linkModel, array $metadata, array $socialMetadata, $defaultLocale)
    {
        parent::__construct($gm, $metadata, $socialMetadata, $defaultLocale);
        $this->linkModel = $linkModel;
    }


    protected function modifyPublicFieldByType($publicField, $name, $values, $locale)
    {
        $publicField = parent::modifyPublicFieldByType($publicField, $name, $values, $locale);

        $choiceOptions = $this->getChoiceOptions($locale);

        if ($values['type'] === 'multiple_choices') {
            $publicField['choices'] = array();
            if (isset($choiceOptions[$name])) {
                $publicField['choices'] = $choiceOptions[$name];
            }
            if (isset($values['max_choices'])) {
                $publicField['max_choices'] = $values['max_choices'];
            }
        } elseif ($values['type'] === 'tags') {
            $publicField['top'] = $this->getTopContentTags($name);
        }

        return $publicField;
    }

    protected function getChoiceOptions($locale)
    {
        return array(
            'type' => $this->linkModel->getValidTypesLabels($locale)
        );
    }

    protected function getTopContentTags($name)
    {
        return array();
    }

}