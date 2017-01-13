EN PROCESO DE CAMBIO:

ProcessorService: https://drive.google.com/open?id=0B09NUPYLIvftNlRzRVI2V1EtT2s

(anticuado en parte) <<

El proceso para obtener información de redes sociales consta de dos partes: fetching (conseguir) y processing (procesar).

En la mayoría de casos se accederá via FetcherService, donde están los dos principales puntos de entrada (fetch y processLinks)

PASO A PASO:

Fetching:

    -Los fetchers son los encargados de obtener una lista de urls de la red social.
    -Se construyen a partir del FetcherFactory, que maneja las opciones provenientes de config/apiConsumer.yml 
    -Cada red social tiene uno o más fetchers.
    
    -Los fetchers requiren tokens como parámetros para pedir en nombre de un usuario.
    -El método principal es FetchLinksFromUserFeed. Este método devuelve objetos de tipo PreprocessedLink con la lógica adicional necesaria para el procesamiento.
    -En el caso de los que descienden de BasicPagination (la mayoría), la construcción se hace en parseLinks.
    
Processing:

    -Se procesa un array de objetos preprocessedLink. La clase LinkProcessor los procesa de uno en uno.
    -El objetivo de procesar es obtener la información extra necesaria y devolverla en forma de array (link) para ser después guardado con LinkModel.
    
    -Procesar es un bucle porque puede ser necesario ejecutarlo varias veces por cambiar la URL final al ser procesado, para obtener todo lo posible.
    
    -Primero se comprueba que no esté ya guardado y procesado en Neo. Si es así se devuelve el enlace con la información obtenida en el fetcheo.
    
    -Resolver el enlace es, principalmente, seguir las redirecciones hasta la URL final. Esta URL es la "canónica".
    -Si ha habido excepciones, o respuestas 4xx o 5xx, el enlace se devuelve con el valor processed = 0. No aparecerá a usuarios.
        -Los enlaces con processed = 0 en ningún momento sobreescribirán un enlace que tenga processed = 1, en caso de actualizarse.
    
    -La URL se "limpia" de acuerdo al tipo de URL que sea: eliminar '/' final, o parámetros extra en vídeos de YouTube...
    -Se vuelve a comprobar si ya está en la base de datos.
    
    -Se selecciona el procesador adecuado y se procesa el enlace.
        -Los procesadores manejan las URLs según subtipos (como canción, álbum o artista en Spotify).
        -Normalmente hacen peticiones a las APIs oportunas mediante el ResourceOwner.
        -En caso de no tener ningún procesamiento especial, scrapean lo posible, via metas y otros tags en el HTML de la página.
        
    -En caso de que al procesar se haya obtenido una nueva URL que considerar canónica, se vuelve al inicio para pasar por todos los pasos.
    -Si en el array devuelto no hay key "url" se llama al scraper processor.
    
ESTRUCTURA DE CLASES:

Fetchers:

        -user: array token para fetchear
        -rawFeed: array con los links almacenados antes de parsearlos
        -resourceOwner: clase que maneja las peticiones HTTP externas
        -url: Url del recurso concreto a pedir a las APIs
        
        -fetchLinksFromUserFeed(token, public)
            -token: array para hacer peticiones en nombre de un usuario
            -public: si se quiere hacer las peticiones sin usar credenciales de usuario
            
            Pide externamente las urls y las devuelve como PreProcessedLinks
        
        -getLinksByPage(public)
            usa el token guardado en "user" para hacer peticiones en nombre del usuario y almacena el resultado en rawFeed
            
        -parseLinks(rawFeed)
            -transforma los datos de rawFeed y los devuelve como PreprocessedLinks
            
LinkProcessor:

        -LinkResolver: encargado de obtener la URL canónica
        -LinkAnalyzer: encargado de obtener el procesador para la URL
        -Procesadores: Varios procesadores a los que pasar el PreprocessedLink
        
        -process(PreprocessedLink)
            Método principal con el control de la información y los errores resultantes explicados arriba.
        
Procesadores:

        -Resourceowners: Para realizar peticiones extra a APIs externas
        -UserAggregator: En caso de obtener urls de perfiles, se añaden de esta manera con addCreator
        -Parser: Extensiones de UrlParser con reglas especiales
        -ScraperProcessor: Encargado de obtener datos del HTML directamente
        
        -process(PreprocessedLink)
            Método principal que devuelve un array link para ser procesado.
            En caso de que sea necesario cambiar de URL para reprocesar, cambia el canonical del argumento.
            
>>