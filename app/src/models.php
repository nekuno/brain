<?php

$app['users.model'] = function ($app) {
    return new \Model\UserModel($app['neo4j.client']);
};

$app['questions.model'] = function ($app) {
    return new \Model\QuestionModel($app['neo4j.client']);
};

$app['content.model'] = function ($app) {
    return new \Model\ContentModel($app['neo4j.client']);
};