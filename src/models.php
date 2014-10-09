<?php

$app['users.model'] = function ($app) {

    return new \Model\UserModel($app['neo4j.client']);
};

$app['users.answer.model'] = function ($app) {

    return new \Model\User\AnswerModel($app['neo4j.client']);
};

$app['users.content.model'] = function ($app) {

    return new \Model\User\ContentPaginatedModel($app['neo4j.client']);
};

$app['users.matching.model'] = function ($app) {

    return new \Model\User\MatchingModelOld($app['neo4j.client'], $app['users.content.model'], $app['users.answer.model']);
};

$app['users.recommendation.users.model'] = function ($app) {

    return new \Model\User\Recommendation\UserRecommendationModel($app['neo4j.client'], $app['users.matching.model']);
};

$app['users.recommendation.content.model'] = function ($app) {

    return new \Model\User\Recommendation\ContentRecommendationPaginatedModel($app['neo4j.client'], $app['users.matching.model']);
};

$app['users.recommendation.content.tag.model'] = function ($app) {

    return new \Model\User\Recommendation\ContentRecommendationTagModel($app['neo4j.client'], $app['users.matching.model']);
};

$app['questions.model'] = function ($app) {

    return new \Model\QuestionModel($app['neo4j.client']);
};

$app['links.model'] = function ($app) {

    return new \Model\LinkModel($app['neo4j.client']);
};
