# avisos-administradores Specification

## Purpose
Avisar por correo a los responsables del sitio cuando aparece o desaparece un administrador, o un
usuario con permisos de administrador sin el rol, para que una puerta trasera no espere a que alguien
abra la pantalla Administradores.

## Requirements

### Requirement: Interruptor del aviso

La pantalla de ajustes SHALL ofrecer, en la sección «Avisos por correo», una casilla «Avisar de cambios en los administradores», desmarcada por defecto. El plugin SHALL revisar y enviar este aviso solo mientras la casilla esté marcada.

#### Scenario: Instalación nueva o actualización del plugin

- **WHEN** EscritorioWP se instala por primera vez o se actualiza desde una versión sin este ajuste, y después se crea un administrador
- **THEN** no se programa ninguna revisión y no se envía ningún correo

#### Scenario: Desmarcar la casilla

- **WHEN** un administrador desmarca la casilla y guarda, y después se crea un administrador
- **THEN** no se envía ningún correo

### Requirement: Revisión cada 6 horas

Mientras la casilla esté marcada, el plugin SHALL tener programada en WP-Cron una revisión que se repite cada 6 horas. Desmarcar la casilla o desactivar el plugin SHALL quitar la revisión programada. Si la casilla está marcada y la revisión no está programada, por ejemplo tras reactivar el plugin, el plugin SHALL volver a programarla.

#### Scenario: Marcar la casilla

- **WHEN** un administrador marca la casilla y guarda
- **THEN** queda programada una revisión para dentro de 6 horas, que después se repite cada 6 horas

#### Scenario: Desactivar el plugin

- **WHEN** con la casilla marcada, se desactiva EscritorioWP
- **THEN** la lista de tareas de WP-Cron ya no contiene la revisión del plugin

#### Scenario: Reactivar el plugin

- **WHEN** con la casilla marcada, se desactiva EscritorioWP, se vuelve a activar y se carga cualquier página del sitio
- **THEN** la revisión vuelve a estar programada

### Requirement: Punto de partida

Al marcar la casilla, el plugin SHALL guardar la situación de ese momento sin enviar ningún correo, de modo que los administradores y usuarios marcados que ya existían no avisen. Desmarcarla SHALL borrar la situación guardada. Si una revisión no encuentra situación guardada, SHALL guardar la actual sin avisar.

#### Scenario: Usuarios que ya existían

- **WHEN** el sitio tiene dos administradores y un suscriptor con `manage_options`, se marca la casilla y pasa la primera revisión sin más cambios
- **THEN** no se envía ningún correo

#### Scenario: Volver a marcar la casilla

- **WHEN** se desmarca la casilla, se crea un administrador, se vuelve a marcar la casilla y pasa la primera revisión
- **THEN** no se envía ningún correo por ese administrador, porque ya estaba al marcarla

#### Scenario: Cambios mientras el plugin estaba desactivado

- **WHEN** con la casilla marcada, se desactiva EscritorioWP, se crea un administrador, se reactiva el plugin y pasa la primera revisión
- **THEN** se envía un correo que nombra a ese administrador como nuevo

### Requirement: Mismo criterio que la pantalla Administradores

Cada revisión SHALL clasificar a los usuarios del sitio con las mismas reglas que la pantalla Administradores: es administrador quien tiene el rol Administrador o, en multisitio, es superadministrador; está marcado quien no lo es y tiene algún permiso crítico. SHALL leer los datos guardados como lo hace esa pantalla, sin límite de usuarios.

#### Scenario: Usuario escondido por otro código

- **WHEN** un código filtra las consultas de usuarios de WordPress para esconder a un administrador recién creado y pasa una revisión
- **THEN** el correo nombra a ese administrador como nuevo

#### Scenario: Más de 100 usuarios marcados

- **WHEN** entre dos revisiones el rol «Suscriptor» pasa a incluir `manage_options` y lo tienen 300 usuarios
- **THEN** la revisión cuenta a los 300 como nuevos usuarios con permisos de administrador sin el rol

### Requirement: Cambios que avisan

La revisión SHALL comparar la clasificación actual con la guardada y avisar de cuatro cambios, con cada usuario en un solo apartado: administradores nuevos; usuarios marcados nuevos; usuarios que ya no son administradores ni están marcados; y usuarios que estaban marcados y ya no lo están. Después SHALL guardar la clasificación actual.

#### Scenario: Administrador nuevo

- **WHEN** entre dos revisiones se crea el usuario `ana` con el rol Administrador
- **THEN** el correo lo nombra en «Administradores nuevos»

#### Scenario: Usuario marcado que pasa a administrador

- **WHEN** entre dos revisiones un suscriptor con `manage_options` recibe el rol Administrador
- **THEN** el correo lo nombra solo en «Administradores nuevos», indicando que antes tenía permisos de administrador sin el rol

#### Scenario: Administrador que pierde el rol pero conserva permisos

- **WHEN** entre dos revisiones a un administrador se le cambia el rol a Suscriptor pero conserva `install_plugins` asignado a él
- **THEN** el correo lo nombra solo en el apartado de usuarios con permisos de administrador sin el rol, indicando que antes era administrador

#### Scenario: Administrador borrado

- **WHEN** entre dos revisiones se borra el usuario administrador `luis`
- **THEN** el correo lo nombra en «Ya no son administradores» e indica que el usuario se ha borrado

