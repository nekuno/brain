<?php
/**
 * @author Roberto Martinez yawmoght@gmail.com>
 */

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\User;
use Model\User\ProfileModel;
use Manager\UserManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Neo4jStatsCommand extends ApplicationAwareCommand
{

    protected $similaritiesDistribution = array(
        'interests' => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'zero' => 0),
        'questions' => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'zero' => 0),
        'skills' => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'zero' => 0),
        'similarity' => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'zero' => 0),
    );

    protected $likesDistribution = array();

    protected function configure()
    {
        $this->setName('neo4j:stats')
            ->setDescription('Get stats from database. It may be slow.')
            ->addOption('popularity', null, InputOption::VALUE_NONE, 'Popularity distribution', null)
            ->addOption('similarity', null, InputOption::VALUE_NONE, 'Similarity distribution', null)
            ->addOption('likes_per_user', null, InputOption::VALUE_NONE, 'Link likes per user', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $popularity = $input->getOption('popularity');
        $similarity = $input->getOption('similarity');
        $likes_per_user = $input->getOption('likes_per_user');

        $output->writeln('Getting user list.');
        /** @var UserManager $userManager */
        $userManager = $this->app['users.manager'];

        $users = $userManager->getAll();
        $output->writeln('Got ' . count($users) . ' users.');

        //checking status

//        if ($popularity) {
//            $this->checkPopularity($users, $popularity, $output);
//        }
//
        if ($similarity) {
            $this->checkSimilarity($users, $output);
        }

        if ($likes_per_user) {
            $this->checkLikesPerUser($users, $output);
        }

        $output->writeln('Finished.');
    }

    /**
     * @param $users User[]
     * @param $output OutputInterface
     */
    private function checkSimilarity(array $users, OutputInterface $output)
    {
        $path = dirname(__FILE__) . '/../../../var/logs/popularity-' . date('d-m-Y');

        foreach ($users as $user) {
            $output->writeln('Getting similarities for user ' . $user->getId());
            $similarities = $this->app['users.similarity.model']->getAllCurrentByUser($user->getId());

            foreach ($similarities as $similarity) {
                $this->similaritiesDistribution['interests'][$similarity['interests'] == 0 ? 'zero' : floor($similarity['interests'] / 0.1)]++;
                $this->similaritiesDistribution['questions'][$similarity['questions'] == 0 ? 'zero' : floor($similarity['questions'] / 0.1)]++;
                $this->similaritiesDistribution['skills'][$similarity['skills'] == 0 ? 'zero' : floor($similarity['skills'] / 0.1)]++;
                $this->similaritiesDistribution['similarity'][$similarity['similarity'] == 0 ? 'zero' : floor($similarity['similarity'] / 0.1)]++;
            }
        }

        $handle = fopen($path, 'a+');
        fwrite($handle, print_r($this->similaritiesDistribution, TRUE));
        fclose($handle);

    }

    /**
     * @param $users User[]
     * @param $output
     */
    private function checkLikesPerUser($users, $output)
    {
        $path = dirname(__FILE__) . '/../../../var/logs/likes-per-user-' . date('d-m-Y');

        foreach ($users as $user) {
            $rates = $this->app['users.rate.model']->getRatesByUser($user->getId(), User\RateModel::LIKE);
            $this->likesDistribution[intval(floor(count($rates) / 10))]++;
        }

        $handle = fopen($path, 'a+');
        fwrite($handle, print_r($this->likesDistribution, TRUE));
        fclose($handle);
    }


}