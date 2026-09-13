## Purpose

Definir el lanzador de EscritorioWP, que sustituye a la paleta de comandos nativa de wp-admin: apertura con Cmd/Ctrl+K, catálogo de comandos de navegación y acciones, presentación de resultados de búsqueda y comportamiento con teclado.

## ADDED Requirements

### Requirement: Desactivación de la paleta nativa de wp-admin

Cuando el ajuste "Desactivar la paleta de comandos nativa" esté activo, en las pantallas de administración distintas del editor de bloques (editor de entradas y editor del sitio) la paleta de comandos nativa de WordPress NO SHALL abrirse con Cmd/Ctrl+K ni SHALL cargarse su interfaz. La paleta del editor de bloques SHALL seguir funcionando sin cambios.

#### Scenario: Listado de entradas

- **WHEN** el usuario pulsa Cmd/Ctrl+K en Entradas › Todas las entradas
- **THEN** se abre el lanzador de EscritorioWP y no la paleta nativa

#### Scenario: Editor de bloques

- **WHEN** el usuario pulsa Cmd/Ctrl+K dentro del editor de una entrada
- **THEN** se abre la paleta de comandos nativa del editor, y el lanzador de EscritorioWP no interfiere

#### Scenario: Ajuste desactivado

- **WHEN** el ajuste "Desactivar la paleta de comandos nativa" está desactivado y el lanzador propio también
- **THEN** la paleta nativa funciona como sin el plugin

### Requirement: Apertura del lanzador

Cuando el ajuste "Activar el lanzador" esté activo, el lanzador SHALL abrirse con Cmd+K (macOS) o Ctrl+K (resto) en cualquier pantalla de administración fuera del editor de bloques, y también en el front-end para usuarios autenticados con barra de administración visible si el ajuste correspondiente está activo. También SHALL abrirse desde un botón "Buscar" con la pista del atajo en la barra de administración. El atajo SHALL prevenir la acción por defecto del navegador y SHALL funcionar aunque el foco esté en un campo de texto. Escape o pulsar fuera SHALL cerrarlo devolviendo el foco al elemento previo.

#### Scenario: Apertura desde cualquier pantalla del admin

- **WHEN** el usuario pulsa Ctrl+K en Ajustes › Generales con el foco en el campo "Título del sitio"
- **THEN** se abre el lanzador con el foco en su campo de búsqueda, y el navegador no ejecuta su acción por defecto

#### Scenario: Apertura en el front-end

- **WHEN** un usuario autenticado navega por el sitio con la barra de administración visible y pulsa Cmd+K
- **THEN** se abre el lanzador

#### Scenario: Botón de la barra de administración

- **WHEN** el usuario pulsa el botón "Buscar ⌘K" de la barra de administración
- **THEN** se abre el lanzador

#### Scenario: Cierre

- **WHEN** el lanzador está abierto y el usuario pulsa Escape
- **THEN** el lanzador se cierra y el foco vuelve al elemento que lo tenía antes

### Requirement: Estado inicial sin búsqueda

Con el campo de búsqueda vacío, el lanzador SHALL mostrar los elementos recientes abiertos desde el lanzador (hasta 8, guardados en el navegador del usuario) y una lista de acciones rápidas disponibles para el usuario.

#### Scenario: Primer uso

- **WHEN** el usuario abre el lanzador por primera vez en un navegador
- **THEN** ve solo las acciones rápidas y una sugerencia de qué puede buscar

#### Scenario: Recientes

- **WHEN** el usuario abrió antes la página "Contacto" desde el lanzador
- **THEN** al abrir el lanzador con el campo vacío "Contacto" aparece en la sección "Recientes"

### Requirement: Catálogo de comandos de navegación y acciones

El lanzador SHALL ofrecer como comandos todos los elementos del menú de administración (menú y submenús) que el usuario actual puede ver, identificados por su ruta ("Ajustes › Lectura"), más acciones fijas condicionadas a capacidades: Nueva entrada, Nueva página, Nuevo usuario, Nuevo producto, Nuevo pedido, Ver el sitio, Mi perfil, Ir al Escritorio, Ajustes de EscritorioWP y Cerrar sesión. La coincidencia de comandos SHALL ser instantánea (en el cliente), insensible a mayúsculas y acentos y tolerante a coincidencias parciales por palabra.

#### Scenario: Buscar una pantalla del admin

