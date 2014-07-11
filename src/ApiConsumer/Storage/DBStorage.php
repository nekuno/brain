<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 28/06/14
 * Time: 18:27
 */

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
     * @param array $linksGroupedByUser
     * @return array
     */
    public function storeLinks(array $linksGroupedByUser)
    {

        foreach ($linksGroupedByUser as $user => $userLinks) {

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

        return sprintf('Error: adding link with url: %s to DDBB.', $link['url']);
    }
}
