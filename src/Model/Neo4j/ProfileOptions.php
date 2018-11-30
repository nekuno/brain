<?php

namespace Model\Neo4j;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class ProfileOptions implements LoggerAwareInterface
{
    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var OptionsResult
     */
    protected $result;

    public function __construct(GraphManager $gm)
    {
        $this->gm = $gm;
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

        $options = array(
            'Alcohol' => array(
                array(
                    'id' => 'yes',
                    'name_en' => 'Yes',
                    'name_es' => 'Sí',
                ),
                array(
                    'id' => 'no',
                    'name_en' => 'No',
                    'name_es' => 'No',
                ),
                array(
                    'id' => 'occasionally',
                    'name_en' => 'Occasionally',
                    'name_es' => 'Ocasionalmente',
                ),
                array(
                    'id' => 'socially-on-parties',
                    'name_en' => 'Socially/On parties',
                    'name_es' => 'Socialmente/En fiestas',
                ),
            ),
            'CivilStatus' => array(
                array(
                    'id' => 'single',
                    'name_en' => 'Single',
                    'name_es' => 'Soltero/a',
                ),
                array(
                    'id' => 'married',
                    'name_en' => 'Married',
                    'name_es' => 'Casado/a',
                ),
                array(
                    'id' => 'open-relationship',
                    'name_en' => 'Open relationship',
                    'name_es' => 'Relación abierta',
                ),
                array(
                    'id' => 'dating-someone',
                    'name_en' => 'Dating someone',
                    'name_es' => 'Saliendo con alguien',
                ),
            ),
            'Complexion' => array(
                array(
                    'id' => 'slim',
                    'name_en' => 'Thin',
                    'name_es' => 'Delgado',
                ),
                array(
                    'id' => 'normal',
                    'name_en' => 'Average build',
                    'name_es' => 'Promedio',
                ),
                array(
                    'id' => 'fat',
                    'name_en' => 'Full-figured',
                    'name_es' => 'Voluptuoso',
                ),
                array(
                    'id' => 'overweight',
                    'name_en' => 'Overweight',
                    'name_es' => 'Con sobrepeso',
                ),
                array(
                    'id' => 'fit',
                    'name_en' => 'Fit',
                    'name_es' => 'En forma',
                ),
                array(
                    'id' => 'jacked',
                    'name_en' => 'Jacked',
                    'name_es' => 'Musculado',
                ),
                array(
                    'id' => 'little-extra',
                    'name_en' => 'A little extra',
                    'name_es' => 'Rellenito',
                ),
                array(
                    'id' => 'curvy',
                    'name_en' => 'Curvy',
                    'name_es' => 'Con curvas',
                ),
                array(
                    'id' => 'used-up',
                    'name_en' => 'Used up',
                    'name_es' => 'Huesudo',
                ),
                array(
                    'id' => 'rather-not-say',
                    'name_en' => 'Rather not say',
                    'name_es' => 'Prefiero no decir',
                ),
            ),
            'Diet' => array(
                array(
                    'id' => 'vegetarian',
                    'name_en' => 'Vegetarian',
                    'name_es' => 'Vegetariana',
                ),
                array(
                    'id' => 'vegan',
                    'name_en' => 'Vegan',
                    'name_es' => 'Vegana',
                ),
                array(
                    'id' => 'other',
                    'name_en' => 'Other',
                    'name_es' => 'Otro',
                ),
            ),
            'Drugs' => array(
                array(
                    'id' => 'antidepressants',
                    'name_en' => 'Antidepressants',
                    'name_es' => 'Antidepresivos',
                ),
                array(
                    'id' => 'cannabis',
                    'name_en' => 'Cannabis',
                    'name_es' => 'Cannabis',
                ),
                array(
                    'id' => 'caffeine',
                    'name_en' => 'Caffeine',
                    'name_es' => 'Cafeína',
                ),
                array(
                    'id' => 'dissociatives',
                    'name_en' => 'Dissociatives',
                    'name_es' => 'Disociativos',
                ),
                array(
                    'id' => 'empathogens',
                    'name_en' => 'Empathogens',
                    'name_es' => 'Empatógenos',
                ),
                array(
                    'id' => 'stimulants',
                    'name_en' => 'Stimulants',
                    'name_es' => 'Estimulantes',
                ),
                array(
                    'id' => 'psychedelics',
                    'name_en' => 'Psychedelics',
                    'name_es' => 'Psicodélicos',
                ),
                array(
                    'id' => 'opiates',
                    'name_en' => 'Opiates',
                    'name_es' => 'Opiáceos',
                ),
                array(
                    'id' => 'others',
                    'name_en' => 'Others',
                    'name_es' => 'Otros',
                ),
            ),
            'EthnicGroup' => array(
                array(
                    'id' => 'oriental',
                    'name_en' => 'Asian',
                    'name_es' => 'Asiática',
                ),
                array(
                    'id' => 'afro-american',
                    'name_en' => 'Black',
                    'name_es' => 'Negra',
                ),
                array(
                    'id' => 'caucasian',
                    'name_en' => 'White',
                    'name_es' => 'Blanca',
                ),
                array(
                    'id' => 'indian',
                    'name_en' => 'Indian',
                    'name_es' => 'India',
                ),
                array(
                    'id' => 'middle-eastern',
                    'name_en' => 'Middle Eastern',
                    'name_es' => 'Medio Oriente',
                ),
                array(
                    'id' => 'native-american',
                    'name_en' => 'Native American',
                    'name_es' => 'Indígena Americana',
                ),
                array(
                    'id' => 'pacific-islander',
                    'name_en' => 'Pacific Islander',
                    'name_es' => 'Isleño del Pacífico',
                ),
                array(
                    'id' => 'gypsy',
                    'name_en' => 'Romani/Gypsy',
                    'name_es' => 'Romaní/Gitana',
                ),
                array(
                    'id' => 'hispanic-latin',
                    'name_en' => 'Hispanic/Latin',
                    'name_es' => 'Hispana/Latina',
                ),
            ),
            'EyeColor' => array(
                array(
                    'id' => 'blue',
                    'name_en' => 'Blue',
                    'name_es' => 'Azules',
                ),
                array(
                    'id' => 'brown',
                    'name_en' => 'Brown',
                    'name_es' => 'Castaños',
                ),
                array(
                    'id' => 'black',
                    'name_en' => 'Black',
                    'name_es' => 'Negros',
                ),
                array(
                    'id' => 'green',
                    'name_en' => 'Green',
                    'name_es' => 'Verdes',
                ),
                array(
                    'id' => 'other',
                    'name_en' => 'Other',
                    'name_es' => 'Otro',
                ),
            ),
            'Gender' => array(
                array(
                    'id' => 'male',
                    'name_en' => 'Male',
                    'name_es' => 'Masculino',
                ),
                array(
                    'id' => 'female',
                    'name_en' => 'Female',
                    'name_es' => 'Femenino',
                ),
            ),
            'DescriptiveGender' => array(
                array(
                    'id' => 'man',
                    'name_en' => 'Man',
                    'name_es' => 'Hombre',
                ),
                array(
                    'id' => 'woman',
                    'name_en' => 'Woman',
                    'name_es' => 'Mujer',
                ),
                array(
                    'id' => 'agender',
                    'name_en' => 'Agender',
                    'name_es' => 'Agénero',
                ),
                array(
                    'id' => 'androgynous',
                    'name_en' => 'Androgynous',
                    'name_es' => 'Andrógino',
                ),
                array(
                    'id' => 'bigender',
                    'name_en' => 'Bigender',
                    'name_es' => 'Bigénero',
                ),
                array(
                    'id' => 'cis-man',
                    'name_en' => 'Cis Man',
                    'name_es' => 'Cis Hombre',
                ),
                array(
                    'id' => 'cis-woman',
                    'name_en' => 'Cis Woman',
                    'name_es' => 'Cis Mujer',
                ),
                array(
                    'id' => 'genderfluid',
                    'name_en' => 'Genderfluid',
                    'name_es' => 'Género fluido',
                ),
                array(
                    'id' => 'genderqueer',
                    'name_en' => 'Genderqueer',
                    'name_es' => 'Genderqueer',
                ),
                array(
                    'id' => 'gender-nonconforming',
                    'name_en' => 'Gender nonconforming',
                    'name_es' => 'Género no conforme',
                ),
                array(
                    'id' => 'hijra',
                    'name_en' => 'Hijra',
                    'name_es' => 'Hijra',
                ),
                array(
                    'id' => 'intersex',
                    'name_en' => 'Intersex',
                    'name_es' => 'Intersex',
                ),
                array(
                    'id' => 'non-binary',
                    'name_en' => 'Non-binary',
                    'name_es' => 'No binario',
                ),
                array(
                    'id' => 'pangender',
                    'name_en' => 'Pangender',
                    'name_es' => 'Pangénero',
                ),
                array(
                    'id' => 'transfeminine',
                    'name_en' => 'Transfeminine',
                    'name_es' => 'Transfeminino',
                ),
                array(
                    'id' => 'transgender',
                    'name_en' => 'Transgender',
                    'name_es' => 'Transgénero',
                ),
                array(
                    'id' => 'transmasculine',
                    'name_en' => 'Transmasculine',
                    'name_es' => 'Transmasculino',
                ),
                array(
                    'id' => 'transsexual',
                    'name_en' => 'Transsexual',
                    'name_es' => 'Transexual',
                ),
                array(
                    'id' => 'trans-man',
                    'name_en' => 'Trans Man',
                    'name_es' => 'Trans Hombre',
                ),
                array(
                    'id' => 'trans-woman',
                    'name_en' => 'Trans Woman',
                    'name_es' => 'Trans Mujer',
                ),
                array(
                    'id' => 'two-spirit',
                    'name_en' => 'Two Spirit',
                    'name_es' => 'Dos Espíritus',
                ),
                array(
                    'id' => 'other',
                    'name_en' => 'Other',
                    'name_es' => 'Otros',
                ),
            ),
            'Mode' => array(
                array(
                    'id' => 'assist',
                    'name_en' => 'Assist',
                    'name_es' => 'Asistir',
                ),
                array(
                    'id' => 'explore',
                    'name_en' => 'Explore',
                    'name_es' => 'Explorar',
                ),
                array(
                    'id' => 'contact',
                    'name_en' => 'Contact',
                    'name_es' => 'Contactar',
                ),
            ),
            'Objective' => array(
                array(
                    'id' => 'human-contact',
                    'name_en' => 'Contact',
                    'name_es' => 'Contacto',
                ),
                array(
                    'id' => 'talk',
                    'name_en' => 'Talk',
                    'name_es' => 'Hablar',
                ),
                array(
                    'id' => 'work',
                    'name_en' => 'Work',
                    'name_es' => 'Trabajar',
                ),
                array(
                    'id' => 'explore',
                    'name_en' => 'Explore',
                    'name_es' => 'Explorar',
                ),
                array(
                    'id' => 'share-space',
                    'name_en' => 'Share space',
                    'name_es' => 'Compartir espacio',
                ),
                array(
                    'id' => 'hobbies',
                    'name_en' => 'Hobbies',
                    'name_es' => 'Aficiones',
                ),
            ),
            'Sons' => array(
                array(
                    'id' => 'yes',
                    'name_en' => 'Have kids(s)',
                    'name_es' => 'Tengo hijos',
                ),
                array(
                    'id' => 'no',
                    'name_en' => "Doesn't have kids",
                    'name_es' => 'No tengo hijos',
                ),
            ),
            'HairColor' => array(
                array(
                    'id' => 'black',
                    'name_en' => 'Black',
                    'name_es' => 'Negro',
                ),
                array(
                    'id' => 'brown',
                    'name_en' => 'Brown',
                    'name_es' => 'Castaño',
                ),
                array(
                    'id' => 'blond',
                    'name_en' => 'Blond',
                    'name_es' => 'Rubio',
                ),
                array(
                    'id' => 'red',
                    'name_en' => 'Red',
                    'name_es' => 'Rojo',
                ),
                array(
                    'id' => 'gray-or-white',
                    'name_en' => 'Gray or White',
                    'name_es' => 'Gris o Blanco',
                ),
                array(
                    'id' => 'other',
                    'name_en' => 'Other',
                    'name_es' => 'Otro',
                ),
            ),
            'Income' => array(
                array(
                    'id' => 'less-than-us-12-000-year',
                    'name_en' => 'Less than US$12,000/year',
                    'name_es' => 'Menos de 12.000 US$/año',
                ),
                array(
                    'id' => 'between-us-12-000-and-us-24-000-year',
                    'name_en' => 'Between US$12,000 and US$24,000/year',
                    'name_es' => 'Entre 12.000 y 24.000 US$/año',
                ),
                array(
                    'id' => 'more-than-us-24-000-year',
                    'name_en' => 'More than US$24,000/year',
                    'name_es' => 'Más de 24.000 US$/año',
                ),
            ),
            'Industry' => array(
                array(
                    'id' => 'accounting',
                    'name_en' => 'Accounting',
                    'name_es' => 'Contabilidad',
                ),
                array(
                    'id' => 'airlines-aviation',
                    'name_en' => 'Airlines/Aviation',
                    'name_es' => 'Aeronáunica/Aviación',
                ),
                array(
                    'id' => 'alternative-dispute-resolution',
                    'name_en' => 'Alternative Dispute Resolution',
                    'name_es' => 'Resolución de conflictos por terceras partes',
                ),
                array(
                    'id' => 'alternative-medicine',
                    'name_en' => 'Alternative Medicine',
                    'name_es' => 'Medicina alternativa',
                ),
                array(
                    'id' => 'animation',
                    'name_en' => 'Animation',
                    'name_es' => 'Animación',
                ),
                array(
                    'id' => 'apparel-and-fashion',
                    'name_en' => 'Apparel & Fashion',
                    'name_es' => 'Industria textil y moda',
                ),
                array(
                    'id' => 'architecture-and-planning',
                    'name_en' => 'Architecture & Planning',
                    'name_es' => 'Arquitectura y planificación',
                ),
                array(
                    'id' => 'arts-and-crafts',
                    'name_en' => 'Arts and Crafts',
                    'name_es' => 'Artesanía',
                ),
                array(
                    'id' => 'automotive',
                    'name_en' => 'Automotive',
                    'name_es' => 'Sector automovilístico',
                ),
                array(
                    'id' => 'aviation-and-aerospace',
                    'name_en' => 'Aviation & Aerospace',
                    'name_es' => 'Industria aeroespacial y aviación',
                ),
                array(
                    'id' => 'banking',
                    'name_en' => 'Banking',
                    'name_es' => 'Banca',
                ),
                array(
                    'id' => 'biotechnology',
                    'name_en' => 'Biotechnology',
                    'name_es' => 'Biotecnología',
                ),
                array(
                    'id' => 'broadcast-media',
                    'name_en' => 'Broadcast Media',
                    'name_es' => 'Medios de difusión',
                ),
                array(
                    'id' => 'building-materials',
                    'name_en' => 'Building Materials',
                    'name_es' => 'Materiales de construcción',
                ),
                array(
                    'id' => 'business-supplies-and-equipment',
                    'name_en' => 'Business Supplies and Equipment',
                    'name_es' => 'Material y equipo de negocios',
                ),
                array(
                    'id' => 'capital-markets',
                    'name_en' => 'Capital Markets',
                    'name_es' => 'Mercados de capital',
                ),
                array(
                    'id' => 'chemicals',
                    'name_en' => 'Chemicals',
                    'name_es' => 'Productos químicos',
                ),
                array(
                    'id' => 'civic-and-social-organization',
                    'name_en' => 'Civic & Social Organization',
                    'name_es' => 'Organización cívica y social',
                ),
                array(
                    'id' => 'civil-engineering',
                    'name_en' => 'Civil Engineering',
                    'name_es' => 'Ingeniería civil',
                ),
                array(
                    'id' => 'commercial-real-estate',
                    'name_en' => 'Commercial Real Estate',
                    'name_es' => 'Bienes inmobiliarios comerciales',
                ),
                array(
                    'id' => 'computer-and-network-security',
                    'name_en' => 'Computer & Network Security',
                    'name_es' => 'Seguridad del ordenador y de las redes',
                ),
                array(
                    'id' => 'computer-games',
                    'name_en' => 'Computer Games',
                    'name_es' => 'Videojuegos',
                ),
                array(
                    'id' => 'computer-hardware',
                    'name_en' => 'Computer Hardware',
                    'name_es' => 'Equipos informáticos',
                ),
                array(
                    'id' => 'computer-networking',
                    'name_en' => 'Computer Networking',
                    'name_es' => 'Interconexión en red',
                ),
                array(
                    'id' => 'computer-software',
                    'name_en' => 'Computer Software',
                    'name_es' => 'Software',
                ),
                array(
                    'id' => 'construction',
                    'name_en' => 'Construction',
                    'name_es' => 'Construcción',
                ),
                array(
                    'id' => 'consumer-electronics',
                    'name_en' => 'Consumer Electronics',
                    'name_es' => 'Electrónica de consumo',
                ),
                array(
                    'id' => 'consumer-goods',
                    'name_en' => 'Consumer Goods',
                    'name_es' => 'Artículos de consumo',
                ),
                array(
                    'id' => 'consumer-services',
                    'name_en' => 'Consumer Services',
                    'name_es' => 'Servicio al consumidor',
                ),
                array(
                    'id' => 'cosmetics',
                    'name_en' => 'Cosmetics',
                    'name_es' => 'Cosmética',
                ),
                array(
                    'id' => 'dairy',
                    'name_en' => 'Dairy',
                    'name_es' => 'Lácteos',
                ),
                array(
                    'id' => 'defense-and-space',
                    'name_en' => 'Defense & Space',
                    'name_es' => 'Departamento de defensa y del espacio exterior',
                ),
                array(
                    'id' => 'design',
                    'name_en' => 'Design',
                    'name_es' => 'Diseño',
                ),
                array(
                    'id' => 'education-management',
                    'name_en' => 'Education Management',
                    'name_es' => 'Gestión educativa',
                ),
                array(
                    'id' => 'e-learning',
                    'name_en' => 'E-Learning',
                    'name_es' => 'E-Learning',
                ),
                array(
                    'id' => 'electrical-electronic-manufacturing',
                    'name_en' => 'Electrical/Electronic Manufacturing',
                    'name_es' => 'Manufactura eléctrica/electrónica',
                ),
                array(
                    'id' => 'entertainment',
                    'name_en' => 'Entertainment',
                    'name_es' => 'Entretenimiento',
                ),
                array(
                    'id' => 'environmental-services',
                    'name_en' => 'Environmental Services',
                    'name_es' => 'Servicios medioambientales',
                ),
                array(
                    'id' => 'events-services',
                    'name_en' => 'Events Services',
                    'name_es' => 'Servicios de eventos',
                ),
                array(
                    'id' => 'executive-office',
                    'name_en' => 'Executive Office',
                    'name_es' => 'Oficina ejecutiva',
                ),
                array(
                    'id' => 'facilities-services',
                    'name_en' => 'Facilities Services',
                    'name_es' => 'Servicios infraestructurales',
                ),
                array(
                    'id' => 'farming',
                    'name_en' => 'Farming',
                    'name_es' => 'Agricultura',
                ),
                array(
                    'id' => 'financial-services',
                    'name_en' => 'Financial Services',
                    'name_es' => 'Servicios financieros',
                ),
                array(
                    'id' => 'fine-art',
                    'name_en' => 'Fine Art',
                    'name_es' => 'Bellas artes',
                ),
                array(
                    'id' => 'fishery',
                    'name_en' => 'Fishery',
                    'name_es' => 'Piscicultura',
                ),
                array(
                    'id' => 'food-and-beverages',
                    'name_en' => 'Food & Beverages',
                    'name_es' => 'Alimentación y bebidas',
                ),
                array(
                    'id' => 'food-production',
                    'name_en' => 'Food Production',
                    'name_es' => 'Producción alimentaria',
                ),
                array(
                    'id' => 'fund-raising',
                    'name_en' => 'Fund-Raising',
                    'name_es' => 'Recaudación de fondos',
                ),
                array(
                    'id' => 'furniture',
                    'name_en' => 'Furniture',
                    'name_es' => 'Mobiliario',
                ),
                array(
                    'id' => 'gambling-and-casinos',
                    'name_en' => 'Gambling & Casinos',
                    'name_es' => 'Apuestas y casinos',
                ),
                array(
                    'id' => 'glass-ceramics-and-concrete',
                    'name_en' => 'Glass, Ceramics & Concrete',
                    'name_es' => 'Cristal, cerámica y hormigón',
                ),
                array(
                    'id' => 'government-administration',
                    'name_en' => 'Government Administration',
                    'name_es' => 'Administración gubernamental',
                ),
                array(
                    'id' => 'government-relations',
                    'name_en' => 'Government Relations',
                    'name_es' => 'Relaciones gubernamentales',
                ),
                array(
                    'id' => 'graphic-design',
                    'name_en' => 'Graphic Design',
                    'name_es' => 'Diseño gráfico',
                ),
                array(
                    'id' => 'health-wellness-and-fitness',
                    'name_en' => 'Health, Wellness and Fitness',
                    'name_es' => 'Sanidad, bienestar y ejercicio',
                ),
                array(
                    'id' => 'higher-education',
                    'name_en' => 'Higher Education',
                    'name_es' => 'Enseñanza superior',
                ),
                array(
                    'id' => 'hospital-and-health-care',
                    'name_en' => 'Hospital & Health Care',
                    'name_es' => 'Atención sanitaria y hospitalaria',
                ),
                array(
                    'id' => 'hospitality',
                    'name_en' => 'Hospitality',
                    'name_es' => 'Hostelería',
                ),
                array(
                    'id' => 'human-resources',
                    'name_en' => 'Human Resources',
                    'name_es' => 'Recursos humanos',
                ),
                array(
                    'id' => 'import-and-export',
                    'name_en' => 'Import and Export',
                    'name_es' => 'Importación y exportación',
                ),
                array(
                    'id' => 'individual-and-family-services',
                    'name_en' => 'Individual & Family Services',
                    'name_es' => 'Servicios para el individuo y la familia',
                ),
                array(
                    'id' => 'industrial-automation',
                    'name_en' => 'Industrial Automation',
                    'name_es' => 'Automación industrial',
                ),
                array(
                    'id' => 'information-services',
                    'name_en' => 'Information Services',
                    'name_es' => 'Servicio de información',
                ),
                array(
                    'id' => 'information-technology-and-services',
                    'name_en' => 'Information Technology and Services',
                    'name_es' => 'Servicios y tecnologías de la información',
                ),
                array(
                    'id' => 'insurance',
                    'name_en' => 'Insurance',
                    'name_es' => 'Seguros',
                ),
                array(
                    'id' => 'international-affairs',
                    'name_en' => 'International Affairs',
                    'name_es' => 'Asuntos internacionales',
                ),
                array(
                    'id' => 'international-trade-and-development',
                    'name_en' => 'International Trade and Development',
                    'name_es' => 'Desarrollo y comercio internacional',
                ),
                array(
                    'id' => 'internet',
                    'name_en' => 'Internet',
                    'name_es' => 'Internet',
                ),
                array(
                    'id' => 'investment-banking',
                    'name_en' => 'Investment Banking',
                    'name_es' => 'Banca de inversiones',
                ),
                array(
                    'id' => 'investment-management',
                    'name_en' => 'Investment Management',
                    'name_es' => 'Gestión de inversiones',
                ),
                array(
                    'id' => 'judiciary',
                    'name_en' => 'Judiciary',
                    'name_es' => 'Judicial',
                ),
                array(
                    'id' => 'law-enforcement',
                    'name_en' => 'Law Enforcement',
                    'name_es' => 'Cumplimiento de la ley',
                ),
                array(
                    'id' => 'law-practice',
                    'name_en' => 'Law Practice',
                    'name_es' => 'Derecho',
                ),
                array(
                    'id' => 'legal-services',
                    'name_en' => 'Legal Services',
                    'name_es' => 'Servicios jurídicos',
                ),
                array(
                    'id' => 'legislative-office',
                    'name_en' => 'Legislative Office',
                    'name_es' => 'Oficina legislativa',
                ),
                array(
                    'id' => 'leisure-travel-and-tourism',
                    'name_en' => 'Leisure, Travel & Tourism',
                    'name_es' => 'Ocio, viajes y turismo',
                ),
                array(
                    'id' => 'libraries',
                    'name_en' => 'Libraries',
                    'name_es' => 'Bibliotecas',
                ),
                array(
                    'id' => 'logistics-and-supply-chain',
                    'name_en' => 'Logistics and Supply Chain',
                    'name_es' => 'Logística y cadena de suministro',
                ),
                array(
                    'id' => 'luxury-goods-and-jewelry',
                    'name_en' => 'Luxury Goods & Jewelry',
                    'name_es' => 'Artículos de lujo y joyas',
                ),
                array(
                    'id' => 'machinery',
                    'name_en' => 'Machinery',
                    'name_es' => 'Maquinaria',
                ),
                array(
                    'id' => 'management-consulting',
                    'name_en' => 'Management Consulting',
                    'name_es' => 'Consultoría de estrategia y operaciones',
                ),
                array(
                    'id' => 'maritime',
                    'name_en' => 'Maritime',
                    'name_es' => 'Naval',
                ),
                array(
                    'id' => 'market-research',
                    'name_en' => 'Market Research',
                    'name_es' => 'Investigación de mercado',
                ),
                array(
                    'id' => 'marketing-and-advertising',
                    'name_en' => 'Marketing and Advertising',
                    'name_es' => 'Marketing y publicidad',
                ),
                array(
                    'id' => 'mechanical-or-industrial-engineering',
                    'name_en' => 'Mechanical or Industrial Engineering',
                    'name_es' => 'Ingeniería industrial o mecánica',
                ),
                array(
                    'id' => 'media-production',
                    'name_en' => 'Media Production',
                    'name_es' => 'Producción multimedia',
                ),
                array(
                    'id' => 'medical-devices',
                    'name_en' => 'Medical Devices',
                    'name_es' => 'Servicios médicos',
                ),
                array(
                    'id' => 'medical-practice',
                    'name_en' => 'Medical Practice',
                    'name_es' => 'Profesiones médicas',
                ),
                array(
                    'id' => 'mental-health-care',
                    'name_en' => 'Mental Health Care',
                    'name_es' => 'Atención a la salud mental',
                ),
                array(
                    'id' => 'military',
                    'name_en' => 'Military',
                    'name_es' => 'Ejército',
                ),
                array(
                    'id' => 'mining-and-metals',
                    'name_en' => 'Mining & Metals',
                    'name_es' => 'Minería y metalurgia',
                ),
                array(
                    'id' => 'motion-pictures-and-film',
                    'name_en' => 'Motion Pictures and Film',
                    'name_es' => 'Películas y cine',
                ),
                array(
                    'id' => 'museums-and-institutions',
                    'name_en' => 'Museums and Institutions',
                    'name_es' => 'Museos e instituciones',
                ),
                array(
                    'id' => 'music',
                    'name_en' => 'Music',
                    'name_es' => 'Música',
                ),
                array(
                    'id' => 'nanotechnology',
                    'name_en' => 'Nanotechnology',
                    'name_es' => 'Nanotecnología',
                ),
                array(
                    'id' => 'newspapers',
                    'name_en' => 'Newspapers',
                    'name_es' => 'Periódicos',
                ),
                array(
                    'id' => 'non-profit-organization-management',
                    'name_en' => 'Non-Profit Organization Management',
                    'name_es' => 'Gestión de organizaciones sin ánimo de lucro',
                ),
                array(
                    'id' => 'oil-and-energy',
                    'name_en' => 'Oil & Energy',
                    'name_es' => 'Petróleo y energía',
                ),
                array(
                    'id' => 'online-media',
                    'name_en' => 'Online Media',
                    'name_es' => 'Medios de comunicación en línea',
                ),
                array(
                    'id' => 'outsourcing-offshoring',
                    'name_en' => 'Outsourcing/Offshoring',
                    'name_es' => 'Subcontrataciones/Offshoring',
                ),
                array(
                    'id' => 'package-freight-delivery',
                    'name_en' => 'Package/Freight Delivery',
                    'name_es' => 'Envío de paquetes y carga',
                ),
                array(
                    'id' => 'packaging-and-containers',
                    'name_en' => 'Packaging and Containers',
                    'name_es' => 'Embalaje y contenedores',
                ),
                array(
                    'id' => 'paper-and-forest-products',
                    'name_en' => 'Paper & Forest Products',
                    'name_es' => 'Productos de papel y forestales',
                ),
                array(
                    'id' => 'performing-arts',
                    'name_en' => 'Performing Arts',
                    'name_es' => 'Artes escénicas',
                ),
                array(
                    'id' => 'pharmaceuticals',
                    'name_en' => 'Pharmaceuticals',
                    'name_es' => 'Industria farmacéutica',
                ),
                array(
                    'id' => 'philanthropy',
                    'name_en' => 'Philanthropy',
                    'name_es' => 'Filantropía',
                ),
                array(
                    'id' => 'photography',
                    'name_en' => 'Photography',
                    'name_es' => 'Fotografía',
                ),
                array(
                    'id' => 'plastics',
                    'name_en' => 'Plastics',
                    'name_es' => 'Plásticos',
                ),
                array(
                    'id' => 'political-organization',
                    'name_en' => 'Political Organization',
                    'name_es' => 'Organización política',
                ),
                array(
                    'id' => 'primary-secondary-education',
                    'name_en' => 'Primary/Secondary Education',
                    'name_es' => 'Educación primaria/secundaria',
                ),
                array(
                    'id' => 'printing',
                    'name_en' => 'Printing',
                    'name_es' => 'Imprenta',
                ),
                array(
                    'id' => 'professional-training-and-coaching',
                    'name_en' => 'Professional Training & Coaching',
                    'name_es' => 'Formación profesional y capacitación',
                ),
                array(
                    'id' => 'program-development',
                    'name_en' => 'Program Development',
                    'name_es' => 'Desarrollo de programación',
                ),
                array(
                    'id' => 'public-policy',
                    'name_en' => 'Public Policy',
                    'name_es' => 'Política pública',
                ),
                array(
                    'id' => 'public-relations-and-communications',
                    'name_en' => 'Public Relations and Communications',
                    'name_es' => 'Relaciones públicas y comunicaciones',
                ),
                array(
                    'id' => 'public-safety',
                    'name_en' => 'Public Safety',
                    'name_es' => 'Protección civil',
                ),
                array(
                    'id' => 'publishing',
                    'name_en' => 'Publishing',
                    'name_es' => 'Publicaciones',
                ),
                array(
                    'id' => 'railroad-manufacture',
                    'name_en' => 'Railroad Manufacture',
                    'name_es' => 'Manufactura ferroviaria',
                ),
                array(
                    'id' => 'ranching',
                    'name_en' => 'Ranching',
                    'name_es' => 'Ganadería',
                ),
                array(
                    'id' => 'real-estate',
                    'name_en' => 'Real Estate',
                    'name_es' => 'Bienes inmobiliarios',
                ),
                array(
                    'id' => 'recreational-facilities-and-services',
                    'name_en' => 'Recreational Facilities and Services',
                    'name_es' => 'Instalaciones y servicios recreativos',
                ),
                array(
                    'id' => 'religious-institutions',
                    'name_en' => 'Religious Institutions',
                    'name_es' => 'Instituciones religiosas',
                ),
                array(
                    'id' => 'renewables-and-environment',
                    'name_en' => 'Renewables & Environment',
                    'name_es' => 'Energía renovable y medio ambiente',
                ),
                array(
                    'id' => 'research',
                    'name_en' => 'Research',
                    'name_es' => 'Investigación',
                ),
                array(
                    'id' => 'restaurants',
                    'name_en' => 'Restaurants',
                    'name_es' => 'Restaurantes',
                ),
                array(
                    'id' => 'retail',
                    'name_en' => 'Retail',
                    'name_es' => 'Venta al por menor',
                ),
                array(
                    'id' => 'security-and-investigations',
                    'name_en' => 'Security and Investigations',
                    'name_es' => 'Seguridad e investigaciones',
                ),
                array(
                    'id' => 'semiconductors',
                    'name_en' => 'Semiconductors',
                    'name_es' => 'Semiconductores',
                ),
                array(
                    'id' => 'shipbuilding',
                    'name_en' => 'Shipbuilding',
                    'name_es' => 'Construcción naval',
                ),
                array(
                    'id' => 'sporting-goods',
                    'name_en' => 'Sporting Goods',
                    'name_es' => 'Artículos deportivos',
                ),
                array(
                    'id' => 'sports',
                    'name_en' => 'Sports',
                    'name_es' => 'Deportes',
                ),
                array(
                    'id' => 'staffing-and-recruiting',
                    'name_en' => 'Staffing and Recruiting',
                    'name_es' => 'Dotación y selección de personal',
                ),
                array(
                    'id' => 'supermarkets',
                    'name_en' => 'Supermarkets',
                    'name_es' => 'Supermercados',
                ),
                array(
                    'id' => 'telecommunications',
                    'name_en' => 'Telecommunications',
                    'name_es' => 'Telecomunicaciones',
                ),
                array(
                    'id' => 'textiles',
                    'name_en' => 'Textiles',
                    'name_es' => 'Sector textil',
                ),
                array(
                    'id' => 'think-tanks',
                    'name_en' => 'Think Tanks',
                    'name_es' => 'Gabinetes estratégicos',
                ),
                array(
                    'id' => 'tobacco',
                    'name_en' => 'Tobacco',
                    'name_es' => 'Tabaco',
                ),
                array(
                    'id' => 'translation-and-localization',
                    'name_en' => 'Translation and Localization',
                    'name_es' => 'Traducción y localización',
                ),
                array(
                    'id' => 'transportation-trucking-railroad',
                    'name_en' => 'Transportation/Trucking/Railroad',
                    'name_es' => 'Transporte por carretera o ferrocarril',
                ),
                array(
                    'id' => 'utilities',
                    'name_en' => 'Utilities',
                    'name_es' => 'Servicios públicos',
                ),
                array(
                    'id' => 'venture-capital-and-private-equity',
                    'name_en' => 'Venture Capital & Private Equity',
                    'name_es' => 'Capital de riesgo y capital privado',
                ),
                array(
                    'id' => 'veterinary',
                    'name_en' => 'Veterinary',
                    'name_es' => 'Veterinaria',
                ),
                array(
                    'id' => 'warehousing',
                    'name_en' => 'Warehousing',
                    'name_es' => 'Almacenamiento',
                ),
                array(
                    'id' => 'wholesale',
                    'name_en' => 'Wholesale',
                    'name_es' => 'Venta al por mayor',
                ),
                array(
                    'id' => 'wine-and-spirits',
                    'name_en' => 'Wine and Spirits',
                    'name_es' => 'Vinos y licores',
                ),
                array(
                    'id' => 'wireless',
                    'name_en' => 'Wireless',
                    'name_es' => 'Tecnología inalámbrica',
                ),
                array(
                    'id' => 'writing-and-editing',
                    'name_en' => 'Writing and Editing',
                    'name_es' => 'Escritura y edición',
                ),
            ),
            'Orientation' => array(
                array(
                    'id' => 'heterosexual',
                    'name_en' => 'Straight',
                    'name_es' => 'Hetero',
                ),
                array(
                    'id' => 'homosexual',
                    'name_en' => 'Gay/Lesbian',
                    'name_es' => 'Homo',
                ),
                array(
                    'id' => 'bisexual',
                    'name_en' => 'Bisexual',
                    'name_es' => 'Bisexual',
                ),
                array(
                    'id' => 'asexual',
                    'name_en' => 'Asexual',
                    'name_es' => 'Asexual',
                ),
                array(
                    'id' => 'demisexual',
                    'name_en' => 'Demisexual',
                    'name_es' => 'Demisexual',
                ),
                array(
                    'id' => 'heteroflexible',
                    'name_en' => 'Heteroflexible',
                    'name_es' => 'Heteroflexible',
                ),
                array(
                    'id' => 'homoflexible',
                    'name_en' => 'Homoflexible',
                    'name_es' => 'Homoflexible',
                ),
                array(
                    'id' => 'pansexual',
                    'name_en' => 'Pansexual',
                    'name_es' => 'Pansexual',
                ),
                array(
                    'id' => 'queer',
                    'name_en' => 'Queer',
                    'name_es' => 'Queer',
                ),
                array(
                    'id' => 'questioning',
                    'name_en' => 'Questioning',
                    'name_es' => 'Cuestionandomelo',
                ),
                array(
                    'id' => 'sapiosexual',
                    'name_en' => 'Sapiosexual',
                    'name_es' => 'Sapiosexual',
                ),
            ),
            'Pets' => array(
                array(
                    'id' => 'cat',
                    'name_en' => 'Cat',
                    'name_es' => 'Gato',
                ),
                array(
                    'id' => 'dog',
                    'name_en' => 'Dog',
                    'name_es' => 'Perro',
                ),
                array(
                    'id' => 'other',
                    'name_en' => 'Other',
                    'name_es' => 'Otras',
                ),
            ),
            'RelationshipInterest' => array(
                array(
                    'id' => 'friendship',
                    'name_en' => 'Friendship',
                    'name_es' => 'Amistad',
                ),
                array(
                    'id' => 'relation',
                    'name_en' => 'Relation',
                    'name_es' => 'Relación',
                ),
                array(
                    'id' => 'open-relation',
                    'name_en' => 'Open Relation',
                    'name_es' => 'Relación Abierta',
                ),
            ),
            'Smoke' => array(
                array(
                    'id' => 'yes',
                    'name_en' => 'Yes',
                    'name_es' => 'Sí',
                ),
                array(
                    'id' => 'no-but-i-tolerate-it',
                    'name_en' => 'No, but I tolerate it',
                    'name_es' => 'No, pero lo toleraría',
                ),
                array(
                    'id' => 'no-and-i-hate-it',
                    'name_en' => 'No, and cannot stand it',
                    'name_es' => 'No, y no lo soporto',
                ),
            ),
            'InterfaceLanguage' => array(
                array(
                    'id' => 'es',
                    'name_en' => 'Español',
                    'name_es' => 'Español',
                ),
                array(
                    'id' => 'en',
                    'name_en' => 'English',
                    'name_es' => 'English',
                ),
            ),
            'ZodiacSign' => array(
                array(
                    'id' => 'capricorn',
                    'name_en' => 'Capricorn',
                    'name_es' => 'Capricornio',
                ),
                array(
                    'id' => 'sagittarius',
                    'name_en' => 'Sagittarius',
                    'name_es' => 'Sagitario',
                ),
                array(
                    'id' => 'scorpio',
                    'name_en' => 'Scorpio',
                    'name_es' => 'Escorpio',
                ),
                array(
                    'id' => 'libra',
                    'name_en' => 'Libra',
                    'name_es' => 'Libra',
                ),
                array(
                    'id' => 'virgo',
                    'name_en' => 'Virgo',
                    'name_es' => 'Virgo',
                ),
                array(
                    'id' => 'leo',
                    'name_en' => 'Leo',
                    'name_es' => 'Leo',
                ),
                array(
                    'id' => 'cancer',
                    'name_en' => 'Cancer',
                    'name_es' => 'Cáncer',
                ),
                array(
                    'id' => 'gemini',
                    'name_en' => 'Gemini',
                    'name_es' => 'Géminis',
                ),
                array(
                    'id' => 'taurus',
                    'name_en' => 'Taurus',
                    'name_es' => 'Tauro',
                ),
                array(
                    'id' => 'aries',
                    'name_en' => 'Aries',
                    'name_es' => 'Aries',
                ),
                array(
                    'id' => 'pisces',
                    'name_en' => 'Pisces',
                    'name_es' => 'Piscis',
                ),
                array(
                    'id' => 'aquarius',
                    'name_en' => 'Aquarius',
                    'name_es' => 'Acuario',
                ),
            ),
            'Religion' => array(
                array(
                    'id' => 'agnosticism',
                    'name_en' => 'Agnosticism',
                    'name_es' => 'Agnóstico',
                ),
                array(
                    'id' => 'atheism',
                    'name_en' => 'Atheism',
                    'name_es' => 'Ateo',
                ),
                array(
                    'id' => 'christianity',
                    'name_en' => 'Christianity',
                    'name_es' => 'Cristiano',
                ),
                array(
                    'id' => 'judaism',
                    'name_en' => 'Judaism',
                    'name_es' => 'Judio',
                ),
                array(
                    'id' => 'catholicism',
                    'name_en' => 'Catholicism',
                    'name_es' => 'Católico',
                ),
                array(
                    'id' => 'islam',
                    'name_en' => 'Islam',
                    'name_es' => 'Musulmán',
                ),
                array(
                    'id' => 'hinduism',
                    'name_en' => 'Hinduism',
                    'name_es' => 'Hinduista',
                ),
                array(
                    'id' => 'buddhism',
                    'name_en' => 'Buddhism',
                    'name_es' => 'Budista',
                ),
                array(
                    'id' => 'sikh',
                    'name_en' => 'Sikh',
                    'name_es' => 'Sikh',
                ),
                array(
                    'id' => 'kopimism',
                    'name_en' => 'Kopimism',
                    'name_es' => 'Kopimista',
                ),
                array(
                    'id' => 'other',
                    'name_en' => 'Other',
                    'name_es' => 'Otra',
                ),
            ),
            'LeisureMoney' => array(
                array(
                    'id' => 'free',
                    'name_en' => 'Free',
                    'name_es' => 'Gratis',
                    'order' => 0
                ),
                array(
                    'id' => 'cheap',
                    'name_en' => 'Cheap',
                    'name_es' => 'Barato',
                    'order' => 1
                ),
                array(
                    'id' => 'quality',
                    'name_en' => 'Quality',
                    'name_es' => 'De calidad',
                    'order' => 2
                ),
                array(
                    'id' => 'luxurious',
                    'name_en' => 'Luxurious',
                    'name_es' => 'Lujoso',
                    'order' => 3
                ),
            ),
            'Plan' => array(
                array(
                    'id' => 'party',
                    'name_en' => 'Throw a party',
                    'name_es' => 'Fiestas en casa',
                    'picture' => 'https://us.123rf.com/450wm/ivankoivanko/ivankoivanko1704/ivankoivanko170400006/110563225-friends-eating-pizza-they-are-having-party-at-home-eating-pizza-and-having-fun-.jpg?ver=6'
                ),
                array(
                    'id' => 'massages-spa',
                    'name_en' => 'Massages and Spa',
                    'name_es' => 'Masajes y Spas',
                    'picture' => 'http://arayaspa.com/wp-content/uploads/2015/05/massage.jpg'
                ),
                array(
                    'id' => 'hairdressing-beauty',
                    'name_en' => 'Hairdressing and beauty',
                    'name_es' => 'Peluquería y belleza',
                    'picture' => 'https://cdn.britannica.com/s:300x300/53/136653-004-E2E9D001.jpg'
                ),
                array(
                    'id' => 'hiking',
                    'name_en' => 'Hiking',
                    'name_es' => 'Rutas y excursiones',
                    'picture' => 'https://cms.groupeditors.com/img/139670ab-8a69-4b2e-9ebc-7e8ad63a7fc0.jpg'
                ),
                array(
                    'id' => 'wine-tasting',
                    'name_en' => 'Wine tasting',
                    'name_es' => 'Catas de vinos',
                    'picture' => 'https://i1.wp.com/www.tufts-skidmore.es/wp-content/uploads/2017/04/cata-vinos-pasos.jpeg?fit=750%2C393'
                ),
                array(
                    'id' => 'courses',
                    'name_en' => 'Take classes',
                    'name_es' => 'Cursos',
                    'picture' => 'data:image/png;base64,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'
                ),
                array(
                    'id' => 'scheduled-adventures',
                    'name_en' => 'Scheduled adventures',
                    'name_es' => 'Aventuras programadas',
                    'picture' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQCeA-j3vRBIk-M1z8MSyPxo8lKGWhvbuDZG9su4BwbcPGlDCWD'
                ),
                array(
                    'id' => 'take a walk',
                    'name_en' => 'Take a walk',
                    'name_es' => 'Dar un paseo',
                    'picture' => 'https://www.callumswanrealty.com/blog/es/wp-content/uploads/sites/2/2016/04/paseo-maritimo-marbella-1650x928.jpg'
                ),
                array(
                    'id' => 'go-to-sales',
                    'name_en' => 'Go to the sales',
                    'name_es' => 'Ir a las rebajas',
                    'picture' => 'https://static2.diariosur.es/www/multimedia/201801/11/media/cortadas/rebajas-U30430927147wfG-U50591802181wbG-624x385@Diario%20Sur-DiarioSur.jpg'
                ),
            ),
            'Restaurant' => array(
                array(
                    'id' => 'asian',
                    'name_en' => 'Japonés',
                    'name_es' => 'Japanese',
                    'picture' => 'http://cdn.shopify.com/s/files/1/1291/3261/products/DSC_7797-Edit_grande.jpg?v=1475050576'
                ),
                array(
                    'id' => 'italian',
                    'name_en' => 'Italian',
                    'name_es' => 'Italianos',
                    'picture' => 'https://cdn.vox-cdn.com/thumbor/LBrK9HXbpy41EzO1f8BlofvFWsw=/155x0:4763x3456/1200x800/filters:focal(155x0:4763x3456)/cdn.vox-cdn.com/uploads/chorus_image/image/50864567/shutterstock_314337134.0.0.jpg',
                ),
                array(
                    'id' => 'fast',
                    'name_en' => 'Fast food',
                    'name_es' => 'Comida rápida',
                    'picture' => 'https://upload.wikimedia.org/wikipedia/commons/2/2e/Fast_food_meal.jpg'
                ),
                array(
                    'id' => 'american',
                    'name_en' => 'American',
                    'name_es' => 'Americanos',
                    'picture' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/70/Mac-and-cheese.jpg/1200px-Mac-and-cheese.jpg'
                ),
                array(
                    'id' => 'spanish',
                    'name_en' => 'Spanish',
                    'name_es' => 'Español',
                    'picture' => 'https://1dib1q3k1s3e11a5av3bhlnb-wpengine.netdna-ssl.com/wp-content/uploads/2012/05/spanish-tortilla.jpg'                ),
                array(
                    'id' => 'indian',
                    'name_en' => 'Indian',
                    'name_es' => 'Indio',
                    'picture' => 'https://media-cdn.tripadvisor.com/media/photo-s/0b/71/97/4e/north-indian-food-kaitaia.jpg'                ),
                array(
                    'id' => 'latin',
                    'name_en' => 'Latin',
                    'name_es' => 'Latinos',
                    'picture' => 'https://cdn10.phillymag.com/wp-content/uploads/sites/3/2014/10/MO-quetzally-michael-persico-940.jpg'                ),
                array(
                    'id' => 'vegetarian',
                    'name_en' => 'Vegetarian',
                    'name_es' => 'Vegetariano',
                    'picture' => 'https://www.vegetariantimes.com/.image/t_share/MTQ3MDM3MzQ5NjA2MzM2NDA3/zi3000-shutterstock-buddha-bowl.jpg'                ),
                array(
                    'id' => 'arab',
                    'name_en' => 'Arab',
                    'name_es' => 'Árabe',
                    'picture' => 'http://www.davehazzan.com/wp-content/uploads/2017/06/BW-Berlin-8420-1.jpg'                ),
            ),
            'Show' => array(
                array(
                    'id' => 'theater-dance',
                    'name_en' => 'Theater and dance',
                    'name_es' => 'Teatro y danza',
                    'picture' => 'https://www.jccmanhattan.org/image/arts-ideas/performances/Music_Dance_landing_page_image.jpg'
                ),
                array(
                    'id' => 'concerts-music',
                    'name_en' => 'Concerts and music',
                    'name_es' => 'Conciertos y música',
                    'picture' => 'http://www.millenniumparkconcerts.org/wp-content/uploads/2015/07/music-concert.jpg'
                ),
                array(
                    'id' => 'museums-exhibitions',
                    'name_en' => 'Museums and exhibitions',
                    'name_es' => 'Museos y exposiciones',
                    'picture' => 'https://www.museumnext.com/app/uploads/2017/04/met_big.jpg'
                ),
                array(
                    'id' => 'circus',
                    'name_en' => 'Circus',
                    'name_es' => 'Circo',
                    'picture' => 'https://cdn10.phillymag.com/wp-content/uploads/sites/3/2017/11/OTO-Caribbean-Dynasty-UniverSoulCircus-900x600.jpg'
                ),
                array(
                    'id' => 'cinema',
                    'name_en' => 'Cinema',
                    'name_es' => 'Cine',
                    'picture' => 'https://i.blogs.es/72b166/cine/450_1000.jpg'
                ),
                array(
                    'id' => 'sports-events',
                    'name_en' => 'Sports events',
                    'name_es' => 'Eventos de deportes',
                    'picture' => 'http://www.reservationsystems.com/Images/Sports_Large.png'
                ),
                array(
                    'id' => 'theme-parks',
                    'name_en' => 'Theme parks',
                    'name_es' => 'Parques temáticos',
                    'picture' => 'https://www.tripsavvy.com/thmb/ZjcjQTJj3vs8a-FGVW0T0-rYlL8=/960x0/filters:no_upscale():max_bytes(150000):strip_icc()/GettyImages-530779260-5aafd013875db900377746bd.jpg'
                ),
                array(
                    'id' => 'conferences',
                    'name_en' => 'Conferences',
                    'name_es' => 'Conferencias',
                    'picture' => 'http://www.laspositascollege.edu/gv/pdc/assets/images/conference.jpg'
                ),
                array(
                    'id' => 'thematic-fairs',
                    'name_en' => 'Thematic fairs',
                    'name_es' => 'Ferias temáticas',
                    'picture' => 'http://spaplatform.com/wp-content/uploads/2017/05/Beauty-Duesseldorf-4-sections.jpg'
                ),
            )
        );

        foreach ($options as $type => $values) {
            foreach ($values as $value) {
                $value['type'] = $type;
                $value['order'] = isset($value['order']) ? $value['order'] : null;
                $value['picture'] = isset($value['picture']) ? $value['picture'] : '';

                $this->merge($value);
            }
        }

        return $this->result;
    }
//
//    /**
//     * @param $type
//     * @param $id
//     * @param $names
//     * @param $order
//     * @param string $picture
//     */
//    public function processOption($type, $id, $names, $order = null, $picture = '')
//    {
//
//        $this->result->incrementTotal();
//
//        $data = array(
//            'type' => $type,
//            'id' => $id,
//            'name_es' => $names['name_es'],
//            'name_en' => $names['name_en'],
//            'order' => $order,
//            'picture' => $picture
//        );
//
//        $this->merge($data);
//        if ($this->optionExists($type, $id)) {
//
//            if ($this->optionExists($type, $id, $names, $order)) {
//
//                $this->logger->info(sprintf('Skipping, Already exists ProfileOption:%s id: "%s", name_en: "%s", name_es: "%s"', $type, $id, $names['name_en'], $names['name_es']));
//
//            } else {
//
//                $this->result->incrementUpdated();
//                $this->logger->info(sprintf('Updating ProfileOption:%s id: "%s", name_en: "%s", name_es: "%s"', $type, $id, $names['name_en'], $names['name_es']));
//                $parameters = array('type' => $type, 'id' => $id);
//                $parameters = array_merge($parameters, $names);
//                $cypher = "MATCH (o:ProfileOption) WHERE {type} IN labels(o) AND o.id = {id} SET o.name_en = {name_en}, o.name_es = {name_es}";
//                if ($order !== null) {
//                    $cypher .= " SET o.order = {order}";
//                    $parameters['order'] = $order;
//                }
//                $cypher .= " RETURN o;";
//
//                $query = $this->gm->createQuery($cypher, $parameters);
//                $query->getResultSet();
//            }
//
//        } else {
//
//            $this->result->incrementCreated();
//            $this->logger->info(sprintf('Creating ProfileOption:%s id: "%s", name_en: "%s", name_es: "%s"', $type, $id, $names['name_en'], $names['name_es']));
//            $parameters = array('id' => $id);
//            $parameters = array_merge($parameters, $names);
//            $cypher = "CREATE (o:ProfileOption:" . $type . " { id: {id}, name_en: {name_en}, name_es: {name_es} })";
//            if ($order !== null) {
//                $cypher .= " SET o.order = {order}";
//                $parameters['order'] = $order;
//            }
//
//            $query = $this->gm->createQuery($cypher, $parameters);
//            $query->getResultSet();
//        }
//    }
//
//    /**
//     * @param array $data
//     * @return boolean
//     * @throws \Exception
//     */
//    public function optionExists($type, $id, $names = array(), $order = null)
//    {
//        $qb = $this->gm->createQueryBuilder();
//
//        $qb->match('MATCH (o:ProfileOption)')
//            ->where('{type} IN labels(o) AND o.id = {id}')
//            ->with('o')
//            ->setParameter('type', $type)
//            ->setParameter('id', $id);
//
//        if (!empty($names)) {
//            $qb->where('o.name_es = {name_es}', 'o.name_en = {name_en}')
//                ->setParameter('name_es', $names('name_es'))
//                ->setParameter('name_en', $names('name_en'))
//                ->with('o');
//        }
//        if ($order !== null) {
//            $qb->where('o.order = {order}')
//                ->setParameter('order', $order)
//                ->with('o');
//        }
//
//        $qb->returns('o');
//
//        $query = $qb->getQuery();
//        $result = $query->getResultSet();
//
//        return count($result) > 0;
//    }

    public function merge(array $data)
    {
        $type = $data['type'];
        $id = $data['id'];
        $name_en = $data['name_en'];
        $name_es = $data['name_es'];
        $order = $data['order'];
        $picture = $data['picture'];

        $qb = $this->gm->createQueryBuilder();

        $qb->merge("(o:ProfileOption:$type{id: {id}})")
            ->with('o')
            ->setParameter('id', $id);

        $qb->set('o.name_es = {name_es}', 'o.name_en = {name_en}', 'o.picture = {picture}')
            ->setParameter('name_es', $name_es)
            ->setParameter('name_en', $name_en)
            ->setParameter('picture', $picture)
            ->with('o');

        if ($order !== null) {
            $qb->set('o.order = {order}')
                ->setParameter('order', $order)
                ->with('o');
        }

        $qb->returns('o');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        return count($result) > 0;
    }
} 