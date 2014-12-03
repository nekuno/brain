<?php

namespace Model\Neo4j;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class ProfileOptions implements LoggerAwareInterface
{
    /**
     * @var \Everyman\Neo4j\Client
     */
    protected $client;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param \Everyman\Neo4j\Client $client
     */
    public function __construct(Client $client)
    {

        $this->client = $client;
    }

    /**
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {

        $this->logger = $logger;
    }

    /**
     * @throws \Exception
     * @return integer New ProfileOptions added
     */
    public function load()
    {

        $new = 0;

        $options = array(
            'Alcohol' => array(
                array(
                    'id' => 'yes',
                    'name' => 'Yes',
                ),
                array(
                    'id' => 'no',
                    'name' => 'No',
                ),
                array(
                    'id' => 'occasionally',
                    'name' => 'Occasionally',
                ),
                array(
                    'id' => 'socially-on-parties',
                    'name' => 'Socially/On parties',
                ),
            ),
            'CivilStatus' => array(
                array(
                    'id' => 'single',
                    'name' => 'Single',
                ),
                array(
                    'id' => 'married',
                    'name' => 'Married',
                ),
                array(
                    'id' => 'open-relationship',
                    'name' => 'Open relationship',
                ),
                array(
                    'id' => 'dating-someone',
                    'name' => 'Dating someone',
                ),
            ),
            'Complexion' => array(
                array(
                    'id' => 'slim',
                    'name' => 'Slim',
                ),
                array(
                    'id' => 'normal',
                    'name' => 'Normal',
                ),
                array(
                    'id' => 'fat',
                    'name' => 'Fat',
                ),
            ),
            'DateAlcohol' => array(
                array(
                    'id' => 'yes',
                    'name' => 'Yes',
                ),
                array(
                    'id' => 'no',
                    'name' => 'No',
                ),
                array(
                    'id' => 'maybe',
                    'name' => 'Maybe',
                ),
            ),
            'DateChildren' => array(
                array(
                    'id' => 'yes',
                    'name' => 'Yes',
                ),
                array(
                    'id' => 'no',
                    'name' => 'No',
                ),
                array(
                    'id' => 'maybe',
                    'name' => 'Maybe',
                ),
            ),
            'DateComplexion' => array(
                array(
                    'id' => 'yes',
                    'name' => 'Yes',
                ),
                array(
                    'id' => 'no',
                    'name' => 'No',
                ),
                array(
                    'id' => 'maybe',
                    'name' => 'Maybe',
                ),
            ),
            'DateHandicap' => array(
                array(
                    'id' => 'yes',
                    'name' => 'Yes',
                ),
                array(
                    'id' => 'no',
                    'name' => 'No',
                ),
                array(
                    'id' => 'maybe',
                    'name' => 'Maybe',
                ),
            ),
            'DateReligion' => array(
                array(
                    'id' => 'yes',
                    'name' => 'Yes',
                ),
                array(
                    'id' => 'no',
                    'name' => 'No',
                ),
                array(
                    'id' => 'maybe',
                    'name' => 'Maybe',
                ),
            ),
            'DateSmoker' => array(
                array(
                    'id' => 'yes',
                    'name' => 'Yes',
                ),
                array(
                    'id' => 'no',
                    'name' => 'No',
                ),
                array(
                    'id' => 'maybe',
                    'name' => 'Maybe',
                ),
            ),
            'Diet' => array(
                array(
                    'id' => 'vegetarian',
                    'name' => 'Vegetarian',
                ),
                array(
                    'id' => 'vegan',
                    'name' => 'Vegan',
                ),
                array(
                    'id' => 'other',
                    'name' => 'Other',
                ),
            ),
            'Drugs' => array(
                array(
                    'id' => 'yes',
                    'name' => 'Yes',
                ),
                array(
                    'id' => 'no',
                    'name' => 'No',
                ),
                array(
                    'id' => 'occasionally',
                    'name' => 'Occasionally',
                ),
                array(
                    'id' => 'socially-on-parties',
                    'name' => 'Socially/On parties',
                ),
            ),
            'EthnicGroup' => array(
                array(
                    'id' => 'oriental',
                    'name' => 'Oriental',
                ),
                array(
                    'id' => 'afro-american',
                    'name' => 'Afro-American',
                ),
                array(
                    'id' => 'caucasian',
                    'name' => 'Caucasian',
                ),
            ),
            'EyeColor' => array(
                array(
                    'id' => 'blue',
                    'name' => 'Blue',
                ),
                array(
                    'id' => 'brown',
                    'name' => 'Brown',
                ),
                array(
                    'id' => 'black',
                    'name' => 'Black',
                ),
                array(
                    'id' => 'green',
                    'name' => 'Green',
                ),
            ),
            'Gender' => array(
                array(
                    'id' => 'male',
                    'name' => 'Male',
                ),
                array(
                    'id' => 'female',
                    'name' => 'Female',
                ),
                array(
                    'id' => 'other',
                    'name' => 'Other',
                ),
            ),
            'HairColor' => array(
                array(
                    'id' => 'black',
                    'name' => 'Black',
                ),
                array(
                    'id' => 'brown',
                    'name' => 'Brown',
                ),
                array(
                    'id' => 'blond',
                    'name' => 'Blond',
                ),
                array(
                    'id' => 'red',
                    'name' => 'Red',
                ),
                array(
                    'id' => 'gray-or-white',
                    'name' => 'Gray or White',
                ),
                array(
                    'id' => 'other',
                    'name' => 'Other',
                ),
            ),
            'Handicap' => array(
                array(
                    'id' => 'physical',
                    'name' => 'Physical',
                ),
                array(
                    'id' => 'cognitive',
                    'name' => 'Cognitive',
                ),
                array(
                    'id' => 'mental',
                    'name' => 'Mental',
                ),
                array(
                    'id' => 'sensory',
                    'name' => 'Sensory',
                ),
                array(
                    'id' => 'emotional',
                    'name' => 'Emotional',
                ),
                array(
                    'id' => 'developmental',
                    'name' => 'Developmental',
                ),
            ),
            'Income' => array(
                array(
                    'id' => 'less-than-us-12-000-year',
                    'name' => 'Less than US$12,000/year',
                ),
                array(
                    'id' => 'between-us-12-000-and-us-24-000-year',
                    'name' => 'Between US$12,000 and US$24,000/year',
                ),
                array(
                    'id' => 'more-than-us-24-000-year',
                    'name' => 'More than US$24,000/year',
                ),
            ),
            'Nationality' => array(
                array(
                    'id' => 'us',
                    'name' => 'US',
                ),
                array(
                    'id' => 'british',
                    'name' => 'British',
                ),
                array(
                    'id' => 'spanish',
                    'name' => 'Spanish',
                ),
            ),
            'Orientation' => array(
                array(
                    'id' => 'heterosexual',
                    'name' => 'Heterosexual',
                ),
                array(
                    'id' => 'homosexual',
                    'name' => 'Homosexual',
                ),
                array(
                    'id' => 'bisexual',
                    'name' => 'Bisexual',
                ),
            ),
            'Pets' => array(
                array(
                    'id' => 'cat',
                    'name' => 'Cat',
                ),
                array(
                    'id' => 'dog',
                    'name' => 'Dog',
                ),
                array(
                    'id' => 'other',
                    'name' => 'Other',
                ),
            ),
            'RelationshipInterest' => array(
                array(
                    'id' => 'friendship',
                    'name' => 'Friendship',
                ),
                array(
                    'id' => 'relation',
                    'name' => 'Relation',
                ),
                array(
                    'id' => 'open-relation',
                    'name' => 'Open Relation',
                ),
            ),
            'Smoke' => array(
                array(
                    'id' => 'yes',
                    'name' => 'Yes',
                ),
                array(
                    'id' => 'no-but-i-tolerate-it',
                    'name' => 'No, but I tolerate it',
                ),
                array(
                    'id' => 'no-and-i-hate-it',
                    'name' => 'No, and I hate it',
                ),
            ),
            'InterfaceLanguage' => array(
                array(
                    'id' => 'es',
                    'name' => 'Español',
                ),
                array(
                    'id' => 'en',
                    'name' => 'English',
                ),
            ),
        );

        foreach ($options as $type => $values) {
            foreach ($values as $value) {
                $id = $value['id'];
                $name = $value['name'];
                if ($this->createOption($type, $id, $name)) {
                    $new += 1;
                }
            }
        }

        return $new;
    }

