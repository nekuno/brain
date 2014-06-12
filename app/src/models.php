<?php

$app['users.model'] = function ($app) {
    return new \Model\UserModel($app['neo4j.client']);
};

$app['questions.model'] = function ($app) {
    return new \Model\UserModel($app['neo4j.client']);
};

$app['answers.model'] = function ($app) {
    return new \Model\AnswerModel($app['neo4j.client']);
};