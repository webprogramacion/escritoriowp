## Purpose

Permitir que una instalación de WordPress detecte, muestre e instale versiones nuevas del plugin publicadas como releases en el repositorio público de GitHub, usando el mecanismo de actualizaciones estándar de WordPress.

## ADDED Requirements

### Requirement: Detección de versión nueva

El plugin SHALL consultar la última release publicada del repositorio `webprogramacion/escritoriowp` cuando WordPress ejecute su comprobación periódica de actualizaciones de plugins, y SHALL informar a WordPress de la versión, la URL del zip y la URL de la release. WordPress SHALL mostrar el aviso de actualización disponible únicamente cuando la versión de la release sea mayor que la instalada. Las releases marcadas como borrador o prelanzamiento SHALL ignorarse.

#### Scenario: Hay una release más reciente

- **WHEN** la instalación tiene `0.1.0` y la última release publicada es `v0.2.0` con el activo `escritoriowp-0.2.0.zip`
- **THEN** la pantalla Plugins muestra en la fila de EscritorioWP «Hay una nueva versión 0.2.0 disponible» y el contador de actualizaciones del menú incluye el plugin

#### Scenario: La instalación ya está al día

- **WHEN** la versión instalada es igual o mayor que la de la última release
- **THEN** no se muestra ningún aviso de actualización para el plugin

#### Scenario: Release sin zip

- **WHEN** la última release no tiene ningún activo con extensión `.zip`
- **THEN** el plugin no ofrece esa release como actualización y no se muestra ningún aviso

#### Scenario: GitHub no responde

- **WHEN** la petición a la API de GitHub falla o devuelve un error
- **THEN** no se muestra ningún aviso ni error al usuario, el plugin sigue funcionando con normalidad y no vuelve a intentarlo hasta pasada al menos una hora

### Requirement: Caché de la consulta

El resultado de la consulta a GitHub SHALL guardarse en un transitorio con prefijo del plugin durante 12 horas, y un fallo SHALL guardarse durante 1 hora, de modo que ninguna carga de página del administrador provoque por sí misma una petición a GitHub.

#### Scenario: Consultas repetidas

- **WHEN** WordPress comprueba actualizaciones varias veces en menos de 12 horas
- **THEN** solo la primera comprobación realiza una petición HTTP a GitHub

#### Scenario: Desinstalación

- **WHEN** el plugin se borra desde la pantalla de Plugins
- **THEN** el transitorio de actualizaciones desaparece de la base de datos

### Requirement: Detalles de la versión

El enlace «Ver detalles de la versión» de la pantalla Plugins SHALL abrir la ventana modal estándar de WordPress con nombre, versión, autor, requisitos de WordPress y PHP, fecha de publicación, enlace al repositorio y el contenido de las notas de la release como changelog, sin acudir a WordPress.org.

#### Scenario: Abrir los detalles

- **WHEN** un administrador pulsa «Ver detalles de la versión 0.2.0»
- **THEN** la modal muestra los datos de la release `v0.2.0` de GitHub y un botón para instalar la actualización

### Requirement: Instalación de la actualización

Al pulsar «Actualizar ahora», WordPress SHALL descargar el zip de la release, reemplazar `wp-content/plugins/escritoriowp/` y conservar activo el plugin con la versión nueva, sin perder ajustes ni preferencias de usuario. Solo los usuarios con la capacidad `update_plugins` SHALL poder hacerlo.

#### Scenario: Actualización con un clic

- **WHEN** un administrador pulsa «Actualizar ahora» en la fila del plugin
- **THEN** al terminar, la pantalla Plugins muestra la versión nueva, el plugin sigue activo y los ajustes guardados son los mismos que antes

#### Scenario: Actualización desde la pantalla Actualizaciones

- **WHEN** un administrador marca EscritorioWP en Escritorio › Actualizaciones y pulsa «Actualizar plugins»
- **THEN** el resultado es el mismo que con «Actualizar ahora»

### Requirement: Comprobación manual

La pantalla Acerca de SHALL ofrecer a los usuarios con `update_plugins` un botón «Buscar actualizaciones ahora» que descarte la caché, vuelva a consultar GitHub y muestre el resultado (al día, o versión nueva con enlace a la pantalla Plugins). La acción SHALL estar protegida con nonce.

#### Scenario: Buscar con versión nueva disponible

- **WHEN** un administrador pulsa el botón y GitHub tiene una release mayor que la instalada
- **THEN** la pantalla vuelve a cargarse indicando la versión disponible y un enlace para actualizarla en Plugins

#### Scenario: Petición sin nonce válido

- **WHEN** se envía la acción de comprobación sin nonce o con uno caducado
- **THEN** WordPress rechaza la petición y no se realiza ninguna consulta a GitHub

### Requirement: Actualizador desactivable

El actualizador SHALL estar aislado en un módulo propio y SHALL activarse únicamente cuando el plugin declare la URL del repositorio, de modo que la variante destinada a WordPress.org lo elimine sin tocar el resto del código.

#### Scenario: Repositorio no declarado

- **WHEN** la constante que declara la URL del repositorio está vacía
- **THEN** el plugin no registra ningún filtro de actualizaciones, no realiza peticiones a GitHub y la pantalla Acerca de no muestra el botón de buscar actualizaciones
