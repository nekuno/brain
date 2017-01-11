<?php

namespace Model\Parser;

use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class LinkedinParser extends BaseParser
{
    public function parse($profileUrl, LoggerInterface $logger = null)
    {
        //$this->client->setHeader('cookie', 'bcookie="...');
        //$proxy = '124.232.165.98:80';
        //$this->client->getClient()->setDefaultOption('proxy', array('http' => $proxy, 'https' => $proxy));
        $crawler = $this->client->request('GET', $profileUrl);

        $skills = $this->getSkills($crawler, $logger);
        $languages = $this->getLanguages($crawler, $logger);

        //$this->client->removeHeader('cookie');
        //$this->client->getClient()->setDefaultOption('proxy', null);

        return array('skills' => $skills, 'languages' => $languages);
    }

    private function getSkills(Crawler $crawler, LoggerInterface $logger = null)
    {
        $skills = array_filter(
            $crawler->filter('#background-skills > #skills-item .endorse-item-name a')->each(
                function (Crawler $node) use ($logger) {
                    return $this->getSkill($node, $logger);
                }
            )
        );
        if (empty($skills)) {
            $skills = array_filter(
                $crawler->filter('#skills .skill a')->each(
                    function (Crawler $node) use ($logger) {
                        return $this->getSkill($node, $logger);
                    }
                )
            );
        }
        if (empty($skills)) {
            $skills = array_filter(
                $crawler->filter('#background-skills > #skills-item .endorse-item-name-text')->each(
                    function (Crawler $node) use ($logger) {
                        return $node->text();
                    }
                )
            );
        }

        return $skills;
    }

    private function getSkill(Crawler $node, LoggerInterface $logger = null)
    {
        $href = $node->attr('href');
        $text = $node->text();
        if (substr($text, -3) === '...') {
            $text = $this->getSkillFromLink($href);
        }

        if ($logger instanceof LoggerInterface) {
            $logger->info($text . ' skill added');
        }

        return $text ?: false;
    }

    private function getSkillFromLink($href)
    {
        $crawler = $this->client->request('GET', $href);

        if ($crawler->filter('meta[content="LinkedIn"]')->count() > 0) {
            return $crawler->filter('h1')->first()->text();
        }

        return false;
    }

    private function getLanguages(Crawler $crawler, LoggerInterface $logger = null)
    {
        $languages = array_filter(
            $crawler->filter('#background-languages > #languages > #languages-view li')->each(
                function (Crawler $node) use ($logger) {
                    return $this->getLanguage($node, $logger);
                }
            )
        );
        if (empty($languages)) {
            $languages = array_filter(
                $crawler->filter('#languages > ul > .language > .wrap')->each(
                    function (Crawler $node) use ($logger) {
                        return $this->getLanguage($node, $logger);
                    }
                )
            );
        }

        return $languages;
    }

    private function getLanguage(Crawler $node, LoggerInterface $logger = null)
    {
        $language = trim($node->filter('h4 > span')->text());
        $translatedLanguage = $this->translateTypicalLanguage($language);

        if ($logger instanceof LoggerInterface) {
            $logger->info($translatedLanguage . ' language added');
        }

        return $translatedLanguage ?: false;
    }

    private function translateTypicalLanguage($language)
    {
        switch ($language) {
            case 'Español':
                return 'Spanish';
            case 'Inglés':
                return 'English';
            case 'Francés':
                return 'French';
            case 'Alemán':
                return 'German';
            case 'Portugués':
                return 'Portuguese';
            case 'Italiano':
                return 'Italian';
            case 'Chino':
                return 'Chinese';
            case 'Japonés':
                return 'Japanese';
            case 'Ruso':
                return 'Russian';
            case 'Árabe':
                return 'Arabic';
            default:
                return $language;
        }
    }
}