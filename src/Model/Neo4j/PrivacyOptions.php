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
                ),
                array(
                    'id' => 'min_similarity',
                    'name_en' => 'Users with minimum similarity',
                    'name_es' => 'Usuarios con similaridad minima de',
                    'value_required' => true,
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
                ),
                array(
                    'id' => 'min_similarity',
                    'name_en' => 'Users with minimum similarity',
                    'name_es' => 'Usuarios con similaridad minima de',
                    'value_required' => true,
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
                ),
                array(
                    'id' => 'min_similarity',
                    'name_en' => 'Users with minimum similarity',
                    'name_es' => 'Usuarios con similaridad minima de',
                    'value_required' => true,
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
                ),
                array(
                    'id' => 'min_similarity',
                    'name_en' => 'Users with minimum similarity',
                    'name_es' => 'Usuarios con similaridad minima de',
                    'value_required' => true,
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
                ),
                array(
                    'id' => 'min_similarity',
                    'name_en' => 'Users with minimum similarity',
                    'name_es' => 'Usuarios con similaridad minima de',
                    'value_required' => true,
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
                $this->processOption($type, $id, $names, $valueRequired);
            }
        }

        return $this->result;
    }

    /**
     * @param $type
     * @param $id
     * @param $names
     * @param $valueRequired
     * @throws \Exception
     */
    public function processOption($type, $id, $names, $valueRequired)
    {

        $this->result->incrementTotal();

        if ($this->optionExists($type, $id)) {

            if ($this->optionExists($type, $id, $names, $valueRequired)) {

                $this->logger->info(sprintf('Skipping, Already exists PrivacyOption:%s id: "%s", name_en: "%s", name_es: "%s", value_required: "%s"', $type, $id, $names['name_en'], $names['name_es'], $valueRequired));

            } else {

                $this->result->incrementUpdated();
                $this->logger->info(sprintf('Updating PrivacyOption:%s id: "%s", name_en: "%s", name_es: "%s", value_required: "%s"', $type, $id, $names['name_en'], $names['name_es'], $valueRequired));
                $parameters = array('type' => $type, 'id' => $id, 'value_required' => $valueRequired);
                $parameters = array_merge($parameters, $names);
                $cypher = "MATCH (o:PrivacyOption) WHERE {type} IN labels(o) AND o.id = {id} SET o.name_en = {name_en}, o.name_es = {name_es}, o.value_required = {value_required} RETURN o;";

                $query = $this->gm->createQuery($cypher, $parameters);
                $query->getResultSet();
            }

        } else {

            $this->result->incrementCreated();
            $this->logger->info(sprintf('Creating PrivacyOption:%s id: "%s", name_en: "%s", name_es: "%s", value_required: "%s"', $type, $id, $names['name_en'], $names['name_es'], $valueRequired));
            $parameters = array('id' => $id, 'value_required' => $valueRequired);
            $parameters = array_merge($parameters, $names);
            $cypher = "CREATE (:PrivacyOption:" . $type . " { id: {id}, name_en: {name_en}, name_es: {name_es}, value_required: {value_required} })";

            $query = $this->gm->createQuery($cypher, $parameters);
            $query->getResultSet();
        }
    }

    /**
     * @param $type
     * @param $id
     * @param array $names
     * @param $valueRequired
     * @return boolean
     * @throws \Exception
     */
    public function optionExists($type, $id, $names = array(), $valueRequired = null)
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
        $cypher .= "RETURN o;";

        $query = $this->gm->createQuery($cypher, $parameters);
        $result = $query->getResultSet();

        return count($result) > 0;
    }
} 