<?php

$app['users.model'] = function ($app) {

    return new \Model\UserModel($app['neo4j.client']);
};

$app['users.answers.model'] = function ($app) {

    return new \Model\User\AnswerModel($app['neo4j.client']);
};

$app['users.questions.model'] = function ($app) {

    return new \Model\User\QuestionPaginatedModel($app['neo4j.client']);
};

$app['users.questions.compare.model'] = function ($app) {

    return new \Model\User\QuestionComparePaginatedModel($app['neo4j.client']);
};

$app['users.content.model'] = function ($app) {

    return new \Model\User\ContentPaginatedModel($app['neo4j.client']);
};

$app['users.content.compare.model'] = function ($app) {

    return new \Model\User\ContentComparePaginatedModel($app['neo4j.client']);
};

$app['users.content.tag.model'] = function ($app) {

    return new \Model\User\ContentTagModel($app['neo4j.client']);
};

$app['users.rate.model'] = function ($app) {

    return new \Model\User\RateModel($app['dispatcher'], $app['neo4j.client']);
};


$app['users.matching.model'] = function ($app) {

    return new \Model\User\MatchingModel($app['dispatcher'], $app['neo4j.client'], $app['users.content.model'], $app['users.answer.model']);
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

$app['questionnaire.questions.model'] = function ($app) {

    return new \Model\Questionnaire\QuestionModel($app['neo4j.client']);
};

$app['links.model'] = function ($app) {

    return new \Model\LinkModel($app['neo4j.client']);
};