    /**
     * @param $type
     * @param $id
     * @param $name
     * @throws \Exception
     * @return boolean
     */
    public function createOption($type, $id, $name)
    {
        if (!$this->optionExists($type, $id, $name)) {

            if ($this->optionExists($type, null, $name)) {
                // This is for BC
                $this->logger->info(sprintf('Updating ProfileOption:%s id: "%s", name: "%s"', $type, $id, $name));
                $params = array('type' => $type, 'id' => $id, 'name' => $name);
                $query = "MATCH (o:ProfileOption) WHERE {type} IN labels(o) AND o.name = {name} SET o.id = {id} RETURN o;";
            } else {
                $this->logger->info(sprintf('Creating ProfileOption:%s id: "%s", name: "%s"', $type, $id, $name));
                $params = array('id' => $id, 'name' => $name);
                $query = "CREATE (:ProfileOption:" . $type . " { id: {id}, name: {name} })";
            }

            $neo4jQuery = new Query(
                $this->client,
                $query,
                $params
            );

            $result = $neo4jQuery->getResultSet();

            return true;

        } else {

            $this->logger->info(sprintf('ProfileOption:%s id: "%s", name: "%s" already exists', $type, $id, $name));

            return false;
        }

    }

    /**
     * @param $type
     * @param $id
     * @param $name
     * @return boolean
     * @throws \Exception
     */
    public function optionExists($type, $id, $name)
    {
        $params = array('type' => $type, 'name' => $name);
        $query = "MATCH (o:ProfileOption) WHERE {type} IN labels(o) AND o.name = {name}";
        if ($id) {
            // For BC
            $params['id'] = $id;
            $query .= " AND o.id = {id}";
        }
        $query .= " RETURN o;";

        $neo4jQuery = new Query(
            $this->client,
            $query,
            $params
        );

        $result = $neo4jQuery->getResultSet();

        return count($result) > 0;
    }
} 