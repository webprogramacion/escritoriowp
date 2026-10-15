## Purpose

Guardar en la base de datos del sitio cada aviso por correo que envía el plugin, con el resultado del
envío, para que un aviso no se pierda aunque el correo falle y se pueda consultar desde el
administrador.

## ADDED Requirements

### Requirement: Registro de cada aviso

Cada correo de aviso que el plugin intenta enviar, de plugins o de administradores, SHALL guardarse en la base de datos del sitio como un registro, sea cual sea la vía que lo provoca: la pantalla, la API REST, WP-CLI o WP-Cron. Lo que no llega a intentar enviarse NO SHALL guardarse.

#### Scenario: Desactivar varios plugins en lote

- **WHEN** con el aviso de desactivación marcado, un administrador desactiva cinco plugins con la acción en lote de Plugins
- **THEN** se guarda un solo registro de tipo «Desactivación», cuyo mensaje lista los cinco plugins

#### Scenario: Instalar y activar desde WP-CLI

- **WHEN** con los avisos de instalación y activación marcados, se ejecuta `wp plugin install <plugin> --activate`
- **THEN** se guardan dos registros, uno de tipo «Instalación» y otro de tipo «Activación»

#### Scenario: Revisión de administradores con cambios

- **WHEN** con el aviso de cambios en los administradores marcado, una revisión encuentra un administrador nuevo
- **THEN** se guarda un registro de tipo «Administradores» con el correo de esa revisión

#### Scenario: Nada que avisar

- **WHEN** una revisión de administradores no encuentra cambios, o se activa un plugin con el aviso de activación desmarcado
- **THEN** no se guarda ningún registro

#### Scenario: Reintento del aviso de administradores

- **WHEN** una revisión encuentra un administrador nuevo y el envío falla, y la siguiente revisión, con el correo ya funcionando, encuentra además un usuario marcado nuevo
- **THEN** quedan dos registros: el primero, «Fallido», con el administrador nuevo; el segundo, «Enviado», con los dos usuarios

### Requirement: Contenido del registro

Cada registro SHALL guardar la fecha y hora del envío, el tipo de aviso (Activación, Desactivación, Instalación, Borrado o Administradores), el asunto y el mensaje exactamente como se pasaron a la función de correo y las direcciones a las que se envió.

#### Scenario: Aviso de activación

- **WHEN** el ajuste de destinatarios es `ana@ejemplo.es, luis@ejemplo.es` y un administrador activa «Akismet» 5.3 con el aviso de activación marcado
- **THEN** el registro es de tipo «Activación», su asunto y su mensaje son idénticos a los del correo y sus destinatarios son las dos direcciones

#### Scenario: Destinatarios vacíos

- **WHEN** el ajuste de destinatarios está vacío y se envía un aviso
- **THEN** el registro guarda como destinatario el correo de administración vigente en el momento del envío

#### Scenario: Textos cambiados después del envío

- **WHEN** se envía un aviso y después un administrador cambia el asunto y el mensaje configurables
- **THEN** el registro de ese aviso conserva el asunto y el mensaje con los que se envió

### Requirement: Estado del envío

Cada registro SHALL tener un estado: «Enviado» si la función de correo de WordPress aceptó el mensaje; «Fallido» si lo rechazó o produjo un error, con el motivo que dé WordPress; o «Sin confirmar» si la petición terminó antes de conocer el resultado. «Enviado» SHALL significar que el mensaje se aceptó, no que se entregó.

#### Scenario: Envío aceptado

- **WHEN** se envía un aviso y la función de correo de WordPress lo acepta
- **THEN** el registro queda como «Enviado»

#### Scenario: El servidor no puede enviar correo

- **WHEN** se envía un aviso y la función de correo de WordPress lo rechaza con el motivo «Could not instantiate mail function.»
- **THEN** el registro queda como «Fallido» con ese motivo

#### Scenario: Fallo sin motivo

- **WHEN** otro plugin sustituye la función de correo de WordPress y devuelve un fallo sin dar ningún motivo
- **THEN** el registro queda como «Fallido» con un motivo genérico que dice que la función de correo no aceptó el mensaje

#### Scenario: Petición interrumpida

- **WHEN** se envía un aviso y la petición termina durante el envío, por ejemplo porque el servidor de correo no responde y se agota el tiempo de ejecución
- **THEN** el registro existe, con su asunto, su mensaje y sus destinatarios, y queda como «Sin confirmar»

### Requirement: El registro no interfiere con el envío

Un fallo al guardar o al actualizar un registro NO SHALL impedir el envío del correo, NI cambiar cuándo se envían los avisos o cuándo avanza la revisión de administradores, NI mostrar errores en la pantalla.

#### Scenario: La base de datos rechaza el registro

- **WHEN** la tabla del registro no existe y no se puede crear, y un administrador activa un plugin con el aviso de activación marcado
- **THEN** el correo de «Activación» se envía, el plugin queda activado y la pantalla Plugins muestra su mensaje habitual, sin errores

### Requirement: Conservación de los registros

Los registros SHALL conservarse sin límite de tiempo ni de número. Desactivar y reactivar el plugin, actualizarlo o desmarcar las casillas de los avisos NO SHALL borrar ningún registro.

#### Scenario: Desactivar y reactivar

