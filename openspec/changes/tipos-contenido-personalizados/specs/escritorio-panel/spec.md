## ADDED Requirements

### Requirement: Paneles de tipos de contenido personalizados

Por cada tipo de contenido personalizado marcado en el ajuste «Paneles», el escritorio SHALL mostrar un panel con su nombre y sus N elementos modificados más recientemente, con las mismas filas que el panel de entradas, «Ver todos» hacia su listado nativo y un botón de crear que lleva a su pantalla nativa de creación. Por defecto SHALL ir detrás de los paneles propios, y cada usuario SHALL poder ocultarlo y reordenarlo como cualquier otro panel.

#### Scenario: Panel de un tipo activado

- **WHEN** el tipo «Libros» está marcado en «Paneles», tiene dos libros publicados y uno en borrador, N = 5 y un administrador abre el Escritorio
- **THEN** ve el panel «Libros» detrás de los paneles propios, con los tres libros ordenados por fecha de modificación descendente, la etiqueta «Borrador» en el correspondiente, la acción «Ver» en los publicados y «Ver todos» enlazando al listado de libros

#### Scenario: Crear desde el panel

- **WHEN** el usuario pulsa el botón de crear del panel «Libros»
- **THEN** va a la pantalla nativa de creación de libros, sin ventana de creación rápida

#### Scenario: Panel vacío

- **WHEN** el tipo «Libros» está marcado en «Paneles» y no existe ningún libro
- **THEN** el panel muestra el texto que el propio tipo usa cuando no hay elementos y un botón con el texto que el tipo usa para añadir uno nuevo, que lleva a su pantalla nativa de creación

#### Scenario: Sin capacidad para crear

- **WHEN** un usuario puede editar libros pero no crearlos
- **THEN** ve el panel «Libros» sin botón de crear, ni en la cabecera del panel ni en su estado vacío

#### Scenario: Solo lo propio para quien no edita lo ajeno

- **WHEN** un usuario puede editar sus libros pero no los de otros usuarios
- **THEN** el panel «Libros» muestra solo los libros de los que es autor

#### Scenario: Personalizar un panel de tipo personalizado

- **WHEN** un usuario oculta el panel «Libros» desde «Personalizar», lo vuelve a mostrar, lo sube por encima de «Entradas» y recarga el Escritorio desde otro navegador
- **THEN** el panel «Libros» se muestra encima del de entradas

#### Scenario: Cabecera sin cambios

- **WHEN** el tipo «Libros» está marcado en «Paneles» y un usuario que puede crear libros abre el Escritorio
- **THEN** la cabecera muestra los mismos botones de creación que sin el tipo marcado, sin ninguno para libros

### Requirement: Indicadores de tipos de contenido personalizados

Por cada tipo de contenido personalizado marcado en el ajuste «Indicadores», el escritorio SHALL mostrar una tarjeta con el nombre del tipo, el número de elementos publicados y, si los hay, el número de borradores, que enlaza al listado nativo del tipo. Estas tarjetas SHALL ir detrás de las propias, en el orden en que el ajuste lista los tipos. El indicador y el panel de un mismo tipo SHALL activarse por separado.

#### Scenario: Tarjeta de un tipo activado

- **WHEN** el tipo «Libros» está marcado en «Indicadores» y tiene 12 libros publicados y 3 en borrador
- **THEN** detrás de las tarjetas propias aparece la tarjeta «Libros» con el valor 12 y el detalle «3 en borrador», que enlaza al listado de libros

#### Scenario: Sin borradores

- **WHEN** el tipo «Libros» tiene 12 libros publicados y ninguno en borrador
- **THEN** su tarjeta muestra el valor 12 sin texto de detalle

#### Scenario: Panel sin indicador

- **WHEN** «Libros» está marcado en «Paneles» y desmarcado en «Indicadores»
- **THEN** el Escritorio muestra el panel «Libros» y no muestra su tarjeta, tampoco en la respuesta de la ruta REST del resumen

#### Scenario: Único indicador activo

- **WHEN** todos los indicadores propios están desmarcados y solo «Libros» está marcado en «Indicadores»
- **THEN** el Escritorio muestra la fila de tarjetas con la tarjeta de libros como única tarjeta

