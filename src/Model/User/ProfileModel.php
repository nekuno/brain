<?php

namespace Model\User;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

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

        $data = array(
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
            $data
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
     * @param array $data
     * @return array
     */
    public function create(array $data)
    {

        return array();
    }

    /**
     * @param array $data
     * @return array
     */
    public function update(array $data)
    {

        return array();
    }

    /**
     * @param array $data
     * @return array
     */
    public function remove(array $data)
    {

        return array();
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
            $fieldName = $row['type'];
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
} 