- **WHEN** hay avisos guardados y se desactiva EscritorioWP y se vuelve a activar
- **THEN** la pantalla Avisos sigue mostrando los mismos avisos

#### Scenario: Avisos desmarcados

- **WHEN** hay avisos guardados y un administrador desmarca todas las casillas de avisos y guarda
- **THEN** la pantalla Avisos sigue mostrando los avisos guardados

### Requirement: Registro disponible tras actualizar

Al actualizar el plugin desde una versión sin registro, por cualquier vía y sin que se ejecute la activación, el primer aviso posterior SHALL guardarse igualmente. En multisitio, cada sitio SHALL tener su propio registro.

#### Scenario: Actualizar subiendo un zip

- **WHEN** se actualiza EscritorioWP desde una versión sin registro subiendo su zip y después se activa un plugin con el aviso de activación marcado
- **THEN** el aviso queda guardado y aparece en la pantalla Avisos

#### Scenario: Multisitio

- **WHEN** en un multisitio con EscritorioWP activado en toda la red se envía un aviso desde el sitio 2
- **THEN** el aviso aparece en la pantalla Avisos del sitio 2 y no en la del sitio principal

### Requirement: Acceso a la pantalla Avisos

La pantalla EscritorioWP › Avisos (`admin.php?page=escritoriowp-avisos`) SHALL ser accesible solo para usuarios con `manage_options`, y SHALL aparecer en el lanzador como cualquier otra pantalla del administrador.

#### Scenario: Administrador

- **WHEN** un administrador escribe «avisos» en el lanzador
- **THEN** aparece la pantalla Avisos y al elegirla ve el listado de avisos

#### Scenario: Usuario sin permisos

- **WHEN** un editor accede directamente a `admin.php?page=escritoriowp-avisos`
- **THEN** WordPress deniega el acceso y no se muestra ningún aviso

### Requirement: Listado de avisos

La pantalla Avisos SHALL listar todos los registros del más reciente al más antiguo, 20 por página con paginación y el total. Cada fila SHALL mostrar la fecha y hora en el formato y la zona horaria del sitio, el tipo, el asunto como enlace a su detalle, los destinatarios y el estado, con texto y color.

#### Scenario: Varias páginas

- **WHEN** hay 45 avisos guardados y un administrador abre la pantalla Avisos
- **THEN** ve los 20 más recientes, el total de 45 y una paginación de 3 páginas

#### Scenario: Aviso fallido

- **WHEN** el listado incluye un aviso fallido
- **THEN** su fila muestra el texto «Fallido» en una etiqueta roja, distinta de la de «Enviado» y de la de «Sin confirmar»

#### Scenario: Sin avisos

- **WHEN** no hay ningún aviso guardado y un administrador abre la pantalla Avisos
- **THEN** ve un texto que dice que todavía no se ha guardado ningún aviso y un enlace a los ajustes de los avisos

### Requirement: Filtros del listado

El listado SHALL poder filtrarse por tipo y por estado, por separado o a la vez, y la paginación SHALL conservar los filtros. Un valor de filtro desconocido SHALL ignorarse sin errores.

#### Scenario: Solo los fallidos

- **WHEN** un administrador filtra el estado «Fallido»
- **THEN** el listado muestra solo los avisos fallidos y el total cuenta solo esos

#### Scenario: Filtro y paginación

- **WHEN** con el filtro de tipo «Administradores» hay 30 avisos y el administrador pasa a la página 2
- **THEN** la página 2 muestra los 10 avisos de tipo «Administradores» restantes y el filtro sigue aplicado

#### Scenario: Sin coincidencias

- **WHEN** ningún aviso coincide con los filtros elegidos
- **THEN** el listado dice que ningún aviso coincide con los filtros y ofrece quitarlos

#### Scenario: Valor manipulado

- **WHEN** se abre la pantalla con `estado=<script>` en la URL
- **THEN** el listado muestra todos los avisos, sin errores y sin repetir ese valor en la página

### Requirement: Detalle de un aviso

El detalle de un aviso SHALL mostrar su fecha y hora, su tipo, su estado, sus destinatarios, su asunto, su mensaje completo con sus saltos de línea y, si falló, el motivo, con un enlace de vuelta al listado. Un aviso que no existe SHALL mostrar un texto que lo diga, sin errores.

#### Scenario: Detalle de un aviso fallido

- **WHEN** un administrador abre el detalle de un aviso fallido de tipo «Administradores»
- **THEN** ve el mensaje completo con sus apartados en líneas separadas, los destinatarios y el motivo del fallo

#### Scenario: Aviso inexistente

- **WHEN** un administrador abre el detalle de un aviso con un identificador que no existe
- **THEN** ve un texto que dice que ese aviso no existe y el enlace al listado, sin avisos de PHP

### Requirement: Contenido del aviso como texto

El asunto, el mensaje, los destinatarios y el motivo de un aviso SHALL mostrarse siempre como texto, en la pantalla Avisos y en el escritorio, sin interpretarse nunca como HTML.

#### Scenario: Nombre de plugin con HTML

- **WHEN** se activa, con el aviso marcado, un plugin cuyo nombre es `<img src=x onerror=alert(1)>` y un administrador abre el escritorio, el listado y el detalle
- **THEN** los tres muestran ese nombre literalmente y no se ejecuta ningún código
