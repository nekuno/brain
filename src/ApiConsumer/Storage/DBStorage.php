<?php

namespace ApiConsumer\Storage;

use Model\LinkModel;

class DBStorage implements StorageInterface
{

    /**
     * @var LinkModel
     */
    protected $model;

    protected $errors = array();

    public function __construct($model)
    {

        $this->model = $model;
    }

    /**
     * @return array
     */
    public function getErrors()
    {

        return $this->errors;
    }

    /**
     * @param array $linksGroupByUser
     * @return array
     */
    public function storeLinks(array $linksGroupByUser)
    {

        foreach ($linksGroupByUser as $user => $userLinks) {

            foreach ($userLinks as $link) {
                $link['userId'] = $user;
                try {
                    $this->model->addLink($link);
                } catch (\Exception $e) {
                    $this->errors[] = $this->getFormattedError($link);
                    continue;
                }
            }
        }

        return $this;
    }

    /**
     * @param $link
     * @return string
     */
    protected function getFormattedError(array $link)
    {

        return sprintf('Adding link with url: %s to DDBB.', $link['url']);
    }
}
