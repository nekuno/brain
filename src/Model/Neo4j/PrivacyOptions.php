<?php

namespace Model\Neo4j;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class PrivacyOptions implements LoggerAwareInterface
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
            'PrivacyOptionProfile' => array(
                array(
                    'id' => 'all',
                    'name_en' => 'Everyone',
                    'name_es' => 'Todo el mundo',
                    'value_required' => false,
                ),
                array(
                    'id' => 'favorite',
                    'name_en' => 'My Favorites',
                    'name_es' => 'Mis Favoritos',
                    'value_required' => false,
                ),
                array(
                    'id' => 'message',
                    'name_en' => 'Users I had sent a message to',
                    'name_es' => 'Usuarios a los que he enviado un mensaje',
                    'value_required' => false,
                ),
                array(
                    'id' => 'min_compatibility',
                    'name_en' => 'Users with minimum compatibility',
                    'name_es' => 'Usuarios con compatibilidad minima de',
                    'value_required' => true,
                    'min_value' => 50,
                    'max_value' => 100,
                ),
                array(
                    'id' => 'min_similarity',
                    'name_en' => 'Users with minimum similarity',
                    'name_es' => 'Usuarios con similaridad minima de',
                    'value_required' => true,
                    'min_value' => 50,
                    'max_value' => 100,
                ),
            ),
            'PrivacyOptionDescription' => array(
                array(
                    'id' => 'all',
                    'name_en' => 'Everyone',
                    'name_es' => 'Todo el mundo',
                    'value_required' => false,
                ),
                array(
                    'id' => 'favorite',
                    'name_en' => 'My Favorites',
                    'name_es' => 'Mis Favoritos',
                    'value_required' => false,
                ),
                array(
                    'id' => 'message',
                    'name_en' => 'Users I had sent a message to',
                    'name_es' => 'Usuarios a los que he enviado un mensaje',
                    'value_required' => false,
                ),
                array(
                    'id' => 'min_compatibility',
                    'name_en' => 'Users with minimum compatibility',
                    'name_es' => 'Usuarios con compatibilidad minima de',
                    'value_required' => true,
                    'min_value' => 50,
                    'max_value' => 100,
                ),
                array(
                    'id' => 'min_similarity',
                    'name_en' => 'Users with minimum similarity',
                    'name_es' => 'Usuarios con similaridad minima de',
                    'value_required' => true,
                    'min_value' => 50,
                    'max_value' => 100,
                ),
            ),
            'PrivacyOptionQuestions' => array(
                array(
                    'id' => 'all',
                    'name_en' => 'Everyone',
                    'name_es' => 'Todo el mundo',
                    'value_required' => false,
                ),
                array(
                    'id' => 'favorite',
                    'name_en' => 'My Favorites',
                    'name_es' => 'Mis Favoritos',
                    'value_required' => false,
                ),
                array(
                    'id' => 'message',
                    'name_en' => 'Users I had sent a message to',
                    'name_es' => 'Usuarios a los que he enviado un mensaje',
                    'value_required' => false,
                ),
                array(
                    'id' => 'min_compatibility',
                    'name_en' => 'Users with minimum compatibility',
                    'name_es' => 'Usuarios con compatibilidad minima de',
                    'value_required' => true,
                    'min_value' => 50,
                    'max_value' => 100,
                ),
                array(
                    'id' => 'min_similarity',
                    'name_en' => 'Users with minimum similarity',
                    'name_es' => 'Usuarios con similaridad minima de',
                    'value_required' => true,
                    'min_value' => 50,
                    'max_value' => 100,
                ),
            ),
            'PrivacyOptionGallery' => array(
                array(
                    'id' => 'all',
                    'name_en' => 'Everyone',
                    'name_es' => 'Todo el mundo',
                    'value_required' => false,
                ),
                array(
                    'id' => 'favorite',
                    'name_en' => 'My Favorites',
                    'name_es' => 'Mis Favoritos',
                    'value_required' => false,
                ),
                array(
                    'id' => 'message',
                    'name_en' => 'Users I had sent a message to',
                    'name_es' => 'Usuarios a los que he enviado un mensaje',
                    'value_required' => false,
                ),
                array(
                    'id' => 'min_compatibility',
                    'name_en' => 'Users with minimum compatibility',
                    'name_es' => 'Usuarios con compatibilidad minima de',
                    'value_required' => true,
                    'min_value' => 50,
                    'max_value' => 100,
                ),
                array(
                    'id' => 'min_similarity',
                    'name_en' => 'Users with minimum similarity',
                    'name_es' => 'Usuarios con similaridad minima de',
                    'value_required' => true,
                    'min_value' => 50,
                    'max_value' => 100,
                ),
            ),
            'PrivacyOptionMessages' => array(
                array(
                    'id' => 'all',
                    'name_en' => 'Everyone',
                    'name_es' => 'Todo el mundo',
                    'value_required' => false,
                ),
                array(
                    'id' => 'favorite',
                    'name_en' => 'My Favorites',
                    'name_es' => 'Mis Favoritos',
                    'value_required' => false,
                ),
                array(
                    'id' => 'message',
                    'name_en' => 'Users I had sent a message to',
                    'name_es' => 'Usuarios a los que he enviado un mensaje',
                    'value_required' => false,
                ),
                array(
                    'id' => 'min_compatibility',
                    'name_en' => 'Users with minimum compatibility',
                    'name_es' => 'Usuarios con compatibilidad minima de',
                    'value_required' => true,
                    'min_value' => 50,
                    'max_value' => 100,
                ),
                array(
                    'id' => 'min_similarity',
                    'name_en' => 'Users with minimum similarity',
                    'name_es' => 'Usuarios con similaridad minima de',
                    'value_required' => true,
                    'min_value' => 50,
                    'max_value' => 100,
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
                $valueRequired = $value['value_required'];
                $minValue = isset($value['min_value']) ? $value['min_value'] : null;
                $maxValue = isset($value['max_value']) ? $value['max_value'] : null;
                $this->processOption($type, $id, $names, $valueRequired, $minValue, $maxValue);
            }
        }

        return $this->result;
    }

    /**
     * @param $type
     * @param $id
     * @param $names
     * @param $valueRequired
     * @param $minValue
     * @param $maxValue
     * @throws \Exception
     */
    public function processOption($type, $id, $names, $valueRequired, $minValue, $maxValue)
    {

        $this->result->incrementTotal();

        if ($this->optionExists($type, $id)) {

            if ($this->optionExists($type, $id, $names, $valueRequired, $minValue, $maxValue)) {

                $this->logger->info(sprintf('Skipping, Already exists PrivacyOption:%s id: "%s", name_en: "%s", name_es: "%s", value_required: "%s", min_value: "%s", max_value: "%s"', $type, $id, $names['name_en'], $names['name_es'], $valueRequired, $minValue, $maxValue));

            } else {

                $this->result->incrementUpdated();
                $this->logger->info(sprintf('Updating PrivacyOption:%s id: "%s", name_en: "%s", name_es: "%s", value_required: "%s", min_value: "%s", max_value: "%s"', $type, $id, $names['name_en'], $names['name_es'], $valueRequired, $minValue, $maxValue));
                $parameters = array(
                    'type' => $type,
                    'id' => $id,
                    'value_required' => $valueRequired,
                    'min_value' => $minValue,
                    'max_value' => $maxValue,
                );
                $parameters = array_merge($parameters, $names);
                $cypher = "MATCH (o:PrivacyOption) WHERE {type} IN labels(o) AND o.id = {id} SET o.name_en = {name_en}, o.name_es = {name_es}, o.value_required = {value_required}\n";
                if ($minValue) {
                    $cypher .= "SET o.min_value = {min_value}\n";
                }
                if ($maxValue) {
                    $cypher .= "SET o.max_value = {max_value}\n";
                }
                $cypher .= "RETURN o;";

                $query = $this->gm->createQuery($cypher, $parameters);
                $query->getResultSet();
            }

        } else {

            $this->result->incrementCreated();
            $this->logger->info(sprintf('Creating PrivacyOption:%s id: "%s", name_en: "%s", name_es: "%s", value_required: "%s", min_value: "%s", max_value: "%s"', $type, $id, $names['name_en'], $names['name_es'], $valueRequired, $minValue, $maxValue));
            $parameters = array(
                'id' => $id,
                'value_required' => $valueRequired,
                'min_value' => $minValue,
                'max_value' => $maxValue,
            );
            $parameters = array_merge($parameters, $names);
            $cypher = "CREATE (o:PrivacyOption:" . $type . " { id: {id}, name_en: {name_en}, name_es: {name_es}, value_required: {value_required} })";
            if ($minValue) {
                $cypher .= "SET o.min_value = {min_value}\n";
            }
            if ($maxValue) {
                $cypher .= "SET o.max_value = {max_value}\n";
            }
            $query = $this->gm->createQuery($cypher, $parameters);
            $query->getResultSet();
        }
    }

    /**
     * @param $type
     * @param $id
     * @param array $names
     * @param $valueRequired
     * @param $minValue
     * @param $maxValue
     * @return boolean
     * @throws \Exception
     */
    public function optionExists($type, $id, $names = array(), $valueRequired = null, $minValue = null, $maxValue = null)
    {
        $parameters = array('type' => $type, 'id' => $id, 'value_required' => $valueRequired);
        $cypher = "MATCH (o:PrivacyOption) WHERE {type} IN labels(o) AND o.id = {id}\n";
        if (!empty($names)) {
            $parameters = array_merge($parameters, $names);
            $cypher .= "AND o.name_es = {name_es} AND o.name_en = {name_en}\n";
        }
        if (!is_null($valueRequired)) {
            $parameters = array_merge($parameters, array('value_required' => $valueRequired));
            $cypher .= "AND o.value_required = {value_required}\n";
        }
        if (!is_null($minValue)) {
            $parameters = array_merge($parameters, array('min_value' => $minValue));
            $cypher .= "AND o.min_value = {min_value}\n";
        }
        if (!is_null($maxValue)) {
            $parameters = array_merge($parameters, array('max_value' => $maxValue));
            $cypher .= "AND o.max_value = {max_value}\n";
        }
        $cypher .= "RETURN o;";

        $query = $this->gm->createQuery($cypher, $parameters);
        $result = $query->getResultSet();

        return count($result) > 0;
    }
} 