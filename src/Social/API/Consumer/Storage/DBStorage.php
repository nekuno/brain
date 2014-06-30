<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 28/06/14
 * Time: 18:27
 */

namespace Social\API\Consumer\Storage;

use Model\ContentModel;

class DBStorage implements StorageInterface {

    /**
     * @var ContentModel
     */
    protected $model;

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

        $errors = 0;
        $result = array();

        foreach ($links as $link) {
            try {
                $link = $this->model->addLink($link);
                if ($link) {
                    $result[] = $link;
                }
            } catch (\Exception $e) {
                $errors++;
                continue;
            }
        }

        // TODO: Log and handle error percentage and make blocking if needed
        return $result;

    }

} 