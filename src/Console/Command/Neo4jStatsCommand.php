<?php
/**
 * @author Roberto Martinez yawmoght@gmail.com>
 */

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\Popularity\Popularity;
use Model\User;
use Manager\UserManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;

class Neo4jStatsCommand extends ApplicationAwareCommand
{

    protected $similaritiesDistribution = array(
        'interests' => array('zero' => 0),
        'questions' => array('zero' => 0),
        'skills' => array('zero' => 0),
        'similarity' => array('zero' => 0),
    );

    protected $likesDistribution = array('zero' => 0);

    protected $popularitiesDistribution = array(
        'linear' => array('popularity' => array('zero' => 0),
                            'unpopularity' => array('zero' => 0)),
        'logarithmic' => array('popularity' => array('zero' => 0),
                                'unpopularity' => array('zero' => 0)),
    );

    protected function configure()
    {
        $this->setName('neo4j:stats')
            ->setDescription('Get stats from database. It may be slow.')
            ->addOption('popularity', null, InputOption::VALUE_NONE, 'Popularity distribution', null)
            ->addOption('similarity', null, InputOption::VALUE_NONE, 'Similarity distribution', null)
            ->addOption('likes_per_user', null, InputOption::VALUE_NONE, 'Link likes per user', null)
            ->addOption('ghost', null, InputOption::VALUE_NONE, 'Include ghost user related metrics', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $popularity = $input->getOption('popularity');
        $similarity = $input->getOption('similarity');
        $likes_per_user = $input->getOption('likes_per_user');
        $includeGhost = $input->getOption('ghost');

        $output->writeln('Getting user list.');
        /** @var UserManager $userManager */
        $userManager = $this->app['users.manager'];

        $users = $userManager->getAll($includeGhost);
        $output->writeln('Got ' . count($users) . ' users.');

        //checking status

        if ($popularity) {
            $this->checkPopularity($output);
        }

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
        $path = $this->buildFilePath('similarity');

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

        $this->exportDistribution($this->similaritiesDistribution['interests'], $path, 'Similarity by interest');
        $this->exportDistribution($this->similaritiesDistribution['questions'], $path, 'Similarity by questions');
        $this->exportDistribution($this->similaritiesDistribution['skills'], $path, 'Similarity by skills');
        $this->exportDistribution($this->similaritiesDistribution['similarity'], $path, 'Total Similarity');
    }

    /**
     * @param $users User[]
     * @param $output
     */
    private function checkLikesPerUser($users, OutputInterface $output)
    {
        $path = $this->buildFilePath('likes-per-user');
        $output->writeln('Getting likes per user');

        foreach ($users as $user) {
            $rates = $this->app['users.rate.model']->getRatesByUser($user->getId(), User\RateModel::LIKE);
            $output->writeln(sprintf('Got %d likes from user %d ', count($rates), $user->getId()));
            $this->likesDistribution[intval(floor(count($rates) / 10))]++;
        }

        $this->exportDistribution($this->likesDistribution, $path, 'Likes per user');
    }

    private function checkPopularity(OutputInterface $output)
    {
        $path = $this->buildFilePath('popularity');

        $paginationSize = 1000;
        $offset = 0;

        $request = new Request();
        $request->query->set('limit', $paginationSize);

        $paginator = $this->app['paginator'];
        $oldPaginationSize = $paginator->getMaxLimit();
        $paginator->setMaxLimit($paginationSize);

        $output->writeln('Getting popularities');
        do {
            $request->query->set('offset', $offset);

            $result = $this->app['paginator']->paginate(array(), $this->app['links.popularity.paginated.model'], $request);
            $offset += $paginationSize; //Use nextUrl if we use filters

            /** @var Popularity[] $popularities */
            $popularities = $result['items'];

            foreach ($popularities as $popularity) {
                $this->popularitiesDistribution['linear']['popularity'][$popularity->getPopularity() == 0 ? 'zero' : floor($popularity->getPopularity() / 0.1)]++;
                $this->popularitiesDistribution['linear']['unpopularity'][$popularity->getUnpopularity() == 0 ? 'zero' : floor($popularity->getUnpopularity() / 0.1)]++;
                $this->popularitiesDistribution['logarithmic']['popularity'][$popularity->getPopularity() == 0 ? 'zero' : -floor(log10($popularity->getPopularity()))]++;
                $this->popularitiesDistribution['logarithmic']['unpopularity'][$popularity->getUnpopularity() == 0 ? 'zero' : -floor(log10($popularity->getUnpopularity()))]++;
            }
        } while (count($popularities) > 0);

        $paginator->setMaxLimit($oldPaginationSize);

        $this->exportDistribution($this->popularitiesDistribution['linear']['popularity'], $path, 'Linear popularity');
        $this->exportDistribution($this->popularitiesDistribution['linear']['unpopularity'], $path, 'Linear unpopularity');
        $this->exportDistribution($this->popularitiesDistribution['logarithmic']['popularity'], $path, 'Logarithmic popularity');
        $this->exportDistribution($this->popularitiesDistribution['logarithmic']['unpopularity'], $path, 'Logarithmic unpopularity');
    }

    private function exportDistribution(array $distribution, $path, $text = null)
    {
        $array = array('zero' => $distribution['zero']);
        $keys = array('zero' => 'zero');
        unset($distribution['zero']);

        ksort($distribution);
        end($distribution);
        for ($i = 0; $i <= key($distribution); $i++) {
            $array[$i] = isset($distribution[$i]) ? $distribution[$i] : 0;
            $keys[$i] = $i;
        }

        $handle = fopen($path, 'a+');
        if ($text){
            fwrite($handle, $text . PHP_EOL);
        }
        fputcsv($handle, $keys);
        fputcsv($handle, $array);
        fclose($handle);
    }

    private function buildFilePath($name)
    {
        return dirname(__FILE__) . '/../../../var/logs/' . $name . '-' . date('d-m-Y') .'.csv';
    }

}