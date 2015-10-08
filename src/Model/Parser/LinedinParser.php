<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Model\Parser;

use Symfony\Component\DomCrawler\Crawler;

class LinkedinParser extends BaseParser
{
    public function parse($profileUrl)
    {
        $crawler = $this->client->request('GET', $profileUrl);

        $skills = $this->getSkills($crawler);
        $languages = $this->getLanguages($crawler);

        return array('skills' => $skills, 'languages' => $languages);
    }

    private function getSkills(Crawler $crawler)
    {
        return array_filter($crawler->filter('#background-skills > #skills-item > #skills-item-view > #profile-skills > .skills-section li a')->each(function (Crawler $node) {
            return $this->getSkill($node);
        }));
    }

    private function getSkill(Crawler $node)
    {
        $href = $node->attr('href');
        $text = $node->text();
        if(substr($text, -3) === '...') {
            $text = $this->getSkillFromLink($href);
        }

        return $text ?: false;
    }

    private function getSkillFromLink($href)
    {
        $crawler = $this->client->request('GET', $href);

        if($crawler->filter('meta[content="LinkedIn"]')->count() > 0) {
            return $crawler->filter('h1')->first()->text();
        }

        return false;
    }

    private function getLanguages(Crawler $crawler)
    {
        return array_filter($crawler->filter('#background-languages > #languages > #languages-view li')->each(function (Crawler $node) {
            return $this->getLanguage($node);
        }));
    }

    private function getLanguage(Crawler $node)
    {
        $language = $node->filter('h4 > span')->text();
        $translatedLanguage = $this->translateTypicalLanguage($language);

        return $translatedLanguage ?: false;
    }

    private function translateTypicalLanguage($language)
    {
        switch($language)
        {
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