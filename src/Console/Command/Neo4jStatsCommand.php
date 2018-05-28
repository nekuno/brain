<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Model\Popularity\PopularityManager;
use Model\Rate\RateManager;
use Model\Similarity\Similarity;
use Model\Similarity\SimilarityManager;
use Model\User\User;
use Model\User\UserManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Neo4jStatsCommand extends ApplicationAwareCommand
{
    protected static $defaultName = 'neo4j:stats';

    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var SimilarityManager
     */
    protected $similarityManager;

    /**
     * @var RateManager
     */
    protected $rateManager;

    /**
     * @var PopularityManager
     */
    protected $popularityManager;

    protected $similaritiesDistribution = array(
        'interests' => array('zero' => 0),
        'questions' => array('zero' => 0),
        'skills' => array('zero' => 0),
        'similarity' => array('zero' => 0),
    );

    protected $likesPerUserDistribution = array('zero' => 0);
    protected $likesPerLinkDistribution = array('zero' => 0);

    // $this[1] = popularity for likes = 1
    protected $popularityByLikes = array(
        'popularity' => array(),
        'unpopularity' => array()
    );

    protected $popularityCalculations = array(
        'countCommon' => array(),
        'unpopularityCommon' => array(),
        'countOnly' => array(),
        'popularityOnly' => array(),
    );

    public function __construct(LoggerInterface $logger, UserManager $userManager, SimilarityManager $similarityManager, RateManager $rateManager, PopularityManager $popularityManager)
    {
        parent::__construct($logger);
        $this->userManager = $userManager;
        $this->similarityManager = $similarityManager;
        $this->rateManager = $rateManager;
        $this->popularityManager = $popularityManager;
    }

    protected function configure()
    {
        $this
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

        $users = $this->userManager->getAll($includeGhost);
        $output->writeln('Got ' . count($users) . ' users.');

        if ($popularity) {
            $this->checkPopularity($users, $output, $includeGhost);
        }

        if ($similarity) {
            $this->checkSimilarity($users, $output, $includeGhost);
        }

        if ($likes_per_user) {
            $this->checkLikesPerUser($users, $output);
        }

        $output->writeln('Finished.');
    }

    /**
     * @param $users User[]
     * @param $output OutputInterface
     * @param $includeGhost
     */
    private function checkSimilarity(array $users, OutputInterface $output, $includeGhost)
    {
        $path = $this->buildFilePath('similarity');

        foreach ($users as $user) {
            $output->writeln('Getting similarities for user ' . $user->getId());
            $similarityData = $this->similarityManager->getAllCurrentByUser($user->getId(), $includeGhost);

            /** @var Similarity[] $similarities */
            $similarities = $similarityData['similarities'];
            foreach ($similarities as $similarity) {
                $this->similaritiesDistribution['interests'][$this->toInterval($similarity->getInterests())]++;
                $this->similaritiesDistribution['questions'][$this->toInterval($similarity->getQuestions())]++;
                $this->similaritiesDistribution['skills'][$this->toInterval($similarity->getSkills())]++;
                $this->similaritiesDistribution['similarity'][$this->toInterval($similarity->getSimilarity())]++;
            }

            $popularityData = $similarityData['popularityData'];
            foreach ($popularityData as $singlePopularityData) {
                $this->popularityCalculations['countCommon'][floor($singlePopularityData['countCommon'] / 10)]++;
                $this->popularityCalculations['unpopularityCommon'][round($singlePopularityData['unpopularityCommon'])]++;
                $this->popularityCalculations['countOnly'][floor($singlePopularityData['countOnlyA'] / 10)]++;
                $this->popularityCalculations['popularityOnly'][round($singlePopularityData['popularityOnlyA'])]++;
                $this->popularityCalculations['countOnly'][floor($singlePopularityData['countOnlyB'] / 10)]++;
                $this->popularityCalculations['popularityOnly'][round($singlePopularityData['popularityOnlyB'])]++;
            }
        }

        $this->exportDistribution($this->similaritiesDistribution['interests'], $path, 'Similarity by interest');
        $this->exportDistribution($this->similaritiesDistribution['questions'], $path, 'Similarity by questions');
        $this->exportDistribution($this->similaritiesDistribution['skills'], $path, 'Similarity by skills');
        $this->exportDistribution($this->similaritiesDistribution['similarity'], $path, 'Total Similarity');

        $this->exportDistribution($this->popularityCalculations['countCommon'], $path, 'Common content count, decade');
        $this->exportDistribution($this->popularityCalculations['unpopularityCommon'], $path, 'Common content unpopularity, rounded');
        $this->exportDistribution($this->popularityCalculations['countOnly'], $path, 'Only one user content count, decade');
        $this->exportDistribution($this->popularityCalculations['popularityOnly'], $path, 'Only one user content popularity, rounded');
    }

    protected function toInterval($number)
    {
        return $number == 0 ? 'zero' : floor($number / 0.1);
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
            $rates = $this->rateManager->getRatesByUser($user->getId(), RateManager::LIKE);
            $output->writeln(sprintf('Got %d likes from user %d ', count($rates), $user->getId()));
            $this->likesPerUserDistribution[intval(floor(count($rates) / 10))]++;
        }

        $this->exportDistribution($this->likesPerUserDistribution, $path, 'Likes per user');
    }

    /**
     * @param User[] $users
     * @param OutputInterface $output
     * @param $includeGhost
     */
    private function checkPopularity(array $users, OutputInterface $output, $includeGhost)
    {
        $path = $this->buildFilePath('popularity');
        $output->writeln('Getting popularities');

        $maxPopularity = $this->popularityManager->getMaxPopularity();
        foreach ($users as $user) {
            $popularities = $this->popularityManager->getPopularitiesByUser($user->getId(), $includeGhost);

            foreach ($popularities as $popularity) {
                $this->likesPerLinkDistribution[$popularity->getAmount()]++;
            }
        }
        foreach ($this->likesPerLinkDistribution as $likes => $total) {

            $popularity = $this->popularityManager->calculatePopularity($likes, $maxPopularity->getAmount());

            //Avoid duplicates
            $this->likesPerLinkDistribution[$likes] = round($total / ($likes ?: 1));

            $this->popularityByLikes['popularity'][$likes] = $popularity->getPopularity();
            $this->popularityByLikes['unpopularity'][$likes] = $popularity->getUnpopularity();
        }

        $this->exportDistribution($this->popularityByLikes['popularity'], $path, 'Popularity');
        $this->exportDistribution($this->popularityByLikes['unpopularity'], $path, 'Unpopularity');
        $this->exportDistribution($this->likesPerLinkDistribution, $path, 'Likes per link');
    }

    private function exportDistribution(array $distribution, $path, $text = null)
    {
        $array = array('zero' => isset($distribution['zero']) ? $distribution['zero'] : 0);
        $keys = array('zero' => 'zero');
        unset($distribution['zero']);

        ksort($distribution);
        end($distribution);

        //build complete distribution array to facilitate graph creation
        for ($i = 0; $i <= key($distribution); $i++) {
            $array[$i] = isset($distribution[$i]) ? $distribution[$i] : 0;
            $keys[$i] = $i;
        }

        //calculate average
        $average = array('numerator' => 0, 'denominator' => 0);
        for ($i=0; $i < count($array) - 1; $i++) {
            $average['numerator'] += $i * $array[$i];
            $average['denominator'] += $array[$i];
        }
        $average['result'] = $average['denominator'] == 0 ? 0 : $average['numerator'] / $average['denominator'];

        //calculate median
        $median = 0;
        $counted = 0;
        for ($i = 0; $i < count($array); $i++) {
            if (null !== $counted) {
                $counted += $array[$i];
                if ($counted >= ($average['denominator'] / 2)) {
                    $median = $i;
                    $counted = null;
                }
            }
        }

        $handle = fopen($path, 'a+');
        if ($text) {
            fwrite($handle, $text . PHP_EOL);
        }
        fputcsv($handle, $keys);
        fputcsv($handle, $array);
        fwrite($handle, 'Average: ' . $average['result'] . PHP_EOL);
        fwrite($handle, 'Median: ' . $median . PHP_EOL);
        fwrite($handle, PHP_EOL);
        fclose($handle);
    }

    private function buildFilePath($name)
    {
        return dirname(__FILE__) . '/../../../var/logs/' . $name . '-' . date('d-m-Y') . '.csv';
    }

}