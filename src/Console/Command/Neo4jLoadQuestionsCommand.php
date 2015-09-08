<?php

namespace Console\Command;

use Console\ApplicationAwareCommand;
use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
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
        $user = $usersModel->getById($userId);

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

        $template = "MATCH (q:Question)"
            . " OPTIONAL MATCH (q)<-[:IS_ANSWER_OF]-(a:Answer)"
            . " RETURN q AS question, collect(a) AS answers"
            . " ORDER BY question.ranking DESC";

        $query = new Query($this->app['neo4j.client'], $template);

        $result = $query->getResultSet();

        $all = array();
        foreach ($result as $one) {
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

                    $answers_es = $answers_en = array();
                    foreach ($allQuestion['answers'] as $answer) {
                        $answers_es[] = array('answerId' => $answer['id'], 'text' => $answer['text_es']);
                        $answers_en[] = array('answerId' => $answer['id'], 'text' => $answer['text_en']);
                    }

                    $question_es = array(
                        'questionId' => $allQuestion['id'],
                        'text' => $allQuestion['text_es'],
                        'locale' => 'es',
                        'answers' => $answers_es,
                    );
                    $question_en = array(
                        'questionId' => $allQuestion['id'],
                        'text' => $allQuestion['text_en'],
                        'locale' => 'en',
                        'answers' => $answers_en,
                    );

                    try {
                        $questionModel->update($question_es);
                        $questionModel->update($question_en);
                        $updated += 1;
                    } catch (ValidationException $e) {
                        $this->output->writeln('There where some errors creating this question:');
                        $this->output->writeln(print_r($question_es, true));
                        $this->output->writeln(print_r($question_en, true));
                        $this->output->writeln(print_r($e->getErrors(), true));
                    }
                }
            } else {
                // Create from scratch
                $answers_es = array();
                foreach ($csvQuestion['answers'] as $answer) {
                    $answers_es[] = array('text' => $answer['text_es']);
                }
                $question_es = array(
                    'text' => $csvQuestion['text_es'],
                    'locale' => 'es',
                    'userId' => $user['qnoow_id'],
                    'answers' => $answers_es,
                );

                try {
                    $question = $questionModel->create($question_es);
                    $question_en = array(
                        'id' => $question['id'],
                        'text' => $csvQuestion['text_en'],
                        'locale' => 'en',
                    );
                    $answers_en = array();
                    foreach ($question['answers'] as $answer) {

                        $keyAnswer = $this->find($answer['answerId'], $csvQuestion['answers']);

                        if (!is_null($keyAnswer)) {
                            $answers_en[] = array('answerId' => $answer['answerId'], 'text' => $csvQuestion['answers'][$keyAnswer]['text_en']);
                        }
                    }
                    $question_en['answers'] = $answers_en;
                    $questionModel->update($question_en);
                    $created += 1;
                } catch (ValidationException $e) {
                    $this->output->writeln('There where some errors creating this question:');
                    $this->output->writeln(print_r($question_es, true));
                    $this->output->writeln(print_r($e->getErrors(), true));
                }
            }
        }
        $this->output->writeln(sprintf('%s questions have been updated', $updated));
        $this->output->writeln(sprintf('%s questions have been created', $created));
    }

    protected function find($text, $haystack)
    {
        foreach ($haystack as $key => $item) {
            if ($text === $item['text'] || $text === $item['text_es']) {
                return $key;
            }
        }

        return null;
    }

}