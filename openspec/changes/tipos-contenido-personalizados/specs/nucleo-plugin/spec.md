## MODIFIED Requirements

### Requirement: Ajustes globales con valores por defecto

El plugin SHALL almacenar sus ajustes en una única opción de WordPress con estos valores por defecto: sustituir el escritorio nativo (sí), ocultar widgets de otros plugins (sí), desactivar la paleta de comandos nativa (sí), activar el lanzador propio (sí), lanzador disponible en el front-end con barra de administración (sí), número de elementos por panel (5), indicadores y paneles del escritorio activos (todos los propios y ninguno de los tipos de contenido personalizados), posición del menú del plugin (debajo de «Escritorio»), color de fondo de su entrada en el menú (ninguno), avisos por correo al activar, desactivar, instalar y borrar un plugin (no, los cuatro), aviso por correo de cambios en los administradores (no), destinatarios de los avisos (ninguno, que significa el correo de administración del sitio) y asunto y mensaje de los avisos (vacíos, que significa el texto predeterminado). Cada ajuste SHALL sanearse según su tipo: casilla, número acotado, lista de casillas, identificador de menú, color hexadecimal, lista de correos o texto.

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

- **WHEN** se guardan los paneles activos con un valor que no es una lista, o con una lista a la que le falta algún panel propio
- **THEN** con el valor que no es una lista todos los paneles propios quedan activos, y con la lista incompleta los paneles propios que faltan quedan activos y el resto conserva el valor enviado

#### Scenario: Tipos de contenido personalizados en una lista de casillas

- **WHEN** se guardan los paneles activos con el tipo «libro» marcado, el tipo «evento» desmarcado, sin el tipo «curso» y con un identificador de tipo que WordPress no admitiría (con mayúsculas, espacios o más de 20 caracteres)
- **THEN** «libro» queda activo, «evento» y «curso» quedan inactivos y la clave no válida se descarta, sin que el plugin compruebe si esos tipos están registrados en ese momento