### Requirement: Tipo de contenido personalizado que deja de existir

Cuando un tipo de contenido personalizado activado deja de estar registrado, el escritorio SHALL dejar de mostrar su panel y su tarjeta sin errores, y los ajustes SHALL dejar de listar sus casillas pero conservar que estaba activado, también si se guardan mientras tanto. Si el tipo vuelve a registrarse, su panel y su tarjeta SHALL volver, y las preferencias de cada usuario sobre su panel SHALL conservarse.

#### Scenario: Plugin del tipo desactivado

- **WHEN** «Libros» está marcado en «Paneles» y en «Indicadores» y se desactiva el plugin que registra el tipo
- **THEN** ningún usuario ve el panel ni la tarjeta de libros, el resto del Escritorio funciona con normalidad y EscritorioWP › Ajustes no lista «Libros»

#### Scenario: Ajustes guardados sin el tipo

- **WHEN** con el plugin de libros desactivado un administrador guarda los ajustes, y después se reactiva el plugin
- **THEN** «Libros» vuelve a aparecer marcado en los dos grupos y su panel y su tarjeta vuelven al Escritorio

#### Scenario: Preferencia conservada

- **WHEN** un usuario tenía oculto el panel «Libros», se desactiva el plugin de libros, el usuario cambia el tema del escritorio y después se reactiva el plugin
- **THEN** el panel «Libros» sigue oculto para ese usuario y aparece en su menú «Personalizar» para reactivarlo

### Requirement: Detección de tipos de contenido personalizados en los ajustes

Los grupos «Indicadores» y «Paneles» de los ajustes SHALL listar, después de sus opciones propias, una casilla por cada tipo de contenido personalizado registrado que tenga pantalla en el menú del administrador, con su nombre, ordenadas por nombre y desmarcadas hasta que el administrador las marque. La lista SHALL calcularse al abrir la pantalla. No SHALL incluir tipos del núcleo de WordPress ni de WooCommerce.

#### Scenario: Tipos detectados

- **WHEN** un plugin registra los tipos «Libros» y «Eventos» y un administrador abre EscritorioWP › Ajustes sin haber guardado nunca los ajustes
- **THEN** «Indicadores» y «Paneles» listan, después de sus opciones propias y desmarcadas, «Eventos» y «Libros», y el Escritorio no muestra ningún panel ni tarjeta de esos tipos

#### Scenario: Tipo nuevo

- **WHEN** se activa un plugin que registra el tipo «Cursos» y un administrador vuelve a abrir EscritorioWP › Ajustes
- **THEN** «Cursos» aparece desmarcado en los dos grupos y el resto de casillas conserva su valor

#### Scenario: Tipos que no se ofrecen

- **WHEN** el sitio tiene WooCommerce activo, un tipo personalizado registrado sin pantalla en el menú del administrador y los tipos propios del núcleo (adjuntos, bloques, plantillas, navegación)
- **THEN** ninguno de ellos aparece en los grupos como tipo personalizado, y productos y pedidos siguen apareciendo solo como opciones propias

#### Scenario: Dos tipos con el mismo nombre

- **WHEN** dos plugins registran tipos distintos que se llaman los dos «Eventos»
- **THEN** cada casilla muestra, junto al nombre, el identificador de su tipo, para distinguirlas

#### Scenario: Sin tipos personalizados

- **WHEN** el sitio no tiene ningún tipo de contenido personalizado y un administrador abre EscritorioWP › Ajustes
- **THEN** los grupos «Indicadores» y «Paneles» muestran solo sus opciones propias y su descripción indica que el sitio no tiene tipos de contenido personalizados

#### Scenario: Valor manipulado

- **WHEN** se envía el formulario con «Libros» marcado en «Paneles» junto a una clave de tipo personalizado con caracteres no válidos
- **THEN** «Libros» se guarda activado, la clave no válida no se guarda y no se muestra ningún error

## MODIFIED Requirements

### Requirement: Control de acceso por capacidades

