<?php

$app['users.model'] = function ($app) {

    return new \Model\UserModel($app['neo4j.graph_manager'], $app['users.profile.model'], $app['dbs']['mysql_social']);
};

$app['users.profile.model'] = function ($app) {

    return new \Model\User\ProfileModel($app['neo4j.client'], $app['fields']['profile'], $app['locale.options']['default']);
};

$app['users.profile.tag.model'] = function ($app) {

    return new \Model\User\ProfileTagModel($app['neo4j.client']);
};

$app['users.answers.model'] = function ($app) {

    return new \Model\User\AnswerModel($app['neo4j.graph_manager'], $app['questionnaire.questions.model'], $app['users.model'], $app['dispatcher']);
};

$app['users.questions.model'] = function ($app) {

    return new \Model\User\QuestionPaginatedModel($app['neo4j.graph_manager'], $app['users.answers.model']);
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

    return new \Model\User\Matching\MatchingModel(
        $app['dispatcher'],
        $app['neo4j.client'],
        $app['users.content.model'],
        $app['users.answers.model']
    );

};
$app['users.similarity.model'] = function ($app) {

    return new \Model\User\Similarity\SimilarityModel($app['neo4j.client'], $app['neo4j.graph_manager'], $app['links.model']);
};

$app['users.recommendation.users.model'] = function ($app) {

    return new \Model\User\Recommendation\UserRecommendationPaginatedModel($app['neo4j.graph_manager'], $app['users.profile.model']);
};

$app['users.affinity.model'] = function ($app) {

    return new \Model\User\Affinity\AffinityModel($app['neo4j.graph_manager']);
};

$app['users.recommendation.content.model'] = function ($app) {

    return new \Model\User\Recommendation\ContentRecommendationPaginatedModel($app['neo4j.graph_manager']);
};

$app['users.recommendation.content.tag.model'] = function ($app) {

    return new \Model\User\Recommendation\ContentRecommendationTagModel($app['neo4j.graph_manager']);
};

$app['questionnaire.questions.model'] = function ($app) {

    return new \Model\Questionnaire\QuestionModel($app['neo4j.graph_manager'], $app['users.model']);
};

$app['links.model'] = function ($app) {

    return new \Model\LinkModel($app['neo4j.graph_manager']);
};

$app['users.groups.model'] = function ($app) {

    return new \Model\User\GroupModel($app['neo4j.graph_manager']);
};
