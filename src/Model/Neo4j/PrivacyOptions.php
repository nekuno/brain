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
                ),
                array(
                    'id' => 'favorite',
                    'name_en' => 'My Favorites',
                    'name_es' => 'Mis Favoritos',
                ),
                array(
                    'id' => 'message',
                    'name_en' => 'Users I had sent a message to',
                    'name_es' => 'Usuarios a los que he enviado un mensaje',
                ),
            ),
            'PrivacyOptionDescription' => array(
                array(
                    'id' => 'all',
                    'name_en' => 'Everyone',
                    'name_es' => 'Todo el mundo',
                ),
                array(
                    'id' => 'favorite',
                    'name_en' => 'My Favorites',
                    'name_es' => 'Mis Favoritos',
                ),
                array(
                    'id' => 'message',
                    'name_en' => 'Users I had sent a message to',
                    'name_es' => 'Usuarios a los que he enviado un mensaje',
                ),
            ),
            'PrivacyOptionQuestions' => array(
                array(
                    'id' => 'all',
                    'name_en' => 'Everyone',
                    'name_es' => 'Todo el mundo',
                ),
                array(
                    'id' => 'favorite',
                    'name_en' => 'My Favorites',
                    'name_es' => 'Mis Favoritos',
                ),
                array(
                    'id' => 'message',
                    'name_en' => 'Users I had sent a message to',
                    'name_es' => 'Usuarios a los que he enviado un mensaje',
                ),
            ),
            'PrivacyOptionGallery' => array(
                array(
                    'id' => 'all',
                    'name_en' => 'Everyone',
                    'name_es' => 'Todo el mundo',
                ),
                array(
                    'id' => 'favorite',
                    'name_en' => 'My Favorites',
                    'name_es' => 'Mis Favoritos',
                ),
                array(
                    'id' => 'message',
                    'name_en' => 'Users I had sent a message to',
                    'name_es' => 'Usuarios a los que he enviado un mensaje',
                ),
            ),
            'PrivacyOptionMessages' => array(
                array(
                    'id' => 'all',
                    'name_en' => 'Everyone',
                    'name_es' => 'Todo el mundo',
                ),
                array(
                    'id' => 'favorite',
                    'name_en' => 'My Favorites',
                    'name_es' => 'Mis Favoritos',
                ),
                array(
                    'id' => 'message',
                    'name_en' => 'Users I had sent a message to',
                    'name_es' => 'Usuarios a los que he enviado un mensaje',
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

                $this->logger->info(sprintf('Skipping, Already exists PrivacyOption:%s id: "%s", name_en: "%s", name_es: "%s"', $type, $id, $names['name_en'], $names['name_es']));

            } else {

                $this->result->incrementUpdated();
                $this->logger->info(sprintf('Updating PrivacyOption:%s id: "%s", name_en: "%s", name_es: "%s"', $type, $id, $names['name_en'], $names['name_es']));
                $parameters = array('type' => $type, 'id' => $id);
                $parameters = array_merge($parameters, $names);
                $cypher = "MATCH (o:PrivacyOption) WHERE {type} IN labels(o) AND o.id = {id} SET o.name_en = {name_en}, o.name_es = {name_es} RETURN o;";

                $query = $this->gm->createQuery($cypher, $parameters);
                $query->getResultSet();
            }

        } else {

            $this->result->incrementCreated();
            $this->logger->info(sprintf('Creating PrivacyOption:%s id: "%s", name_en: "%s", name_es: "%s"', $type, $id, $names['name_en'], $names['name_es']));
            $parameters = array('id' => $id);
            $parameters = array_merge($parameters, $names);
            $cypher = "CREATE (:PrivacyOption:" . $type . " { id: {id}, name_en: {name_en}, name_es: {name_es} })";

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
        $cypher = "MATCH (o:PrivacyOption) WHERE {type} IN labels(o) AND o.id = {id}\n";
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