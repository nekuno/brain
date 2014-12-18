<?php

namespace Model\User;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Model\Exception\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProfileModel
{
    protected $client;
    protected $metadata;

    public function __construct(Client $client, array $metadata)
    {

        $this->client = $client;
        $this->metadata = $metadata;
    }

    public function getMetadata($language='en')
    {
        $choiceOptions = $this->getChoiceOptions($language);
        $metadata = $this->metadata;

        $publicMetadata = array();
        foreach ($metadata as $name => $values) {
            $publicField = $values;
            if ($values['type'] == 'choice') {
                $publicField['choices'] = $choiceOptions[$name];
            }

            $publicField['label'] = $values['label'][$language];
            unset($publicField['optionsLabel']);
            unset($publicField['tagsLabel']);

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

        $template = "MATCH (user:User)<-[:PROFILE_OF]-(profile:Profile)"
            . " WHERE user.qnoow_id = {id} "
            . " OPTIONAL MATCH (profile)<-[:OPTION_OF]-(option:ProfileOption)"
            . " WITH profile, collect(option) AS options"
            . " OPTIONAL MATCH (profile)<-[:TAGGED]-(tag:ProfileTag)"
            . " RETURN profile, options, collect(tag) as tags"
            . " LIMIT 1;";

        $query = new Query(
            $this->client,
            $template,
            $params
        );

        $result = $query->getResultSet();

        if (count($result) < 1) {
            throw new NotFoundHttpException('Profile not found');
        }

        $row = $result[0];
        $profile = $row['profile']->getProperties();

        foreach ($row['options'] as $option) {
            /* @var $option Node */
            $labels = $option->getLabels();
            foreach ($labels as $index => $label) {
                /* @var $label Label */
                $labelName = $label->getName();
                if ($labelName != 'ProfileOption') {
                    $labelName = lcfirst($labelName);
                    $profile[$labelName] = $option->getProperty('id');
                }

            }
        }

        foreach ($row['tags'] as $tag) {
            /* @var $tag Node */
            $labels = $tag->getLabels();
            foreach ($labels as $label) {
                /* @var $label Label */
                $labelName = $label->getName();
                if ($labelName != 'ProfileTag') {
                    $labelName = lcfirst($labelName);
                    $profile[$labelName][] = $tag->getProperty('name');
                }

            }
        }

        return $profile;
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

        list($userNode, $profileNode) = $this->getUserAndProfileNodesById($id);

        if (!($userNode instanceof Node)) {
            throw new NotFoundHttpException('User not found');
        }

        if ($profileNode instanceof Node) {
            throw new MethodNotAllowedHttpException(array('PUT'), 'Profile already exists');
        }

        $profileNode = $this->client->makeNode();
        $profileNode->save();

        $profileLabel = $this->client->makeLabel('Profile');
        $profileNode->addLabels(array($profileLabel));

        $profileNode->relateTo($userNode, 'PROFILE_OF')->save();

        $this->saveProfileData($profileNode, $data);

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

        list($userNode, $profileNode) = $this->getUserAndProfileNodesById($id);

        if (!($userNode instanceof Node)) {
            throw new NotFoundHttpException('User not found');
        }

        if (!($profileNode instanceof Node)) {
            throw new NotFoundHttpException('Profile not found');
        }

        $this->saveProfileData($profileNode, $data);

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

        $template = "MATCH (user:User)<-[:PROFILE_OF]-(profile:Profile) "
            . " WHERE user.qnoow_id = {id} "
            . " OPTIONAL MATCH (profile)-[r]-() "
            . " DELETE profile, r;";

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
                        case 'string':
                            if (isset($fieldData['min'])) {
                                if (strlen($fieldValue) < $fieldData['min']) {
                                    $fieldErrors[] = 'Must have ' . $fieldData['min'] . ' characters min.';
                                }
                            }
                            if (isset($fieldData['max'])) {
                                if (strlen($fieldValue) > $fieldData['max']) {
                                    $fieldErrors[] = 'Must have ' . $fieldData['max'] . ' characters max.';
                                }
                            }
                            break;

                        case 'date':
                            $date = \DateTime::createFromFormat('Y-m-d', $fieldValue);
                            if (!($date && $date->format('Y-m-d') == $fieldValue)) {
                                $fieldErrors[] = 'Invalid date format, valid format is "Y-m-d".';
                            }
                            break;

                        case 'birthday':
                            $date = \DateTime::createFromFormat('Y-m-d', $fieldValue);
                            if (!($date && $date->format('Y-m-d') == $fieldValue)) {
                                $fieldErrors[] = 'Invalid date format, valid format is "Y-m-d".';
                            } elseif (new \DateTime() < $date) {
                                $fieldErrors[] = 'Invalid birthday date, can not be on the future.';
                            }
                            break;

                        case 'boolean':
                            if ($fieldValue !== true && $fieldValue !== false) {
                                $fieldErrors[] = 'Must be a boolean.';
                            }
                            break;

                        case 'choice':
                            $choices = $fieldData['choices'];
                            if (!in_array($fieldValue, array_keys($choices))) {
                                $fieldErrors[] = sprintf('Option with value \'%s\' is not valid, possible values are \'%s\'', $fieldValue, implode("', '", array_keys($choices)));
                            }
                            break;
                    }
                }
            } else {
                if (isset($fieldData['required']) && $fieldData['required']) {
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

    protected function getChoiceOptions($language)
    {
        $translationField = 'name_'.$language;
        $template = "MATCH (option:ProfileOption) "
            . "RETURN head(filter(x IN labels(option) WHERE x <> 'ProfileOption')) AS type, option.id AS id, option." . $translationField . " AS name "
            . "ORDER BY type;";

        $query = new Query(
            $this->client,
            $template
        );

        $result = $query->getResultSet();
        $choiceOptions = array();
        foreach ($result as $row) {
            $fieldName = lcfirst($row['type']);
            $optionId = $row['id'];
            $optionName = $row['name'];

            $choiceOptions[$fieldName][$optionId] = $optionName;
        }

        return $choiceOptions;
    }

    protected function getUserAndProfileNodesById($id)
    {
        $data = array(
            'id' => (integer)$id,
        );

        $template = "MATCH (user:User)"
            . " WHERE user.qnoow_id = {id} "
            . " OPTIONAL MATCH (user)<-[:PROFILE_OF]-(profile:Profile)"
            . " RETURN user, profile"
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
        $profileNode = $row['profile'];

        return array($userNode, $profileNode);
    }

    protected function saveProfileData(Node $profileNode, array $data)
    {
        $metadata = $this->getMetadata();
        $options = $this->getProfileNodeOptions($profileNode);
        $tags = $this->getProfileNodeTags($profileNode);

        foreach ($data as $fieldName => $fieldValue) {
            if (isset($metadata[$fieldName])) {
                $fieldType = $metadata[$fieldName]['type'];

                switch ($fieldType) {
                    case 'string':
                    case 'boolean':
                    case 'integer':
                    case 'date':
                        $profileNode->setProperty($fieldName, $fieldValue);
                        break;

                    case 'birthday':
                        $profileNode->setProperty('zodiacSign', $this->getZodiacSignNonsenseFromDate($fieldValue));
                        $profileNode->setProperty($fieldName, $fieldValue);
                        break;

                    case 'choice':
                        if (isset($options[$fieldName])) {
                            $options[$fieldName]->delete();
                        }
                        if (!is_null($fieldValue)) {
                            $optionNode = $this->getProfileOptionNode($fieldValue, $fieldName);
                            $optionNode->relateTo($profileNode, 'OPTION_OF')->save();

                        }
                        break;

                    case 'tags':
                        if (isset($tags[$fieldName])) {
                            foreach ($tags[$fieldName] as $tagRelation) {
                                $tagRelation->delete();
                            }
                        }
                        if (!is_null($fieldValue)) {
                            foreach ($fieldValue as $tag) {
                                $tagNode = $this->getProfileTagNode($tag, $fieldName);
                                $tagNode->relateTo($profileNode, 'TAGGED')->save();
                            }
                        }
                        break;
                }
            }
        }

        return $profileNode->save();
    }

    protected function getProfileNodeOptions(Node $profileNode)
    {
        $options = array();
        $optionRelations = $profileNode->getRelationships('OPTION_OF');

        foreach ($optionRelations as $optionRelation) {

            $optionNode = $optionRelation->getStartNode();
            $optionLabels = $optionNode->getLabels();

            foreach ($optionLabels as $optionLabel) {
                $labelName = $optionLabel->getName();
                if ($labelName != 'ProfileOption') {
                    $typeName = lcfirst($labelName);
                    $options[$typeName] = $optionRelation;
                }
            }
        }

        return $options;
    }

    protected function getProfileNodeTags(Node $profileNode)
    {
        $tags = array();
        $tagRelations = $profileNode->getRelationships('TAGGED');

        foreach ($tagRelations as $tagRelation) {
            $tagNode = $tagRelation->getStartNode();
            $tagLabels = $tagNode->getLabels();

            foreach ($tagLabels as $tagLabel) {
                $labelName = $tagLabel->getName();
                if ($labelName != 'ProfileTag') {
                    $typeName = lcfirst($labelName);
                    if (!isset($tags[$typeName])) {
                        $tags[$typeName] = array();
                    }
                    $tags[$typeName][] = $tagRelation;
                }
            }
        }

        return $tags;
    }

    /**
     * @param $id
     * @param $profileType
     * @return Node
     */
    protected function getProfileOptionNode($id, $profileType)
    {
        $profileLabelName = ucfirst($profileType);

        $params = array(
            'id' => $id,
        );

        $template = "MATCH (profileOption:" . $profileLabelName . ")"
            . " WHERE profileOption.id = {id} "
            . " RETURN profileOption "
            . " LIMIT 1;";

        $query = new Query(
            $this->client,
            $template,
            $params
        );

        $result = $query->getResultSet();

        return $result[0]['profileOption'];
    }

    /**
     * @param $tagName
     * @param $tagType
     * @return Node
     * @throws \Everyman\Neo4j\Exception
     */
    protected function getProfileTagNode($tagName, $tagType)
    {
        $tagLabelName = ucfirst($tagType);

        $params = array(
            'name' => $tagName,
        );

        $template = "MATCH (tag:" . $tagLabelName . ")"
            . " WHERE tag.name = {name} "
            . " RETURN tag "
            . " LIMIT 1;";

        $query = new Query(
            $this->client,
            $template,
            $params
        );

        $result = $query->getResultSet();

        if (count($result) < 1) {
            $tagNode = $this->client->makeNode();
            $tagNode->setProperty('name', $tagName);
            $tagNode->save();

            $genericLabel = $this->client->makeLabel('ProfileTag');
            $specificLabel = $this->client->makeLabel($tagLabelName);
            $tagNode->addLabels(array($genericLabel, $specificLabel));
        } else {
            $tagNode = $result[0]['tag'];
        }

        return $tagNode;
    }

    /*
     * Please don't believe in this crap
     */
    protected function getZodiacSignNonsenseFromDate($date)
    {

        $sign = '';
        $birthday = \DateTime::createFromFormat('Y-m-d', $date);

        $zodiac[356] = "Capricorn";
        $zodiac[326] = "Sagittarius";
        $zodiac[296] = "Scorpio";
        $zodiac[266] = "Libra";
        $zodiac[235] = "Virgo";
        $zodiac[203] = "Leo";
        $zodiac[172] = "Cancer";
        $zodiac[140] = "Gemini";
        $zodiac[111] = "Taurus";
        $zodiac[78] = "Aries";
        $zodiac[51] = "Pisces";
        $zodiac[20] = "Aquarius";
        $zodiac[0] = "Capricorn";

        if (!$date) {
            return $sign;
        }

        $dayOfTheYear = $birthday->format('z');
        $isLeapYear = $birthday->format('L');
        if ($isLeapYear && ($dayOfTheYear > 59)) {
            $dayOfTheYear = $dayOfTheYear - 1;
        }

        foreach ($zodiac as $day => $sign) {
            if ($dayOfTheYear > $day) {
                break;
            }
        }

        return $sign;
    }

} 