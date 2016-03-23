<?php

namespace Provider;

use Model\EnterpriseUser\EnterpriseUserModel;
use Model\LinkModel;
use Model\Questionnaire\QuestionModel;
use Model\User\Affinity\AffinityModel;
use Model\User\AnswerModel;
use Model\EnterpriseUser\CommunityModel;
use Model\User\ContentComparePaginatedModel;
use Model\User\ContentFilterModel;
use Model\User\ContentPaginatedModel;
use Model\User\ContentTagModel;
use Model\User\Filters\FilterContentManager;
use Model\User\Filters\FilterUsersManager;
use Model\User\GhostUser\GhostUserManager;
use Model\User\GroupModel;
use Model\User\InvitationModel;
use Model\User\LookUpModel;
use Model\User\Matching\MatchingModel;
use Model\User\OldQuestionComparePaginatedModel;
use Model\User\PrivacyModel;
use Model\User\ProfileFilterModel;
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
use Model\User\SocialNetwork\SocialProfileManager;
use Model\User\Thread\ContentThreadManager;
use Model\User\Thread\ThreadManager;
use Model\User\Thread\ThreadPaginatedModel;
use Model\User\Thread\UsersThreadManager;
use Model\User\TokensModel;
use Model\User\UserFilterModel;
use Model\User\UserStatsManager;
use Manager\UserManager;
use Security\UserProvider;
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

        $app['security.users_provider'] = $app['users'] = $app->share(
            function ($app) {

                return new UserProvider($app['users.manager']);
            }
        );

        $app['users.manager'] = $app->share(
            function ($app) {

                return new UserManager($app['dispatcher'], $app['neo4j.graph_manager'], $app['security.password_encoder'], $app['users.userFilter.model']);
            }
        );

        $app['users.tokens.model'] = $app->share(
            function ($app) {

                return new TokensModel($app['dispatcher'], $app['neo4j.graph_manager'], $app['orm.ems']['mysql_brain']);
            }
        );

        $app['users.profile.model'] = $app->share(
            function ($app) {

                return new ProfileModel($app['neo4j.graph_manager'], $app['users.profileFilter.model']);
            }
        );

        $app['users.userFilter.model'] = $app->share(
            function ($app) {

                return new UserFilterModel($app['neo4j.graph_manager'], $app['fields']['user'], $app['locale.options']['default'], $app['users.groups.model']);
            }
        );

        $app['users.profileFilter.model'] = $app->share(
            function ($app) {

                return new ProfileFilterModel($app['neo4j.graph_manager'], $app['fields']['profile'], $app['locale.options']['default']);
            }
        );

        $app['users.contentFilter.model'] = $app->share(
            function ($app) {

                return new ContentFilterModel($app['neo4j.graph_manager'], $app['fields']['filters']['content'], $app['locale.options']['default']);
            }
        );

        $app['users.privacy.model'] = $app->share(
            function ($app) {

                return new PrivacyModel($app['neo4j.graph_manager'], $app['dispatcher'], $app['fields']['privacy'], $app['locale.options']['default']);
            }
        );

        $app['users.profile.tag.model'] = $app->share(
            function ($app) {

                return new ProfileTagModel($app['neo4j.client']);
            }
        );

        $app['users.answers.model'] = $app->share(
            function ($app) {

                return new AnswerModel($app['neo4j.graph_manager'], $app['questionnaire.questions.model'], $app['users.manager'], $app['dispatcher']);
            }
        );

        $app['users.questions.model'] = $app->share(
            function ($app) {

                return new QuestionPaginatedModel($app['neo4j.graph_manager'], $app['users.answers.model']);
            }
        );

        $app['old.users.questions.compare.model'] = $app->share(
            function ($app) {

                return new OldQuestionComparePaginatedModel($app['neo4j.client']);
            }
        );

        $app['users.questions.compare.model'] = $app->share(
            function ($app) {

                return new QuestionComparePaginatedModel($app['neo4j.client'], $app['users.answers.model']);
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

                return new MatchingModel($app['dispatcher'], $app['neo4j.graph_manager'], $app['users.content.model'], $app['users.answers.model']);

            }
        );
        $app['users.similarity.model'] = $app->share(
            function ($app) {

                return new SimilarityModel($app['dispatcher'], $app['neo4j.graph_manager'], $app['links.model'], $app['users.questions.model'], $app['users.content.model'], $app['users.profile.model']);
            }
        );

        $app['users.recommendation.users.model'] = $app->share(
            function ($app) {

                return new UserRecommendationPaginatedModel($app['neo4j.graph_manager'], $app['users.profileFilter.model'], $app['users.userFilter.model']);
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

        $app['users.ghostuser.manager'] = $app->share(
            function ($app) {

                return new GhostUserManager($app['neo4j.graph_manager'], $app['users.manager']);
            }
        );

        $app['users.socialprofile.manager'] = $app->share(
            function ($app) {

                return new SocialProfileManager($app['neo4j.graph_manager'], $app['users.tokens.model'], $app['users.lookup.model']);
            }
        );

        $app['users.stats.manager'] = $app->share(
            function ($app) {

                return new UserStatsManager($app['neo4j.graph_manager'], $app['orm.ems']['mysql_brain'], $app['users.tokens.model'], $app['users.groups.model'], $app['users.relations.model']);
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

                return new QuestionModel($app['neo4j.graph_manager'], $app['users.manager']);
            }
        );

        $app['links.model'] = $app->share(
            function ($app) {

                return new LinkModel($app['neo4j.graph_manager']);
            }
        );

        $app['users.filterusers.manager'] = $app->share(
            function ($app) {

                return new FilterUsersManager($app['fields']['user'], $app['neo4j.graph_manager'], $app['users.profileFilter.model'], $app['users.userFilter.model']);
            }
        );

        $app['users.filtercontent.manager'] = $app->share(
            function ($app) {

                return new FilterContentManager($app['neo4j.graph_manager'], $app['users.contentFilter.model'], $app['validator.service']);
            }
        );

        $app['users.groups.model'] = $app->share(
            function ($app) {

                return new GroupModel($app['neo4j.graph_manager'], $app['users.manager'], $app['users.filterusers.manager']);
            }
        );

        $app['users.threadusers.manager'] = $app->share(
            function ($app) {

                return new UsersThreadManager($app['neo4j.graph_manager'], $app['users.filterusers.manager'], $app['users.manager']);
            }
        );

        $app['users.threadcontent.manager'] = $app->share(
            function ($app) {

                return new ContentThreadManager($app['neo4j.graph_manager'], $app['links.model'], $app['users.filtercontent.manager']);
            }
        );

        $app['users.threads.manager'] = $app->share(
            function ($app) {

                return new ThreadManager($app['neo4j.graph_manager'], $app['users.manager'], $app['users.threadusers.manager'], $app['users.threadcontent.manager'], $app['validator.service']);
            }
        );

        $app['users.threads.paginated.model'] = $app->share(
            function ($app) {

                return new ThreadPaginatedModel($app['neo4j.graph_manager'], $app['users.threads.manager']);
            }
        );

        $app['users.invitations.model'] = $app->share(
            function ($app) {
                return new InvitationModel($app['tokenGenerator.service'], $app['neo4j.graph_manager'], $app['users.groups.model'], $app['users.manager'], $app['admin_domain_plus_post']);
            }
        );

        $app['users.relations.model'] = $app->share(
            function ($app) {

                return new RelationsModel($app['neo4j.graph_manager'], $app['dbs']['mysql_social'], $app['users.manager']);
            }
        );

        $app['enterpriseUsers.model'] = $app->share(
            function ($app) {

                return new EnterpriseUserModel($app['neo4j.graph_manager']);
            }
        );

        $app['enterpriseUsers.communities.model'] = $app->share(
            function ($app) {

                return new CommunityModel($app['neo4j.graph_manager'], $app['users.manager']);
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
