<?php

namespace Model\Neo4j;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class ProfileOptions implements LoggerAwareInterface
{
    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var OptionsResult
     */
    protected $result;

    public function __construct(GraphManager $gm)
    {

        $this->gm = $gm;
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
     * @return OptionsResult
     */
    public function load()
    {

        $this->result = new OptionsResult();

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
            'Sons' => array(
                array(
                    'id' => 'yes',
                    'name_en' => 'Have kids(s)',
                    'name_es' => 'Tengo hijos',
                ),
                array(
                    'id' => 'no',
                    'name_en' => "Doesn't have kids",
                    'name_es' => 'No tengo hijos',
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
            'Religion' => array(
                array(
                    'id' => 'agnosticism',
                    'name_en' => 'Agnosticism',
                    'name_es' => 'Agnóstico',
                ),
                array(
                    'id' => 'atheism',
                    'name_en' => 'Atheism',
                    'name_es' => 'Ateo',
                ),
                array(
                    'id' => 'christianity',
                    'name_en' => 'Christianity',
                    'name_es' => 'Cristiano',
                ),
                array(
                    'id' => 'judaism',
                    'name_en' => 'Judaism',
                    'name_es' => 'Judio',
                ),
                array(
                    'id' => 'catholicism',
                    'name_en' => 'Catholicism',
                    'name_es' => 'Católico',
                ),
                array(
                    'id' => 'islam',
                    'name_en' => 'Islam',
                    'name_es' => 'Musulmán',
                ),
                array(
                    'id' => 'hinduism',
                    'name_en' => 'Hinduism',
                    'name_es' => 'Hinduista',
                ),
                array(
                    'id' => 'buddhism',
                    'name_en' => 'Buddhism',
                    'name_es' => 'Budista',
                ),
                array(
                    'id' => 'sikh',
                    'name_en' => 'Sikh',
                    'name_es' => 'Sikh',
                ),
                array(
                    'id' => 'kopimism',
                    'name_en' => 'Kopimism',
                    'name_es' => 'Kopimista',
                ),
                array(
                    'id' => 'other',
                    'name_en' => 'Other',
                    'name_es' => 'Otra',
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
                $parameters = array('type' => $type, 'id' => $id);
                $parameters = array_merge($parameters, $names);
                $cypher = "MATCH (o:ProfileOption) WHERE {type} IN labels(o) AND o.id = {id} SET o.name_en = {name_en}, o.name_es = {name_es} RETURN o;";

                $query = $this->gm->createQuery($cypher, $parameters);
                $query->getResultSet();
            }

        } else {

            $this->result->incrementCreated();
            $this->logger->info(sprintf('Creating ProfileOption:%s id: "%s", name_en: "%s", name_es: "%s"', $type, $id, $names['name_en'], $names['name_es']));
            $parameters = array('id' => $id);
            $parameters = array_merge($parameters, $names);
            $cypher = "CREATE (:ProfileOption:" . $type . " { id: {id}, name_en: {name_en}, name_es: {name_es} })";

            $query = $this->gm->createQuery($cypher, $parameters);
            $query->getResultSet();
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
        $parameters = array('type' => $type, 'id' => $id);
        $cypher = "MATCH (o:ProfileOption) WHERE {type} IN labels(o) AND o.id = {id}\n";
        if (!empty($names)) {
            $parameters = array_merge($parameters, $names);
            $cypher .= "AND o.name_es = {name_es} AND o.name_en = {name_en}\n";
        }
        $cypher .= "RETURN o;";

        $query = $this->gm->createQuery($cypher, $parameters);
        $result = $query->getResultSet();

        return count($result) > 0;
    }
} 