<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 28/06/14
 * Time: 18:27
 */

namespace ApiConsumer\Storage;

use Model\ContentModel;

class DBStorage implements StorageInterface
{

    /**
     * @var ContentModel
     */
    protected $model;

    protected $errors = array();

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * @param array $links
     * @return array
     */
    public function storeLinks(array $links)
    {

        foreach ($links as $user => $userLinks) {
            try {
                $this->model->saveUserLinks($user, $userLinks);
            } catch (\Exception $e) {
                $this->errors[] = $this->getFormattedError($e);
            }
        }

        return $this;

    }

    /**
     * @param $e
     * @return string
     */
    protected function getFormattedError(\Exception $e)
    {
        return sprintf('Error: %s on file %s line %s', $e->getMessage(), $e->getFile(), $e->getLine());
    }

}
