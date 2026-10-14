# avisos-plugins Specification

## Purpose
Avisar por correo a los responsables del sitio cada vez que se activa, desactiva, instala o borra un
plugin, con destinatarios y texto configurables, para que un cambio así no pase desapercibido.

## Requirements

### Requirement: Un interruptor por evento

La pantalla de ajustes SHALL ofrecer una casilla por evento: avisar al activar, al desactivar, al instalar y al borrar un plugin. Las cuatro SHALL estar desmarcadas por defecto, y el plugin SHALL enviar el aviso de un evento solo si su casilla está marcada.

#### Scenario: Instalación nueva o actualización del plugin

- **WHEN** EscritorioWP se instala por primera vez o se actualiza desde una versión sin estos ajustes, y después se activa otro plugin
- **THEN** no se envía ningún correo

#### Scenario: Solo un evento marcado

- **WHEN** solo está marcada «Avisar al activar un plugin» y un administrador activa un plugin y después lo desactiva
- **THEN** se envía un correo por la activación y ninguno por la desactivación

### Requirement: Aviso al activar o desactivar

El plugin SHALL avisar cuando un plugin se activa o se desactiva mediante las funciones de WordPress, sea cual sea la vía: la pantalla Plugins, sus acciones en lote, la API REST o WP-CLI. Las desactivaciones y reactivaciones silenciosas que WordPress hace al actualizar un plugin NO SHALL avisar.

#### Scenario: Activar desde la pantalla Plugins

- **WHEN** con el aviso de activación marcado, un administrador activa «Akismet» 5.3 desde Plugins
- **THEN** se envía un correo cuyo evento es «Activación» y que nombra «Akismet» con su versión 5.3

#### Scenario: Actualizar un plugin activo

- **WHEN** con los avisos de activación y desactivación marcados, se actualiza un plugin activo desde Plugins, desde Actualizaciones o de forma automática
- **THEN** no se envía ningún correo de desactivación ni de activación

#### Scenario: Desactivar el propio EscritorioWP

- **WHEN** con el aviso de desactivación marcado, alguien desactiva EscritorioWP
- **THEN** se envía el correo de su desactivación

#### Scenario: Activación en toda la red

- **WHEN** en un multisitio, con el aviso de activación marcado en el sitio principal, un superadministrador activa un plugin para toda la red
- **THEN** el correo indica, junto al plugin, que la activación es en toda la red

### Requirement: Aviso al instalar

El plugin SHALL avisar cuando se instala un plugin con el instalador de WordPress: desde el directorio de plugins, subiendo un zip (también si sustituye a uno ya instalado), por la API REST o con WP-CLI. El aviso SHALL nombrar el plugin instalado y su versión. Una instalación fallida NO SHALL avisar.

#### Scenario: Instalar desde el directorio

- **WHEN** con el aviso de instalación marcado, un administrador instala un plugin desde Plugins › Añadir plugin
- **THEN** se envía un correo cuyo evento es «Instalación» con el nombre y la versión del plugin instalado

#### Scenario: Subir un zip que sustituye a un plugin instalado

- **WHEN** con el aviso de instalación marcado, un administrador sube un zip de un plugin ya instalado y elige sustituir la versión actual
- **THEN** se envía un correo de «Instalación» con la versión del zip subido

#### Scenario: Instalación fallida

- **WHEN** con el aviso de instalación marcado, se sube un zip que no contiene ningún plugin válido
- **THEN** no se envía ningún correo

### Requirement: Aviso al borrar

El plugin SHALL avisar cuando se borra un plugin, con el nombre y la versión que tenía antes de borrarse. Si WordPress no consigue borrar sus ficheros, NO SHALL avisar.

#### Scenario: Borrar desde la pantalla Plugins

- **WHEN** con el aviso de borrado marcado, un administrador borra un plugin desactivado
- **THEN** se envía un correo cuyo evento es «Borrado» con el nombre y la versión que tenía el plugin

#### Scenario: Borrado fallido

- **WHEN** con el aviso de borrado marcado, WordPress intenta borrar un plugin pero no puede eliminar sus ficheros
- **THEN** no se envía ningún correo

### Requirement: Un correo por evento y petición

Los plugins afectados por un mismo evento en una misma petición SHALL ir en un único correo, y cada evento distinto SHALL ir en su propio correo. Los correos SHALL enviarse al terminar la petición.

#### Scenario: Desactivar varios plugins en lote

- **WHEN** con el aviso de desactivación marcado, un administrador desactiva cinco plugins con la acción en lote de Plugins
- **THEN** se envía un solo correo de «Desactivación» que lista los cinco plugins

#### Scenario: Instalar y activar en la misma orden

- **WHEN** con los avisos de instalación y activación marcados, se ejecuta `wp plugin install <plugin> --activate`
- **THEN** se envían dos correos: uno de «Instalación» y otro de «Activación», cada uno con ese plugin

### Requirement: El aviso no interfiere con la acción

Un fallo al enviar el correo NO SHALL impedir ni deshacer la activación, desactivación, instalación o borrado, ni mostrar errores en la pantalla.

#### Scenario: El servidor no puede enviar correo

- **WHEN** con todos los avisos marcados, el envío de correo del sitio falla y un administrador activa un plugin
- **THEN** el plugin queda activado y la pantalla Plugins muestra su mensaje habitual, sin errores

### Requirement: Destinatarios