#### Scenario: Administrador degradado

- **WHEN** entre dos revisiones a un administrador se le cambia el rol a Editor, sin permisos críticos
- **THEN** el correo lo nombra en «Ya no son administradores» e indica que el usuario sigue existiendo

#### Scenario: Permisos retirados

- **WHEN** entre dos revisiones se retiran los permisos críticos de un usuario marcado desde la pantalla Administradores
- **THEN** el correo lo nombra en «Ya no tienen permisos de administrador»

### Requirement: Un correo por revisión

Cada revisión con algún cambio SHALL enviar un único correo con todos ellos, y una revisión sin cambios NO SHALL enviar ninguno.

#### Scenario: Varios cambios a la vez

- **WHEN** entre dos revisiones se crea un administrador y se borra otro
- **THEN** se envía un solo correo con los dos apartados

#### Scenario: Sin cambios

- **WHEN** pasa una revisión y nadie ha ganado ni perdido el rol de administrador ni permisos críticos desde la anterior
- **THEN** no se envía ningún correo

### Requirement: Contenido del correo

El correo SHALL indicar el sitio, la fecha de la revisión anterior con la que se compara y un enlace a la pantalla Administradores. Cada usuario SHALL aparecer con su nombre de usuario y, si sigue existiendo, su nombre visible y su correo; los marcados, con sus permisos críticos y su origen; y los que esconde otro código, con una señal que lo diga.

#### Scenario: Usuario marcado nuevo

- **WHEN** entre dos revisiones el suscriptor `tienda` recibe `manage_options` asignado a él, y un código lo esconde de la pantalla Usuarios
- **THEN** su línea incluye `tienda`, su nombre visible, su correo, `manage_options` como asignado al usuario y la señal de que está oculto en la pantalla Usuarios

#### Scenario: Revisión anterior

- **WHEN** la revisión anterior fue el 14 de octubre a las 12:00 y se envía un correo
- **THEN** el correo dice que los cambios son posteriores a esa fecha, en el formato y la zona horaria del sitio

### Requirement: Límite de usuarios por apartado

Cada apartado del correo SHALL listar como mucho 50 usuarios y, si hay más, SHALL decir cuántos quedan sin listar y remitir a la pantalla Administradores. El recuento de cada apartado SHALL incluir a todos.

#### Scenario: Rol alterado con muchos usuarios

- **WHEN** entre dos revisiones 300 usuarios pasan a estar marcados
- **THEN** el apartado indica 300 usuarios, lista 50 y dice que hay 250 más

### Requirement: Destinatarios y formato

El aviso SHALL enviarse a los mismos destinatarios que los avisos de plugins, con la misma regla para el ajuste vacío, en texto plano UTF-8 con la función de correo de WordPress, aunque otro plugin cambie el formato por defecto a HTML. Su asunto y su mensaje SHALL ser fijos y traducibles; el asunto y el mensaje configurables NO SHALL aplicarse a este aviso.

#### Scenario: Destinatarios vacíos

- **WHEN** el ajuste de destinatarios está vacío y una revisión encuentra un administrador nuevo
- **THEN** el correo llega al correo de administración del sitio

#### Scenario: Asunto de los avisos de plugins cambiado

- **WHEN** el asunto configurable es `Cambio en {plugins}` y una revisión encuentra un administrador nuevo
- **THEN** el asunto del correo es el fijo del aviso de administradores, con el nombre del sitio

### Requirement: Un envío fallido se vuelve a avisar

Si el correo de una revisión no se puede enviar, el plugin NO SHALL guardar la clasificación nueva, de modo que la siguiente revisión vuelva a avisar de esos cambios junto con los que haya después. El fallo NO SHALL producir errores visibles.

#### Scenario: El servidor no puede enviar correo

- **WHEN** una revisión encuentra un administrador nuevo y el envío falla, y la siguiente revisión encuentra además un usuario marcado nuevo con el correo ya funcionando
- **THEN** el segundo correo nombra a los dos usuarios y la fecha de comparación es la de la última revisión cuyo resultado se guardó

### Requirement: Estado visible en los ajustes

Con la casilla marcada, la pantalla de ajustes SHALL mostrar junto a ella la fecha de la última revisión, o la del punto de partida si aún no ha pasado ninguna, y la de la próxima, en el formato y la zona horaria del sitio.

#### Scenario: Después de marcar la casilla

- **WHEN** un administrador marca la casilla y guarda
- **THEN** la pantalla muestra como última revisión el momento de guardar y como próxima, unas 6 horas después

### Requirement: Cambios que no avisan

El aviso NO SHALL nombrar a quién hizo un cambio ni desde qué IP, NI SHALL avisar de un cambio que se deshace entre dos revisiones, de cambios en los permisos críticos de un usuario que sigue marcado o sigue siendo administrador, ni de permisos que un plugin concede solo mientras se ejecuta sin guardarlos.

#### Scenario: Administrador creado y borrado entre dos revisiones

- **WHEN** entre dos revisiones se crea un administrador y se borra antes de la siguiente
- **THEN** no se envía ningún correo

#### Scenario: Más permisos a un usuario ya marcado

- **WHEN** entre dos revisiones un usuario que ya tenía `manage_options` sin el rol recibe además `install_plugins`
- **THEN** no se envía ningún correo
