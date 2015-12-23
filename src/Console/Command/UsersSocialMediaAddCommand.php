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
use Model\User\TokensModel;
use Model\UserModel;
use Service\AMQPManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UsersSocialMediaAddCommand extends ApplicationAwareCommand
{
    protected function configure()
    {

        $this->setName('users:social-media:add')
            ->setDescription('Creates a social profile for an user')
            ->addArgument('resource', InputArgument::OPTIONAL, 'Social network to add')
            ->addArgument('username', InputArgument::OPTIONAL, 'The username of the user in the social media')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Id or name of user to add the social network to', null)
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Name of CSV file to read users from', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $username = $input->getArgument('username');
        $resource = $input->getArgument('resource');
        $id = $input->getOption('id');
        $file = $input->getOption('file');

        if (!$file) {
            if (!($username && $resource)) {
                $output->writeln('Please add an user identifier and a resource.');
                $output->writeln('Optionally use the file option to load a CSV file with users and resources');
                return;
            } else {
                $toLoad = array(array($username, $resource, $id));
            }
        } else {
            $toLoad = $this->loadCSV($file);
        }

        foreach ($toLoad as $userToLoad)
        {
            $output->writeln('--------');
            $username = $userToLoad[0];
            $resource = $userToLoad[1];
            $id = $userToLoad[2];

            $output->writeln(sprintf('Loading user %s in %s', $username, $resource));

            if (!($username && $resource)){
                $output->writeln('Skipped user to load');
                continue;
            }

            if (!in_array($resource, TokensModel::getResourceOwners())){
                $output->writeln('Resource '.$resource.' not supported.');
                continue;
            }

            /** @var ResourceOwnerFactory $resourceOwnerFactory */
            $resourceOwnerFactory = $this->app['api_consumer.resource_owner_factory'];

            $resourceOwner = $resourceOwnerFactory->build($resource);

            //if not implemented for resource or request error when asking API
            try{
                $url = $resourceOwner->getProfileUrl(array('screenName'=>$username));
            } catch (\Exception $e){
                $output->writeln('ERROR: Could not get profile url for user '.$username. ' and resource '.$resource);
                $output->writeln('Reason: '.$e->getMessage());
                continue;
            }

            /** @var SocialProfileManager $socialProfileManager */
            $socialProfileManager = $this->app['users.socialprofile.manager'];
            $socialProfiles = $socialProfileManager->getByUrl($url);

            if (count($socialProfiles) == 0) {

                $output->writeln('Creating new social profile with url '. $url);

                if ($id) {
                    /** @var UserModel $userModel */
                    $userModel = $this->app['users.model'];
                    $user = $userModel->getById((integer)$id, true);
                    $id = $user['qnoow_id'];
                    $output->writeln('SUCCESS: Found user with id '.$id);
                } else {
                    /** @var GhostUserManager $ghostUserManager */
                    $ghostUserManager = $this->app['users.ghostuser.manager'];
                    $user = $ghostUserManager->create();
                    $id = $user->getId();
                    $output->writeln('SUCCESS: Created ghost user with id:' . $id);
                }

                $socialProfileArray = array($resource => $url);

                /** @var LookUpModel $lookupModel */
                $lookupModel = $this->app['users.lookup.model'];
                $lookupModel->setSocialProfiles($socialProfileArray, $id);
                $lookupModel->dispatchSocialNetworksAddedEvent($id, $socialProfileArray);

                /** @var SocialProfileManager $socialProfileManager $sps */
                $socialProfiles = $socialProfileManager->getByUrl($url);

            } else {
                $output->writeln('Found an already existing social profile with url '.$url);
            }

            $output->write('Enqueuing fetching. ');

            /* @var $amqpManager AMQPManager */
            $amqpManager = $this->app['amqpManager.service'];

            /** @var SocialProfile $socialProfile */
            foreach ($socialProfiles as $socialProfile) {
                $amqpManager->enqueueMessage(array(
                    'userId' => $socialProfile->getUserId(),
                    'resourceOwner' => $socialProfile->getResource(),
                    'public' => true,
                ), 'brain.fetching.links');
            }

            $output->writeln('Success!');
        }

        $output->writeln('Finished.');

    }

    private function loadCSV($file)
    {

        $users = array();
        $first = true;

        if (($handle = fopen($file, 'r')) !== false) {

            while (($data = fgetcsv($handle, 0, ';')) !== false) {

                if ($first) {
                    $first = false;
                    continue;
                }

                $users[] = array($data[1], $data[2], $data[3]);

            }
            fclose($handle);
        }

        return $users;

    }
}