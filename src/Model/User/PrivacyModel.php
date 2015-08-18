<?php

namespace Model\User;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PrivacyModel
{
    protected $client;
    protected $metadata;
    protected $defaultLocale;

    public function __construct(Client $client, array $metadata, $defaultLocale)
    {

        $this->client = $client;
        $this->metadata = $metadata;
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * Returns the metadata for editing the privacy
     * @param null $locale Locale of the metadata
     * @return array
     */
    public function getMetadata($locale = null)
    {
        $locale = $this->getLocale($locale);
        $choiceOptions = $this->getChoiceOptions($locale);

        $publicMetadata = array();
        foreach ($this->metadata as $name => $values) {
            $publicField = $values;
            $publicField['label'] = $values['label'][$locale];

            if ($values['type'] === 'choice') {
                $publicField['choices'] = array();
                if (isset($choiceOptions[$name])) {
                    $publicField['choices'] = $choiceOptions[$name];
                }
            }

            $publicMetadata[$name] = $publicField;
        }

        return $publicMetadata;
    }

    /**
     * @param int $id
     * @return array
     * @throws NotFoundHttpException
     */
    public function getById($id)
    {
        $params = array(
            'id' => (integer)$id,
        );

        $template = "MATCH (user:User)<-[:PRIVACY_OF]-(privacy:Privacy)"
            . " WHERE user.qnoow_id = {id} "
            . " OPTIONAL MATCH (privacy)<-[:OPTION_OF]-(option:PrivacyOption)"
            . " WITH privacy, collect(option) AS options"
            . " RETURN privacy, options"
            . " LIMIT 1;";

        $query = new Query(
            $this->client,
            $template,
            $params
        );

        $result = $query->getResultSet();

        if (count($result) < 1) {
            throw new NotFoundHttpException('Privacy not found');
        }

        /* @var $row Row */
        $row = $result->current();
        /* @var $node Node */
        $node = $row->offsetGet('privacy');
        $privacy = $node->getProperties();

        foreach ($row->offsetGet('options') as $option) {
            /* @var $option Node */
            $labels = $option->getLabels();
            foreach ($labels as $index => $label) {
                /* @var $label Label */
                $labelName = $label->getName();
                if ($labelName != 'PrivacyOption') {
                    $typeName = $this->labelToType($labelName);
                    $privacy[$typeName] = $option->getProperty('id');
                }

            }
        }

        return $privacy;
    }

    /**
     * @param $id
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function create($id, array $data)
    {
        $this->validate($data);

        list($userNode, $privacyNode) = $this->getUserAndPrivacyNodesById($id);

        if (!($userNode instanceof Node)) {
            throw new NotFoundHttpException('User not found');
        }

        if ($privacyNode instanceof Node) {
            throw new MethodNotAllowedHttpException(array('PUT'), 'Privacy already exists');
        }

        $privacyNode = $this->client->makeNode();
        $privacyNode->save();

        $privacyLabel = $this->client->makeLabel('Privacy');
        $privacyNode->addLabels(array($privacyLabel));

        $privacyNode->relateTo($userNode, 'PRIVACY_OF')->save();

        $this->savePrivacyData($privacyNode, $data);

        return $this->getById($id);
    }

    /**
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function update($id, array $data)
    {

        $this->validate($data);

        list($userNode, $privacyNode) = $this->getUserAndPrivacyNodesById($id);

        if (!($userNode instanceof Node)) {
            throw new NotFoundHttpException('User not found');
        }

        if (!($privacyNode instanceof Node)) {
            throw new NotFoundHttpException('Privacy not found');
        }

        $this->savePrivacyData($privacyNode, $data);

        return $this->getById($id);
    }

    /**
     * @param $id
     */
    public function remove($id)
    {
        $params = array(
            'id' => (integer)$id,
        );

        $template = "MATCH (user:User)<-[:PRIVACY_OF]-(privacy:Privacy) "
            . " WHERE user.qnoow_id = {id} "
            . " OPTIONAL MATCH (privacy)-[r]-() "
            . " DELETE privacy, r";

        $query = new Query(
            $this->client,
            $template,
            $params
        );

        $query->getResultSet();
    }

    /**
     * @param array $data
     * @throws ValidationException
     */
    public function validate(array $data)
    {
        $errors = array();
        $metadata = $this->getMetadata();

        foreach ($metadata as $fieldName => $fieldData) {

            $fieldErrors = array();

            if (isset($data[$fieldName])) {

                $fieldValue = $data[$fieldName];

                if (isset($fieldData['type'])) {
                    switch ($fieldData['type']) {
                        case 'choice':
                            $choices = $fieldData['choices'];
                            if (!in_array($fieldValue, array_keys($choices))) {
                                $fieldErrors[] = sprintf('Option with value "%s" is not valid, possible values are "%s"', $fieldValue, implode("', '", array_keys($choices)));
                            }
                            break;
                    }
                }
            } else {

                if (isset($fieldData['required']) && $fieldData['required'] === true) {
                    $fieldErrors[] = 'It\'s required.';
                }
            }

            if (count($fieldErrors) > 0) {
                $errors[$fieldName] = $fieldErrors;
            }
        }

        if (count($errors) > 0) {
            $e = new ValidationException('Validation error');
            $e->setErrors($errors);
            throw $e;
        }
    }

    protected function getChoiceOptions($locale)
    {
        $translationField = 'name_' . $locale;
        $template = "MATCH (option:PrivacyOption) "
            . "RETURN head(filter(x IN labels(option) WHERE x <> 'PrivacyOption')) AS labelName, option.id AS id, option." . $translationField . " AS name "
            . "ORDER BY labelName;";

        $query = new Query(
            $this->client,
            $template
        );

        $result = $query->getResultSet();
        $choiceOptions = array();
        foreach ($result as $row) {
            $typeName = $this->labelToType($row['labelName']);
            $optionId = $row['id'];
            $optionName = $row['name'];

            $choiceOptions[$typeName][$optionId] = $optionName;
        }

        return $choiceOptions;
    }

    protected function getUserAndPrivacyNodesById($id)
    {
        $data = array(
            'id' => (integer)$id,
        );

        $template = "MATCH (user:User)"
            . " WHERE user.qnoow_id = {id} "
            . " OPTIONAL MATCH (user)<-[:PRIVACY_OF]-(privacy:Privacy)"
            . " RETURN user, privacy"
            . " LIMIT 1;";

        $query = new Query(
            $this->client,
            $template,
            $data
        );

        $result = $query->getResultSet();

        if (count($result) < 1) {
            return array(null, null);
        }

        $row = $result[0];
        $userNode = $row['user'];
        $privacyNode = $row['privacy'];

        return array($userNode, $privacyNode);
    }

    protected function savePrivacyData(Node $privacyNode, array $data)
    {
        $metadata = $this->getMetadata();
        $options = $this->getPrivacyNodeOptions($privacyNode);

        foreach ($data as $fieldName => $fieldValue) {

            if (isset($metadata[$fieldName])) {

                $fieldType = $metadata[$fieldName]['type'];
                $editable = isset($metadata[$fieldName]['editable']) ? $metadata[$fieldName]['editable'] === true : true;

                if (!$editable) {
                    continue;
                }

                switch ($fieldType) {
                    case 'choice':
                        if (isset($options[$fieldName])) {
                            $options[$fieldName]->delete();
                        }
                        if (!is_null($fieldValue)) {
                            $optionNode = $this->getPrivacyOptionNode($fieldValue, $fieldName);
                            $optionNode->relateTo($privacyNode, 'OPTION_OF')->save();
                        }
                        break;
                }
            }
        }

        return $privacyNode->save();
    }

    protected function getPrivacyNodeOptions(Node $privacyNode)
    {
        $options = array();
        $optionRelations = $privacyNode->getRelationships('OPTION_OF');

        foreach ($optionRelations as $optionRelation) {

            $optionNode = $optionRelation->getStartNode();
            $optionLabels = $optionNode->getLabels();

            foreach ($optionLabels as $optionLabel) {
                $labelName = $optionLabel->getName();
                if ($labelName != 'PrivacyOption') {
                    $typeName = $this->labelToType($labelName);
                    $options[$typeName] = $optionRelation;
                }
            }
        }

        return $options;
    }

    /**
     * @param $id
     * @param $privacyType
     * @return Node
     */
    protected function getPrivacyOptionNode($id, $privacyType)
    {
        $privacyLabelName = $this->typeToLabel($privacyType);

        $params = array(
            'id' => $id,
        );

        $template = "MATCH (privacyOption:" . $privacyLabelName . ")"
            . " WHERE privacyOption.id = {id} "
            . " RETURN privacyOption "
            . " LIMIT 1;";

        $query = new Query(
            $this->client,
            $template,
            $params
        );

        $result = $query->getResultSet();

        return $result[0]['privacyOption'];
    }

    protected function getLocale($locale)
    {

        if (!$locale || !in_array($locale, array('en', 'es'))) {
            $locale = $this->defaultLocale;
        }

        return $locale;
    }

    protected function labelToType($labelName)
    {

        return lcfirst(str_replace('PrivacyOption', '', $labelName));
    }

    protected function typeToLabel($typeName)
    {

        return 'PrivacyOption' . ucfirst($typeName);
    }

}