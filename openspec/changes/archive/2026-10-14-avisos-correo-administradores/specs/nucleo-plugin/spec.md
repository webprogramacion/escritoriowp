## MODIFIED Requirements

### Requirement: Ajustes globales con valores por defecto

El plugin SHALL almacenar sus ajustes en una única opción de WordPress con estos valores por defecto: sustituir el escritorio nativo (sí), ocultar widgets de otros plugins (sí), desactivar la paleta de comandos nativa (sí), activar el lanzador propio (sí), lanzador disponible en el front-end con barra de administración (sí), número de elementos por panel (5), indicadores y paneles del escritorio activos (todos), posición del menú del plugin (debajo de «Escritorio»), color de fondo de su entrada en el menú (ninguno), avisos por correo al activar, desactivar, instalar y borrar un plugin (no, los cuatro), aviso por correo de cambios en los administradores (no), destinatarios de los avisos (ninguno, que significa el correo de administración del sitio) y asunto y mensaje de los avisos (vacíos, que significa el texto predeterminado). Cada ajuste SHALL sanearse según su tipo: casilla, número acotado, lista de casillas, identificador de menú, color hexadecimal, lista de correos o texto.

#### Scenario: Primera activación

- **WHEN** el plugin se activa por primera vez
- **THEN** la opción de ajustes existe con los valores por defecto, todas las funciones están activas salvo los avisos por correo, y no se envía ningún aviso

#### Scenario: Ajuste ausente tras actualización

- **WHEN** una versión nueva del plugin introduce un ajuste que no existe en la opción guardada
- **THEN** ese ajuste toma su valor por defecto sin perder los ajustes ya guardados

#### Scenario: Ajustes de texto que no afectan a los números

- **WHEN** se guardan la posición del menú y el color de fondo junto al número de elementos por panel
- **THEN** la posición y el color se guardan como texto y el número de elementos por panel se sigue acotando entre 3 y 20

#### Scenario: Textos de los avisos

- **WHEN** se guarda un asunto con un salto de línea y etiquetas HTML, y un mensaje de varias líneas
- **THEN** el asunto se guarda en una sola línea y sin etiquetas, y el mensaje conserva sus saltos de línea

#### Scenario: Lista de casillas

- **WHEN** se guardan los paneles activos con un valor que no es una lista, o con una lista a la que le falta algún panel
- **THEN** con el valor que no es una lista todos los paneles quedan activos, y con la lista incompleta los paneles que faltan quedan activos y el resto conserva el valor enviado

### Requirement: Ciclo de vida y desinstalación limpia

La desactivación del plugin SHALL conservar ajustes, preferencias y cualquier otro dato guardado, y SHALL quitar las tareas programadas del plugin; la desinstalación (borrado desde Plugins) SHALL eliminar la opción de ajustes, cualquier otra opción propia, los metadatos de usuario creados por el plugin, cualquier transitorio propio y sus tareas programadas.

#### Scenario: Desactivar y reactivar

- **WHEN** el plugin se desactiva y se vuelve a activar
- **THEN** los ajustes y las preferencias de usuario previas se conservan

#### Scenario: Desactivar con tareas programadas

- **WHEN** con el aviso de cambios en los administradores activado, el plugin se desactiva
- **THEN** WP-Cron no conserva ninguna tarea del plugin, y la última situación revisada sigue guardada

#### Scenario: Desinstalar

- **WHEN** el plugin se borra desde la pantalla de Plugins
- **THEN** no queda en la base de datos ninguna opción, metadato de usuario, transitorio ni tarea de WP-Cron con el prefijo del plugin
