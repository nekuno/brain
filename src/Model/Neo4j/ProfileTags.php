<?php

namespace Model\Neo4j;

use Model\LanguageText\LanguageTextManager;
use Model\Profile\ProfileTagManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class ProfileTags implements LoggerAwareInterface
{
    protected $profileTagModel;
    protected $languageTextManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var OptionsResult
     */
    protected $result;

    public function __construct(ProfileTagManager $profileTagModel, LanguageTextManager $languageTextManager)
    {

        $this->profileTagModel = $profileTagModel;
        $this->languageTextManager = $languageTextManager;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return OptionsResult
     */
    public function load()
    {

        $this->result = new OptionsResult();

        $tags = array(

            'Allergy' => array(
                array(
                    'locales' => array(
                        'es' => 'Polen',
                        'en' => 'Pollen'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Ácaros',
                        'en' => 'Mites'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Pelo de animal',
                        'en' => 'Animal hair'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Picaduras de insectos',
                        'en' => 'Insect bites'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Moho',
                        'en' => 'Mold'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Gluten',
                        'en' => 'Gluten'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Lactosa',
                        'en' => 'Lactose'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Marisco',
                        'en' => 'Shellfish'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Látex',
                        'en' => 'Latex'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Penicilina',
                        'en' => 'Penicillin'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Cucarachas',
                        'en' => 'Cockroaches'
                    ),
                ),
            ),
            'Handicap' => array(
                array(
                    'locales' => array(
                        'es' => 'ADD',
                        'en' => 'ADD'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Afasia',
                        'en' => 'Aphasia'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Apraxia',
                        'en' => 'Apraxia'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Procesamiento auditivo',
                        'en' => 'Auditory processing'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Autismo',
                        'en' => 'Autism'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Fibrosis cística',
                        'en' => 'Cystic fibrosis'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Parálisis cerebral',
                        'en' => 'Cerebral palsy'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Retrasos del desarrollo',
                        'en' => 'Developmental delays'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Síndrome de Down',
                        'en' => 'Down Syndrome'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Dislexia',
                        'en' => 'Dyslexia'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Desórdenes emocionales',
                        'en' => 'Emotional disorders'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Síndrome de alcoholismo fetal',
                        'en' => 'Fetal alcohol syndrome'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Discapacidad auditiva',
                        'en' => 'Hearing impairment'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Dificultades de aprendizaje',
                        'en' => 'Learning disabilities'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Retraso mental',
                        'en' => 'Mental retardation'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Problemas neurológicos',
                        'en' => 'Neurological disabilities'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Trastorno convulsivo',
                        'en' => 'Seizure disorder'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Discapacidad visual',
                        'en' => 'Visual impairment'
                    ),
                ),
            ),
            'Sports' => array(
                array(
                    'locales' => array(
                        'es' => 'Buceo',
                        'en' => 'Diving'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Natación',
                        'en' => 'Swimming'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Natación sincronizada',
                        'en' => 'Synchronized swimming'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Water polo',
                        'en' => 'Water polo'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Baloncesto',
                        'en' => 'Basketball'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Canoa',
                        'en' => 'Canoe'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'BMX',
                        'en' => 'BMX'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Ciclismo de montaña',
                        'en' => 'Mountain biking'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Ciclismo',
                        'en' => 'Cycling'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Gimnasia artística',
                        'en' => 'Artistic gymnastics'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Gimnasia rítmica',
                        'en' => 'Rhytmic gymnastics'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Gimnasia de trampolín',
                        'en' => 'Trampoline gymnastics'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Voleibol',
                        'en' => 'Volleyball'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Equitación',
                        'en' => 'Equestrian'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Lucha',
                        'en' => 'Wrestling'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Arquería',
                        'en' => 'Archery'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Atletismo',
                        'en' => 'Athletics'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Badminton',
                        'en' => 'Badminton'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Béisbol',
                        'en' => 'Baseball'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Boxeo',
                        'en' => 'Boxing'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Esgrima',
                        'en' => 'Fencing'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Hockey',
                        'en' => 'Hockey'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Fútbol',
                        'en' => 'Football'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Golf',
                        'en' => 'Golf'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Balonmano',
                        'en' => 'Handball'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Judo',
                        'en' => 'Judo'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Karate',
                        'en' => 'Karate'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Remo',
                        'en' => 'Rowing'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Rugby',
                        'en' => 'Rubgy'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Navegación',
                        'en' => 'Sailing'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Disparo',
                        'en' => 'Shooting'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Monopatín',
                        'en' => 'Skateboarding'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Softball',
                        'en' => 'Softball'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Escalada',
                        'en' => 'Climbing'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Surf',
                        'en' => 'Surfing'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Ténis de mesa',
                        'en' => 'Table tennis'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'taekwondo',
                        'en' => 'Taekwondo'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Tennis',
                        'en' => 'Tennis'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Levantamiento de pesas',
                        'en' => 'Weightlifting'
                    ),
                ),
            ),
            'Creative' => array(
                array(
                    'locales' => array(
                        'es' => 'Punto de cruz',
                        'en' => 'Cross-stitch'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Bordado',
                        'en' => 'Embroidery'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Tejido de punto',
                        'en' => 'Knitting'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Patchwork',
                        'en' => 'Patchwork'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Costura',
                        'en' => 'Sewing'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Tapices',
                        'en' => 'Tapestry'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Caligrafía',
                        'en' => 'Calligraphy'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Collage',
                        'en' => 'Collage'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Origami',
                        'en' => 'Origami'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Scrapbooking',
                        'en' => 'Scrapbooking'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Carpintería',
                        'en' => 'Carpentry'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Talla de madera',
                        'en' => 'Wood carving'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Cristalería',
                        'en' => 'Glassware'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Cerámica',
                        'en' => 'Pottery'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Hacer muñecas',
                        'en' => 'Doll making'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Miniaturas',
                        'en' => 'Miniatures'
                    ),
                ),
            ),
            'Ideology' => array(
                array(
                    'locales' => array(
                        'es' => 'Anarquismo',
                        'en' => 'Anarchism'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Centrismo',
                        'en' => 'Centrism'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Conservadurismo',
                        'en' => 'Conservatism'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Ecologismo',
                        'en' => 'Environmentalism'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Feminismo',
                        'en' => 'Feminism'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Liberalismo',
                        'en' => 'Liberalism'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Nacionalismo',
                        'en' => 'Nationalism'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Socialismo',
                        'en' => 'Socialism'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Comunismo',
                        'en' => 'Communism'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Progresismo',
                        'en' => 'Progressism'
                    ),
                ),
            ),
            'Language' => array(
                array(
                    'locales' => array(
                        'es' => 'Chino mandarín',
                        'en' => 'Mandarin Chinese'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Español',
                        'en' => 'Spanish'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Inglés',
                        'en' => 'English'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Hindi',
                        'en' => 'Hindi'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Árabe',
                        'en' => 'Arabic'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Portugués',
                        'en' => 'Portuguese'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Bengalí',
                        'en' => 'Bengali'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Ruso',
                        'en' => 'Russian'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Japonés',
                        'en' => 'Japanese'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Panyabí',
                        'en' => 'Punjabi'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Alemán',
                        'en' => 'German'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Francés',
                        'en' => 'French'
                    ),
                ),
                array(
                    'locales' => array(
                        'es' => 'Italiano',
                        'en' => 'Italian'
                    ),
                ),
            )
        );

        foreach ($tags as $label => $values) {

            $this->result->incrementTotal();

            foreach ($values as $value) {

                $tagId = $this->findTagId($label, $value);
                if (!$tagId)
                {
                    $googleGraphId = isset($value['googleGraphId']) ? $value['googleGraphId'] : null;
                    $tag = $this->profileTagModel->mergeTag($label, $googleGraphId);
                    $tagId = $tag['id'];
                    $this->result->incrementCreated();
                }

                foreach ($value['locales'] as $locale=>$text)
                {
                    $this->languageTextManager->merge($tagId, $locale, $text);
                    $this->result->incrementUpdated();
                }
            }
        }

        return $this->result;
    }

    protected function findTagId($label, array $value)
    {
        $locales = $value['locales'];

        $tagId = null;
        foreach ($locales as $locale=>$text)
        {
            $tagId = $this->languageTextManager->findNodeWithText($label, $locale, $text);
            if ($tagId)
            {
                break;
            }
        }

        return $tagId;
    }


} 