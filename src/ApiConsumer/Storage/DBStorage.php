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
     * @param $userId
     * @param array $links
     * @return array
     */
    public function storeLinks($userId, array $links)
    {

        foreach ($links as $link) {
            $link['userId'] = $userId;
            try {
                $this->model->addLink($link);

                if (isset($link['tags'])) {
                    foreach ($link['tags'] as $tag) {
                        $this->model->createTag($tag);
                        $this->model->addTag($link, $tag);
                    }
                }
            } catch (\Exception $e) {
                $this->errors[] = $this->getFormattedError($link);
                continue;
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
