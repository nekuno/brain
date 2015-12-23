<?php
/**
 * Created by PhpStorm.
 * User: yawmoght
 * Date: 29/10/15
 * Time: 15:03
 */

namespace Console\Command;


use Console\ApplicationAwareCommand;
use Http\OAuth\Factory\ResourceOwnerFactory;
use Model\User\LookUpModel;
use Model\User\GhostUser\GhostUserManager;
use Model\User\SocialNetwork\SocialProfile;
use Model\User\SocialNetwork\SocialProfileManager;
use Model\User\Thread\ThreadManager;
use Model\User\TokensModel;
use Model\UserModel;
use Service\AMQPManager;
use Service\Recommendator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UsersThreadsCreateCommand extends ApplicationAwareCommand
{
    protected function configure()
    {

        $this->setName('users:threads:create')
            ->setDescription('Creates threads for users')
            ->addArgument('scenario', InputArgument::REQUIRED, 'Set of threads to add. Options available: default')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Create them to all users', null)
            ->addOption('userId', null, InputOption::VALUE_REQUIRED, 'Id of thread owner', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $scenario = $input->getArgument('scenario');
        $all = $input->getOption('all');
        $userId = $input->getOption('userId');

        if (!in_array($scenario, array('default'))) {
            $output->writeln('Scenario not valid. Available scenarios: default.');
            return;
        }

        if (!($all || $userId)) {
            $output->writeln('Please specify userId or all users');
            return;
        }

        /** @var UserModel $userModel */
        $userModel = $this->app['users.model'];

        $users = array();
        if ($all) {
            $users = $userModel->getAll();
        } else if ($userId) {
            $users = array($userModel->getById($userId, true));
        }

        $threads = $this->loadThreads($scenario);

        /** @var ThreadManager $threadManager */
        $threadManager = $this->app['users.threads.manager'];
        /** @var Recommendator $recommendator */
        $recommendator = $this->app['recommendator.service'];

        foreach ($users as $user) {
            foreach ($threads as $threadProperties){
                $thread = $threadManager->create($user['qnoow_id'], $threadProperties);

                $result = $recommendator->getRecommendationFromThread($thread);

                $threadManager->cacheResults($thread,
                    array_slice($result['items'], 0, 5),
                    $result['pagination']['total']);

            }
            $output->writeln('Added threads for scenario '.$scenario.' and user with id '.$user['qnoow_id']);
        }


    }

    protected function loadThreads($scenario)
    {
        $threads = array(
            'default' => array(
                array(
                    'name' => 'Chicas de Madrid',
                    'category' => ThreadManager::LABEL_THREAD_USERS,
                    'filters' => array(
                        'profileFilters' => array(
                            'birthday' => array(
                                'min' => $this->YearsToBirthday(22),
                                'max' => $this->YearsToBirthday(32),
                            ),
                            'location' => array(
                                'distance' => 10,
                                'location' => array(
                                    'latitude' => 40.4167754,
                                    'longitude' => -3.7037901999999576,
                                    'address' => 'Madrid, Madrid, Spain'
                                )
                            )
                        ),
                        'order' => 'content',
                    )
                ),
                array(
                    'name' => 'Música',
                    'category' => ThreadManager::LABEL_THREAD_CONTENT,
                    'filters' => array(
                        'type' => 'Audio'
                    )
                ),
                array(
                    'name' => 'Vídeos',
                    'category' => ThreadManager::LABEL_THREAD_CONTENT,
                    'filters' => array(
                        'type' => 'Video'
                    )
                ),
                array(
                    'name' => 'Imágenes',
                    'category' => ThreadManager::LABEL_THREAD_CONTENT,
                    'filters' => array(
                        'type' => 'Image'
                    )
                ),
                array(
                    'name' => 'Contenidos de Madrid',
                    'category' => ThreadManager::LABEL_THREAD_CONTENT,
                    'filters' => array(
                        'tag' => 'madrid'
                    )
                ),
                array(
                    'name' => 'Noticias',
                    'category' => ThreadManager::LABEL_THREAD_CONTENT,
                    'filters' => array(
                        'tag' => 'noticias'
                    )
                ),
                array(
                    'name' => 'Los mejores contenidos para ti',
                    'category' => ThreadManager::LABEL_THREAD_CONTENT,
                ),
            )
        );

        if (!isset($threads[$scenario])){
            return null;
        }

        return $threads[$scenario];
    }

    protected function YearsToBirthday($years){
        $now = new \DateTime();
        $birthday = $now->modify('-'.$years.' years')->format('Y-m-d');
        return $birthday;
    }
}