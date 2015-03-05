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
     * @var ProfileOptionsResult
     */
    protected $result;

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
     * @return ProfileOptionsResult New ProfileOptions added
     */
    public function load()
    {

        $this->result = new ProfileOptionsResult();

        $options = array(
            'Alcohol' => array(
                array(
                    'id' => 'yes',
                    'name_en' => 'Yes',
                    'name_es' => 'Sí',
                ),
                array(
                    'id' => 'no',
                    'name_en' => 'No',
                    'name_es' => 'No',
                ),
                array(
                    'id' => 'occasionally',
                    'name_en' => 'Occasionally',
                    'name_es' => 'Ocasionalmente',
                ),
                array(
                    'id' => 'socially-on-parties',
                    'name_en' => 'Socially/On parties',
                    'name_es' => 'Socialmente/En fiestas',
                ),
            ),
            'CivilStatus' => array(
                array(
                    'id' => 'single',
                    'name_en' => 'Single',
                    'name_es' => 'Soltero/a',
                ),
                array(
                    'id' => 'married',
                    'name_en' => 'Married',
                    'name_es' => 'Casado/a',
                ),
                array(
                    'id' => 'open-relationship',
                    'name_en' => 'Open relationship',
                    'name_es' => 'Relación abierta',
                ),
                array(
                    'id' => 'dating-someone',
                    'name_en' => 'Dating someone',
                    'name_es' => 'Saliendo con alguien',
                ),
            ),
            'Complexion' => array(
                array(
                    'id' => 'slim',
                    'name_en' => 'Slim',
                    'name_es' => 'Flaco',
                ),
                array(
                    'id' => 'normal',
                    'name_en' => 'Normal',
                    'name_es' => 'Normal',
                ),
                array(
                    'id' => 'fat',
                    'name_en' => 'Fat',
                    'name_es' => 'Gordo',
                ),
            ),
            'DateAlcohol' => array(
                array(
                    'id' => 'yes',
                    'name_en' => 'Yes',
                    'name_es' => 'Sí',
                ),
                array(
                    'id' => 'no',
                    'name_en' => 'No',
                    'name_es' => 'No',
                ),
                array(
                    'id' => 'maybe',
                    'name_en' => 'Maybe',
                    'name_es' => 'Tal vez',
                ),
            ),
            'DateChildren' => array(
                array(
                    'id' => 'yes',
                    'name_en' => 'Yes',
                    'name_es' => 'Sí',
                ),
                array(
                    'id' => 'no',
                    'name_en' => 'No',
                    'name_es' => 'No',
                ),
                array(
                    'id' => 'maybe',
                    'name_en' => 'Maybe',
                    'name_es' => 'Tal vez',
                ),
            ),
            'DateComplexion' => array(
                array(
                    'id' => 'yes',
                    'name_en' => 'Yes',
                    'name_es' => 'Sí',
                ),
                array(
                    'id' => 'no',
                    'name_en' => 'No',
                    'name_es' => 'No',
                ),
                array(
                    'id' => 'maybe',
                    'name_en' => 'Maybe',
                    'name_es' => 'Tal vez',
                ),
            ),
            'DateHandicap' => array(
                array(
                    'id' => 'yes',
                    'name_en' => 'Yes',
                    'name_es' => 'Sí',
                ),
                array(
                    'id' => 'no',
                    'name_en' => 'No',
                    'name_es' => 'No',
                ),
                array(
                    'id' => 'maybe',
                    'name_en' => 'Maybe',
                    'name_es' => 'Tal vez',
                ),
            ),
            'DateReligion' => array(
                array(
                    'id' => 'yes',
                    'name_en' => 'Yes',
                    'name_es' => 'Sí',
                ),
                array(
                    'id' => 'no',
                    'name_en' => 'No',
                    'name_es' => 'No',
                ),
                array(
                    'id' => 'maybe',
                    'name_en' => 'Maybe',
                    'name_es' => 'Tal vez',
                ),
            ),
            'DateSmoker' => array(
                array(
                    'id' => 'yes',
                    'name_en' => 'Yes',
                    'name_es' => 'Sí',
                ),
                array(
                    'id' => 'no',
                    'name_en' => 'No',
                    'name_es' => 'No',
                ),
                array(
                    'id' => 'maybe',
                    'name_en' => 'Maybe',
                    'name_es' => 'Tal vez',
                ),
            ),
            'Diet' => array(
                array(
                    'id' => 'vegetarian',
                    'name_en' => 'Vegetarian',
                    'name_es' => 'Vegetariana',
                ),
                array(
                    'id' => 'vegan',
                    'name_en' => 'Vegan',
                    'name_es' => 'Vegana',
                ),
                array(
                    'id' => 'other',
                    'name_en' => 'Other',
                    'name_es' => 'Otro',
                ),
            ),
            'Drugs' => array(
                array(
                    'id' => 'yes',
                    'name_en' => 'Yes',
                    'name_es' => 'Sí',
                ),
                array(
                    'id' => 'no',
                    'name_en' => 'No',
                    'name_es' => 'No',
                ),
                array(
                    'id' => 'occasionally',
                    'name_en' => 'Occasionally',
                    'name_es' => 'Occasionalmente',
                ),
                array(
                    'id' => 'socially-on-parties',
                    'name_en' => 'Socially/On parties',
                    'name_es' => 'Socialmente/En fiestas',
                ),
            ),
            'EthnicGroup' => array(
                array(
                    'id' => 'oriental',
                    'name_en' => 'Oriental',
                    'name_es' => 'Oriental',
                ),
                array(
                    'id' => 'afro-american',
                    'name_en' => 'Afro-American',
                    'name_es' => 'Afro-Americano',
                ),
                array(
                    'id' => 'caucasian',
                    'name_en' => 'Caucasian',
                    'name_es' => 'Caucasico',
                ),
            ),
            'EyeColor' => array(
                array(
                    'id' => 'blue',
                    'name_en' => 'Blue',
                    'name_es' => 'Azules',
                ),
                array(
                    'id' => 'brown',
                    'name_en' => 'Brown',
                    'name_es' => 'Castaños',
                ),
                array(
                    'id' => 'black',
                    'name_en' => 'Black',
                    'name_es' => 'Negros',
                ),
                array(
                    'id' => 'green',
                    'name_en' => 'Green',
                    'name_es' => 'Verdes',
                ),
            ),
            'Gender' => array(
                array(
                    'id' => 'male',
                    'name_en' => 'Male',
                    'name_es' => 'Masculino',
                ),
                array(
                    'id' => 'female',
                    'name_en' => 'Female',
                    'name_es' => 'Femenino',
                ),
            ),
            'Car' => array(
                array(
                    'id' => 'yes',
                    'name_en' => 'Yes',
                    'name_es' => 'Sí',
                ),
                array(
                    'id' => 'no',
                    'name_en' => 'No',
                    'name_es' => 'No',
                ),
            ),
            'Sons' => array(
                array(
                    'id' => 'yes',
                    'name_en' => 'Yes',
                    'name_es' => 'Sí',
                ),
                array(
                    'id' => 'no',
                    'name_en' => 'No',
                    'name_es' => 'No',
                ),
            ),
            'WantSons' => array(
                array(
                    'id' => 'yes',
                    'name_en' => 'Yes',
                    'name_es' => 'Sí',
                ),
                array(
                    'id' => 'no',
                    'name_en' => 'No',
                    'name_es' => 'No',
                ),
            ),
            'HairColor' => array(
                array(
                    'id' => 'black',
                    'name_en' => 'Black',
                    'name_es' => 'Negro',
                ),
                array(
                    'id' => 'brown',
                    'name_en' => 'Brown',
                    'name_es' => 'Castaño',
                ),
                array(
                    'id' => 'blond',
                    'name_en' => 'Blond',
                    'name_es' => 'Rubio',
                ),
                array(
                    'id' => 'red',
                    'name_en' => 'Red',
                    'name_es' => 'Rojo',
                ),
                array(
                    'id' => 'gray-or-white',
                    'name_en' => 'Gray or White',
                    'name_es' => 'Gris o Blanco',
                ),
                array(
                    'id' => 'other',
                    'name_en' => 'Other',
                    'name_es' => 'Otro',
                ),
            ),
            'Handicap' => array(
                array(
                    'id' => 'physical',
                    'name_en' => 'Physical',
                    'name_es' => 'Física',
                ),
                array(
                    'id' => 'cognitive',
                    'name_en' => 'Cognitive',
                    'name_es' => 'Cognitiva',
                ),
                array(
                    'id' => 'mental',
                    'name_en' => 'Mental',
                    'name_es' => 'Mental',
                ),
                array(
                    'id' => 'sensory',
                    'name_en' => 'Sensory',
                    'name_es' => 'Sensorial',
                ),
                array(
                    'id' => 'emotional',
                    'name_en' => 'Emotional',
                    'name_es' => 'Emocional',
                ),
                array(
                    'id' => 'developmental',
                    'name_en' => 'Developmental',
                    'name_es' => 'De Desarrollo',
                ),
            ),
            'Income' => array(
                array(
                    'id' => 'less-than-us-12-000-year',
                    'name_en' => 'Less than US$12,000/year',
                    'name_es' => 'Menos de 12.000 US$/año',
                ),
                array(
                    'id' => 'between-us-12-000-and-us-24-000-year',
                    'name_en' => 'Between US$12,000 and US$24,000/year',
                    'name_es' => 'Entre 12.000 y 24.000 US$/año',
                ),
                array(
                    'id' => 'more-than-us-24-000-year',
                    'name_en' => 'More than US$24,000/year',
                    'name_es' => 'Más de 24.000 US$/año',
                ),
            ),
            'Nationality' => array(
                array(
                    'id' => 'us',
                    'name_en' => 'US',
                    'name_es' => 'Estadounidense',
                ),
                array(
                    'id' => 'british',
                    'name_en' => 'British',
                    'name_es' => 'Británica',
                ),
                array(
                    'id' => 'spanish',
                    'name_en' => 'Spanish',
                    'name_es' => 'Española',
                ),
            ),
            'Orientation' => array(
                array(
                    'id' => 'heterosexual',
                    'name_en' => 'Heterosexual',
                    'name_es' => 'Heterosexual',
                ),
                array(
                    'id' => 'homosexual',
                    'name_en' => 'Homosexual',
                    'name_es' => 'Homosexual',
                ),
                array(
                    'id' => 'bisexual',
                    'name_en' => 'Bisexual',
                    'name_es' => 'Bisexual',
                ),
            ),
            'Pets' => array(
                array(
                    'id' => 'cat',
                    'name_en' => 'Cat',
                    'name_es' => 'Gato',
                ),
                array(
                    'id' => 'dog',
                    'name_en' => 'Dog',
                    'name_es' => 'Perro',
                ),
                array(
                    'id' => 'other',
                    'name_en' => 'Other',
                    'name_es' => 'Otras',
                ),
            ),
            'RelationshipInterest' => array(
                array(
                    'id' => 'friendship',
                    'name_en' => 'Friendship',
                    'name_es' => 'Amistad',
                ),
                array(
                    'id' => 'relation',
                    'name_en' => 'Relation',
                    'name_es' => 'Relación',
                ),
                array(
                    'id' => 'open-relation',
                    'name_en' => 'Open Relation',
                    'name_es' => 'Relación Abierta',
                ),
            ),
            'Smoke' => array(
                array(
                    'id' => 'yes',
                    'name_en' => 'Yes',
                    'name_es' => 'Sí',
                ),
                array(
                    'id' => 'no-but-i-tolerate-it',
                    'name_en' => 'No, but I tolerate it',
                    'name_es' => 'No, pero lo toleraría',
                ),
                array(
                    'id' => 'no-and-i-hate-it',
                    'name_en' => 'No, and I hate it',
                    'name_es' => 'No, y lo odio',
                ),
            ),
            'InterfaceLanguage' => array(
                array(
                    'id' => 'es',
                    'name_en' => 'Español',
                    'name_es' => 'Español',
                ),
                array(
                    'id' => 'en',
                    'name_en' => 'English',
                    'name_es' => 'English',
                ),
            ),
            'ZodiacSign' => array(
                array(
                    'id' => 'capricorn',
                    'name_en' => 'Capricorn',
                    'name_es' => 'Capricornio',
                ),
                array(
                    'id' => 'sagittarius',
                    'name_en' => 'Sagittarius',
                    'name_es' => 'Sagitario',
                ),
                array(
                    'id' => 'scorpio',
                    'name_en' => 'Scorpio',
                    'name_es' => 'Escorpio',
                ),
                array(
                    'id' => 'libra',
                    'name_en' => 'Libra',
                    'name_es' => 'Libra',
                ),
                array(
                    'id' => 'virgo',
                    'name_en' => 'Virgo',
                    'name_es' => 'Virgo',
                ),
                array(
                    'id' => 'leo',
                    'name_en' => 'Leo',
                    'name_es' => 'Leo',
                ),
                array(
                    'id' => 'cancer',
                    'name_en' => 'Cancer',
                    'name_es' => 'Cáncer',
                ),
                array(
                    'id' => 'gemini',
                    'name_en' => 'Gemini',
                    'name_es' => 'Géminis',
                ),
                array(
                    'id' => 'taurus',
                    'name_en' => 'Taurus',
                    'name_es' => 'Tauro',
                ),
                array(
                    'id' => 'aries',
                    'name_en' => 'Aries',
                    'name_es' => 'Aries',
                ),
                array(
                    'id' => 'pisces',
                    'name_en' => 'Pisces',
                    'name_es' => 'Piscis',
                ),
                array(
                    'id' => 'aquarius',
                    'name_en' => 'Aquarius',
                    'name_es' => 'Acuario',
                ),
            ),
        );

        foreach ($options as $type => $values) {
            foreach ($values as $value) {
                $id = $value['id'];
                $names = array(
                    'name_es' => $value['name_es'],
                    'name_en' => $value['name_en'],
                );
                $this->processOption($type, $id, $names);
            }
        }

        return $this->result;
    }

    /**
     * @param $type
     * @param $id
     * @param $names
     * @throws \Exception
     */
    public function processOption($type, $id, $names)
    {

        $this->result->incrementTotal();

        if ($this->optionExists($type, $id)) {

            if ($this->optionExists($type, $id, $names)) {

                $this->logger->info(sprintf('Skipping, Already exists ProfileOption:%s id: "%s", name_en: "%s", name_es: "%s"', $type, $id, $names['name_en'], $names['name_es']));

            } else {

                $this->result->incrementUpdated();
                $this->logger->info(sprintf('Updating ProfileOption:%s id: "%s", name_en: "%s", name_es: "%s"', $type, $id, $names['name_en'], $names['name_es']));
                $params = array('type' => $type, 'id' => $id);
                $params = array_merge($params, $names);
                $query = "MATCH (o:ProfileOption) WHERE {type} IN labels(o) AND o.id = {id} SET o.name_en = {name_en}, o.name_es = {name_es} RETURN o;";

                $neo4jQuery = new Query(
                    $this->client,
                    $query,
                    $params
                );

                $neo4jQuery->getResultSet();
            }

        } else {

            $this->result->incrementCreated();
            $this->logger->info(sprintf('Creating ProfileOption:%s id: "%s", name_en: "%s", name_es: "%s"', $type, $id, $names['name_en'], $names['name_es']));
            $params = array('id' => $id);
            $params = array_merge($params, $names);
            $query = "CREATE (:ProfileOption:" . $type . " { id: {id}, name_en: {name_en}, name_es: {name_es} })";

            $neo4jQuery = new Query(
                $this->client,
                $query,
                $params
            );

            $neo4jQuery->getResultSet();
        }
    }

    /**
     * @param $type
     * @param $id
     * @param array $names
     * @return boolean
     * @throws \Exception
     */
    public function optionExists($type, $id, $names = array())
    {
        $params = array('type' => $type, 'id' => $id);
        $query = "MATCH (o:ProfileOption) WHERE {type} IN labels(o) AND o.id = {id}\n";
        if (!empty($names)) {
            $params = array_merge($params, $names);
            $query .= "AND o.name_es = {name_es} AND o.name_en = {name_en}\n";
        }
        $query .= "RETURN o;";

        $neo4jQuery = new Query(
            $this->client,
            $query,
            $params
        );

        $result = $neo4jQuery->getResultSet();

        return count($result) > 0;
    }
} 