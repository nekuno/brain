Para el recálculo de popularidad de contenidos de un usuario (habilidad y lenguaje son similares) se hace lo siguiente:

-Se halla el nodo popularidad con popularidad = 1 para saber el número máximo de LIKES de un contenido

    -En caso de no existir, se toma como numero máximo el máximo de LIKES del usuario que estamos analizando
        
        -En caso de no existir, ese usuario no tiene enlaces y se para el proceso
        
-Se recalculan las popularidades en base a ese número máximo de LIKES. Si algún contenido tiene más LIKES, se le asigna popularidad = 1
    
    -Se aprovecha para "migrar" la popularidad en el nodo Link al nuevo nodo Popularidad

-Si ha habido algún contenido con popularidad = 1, se ejecuta una query que analiza todos los nodos con popularidad = 1 para "desempatar".

    -Si son iguales, se deja como está puesto que no afecta al cálculo.