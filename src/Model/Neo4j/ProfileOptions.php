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
                array('name' => 'Yes'),
                array('name' => 'No'),
                array('name' => 'Occasionally'),
                array('name' => 'Socially/On parties'),
            ),
            'CivilStatus' => array(
                array('name' => 'Single'),
                array('name' => 'Married'),
                array('name' => 'Open relationship'),
                array('name' => 'Dating someone'),
            ),
            'Complexion' => array(
                array('name' => 'Slim'),
                array('name' => 'Normal'),
                array('name' => 'Fat'),
            ),
            'DateAlcohol' => array(
                array('name' => 'Yes'),
                array('name' => 'No'),
                array('name' => 'Maybe'),
            ),
            'DateChildren' => array(
                array('name' => 'Yes'),
                array('name' => 'No'),
                array('name' => 'Maybe'),
            ),
            'DateComplexion' => array(
                array('name' => 'Yes'),
                array('name' => 'No'),
                array('name' => 'Maybe'),
            ),
            'DateHandicap' => array(
                array('name' => 'Yes'),
                array('name' => 'No'),
                array('name' => 'Maybe'),
            ),
            'DateReligion' => array(
                array('name' => 'Yes'),
                array('name' => 'No'),
                array('name' => 'Maybe'),
            ),
            'DateSmoker' => array(
                array('name' => 'Yes'),
                array('name' => 'No'),
                array('name' => 'Maybe'),
            ),
            'Diet' => array(
                array('name' => 'Vegetarian'),
                array('name' => 'Vegan'),
                array('name' => 'Other'),
            ),
            'Drugs' => array(
                array('name' => 'Yes'),
                array('name' => 'No'),
                array('name' => 'Occasionally'),
                array('name' => 'Socially/On parties'),
            ),
            'EthnicGroup' => array(
                array('name' => 'Oriental'),
                array('name' => 'Afro-American'),
                array('name' => 'Caucasian'),
            ),
            'EyeColor' => array(
                array('name' => 'Blue'),
                array('name' => 'Brown'),
                array('name' => 'Black'),
                array('name' => 'Green'),
            ),
            'Gender' => array(
                array('name' => 'Male'),
                array('name' => 'Female'),
                array('name' => 'Other'),
            ),
            'HairColor' => array(
                array('name' => 'Black'),
                array('name' => 'Brown'),
                array('name' => 'Blond'),
                array('name' => 'Red'),
                array('name' => 'Gray or White'),
                array('name' => 'Other'),
            ),
            'Handicap' => array(
                array('name' => 'Physical'),
                array('name' => 'Cognitive'),
                array('name' => 'Mental'),
                array('name' => 'Sensory'),
                array('name' => 'Emotional'),
                array('name' => 'Developmental'),
            ),
            'Income' => array(
                array('name' => 'Less than US$12,000/year'),
                array('name' => 'Between US$12,000 and US$24,000/year'),
                array('name' => 'More than US$24,000/year'),
            ),
            'Nationality' => array(
                array('name' => 'US'),
                array('name' => 'British'),
                array('name' => 'Spanish'),
            ),
            'Orientation' => array(
                array('name' => 'Heterosexual'),
                array('name' => 'Homosexual'),
                array('name' => 'Bisexual'),
            ),
            'Pets' => array(
                array('name' => 'Cat'),
                array('name' => 'Dog'),
                array('name' => 'Other'),
            ),
            'RelationshipInterest' => array(
                array('name' => 'Friendship'),
                array('name' => 'Relation'),
                array('name' => 'Open Relation'),
            ),
            'Smoke' => array(
                array('name' => 'Yes'),
                array('name' => 'No, but I tolerate it'),
                array('name' => 'No, and I hate it'),
            ),
            'InterfaceLanguage' => array(
                array('name' => 'Español', 'id' => 'es'),
                array('name' => 'English', 'id' => 'en'),
            ),
        );

        foreach ($options as $type => $values) {
            foreach ($values as $value) {
                $name = $value['name'];
                $id = isset($value['id']) ? $value['id'] : null;
                if ($this->createOption($type, $name, $id)) {
                    $new += 1;
                }
            }
        }

        return $new;
    }

    /**
     * @param $type
     * @param $name
     * @param $id
     * @throws \Exception
     * @return boolean
     */
    public function createOption($type, $name, $id = null)
    {
        if (!$this->optionExists($type, $name)) {

            if ($id) {
                $this->logger->info(sprintf('Creating option "%s:%s" (id: "%s")', $type, $name, $id));
                $params = array('name' => $name, 'id' => $id);
                $query = "CREATE (:ProfileOption:" . $type . " { name: {name}, id: {id} })";
            } else {
                $this->logger->info(sprintf('Creating option "%s:%s"', $type, $name));
                $params = array('name' => $name);
                $query = "CREATE (:ProfileOption:" . $type . " { name: {name} })";
            }

            $neo4jQuery = new Query(
                $this->client,
                $query,
                $params
            );

            $result = $neo4jQuery->getResultSet();

            return true;

        } else {

            $this->logger->info(sprintf('Option "%s:%s" already exists.', $type, $name));

            return false;
        }

    }

    /**
     * @param $type
     * @param $name
     * @return boolean
     * @throws \Exception
     */
    public function optionExists($type, $name)
    {
        $params = array('type' => $type, 'name' => $name);
        $query =
            "
            MATCH
            (o:ProfileOption)
            WHERE
            {type} IN labels(o) AND
            o.name = {name}
            RETURN
            o;";

        $neo4jQuery = new Query(
            $this->client,
            $query,
            $params
        );

        $result = $neo4jQuery->getResultSet();

        return count($result) > 0;
    }
} 