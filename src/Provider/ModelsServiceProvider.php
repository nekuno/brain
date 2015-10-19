<?php

namespace Provider;

use Model\EnterpriseUser\EnterpriseUserModel;
use Model\LinkModel;
use Model\Questionnaire\QuestionModel;
use Model\User\Affinity\AffinityModel;
use Model\User\AnswerModel;
use Model\EnterpriseUser\CommunityModel;
use Model\User\ContentComparePaginatedModel;
use Model\User\ContentPaginatedModel;
use Model\User\ContentTagModel;
use Model\User\GroupModel;
use Model\User\InvitationModel;
use Model\User\LookUpModel;
use Model\User\Matching\MatchingModel;
use Model\User\PrivacyModel;
use Model\User\ProfileModel;
use Model\User\ProfileTagModel;
use Model\User\QuestionComparePaginatedModel;
use Model\User\QuestionPaginatedModel;
use Model\User\RateModel;
use Model\User\Recommendation\ContentRecommendationPaginatedModel;
use Model\User\Recommendation\ContentRecommendationTagModel;
use Model\User\Recommendation\UserRecommendationPaginatedModel;
use Model\User\RelationsModel;
use Model\User\Similarity\SimilarityModel;
use Model\User\SocialNetwork\LinkedinSocialNetworkModel;
use Model\User\SocialNetwork\SocialNetworkModel;
use Model\User\TokensModel;
use Model\UserModel;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

class ModelsServiceProvider implements ServiceProviderInterface
{

    /**
     * { @inheritdoc }
     */
    public function register(Application $app)
    {

        $app['security.password_encoder'] = $app->share(
            function () {

                return new MessageDigestPasswordEncoder();
            }
        );

        $app['users.model'] = $app->share(
            function ($app) {

                return new UserModel($app['neo4j.graph_manager'], $app['security.password_encoder'], $app['dbs']['mysql_social'], $app['orm.ems']['mysql_brain'], $app['users.relations.model'], $app['fields']['user'], $app['locale.options']['default']);
            }
        );

        $app['users.tokens.model'] = $app->share(
            function ($app) {

                return new TokensModel($app['neo4j.graph_manager'], $app['orm.ems']['mysql_brain']);
            }
        );

        $app['users.profile.model'] = $app->share(
            function ($app) {

                return new ProfileModel($app['neo4j.graph_manager'], $app['fields']['profile'], $app['locale.options']['default']);
            }
        );

        $app['users.privacy.model'] = $app->share(
            function ($app) {

                return new PrivacyModel($app['neo4j.graph_manager'], $app['fields']['privacy'], $app['locale.options']['default']);
            }
        );

        $app['users.profile.tag.model'] = $app->share(
            function ($app) {

                return new ProfileTagModel($app['neo4j.client']);
            }
        );

        $app['users.answers.model'] = $app->share(
            function ($app) {

                return new AnswerModel($app['neo4j.graph_manager'], $app['questionnaire.questions.model'], $app['users.model'], $app['dispatcher']);
            }
        );

        $app['users.questions.model'] = $app->share(
            function ($app) {

                return new QuestionPaginatedModel($app['neo4j.graph_manager'], $app['users.answers.model']);
            }
        );

        $app['users.questions.compare.model'] = $app->share(
            function ($app) {

                return new QuestionComparePaginatedModel($app['neo4j.client']);
            }
        );

        $app['users.content.model'] = $app->share(
            function ($app) {

                return new ContentPaginatedModel($app['neo4j.graph_manager'], $app['users.tokens.model']);
            }
        );

        $app['users.content.compare.model'] = $app->share(
            function ($app) {

                return new ContentComparePaginatedModel($app['neo4j.graph_manager'], $app['users.tokens.model']);
            }
        );

        $app['users.content.tag.model'] = $app->share(
            function ($app) {

                return new ContentTagModel($app['neo4j.client'], $app['neo4j.graph_manager']);
            }
        );

        $app['users.rate.model'] = $app->share(
            function ($app) {

                return new RateModel($app['dispatcher'], $app['neo4j.client'], $app['neo4j.graph_manager']);
            }
        );

        $app['users.matching.model'] = $app->share(
            function ($app) {

                return new MatchingModel($app['dispatcher'], $app['neo4j.client'], $app['users.content.model'], $app['users.answers.model']);

            }
        );
        $app['users.similarity.model'] = $app->share(
            function ($app) {

                return new SimilarityModel($app['neo4j.client'], $app['neo4j.graph_manager'], $app['links.model']);
            }
        );

        $app['users.recommendation.users.model'] = $app->share(
            function ($app) {

                return new UserRecommendationPaginatedModel($app['neo4j.graph_manager'], $app['users.profile.model']);
            }
        );

        $app['users.affinity.model'] = $app->share(
            function ($app) {

                return new AffinityModel($app['neo4j.graph_manager']);
            }
        );

        $app['users.recommendation.content.model'] = $app->share(
            function ($app) {

                return new ContentRecommendationPaginatedModel($app['neo4j.graph_manager'], $app['users.affinity.model'], $app['links.model']);
            }
        );

        $app['users.recommendation.content.tag.model'] = $app->share(
            function ($app) {

                return new ContentRecommendationTagModel($app['neo4j.graph_manager']);
            }
        );

        $app['users.lookup.model'] = $app->share(
            function ($app) {

                return new LookUpModel($app['neo4j.graph_manager'], $app['orm.ems']['mysql_brain'], $app['users.tokens.model'], $app['lookUp.fullContact.service'], $app['lookUp.peopleGraph.service'], $app['dispatcher']);
            }
        );

        $app['users.socialNetwork.linkedin.model'] = $app->share(
            function ($app) {

                return new LinkedinSocialNetworkModel($app['neo4j.graph_manager'], $app['parser.linkedin']);
            }
        );

        $app['questionnaire.questions.model'] = $app->share(
            function ($app) {

                return new QuestionModel($app['neo4j.graph_manager'], $app['users.model']);
            }
        );

        $app['links.model'] = $app->share(
            function ($app) {

                return new LinkModel($app['neo4j.graph_manager']);
            }
        );

        $app['users.groups.model'] = $app->share(
            function ($app) {

                return new GroupModel($app['neo4j.graph_manager'], $app['users.model']);
            }
        );

        $app['users.invitations.model'] = $app->share(
            function ($app) {
                return new InvitationModel($app['neo4j.graph_manager'], $app['users.groups.model'], $app['users.model'], $app['admin_domain_plus_post']);
            }
        );

        $app['users.relations.model'] = $app->share(
            function ($app) {

                return new RelationsModel($app['neo4j.graph_manager'], $app['dbs']['mysql_social']);
            }
        );

        $app['enterpriseUsers.model'] = $app->share(
            function ($app) {

                return new EnterpriseUserModel($app['neo4j.graph_manager']);
            }
        );

        $app['enterpriseUsers.communities.model'] = $app->share(
            function ($app) {

                return new CommunityModel($app['neo4j.graph_manager'], $app['users.model']);
            }
        );
    }

    /**
     * { @inheritdoc }
     */
    public function boot(Application $app)
    {

    }

}