La interfaz SHALL mostrar cada panel e indicador únicamente a usuarios con la capacidad que WordPress o WooCommerce exigen para la pantalla nativa equivalente: `edit_posts` (entradas), `edit_pages` (páginas), `list_users` (usuarios), `edit_products` (productos), `edit_shop_orders` (pedidos), `moderate_comments` (comentarios), `update_core` o `update_plugins` (actualizaciones) y, para cada tipo de contenido personalizado, la capacidad de editar elementos de ese tipo que exige su listado nativo. Las rutas REST que alimentan cada panel SHALL aplicar la misma comprobación en el servidor.

#### Scenario: Autor

- **WHEN** un usuario con rol Autor abre el Escritorio
- **THEN** ve el panel de entradas y no ve los paneles de páginas, usuarios, productos ni pedidos

#### Scenario: Petición REST directa sin capacidad

- **WHEN** un Autor llama directamente a la ruta REST de últimos usuarios
- **THEN** recibe un error 403 sin datos

#### Scenario: Tipo personalizado sin capacidad

- **WHEN** el tipo «Libros» está marcado en «Paneles» y en «Indicadores», y un usuario sin la capacidad de editar libros abre el Escritorio
- **THEN** no ve ni el panel ni la tarjeta de libros, y si llama directamente a la ruta REST de últimos libros recibe un error 403 sin datos

#### Scenario: Tipo personalizado no activado

- **WHEN** el tipo «Libros» está desmarcado en «Paneles» y un usuario con la capacidad de editar libros llama directamente a la ruta REST de últimos libros
- **THEN** recibe los últimos libros, igual que con cualquier otro panel desactivado

#### Scenario: Tipo que no se ofrece

- **WHEN** un usuario identificado llama a la ruta REST de últimos elementos con la clave de un tipo personalizado que no está registrado, que no se ofrece en los ajustes o que es de WooCommerce
- **THEN** recibe un error 404 sin datos

### Requirement: Elección de paneles e indicadores en los ajustes

La sección «Escritorio» de los ajustes SHALL ofrecer «Indicadores» y «Paneles» como dos grupos de casillas con nombre accesible, una casilla por tarjeta o panel, con su nombre en el escritorio y en su orden. Los de WooCommerce SHALL listarse solo con WooCommerce activo y conservar su valor mientras no lo está. El servidor SHALL descartar las claves desconocidas.

#### Scenario: Opciones sin WooCommerce

- **WHEN** un administrador abre EscritorioWP › Ajustes en un sitio sin WooCommerce ni tipos de contenido personalizados y sin haber guardado nunca estos ajustes
- **THEN** «Indicadores» lista, todas marcadas, «Entradas publicadas», «Páginas publicadas», «Usuarios registrados», «Comentarios pendientes» y «Actualizaciones pendientes», y «Paneles» lista, todas marcadas, «Entradas», «Páginas» y «Usuarios»

#### Scenario: Opciones con WooCommerce

- **WHEN** un administrador abre EscritorioWP › Ajustes con WooCommerce activo
- **THEN** «Indicadores» incluye además «Productos publicados», «Pedidos este mes» e «Ingresos este mes» después de «Usuarios registrados», y «Paneles» incluye además «Productos» y «Pedidos» después de «Usuarios», antes de cualquier tipo de contenido personalizado

#### Scenario: Valor de WooCommerce conservado

- **WHEN** el panel «Pedidos» está desmarcado, se desactiva WooCommerce, un administrador guarda los ajustes y después se reactiva WooCommerce
- **THEN** el panel «Pedidos» sigue desmarcado en los ajustes y no se muestra en el escritorio

#### Scenario: Valor manipulado

- **WHEN** se envía el formulario con una clave que no es un panel, como `inventado`, junto a «Páginas» desmarcado
- **THEN** la clave desconocida no se guarda, «Páginas» se guarda desmarcado y no se muestra ningún error

#### Scenario: Lector de pantalla

- **WHEN** un usuario de lector de pantalla llega a la primera casilla del grupo «Paneles»
- **THEN** el lector anuncia el nombre del grupo, «Paneles», y el nombre del panel de esa casilla
