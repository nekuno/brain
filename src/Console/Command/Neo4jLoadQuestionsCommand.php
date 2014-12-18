<?php

namespace Console\Command;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Questionnaire\QuestionModel;
use Model\UserModel;
use Silex\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class Neo4jLoadQuestionsCommand extends ApplicationAwareCommand
{

    /**
     * @var OutputInterface
     */
    protected $output;

    protected function configure()
    {
        $this
            ->setName('neo4j:load-questions')
            ->setDescription('Load questions from csv file')
            ->addArgument('userId', InputArgument::REQUIRED, 'The id of the user to associate questions')
            ->addArgument('file', InputArgument::REQUIRED, 'The name of the csv file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->output = $output;

        $userId = $input->getArgument('userId');
        $file = $input->getArgument('file');

        /* @var UserModel $usersModel */
        $usersModel = $this->app['users.model'];
        $users = $usersModel->getById($userId);

        if (count($users) === 0) {

            $output->writeln(sprintf('User with id "%s" not found', $userId));

            return;
        }
        $user = array_shift($users);

        if (!is_readable($file)) {

            $output->writeln(sprintf('file "%s" not found', $file));

            return;
        }

        $output->writeln(sprintf('Processing file "%s"...', $file));
        $csv = $this->loadCsv($file);
        $all = $this->getAll();
        $output->writeln(sprintf('Loaded %s questions from file "%s"', count($csv), $file));
        $output->writeln(sprintf('Loaded %s questions from Neo4j', count($all)));
        $this->process($csv, $all, $user);
        $output->writeln('Success!');
    }

    protected function extractAnswers($haystack, $keysArray)
    {
        $answers = array();
        foreach ($keysArray as $keys) {
            $answer = $this->extractAnswer($haystack, $keys);
            if (count($answer) > 0) {
                $answers[] = $answer;
            }
        }

        return $answers;
    }

    protected function extractAnswer($haystack, $keys)
    {
        $answer = array();

        foreach ($keys as $key => $keyPosition) {
            if (isset($haystack[$keyPosition]) && $haystack[$keyPosition] != '') {
                $answer[$key] = $haystack[$keyPosition];
            }
        }

        return $answer;
    }

    protected function loadCsv($file)
    {

        $questions = array();
        $differentAnswers = array();
        $first = true;

        if (($handle = fopen($file, 'r')) !== false) {

            while (($data = fgetcsv($handle, 0, ';')) !== false) {

                if ($first) {
                    $first = false;
                    continue;
                }

                $question = array(
                    'text' => $data[2],
                    'text_es' => $data[2],
                    'text_en' => $data[3],
                );

                $question['answers'] = $this->extractAnswers(
                    $data,
                    array(
                        array('text' => 9, 'text_es' => 9, 'text_en' => 4),
                        array('text' => 10, 'text_es' => 10, 'text_en' => 5),
                        array('text' => 11, 'text_es' => 11, 'text_en' => 6),
                        array('text' => 12, 'text_es' => 12, 'text_en' => 7),
                    )
                );

                $questions[] = $question;

            }
            fclose($handle);
        }

        foreach ($questions as $key => $question) {
            foreach ($question['answers'] as $answer) {
                if (count($answer) !== 3) {
                    $differentAnswers[] = $question;
                    unset($questions[$key]);
                    break;
                }
            }
        }

        return $questions;
    }

    protected function getAll()
    {
        /* @var $questionModel QuestionModel */
        $questionModel = $this->app['questionnaire.questions.model'];
        $all = array();
        foreach ($questionModel->getAll() as $one) {
            /* @var $one Row */
            /* @var $node Node */
            $node = $one->current();
            $question = array();
            $question['id'] = $node->getId();
            $question['text'] = $node->getProperty('text');
            $question['text_es'] = $node->getProperty('text_es');
            $question['text_en'] = $node->getProperty('text_en');
            $question['answers'] = array();
            foreach ($one['answers'] as $answer) {
                /* @var $answer Node */
                $question['answers'][] = array(
                    'id' => $answer->getId(),
                    'text' => $answer->getProperty('text'),
                    'text_es' => $answer->getProperty('text_es'),
                    'text_en' => $answer->getProperty('text_en'),
                );
            }
            $all[] = $question;
        }

        return $all;
    }

    protected function process($csv, $all, $user)
    {

        /* @var $questionModel QuestionModel */
        $questionModel = $this->app['questionnaire.questions.model'];

        $updated = $created = 0;
        foreach ($csv as $csvQuestion) {

            $keyQuestion = $this->find($csvQuestion['text_es'], $all);

            if (!is_null($keyQuestion)) {

                // Already exists
                $allQuestion = &$all[$keyQuestion];

                if (is_null($allQuestion['text_es']) || is_null($allQuestion['text_en'])) {
                    if (is_null($allQuestion['text_es'])) {
                        $allQuestion['text_es'] = $csvQuestion['text_es'];
                    }
                    if (is_null($allQuestion['text_en'])) {
                        $allQuestion['text_en'] = $csvQuestion['text_en'];
                    }

                    foreach ($csvQuestion['answers'] as $csvAnswer) {

                        $keyAnswer = $this->find($csvAnswer['text_es'], $allQuestion['answers']);

                        if (!is_null($keyAnswer)) {

                            $allAnswer = &$allQuestion['answers'][$keyAnswer];

                            if (is_null($allAnswer['text_es']) || is_null($allAnswer['text_en'])) {
                                if (is_null($allAnswer['text_es'])) {
                                    $allAnswer['text_es'] = $csvAnswer['text_es'];
                                }
                                if (is_null($allAnswer['text_en'])) {
                                    $allAnswer['text_en'] = $csvAnswer['text_en'];
                                }
                            }
                        }
                    }

                    $questionModel->update($allQuestion);
                    $updated += 1;
                }
            } else {
                // Create from scratch
                $csvQuestion['userId'] = $user['qnoow_id'];
                $questionModel->create($csvQuestion);
                $created += 1;
            }
        }
        $this->output->writeln(sprintf('%s questions have been updated', $updated));
        $this->output->writeln(sprintf('%s questions have been created', $created));
    }

    protected function find($text, $haystack)
    {
        foreach ($haystack as $key => $item) {
            if ($text === $item['text']) {
                return $key;
            }
        }

        return null;
    }

}