- **WHEN** el usuario escribe "plug"
- **THEN** aparecen "Plugins", "Plugins › Añadir nuevo" y "Plugins › Editor de archivos de plugins" (si los puede ver)

#### Scenario: Insensible a acentos

- **WHEN** el usuario escribe "pagina"
- **THEN** coincide "Páginas" y sus subelementos

#### Scenario: Menú añadido por otro plugin

- **WHEN** otro plugin añade "WooCommerce › Ajustes" al menú
- **THEN** ese elemento es localizable en el lanzador sin configuración adicional

#### Scenario: Acción sin capacidad

- **WHEN** el usuario no tiene `create_users`
- **THEN** la acción "Nuevo usuario" no aparece

### Requirement: Presentación de resultados de contenido

A partir de 2 caracteres, el lanzador SHALL consultar la búsqueda unificada del servidor (con espera de escritura de unos 200 ms y cancelando peticiones obsoletas) y presentar los resultados agrupados en este orden: Comandos, Entradas, Páginas, Otros contenidos, Usuarios, Productos, Pedidos. Cada grupo SHALL mostrar como máximo el límite del servidor y, si hay más, un enlace "Ver todos en el listado" que abre el listado nativo filtrado por el texto buscado. Cada resultado SHALL mostrar título, subtítulo (estado, email, precio, cliente, según tipo) e icono del tipo.

#### Scenario: Resultado mixto

- **WHEN** el usuario escribe "ana"
- **THEN** ve, agrupados, entradas y páginas cuyo título contiene "ana", usuarios cuyo nombre, login o email contiene "ana", productos y pedidos coincidentes, cada grupo con su cabecera

#### Scenario: Sin resultados

- **WHEN** ninguna fuente devuelve resultados
- **THEN** el lanzador muestra "Sin resultados para «…»" y mantiene las acciones rápidas coincidentes si las hay

#### Scenario: Peticiones obsoletas

- **WHEN** el usuario sigue escribiendo mientras una búsqueda anterior está en curso
- **THEN** solo se muestran los resultados de la última búsqueda

#### Scenario: Error del servidor

- **WHEN** la búsqueda unificada devuelve error
- **THEN** el lanzador muestra un aviso de error no bloqueante y los comandos siguen funcionando

### Requirement: Filtros por tipo

El lanzador SHALL ofrecer filtros (Todo, Menú, Entradas, Páginas, Usuarios, Productos, Pedidos) que restringen los resultados a un tipo, mostrando solo los filtros para los que el usuario tiene capacidad y WooCommerce está activo cuando corresponda. Tab y Shift+Tab SHALL rotar el filtro activo.

#### Scenario: Filtrar a usuarios

- **WHEN** el usuario selecciona el filtro "Usuarios" y escribe "@gmail"
- **THEN** solo se listan usuarios cuyo email contiene "@gmail"

### Requirement: Navegación y acciones con teclado

Las flechas arriba/abajo SHALL mover la selección entre resultados (saltando cabeceras de grupo y con desplazamiento automático), Enter SHALL ejecutar la acción principal (abrir la pantalla de edición o el comando), Cmd/Ctrl+Enter SHALL abrirla en una pestaña nueva y Shift+Enter SHALL ejecutar la acción secundaria si existe (ver en el sitio). El ratón SHALL poder seleccionar y ejecutar igualmente, y cada resultado SHALL mostrar sus acciones al estar seleccionado.

#### Scenario: Abrir en pestaña nueva

- **WHEN** el resultado seleccionado es la página "Contacto" y el usuario pulsa Cmd+Enter
- **THEN** la pantalla de edición de "Contacto" se abre en una pestaña nueva y el lanzador permanece abierto

#### Scenario: Ver en el sitio

- **WHEN** el resultado seleccionado es una entrada publicada y el usuario pulsa Shift+Enter
- **THEN** se abre la entrada en el sitio

#### Scenario: Selección inicial

- **WHEN** llegan resultados
- **THEN** el primer resultado queda seleccionado y Enter lo abre

### Requirement: Accesibilidad del lanzador

El lanzador SHALL comportarse como un diálogo modal accesible: rol de diálogo con etiqueta, foco atrapado dentro mientras está abierto, lista de resultados anunciada como listbox con la opción activa identificada, y contraste suficiente en tema claro y oscuro (siguiendo la preferencia de tema del escritorio).

#### Scenario: Lector de pantalla

- **WHEN** el usuario cambia la selección con las flechas
- **THEN** el lector de pantalla anuncia el título y el tipo del resultado seleccionado
