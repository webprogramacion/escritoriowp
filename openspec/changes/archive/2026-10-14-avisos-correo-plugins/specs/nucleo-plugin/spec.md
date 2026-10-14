## MODIFIED Requirements

### Requirement: Ajustes globales con valores por defecto

El plugin SHALL almacenar sus ajustes en una única opción de WordPress con estos valores por defecto: sustituir el escritorio nativo (sí), ocultar widgets de otros plugins (sí), desactivar la paleta de comandos nativa (sí), activar el lanzador propio (sí), lanzador disponible en el front-end con barra de administración (sí), número de elementos por panel (5), posición del menú del plugin (debajo de «Escritorio»), color de fondo de su entrada en el menú (ninguno), avisos por correo al activar, desactivar, instalar y borrar un plugin (no, los cuatro), destinatarios de los avisos (ninguno, que significa el correo de administración del sitio) y asunto y mensaje de los avisos (vacíos, que significa el texto predeterminado). Cada ajuste SHALL sanearse según su tipo: casilla, número acotado, identificador de menú, color hexadecimal, lista de correos o texto.

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
