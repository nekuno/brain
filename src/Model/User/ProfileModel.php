<?php

namespace Model\User;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Node;

class ProfileModel
{
    protected $client;

    public function __construct(Client $client)
    {

        $this->client = $client;
    }

    public function getMetadata()
    {
        return array_merge(
            $this->getScalarMetadata(),
            $this->getChoiceMetadata(),
            $this->getTagsMetadata()
        );
    }

    /**
     * @param int $id
     * @return array
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
            return array();
        }

        $row = $result[0];
        $profile = $row['profile']->getProperties();

        foreach ($row['options'] as $option) {
            $labels = $option->getLabels();
            foreach ($labels as $index => $label) {
                $labelName = $label->getName();
                if ($labelName != 'ProfileOption') {
                    $profile[$labelName] = $option->getProperty('name');
                }

            }
        }

        foreach ($row['tags'] as $tag) {
            $labels = $tag->getLabels();
            foreach ($labels as $label) {
                $labelName = $label->getName();
                if ($labelName != 'ProfileTag') {
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
     * @throws \Exception
     */
    public function create($id, array $data)
    {
        $this->validate($data);

        list($userNode, $profileNode) =  $this->getUserAndProfileNodesById($id);

        if (($userNode instanceof Node) && !($profileNode instanceof Node)) {
            $profileNode = $this->client->makeNode();
            $profileNode->save();

            $profileLabel = $this->client->makeLabel('Profile');
            $profileNode->addLabels(array($profileLabel));

            $profileNode->relateTo($userNode, 'PROFILE_OF')->save();

            $this->saveProfileData($profileNode, $data);
        } else {
            return 0; //todo throw Exception profile already exists or user not found
        }

        return $profileNode->getId();
    }

    /**
     * @param array $data
     * @return array
     */
    public function update($id, array $data)
    {
        $this->validate($data);

        list($userNode, $profileNode) =  $this->getUserAndProfileNodesById($id);

        if ($profileNode instanceof Node) {
            $this->saveProfileData($profileNode, $data);
        } else {
            return 0; //todo throw Exception profile not found
        }

        return $profileNode->getId();
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
     * @return boolean
     * @throws /Exception
     */
    public function validate(array $data)
    {
        $errors = array();
        $metadata = $this->getMetadata();

        foreach($data as $fieldName => $fieldValue) {
            $fieldErrors = array();
            if (isset($metadata[$fieldName])) {
                $fieldData = $metadata[$fieldName];

                if (isset($fieldData['type'])) {
                    switch ($fieldData['type']) {
                        case 'string':
                            if (strlen($fieldValue) <= $fieldData['min'] && strlen($fieldValue) >= $fieldData['max']) {
                                $error = 'The value must have a length between ' . $fieldData['min'];
                                $error .= ' and ' . $fieldData['max'];
                                $fieldErrors[] = $error;

                            }
                        break;
                    }
                }
            } else {
                $fieldErrors[] = 'Unknown profile field.';
            }

            if (count($fieldErrors)>0) {
                $errors[$fieldName] = $fieldErrors;
            }
        }

        if(count($errors)>0) {
            throw new \Exception();
        }

        return true;
    }

    protected function getScalarMetadata()
    {
        return array(
            'realName' => array(
                'type' => 'string',
                'min' => 0,
                'max' => 255,
            ),
            'picture' => array(
                'type' => 'string',
                'min' => 0,
                'max' => 255,
            ),
            'description' => array(
                'type' => 'string',
                'min' => 0,
                'max' => 1024,
            ),
            'birthday' => array(
                'type' => 'date',
            ),
            'height' => array(
                'type' => 'string',
                'min' => 0,
                'max' => 255,
            ),
            'allergy' => array(
                'type' => 'string',
                'min' => 0,
                'max' => 255,
            ),
            'car' => array(
                'type' => 'boolean',
            ),
            'sons' => array(
                'type' => 'boolean',
            ),
            'languages' => array(
                'type' => 'string',
                'min' => 0,
                'max' => 255,
            ),
            'wantSons' => array(
                'type' => 'boolean',
            ),
            'points' => array(
                'type' => 'integer',
                'min' => 0,
                'max' => 1024,
            ),
        );
    }

    protected function getChoiceMetadata()
    {
        $template = "MATCH (option:ProfileOption) "
            . "RETURN head(filter(x IN labels(option) WHERE x <> 'ProfileOption')) As type, id(option) as id, option.name AS name "
            . "ORDER BY type;";

        $query = new Query(
            $this->client,
            $template
        );

        $result = $query->getResultSet();
        $choiceMetadata = array();
        foreach ($result as $row) {
            $fieldName = lcfirst ($row['type']);
            $optionId = $row['id'];
            $optionName = $row['name'];
            if (!isset($choiceMetadata[$fieldName])) {
                $choiceMetadata[$fieldName] = array(
                    'type' => 'choice',
                    'choices' => array(),
                );
            }
            $choiceMetadata[$fieldName]['choices'][$optionId] = $optionName;
        }

        return $choiceMetadata;
    }

    protected function getTagsMetadata()
    {
        return array(
            'religion' => array(
                'type' => 'tags',
            ),
            'ideology' => array(
                'type' => 'tags',
            ),
            'profession' => array(
                'type' => 'tags',
            ),
            'education' => array(
                'type' => 'tags',
            ),
        );
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

    protected function saveProfileData (Node $profileNode, array $data)
    {
        $metadata = $this->getMetadata();
        $options = $this->getProfileNodeOptions($profileNode);
        $tags = $this->getProfileNodeTags($profileNode);

        foreach($data as $fieldName => $fieldValue) {
            if (isset($metadata[$fieldName])) {
                $fieldType = $metadata[$fieldName]['type'];

                switch ($fieldType) {
                    case 'string':
                    case 'boolean':
                    case 'date':
                    case 'integer':
                        $profileNode->setProperty($fieldName, $fieldValue);

                        break;

                    case 'choice':
                        if (isset($options[$fieldName])) {
                            $options[$fieldName]->delete();
                        }
                        $optionNode = $this->client->getNode($fieldValue);
                        $optionNode->relateTo($profileNode, 'OPTION_OF')->save();

                        break;

                    case 'tags':
                        if (isset($tags[$fieldName])) {
                            foreach ($tags[$fieldName] as $tagRelation) {
                                $tagRelation->delete();
                            }
                        }

                        foreach ($fieldValue as $tag) {
                            $tagNode = $this->getProfileTagNode($tag, $fieldName);
                            $tagNode->relateTo($profileNode, 'TAGGED')->save();
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
            $tagNode = $result[0];
        }

        return $tagNode;
    }

} 