El aviso SHALL enviarse a las direcciones del ajuste de destinatarios, una o varias separadas por comas. Con el ajuste vacío SHALL enviarse al correo de administración del sitio vigente en el momento del envío, que la pantalla de ajustes muestra como ejemplo en el campo vacío.

#### Scenario: Varias direcciones

- **WHEN** el ajuste de destinatarios es `ana@ejemplo.es, luis@ejemplo.es` y se activa un plugin con el aviso marcado
- **THEN** el correo llega a las dos direcciones

#### Scenario: Destinatarios vacíos

- **WHEN** el ajuste de destinatarios está vacío, se cambia el correo de administración en Ajustes › Generales y después se activa un plugin con el aviso marcado
- **THEN** el correo llega a la nueva dirección de administración

### Requirement: Saneado de los destinatarios

Al guardar los ajustes, el plugin SHALL descartar las direcciones no válidas y las repetidas, y conservar como máximo diez. Si descarta alguna por no ser válida, SHALL mostrar un aviso en la pantalla de ajustes que la nombre.

#### Scenario: Una dirección no válida y una repetida

- **WHEN** se guarda `ana@ejemplo.es, no-es-un-correo, ana@ejemplo.es`
- **THEN** se guarda `ana@ejemplo.es` y el aviso nombra `no-es-un-correo`

#### Scenario: Ninguna dirección válida

- **WHEN** se guarda `no-es-un-correo`
- **THEN** el ajuste queda vacío, los avisos van al correo de administración y el aviso nombra `no-es-un-correo`

### Requirement: Asunto y mensaje configurables

El asunto y el mensaje SHALL ser comunes a los cuatro eventos y editables en la pantalla de ajustes, que los muestra rellenados con su texto predeterminado mientras no se hayan cambiado. Un campo que se guarda vacío SHALL volver a su texto predeterminado.

#### Scenario: Textos predeterminados

- **WHEN** un administrador abre los ajustes sin haber tocado nunca el asunto ni el mensaje
- **THEN** el asunto muestra `[{sitio}] {evento}: {plugins}` y el mensaje, el texto predeterminado con el evento, la lista de plugins, el usuario, la IP y la fecha

#### Scenario: Vaciar el mensaje

- **WHEN** un administrador vacía el campo del mensaje y guarda
- **THEN** los avisos usan el mensaje predeterminado y el campo vuelve a mostrarlo

### Requirement: Marcadores

El asunto y el mensaje SHALL sustituir `{evento}`, `{plugins}`, `{lista}`, `{cantidad}`, `{usuario}`, `{fecha}`, `{sitio}`, `{url}`, `{url_plugins}` e `{ip}` por sus valores. Un marcador desconocido SHALL quedar tal cual y el texto de un valor NO SHALL volver a interpretarse como marcador. La pantalla de ajustes SHALL listar los marcadores con su significado.

#### Scenario: Valores de un aviso

- **WHEN** un usuario con nombre visible «Ana» y nombre de usuario `ana` activa «Akismet» 5.3 (`akismet/akismet.php`)
- **THEN** `{evento}` es «Activación», `{plugins}` es «Akismet 5.3», `{lista}` es la línea «- Akismet 5.3 (akismet/akismet.php)», `{cantidad}` es 1, `{usuario}` es «Ana (ana)», `{fecha}` es la fecha y hora en el formato y la zona horaria del sitio, y `{url_plugins}` es la URL de la pantalla Plugins

#### Scenario: Muchos plugins en el asunto

- **WHEN** un mismo correo incluye cinco plugins
- **THEN** `{plugins}` nombra los tres primeros con su versión y termina en «y 2 más», mientras que `{lista}` tiene las cinco líneas

#### Scenario: Cambio desde WP-CLI

- **WHEN** el cambio se hace con WP-CLI
- **THEN** `{usuario}` es «WP-CLI» y `{ip}` es «—»

#### Scenario: Marcador desconocido

- **WHEN** el mensaje contiene `{plugn}`
- **THEN** el correo muestra `{plugn}` literalmente

#### Scenario: Un nombre de plugin con un marcador

- **WHEN** se activa un plugin cuyo nombre es «Prueba {sitio}»
- **THEN** el correo muestra «Prueba {sitio}» literalmente, sin sustituirlo por el nombre del sitio

### Requirement: Formato del correo

El correo SHALL enviarse en texto plano UTF-8 con la función de correo de WordPress, aunque otro plugin cambie el formato por defecto a HTML, y su asunto SHALL ocupar una sola línea aunque los valores de los marcadores tengan saltos de línea.

#### Scenario: Otro plugin pone el correo en HTML

- **WHEN** otro plugin filtra el tipo de contenido de todos los correos a `text/html` y se envía un aviso
- **THEN** el aviso llega como texto plano y conserva sus saltos de línea

#### Scenario: La lista en el asunto

- **WHEN** el asunto contiene `{lista}` y el correo incluye dos plugins
- **THEN** el asunto llega en una sola línea con los dos plugins

### Requirement: Cambios que no avisan

El plugin NO SHALL avisar de actualizaciones de plugins, de cambios en temas, en plugins imprescindibles (`mu-plugins`) o en complementos (`drop-ins`), ni de cambios hechos sin las funciones de WordPress, como copiar o borrar ficheros por FTP o editar la lista de plugins activos en la base de datos.

#### Scenario: Copiar un plugin por FTP

- **WHEN** con todos los avisos marcados, se copia la carpeta de un plugin en `wp-content/plugins/` por FTP
- **THEN** no se envía ningún correo
