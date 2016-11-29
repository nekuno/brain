**Validator**

Validator es el servicio para validar cualquier array de datos. Para ello lee el archivo fields.yml (a través de los metadataModel, WIP) y valida a partir de esas reglas.

El método validateUserId es distinto del resto de validación. Sólo comprueba que el usuario exista y, si no, emite una ValidationException.

Los métodos validate tienen dos argumentos:

    data: Array con los datos a validar
    choices: Array con las opciones para los tipos que lo usen, si se obtienen del modelo y no de los metadatos.

**Validaciones por tipo**

Para todos los tipos:

    required: Si es true comprueba si los datos lo incluyen
    label: Nombre del campo, en varios idiomas
    labelFilter: Nombre a mostrar en los filtros donde se use este campo, en varios idiomas.

text/textarea:

//TODO: Comprobar tipo (string)

    min: Longitud mínima del texto
    max: Longitud máxima del texto
    

integer:

Comprueba tipo (entero)

    min: Valor mínimo del entero
    max: Valor máximo del entero
    
integer_range:

Comprueba tipo (array)

Comprueba tipo de min y max si existen

Comprueba que el mínimo no sea superior al máximo

    min: Valor mínimo del mínimo y del máximo
    max: Valor máximo del mínimo y del máximo
    
date:

    Comprueba formato Y-m-d

birthday:

Comprueba tipo (string)

    //TODO: Hacer min - max en tipo date y eliminar este tipo especial de profile

birthday_range:
    
Comprueba tipo (array)

Si hay mínimo y máximo, comprueba que el mínimo sea menor que el máximo
    
    min: Valor mínimo de cualquiera del input (mínimo o máximo)
    max: Valor máximo de cualquiera del input (mínimo o máximo)

boolean:

Comprueba tipo (booleano)

choice:

Comprueba que el valor no esté en las opciones habilitadas

double_choice:

Comprueba que el valor no esté en las opciones habilitadas

    double_choices: Opciones posibles para el valor de 'detail' en los datos a validar. Formato: {choice: {detail: {locale: X}}}
    
double_multiple_choices:

Compreba tipo (array)

Para cada valor, comprueba igual que en double_choice

tags:

//TODO: Max/min

tags_and_choice:

Comprueba tipo (array)

Comprueba el máximo de tags
//TODO: Pasar máximo de tags a metadata
Comprueba que haya keys tag y choice

    choices: Opciones posibles para el valor de 'choice' en los datos a validar. Formato: {choice: {locale: X}}
    
multiple_choices:

Comprueba tipo (array)

    max_choices: Máxima cantidad de choices
    
Comprueba que el valor no esté en las opciones habilitadas
//TODO: Comprobar origen de las choices

location:

Comprueba tipo (array)
//TODO: Cambiar mensaje de validación, habla incorrectamente de keys ahí

Comprueba keys: address, latitude (float), longitude (float), locality, country
//TODO: Cambiar estructura de if para devolver todos los errores al inicio

**Comprobación de usaurio**

Si los datos incluyen la key 'userId' se comprueba que el usuario exista

**Output**

En caso de haber algún error se lanza una ValidationException con dichos errores para ser tratada.

En caso contrario devuelve true