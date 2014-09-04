<?php

namespace ApiConsumer\Storage;

interface StorageInterface
{

    /**
     * @param $userId
     * @param array $links
     * @return mixed
     */
    public function storeLinks($userId, array $links);

    public function getErrors();
}
