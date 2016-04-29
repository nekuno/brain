APLICACION DE FILTROS:

BÚSQUEDA A TRAVÉS DE LA BASE DE DATOS:

Se busca de tres maneras consecutivas:

    -Primero se buscan contenidos con los que el usuario tenga calculada una afinidad. Como se recalculan periódicamente las mejores afinidades, éstas serán las afinidades más altas en la mayoría de los casos.
 
    -Después se buscan contenidos con los que el usuario pueda tener una afinidad (según los límites de dicho algoritmo) pero no la tiene. Son, por definición, afinidades más bajas que antes.
 
    -Después se buscan contenidos "ajenos al usuario" en la base de datos  que cumplan los filtros. Estos contenidos no pueden tener afinidad con  el usuario, por lo que se le muestra 0.

ALGORITMO DE BÚSQUEDA DE CONTENIDO AJENO:

La búsqueda de contenido ajeno tiene como principal dificultad que se busca en toda la base de datos. Las peticiones pueden, por tanto, ser extremadamente lentas si no tenemos cuidado.

Para evitarlo, se realiza una paginación de la base de datos, utilizando "foreign" como offset de dicha paginación. Además, esto nos permite realizar búsquedas consecutivas sin repetir resultados.

**Dentro del método getForeignContent:**

    -Limit es la cantidad de contenidos que buscamos.
    
    -PageSizeMultiplier indica el tamaño de cada página. Si es muy pequeño, serán muchas búsquedas consecutivas. Si es muy grande será más rápido, pero pueden saltarse contenidos (si necesitamos 10 contenidos y una página nos resultan 12 válidos, la siguiente búsqueda empezará por la siguiente página perdiendo esos 2)
    
    -InternalLimit es la cantidad total de contenidos que pediremos a cada página.
    
    -MaxPagesSearched es un límite arbitrario para que haya un límite de tiempo. Pasado ese número de búsquedas se considera siempre que ya no hay más por ofrecer con esos filtros.
    
    -DatabaseSize es la cantidad total de contenidos con esos filtros.
    
    -PagesSearched es la cantidad de páginas que vamos a buscar. Depende de cuántos contenidos tenemos que nos puedan valer.
    
    -InternalPaginationLimit es el límite final de contenidos que le pondremos a las búsquedas.
