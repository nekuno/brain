**Configuración de PHPUnit en PHPStorm**

    - Ir a File -> Settings...

    - PHP -> PHPUnit
    - En PHPUnit library seleccionar "Use custom loader" y en "path to script" escribir pathToBrain/vendor/autoload.php
    - Guardar

    - Ir a Run -> Edit Configurations...
    - Botón + -> PHPUnit
    - Nombrar test y seleccionar en "Test running" "Defined in the configuration file"
    - Seleccionar "Use alternative configuratin file" y escribir pathToBrain/phpunit.xml
    - Guardar

    - Run -> Run... y seleccionar test creado

*sustituir "pathToBrain" por el path de brain*