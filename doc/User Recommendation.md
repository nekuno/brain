El algoritmo de recomendación de usuarios hace lo siguiente:

    -Busca TODOS los usuarios reales con los que el usuario logado tiene similaridad o matching calculado
    -Filtra: condiciones de los usuarios (grupos, similaridad...)
    -Se lee la información de similaridad, matching, likes con ellos
    -Filtra: solo los usuarios que tengan similaridad o matching > 0
    -Se leen perfiles y localizaciones
    -Filtra: condiciones de perfiles
    -Se ordenan y devuelven los datos de la cantidad de usuarios.