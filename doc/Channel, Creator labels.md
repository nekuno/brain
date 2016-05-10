En nuestro modelo en Neo4j hay dos etiquetas parecidas semánticamente: Creator y ChannelTwitter.

Creator es una etiqueta de CONTENIDOS (:Link), que indica que la URL del contenido es el perfil de una persona (si alguien postea en facebook "me gusta youtube.com/stevejobs").

ChannelTwitter es una etiqueta de PERSONAS (:User), que indica que esa persona es una fuente de contenidos a fetchear (por ejemplo, crear usuarios fantasma con sus direcciones de Twitter y fetchearlos).

Nunca un nodo será, por tanto, Creator y ChannelTwitter (o, en el futuro, ChannelYoutube, etc) al mismo tiempo.

Pueden estar relacionados si un contenido Creator (www.twitter.com/yawmoght) es también la url que el usuario ChannelTwitter tiene en su relación HAS_SOCIAL_NETWORK que apunta a qué red social tiene. 