# nucleo-plugin Specification

## Purpose
Definir el arranque, los requisitos, la estructura distribuible, los ajustes globales, la integración opcional con WooCommerce y el ciclo de vida (activación, desactivación, desinstalación) del plugin EscritorioWP.

## Requirements

### Requirement: Estructura distribuible del plugin

El plugin SHALL vivir íntegramente dentro del directorio `escritoriowp/` del repositorio, de forma que copiar ese directorio a `wp-content/plugins/` (o subir un zip de su contenido) sea suficiente para instalarlo, sin pasos de compilación ni dependencias externas en tiempo de ejecución.

#### Scenario: Instalación por copia del directorio

- **WHEN** se copia el directorio `escritoriowp/` a `wp-content/plugins/` de una instalación WordPress compatible y se activa el plugin
- **THEN** el plugin arranca sin errores y sin requerir `vendor/`, `node_modules/` ni ficheros generados

#### Scenario: Material de desarrollo fuera del plugin

- **WHEN** se inspecciona el directorio `escritoriowp/`
- **THEN** no contiene ficheros de desarrollo (configuración de linters, specs, documentación interna del repositorio, configuración de agentes)

### Requirement: Cabecera y requisitos mínimos

El plugin SHALL declarar en su cabecera `Requires at least: 6.7`, `Requires PHP: 8.1`, `Text Domain: escritoriowp`, `Update URI: https://github.com/webprogramacion/escritoriowp` y una versión SemVer; y SHALL comprobar en el arranque que la instalación cumple esos requisitos. La cabecera `Update URI` SHALL impedir que WordPress.org ofrezca actualizaciones para este slug.

#### Scenario: Entorno compatible

- **WHEN** WordPress ≥ 6.7 y PHP ≥ 8.1
- **THEN** el plugin carga todos sus módulos

#### Scenario: Entorno incompatible

- **WHEN** la versión de PHP o de WordPress es inferior a la mínima
- **THEN** el plugin no carga ningún módulo funcional, muestra un aviso en el administrador indicando el requisito incumplido y no provoca ningún error fatal

#### Scenario: Slug coincidente en WordPress.org

- **WHEN** existe en WordPress.org un plugin distinto con el slug `escritoriowp`
- **THEN** WordPress no ofrece ese plugin como actualización de esta instalación

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

### Requirement: Página de ajustes

El plugin SHALL ofrecer la pantalla de ajustes como primera entrada de su menú propio «EscritorioWP» (`admin.php?page=escritoriowp`), visible solo para usuarios con capacidad `manage_options`, desde la que modificar todos los ajustes globales con protección contra CSRF y saneamiento de cada valor. La subpágina antigua en Ajustes › EscritorioWP deja de existir.

#### Scenario: Guardado correcto

- **WHEN** un administrador cambia "Sustituir el escritorio nativo" a "No" y guarda
- **THEN** la opción se actualiza, se muestra un aviso de éxito y el escritorio nativo vuelve a mostrarse en la siguiente carga

#### Scenario: Usuario sin permisos

- **WHEN** un usuario sin `manage_options` intenta acceder a la URL de la página de ajustes
- **THEN** WordPress deniega el acceso y no se muestra ningún ajuste

#### Scenario: Valor inválido

- **WHEN** se envía un número de elementos por panel fuera del rango permitido (menos de 3 o más de 20)
- **THEN** el valor se ajusta al límite más cercano y se guarda sin error

#### Scenario: URL antigua

- **WHEN** se accede a `options-general.php?page=escritoriowp`
- **THEN** WordPress no encuentra la página y no se muestra ningún formulario del plugin

### Requirement: Integración opcional con WooCommerce

El plugin SHALL funcionar completamente sin WooCommerce, y SHALL activar las funciones de productos y pedidos únicamente cuando WooCommerce esté activo, declarando compatibilidad con el almacenamiento de pedidos HPOS antes de que WooCommerce se inicialice.

#### Scenario: WooCommerce no instalado

- **WHEN** WooCommerce no está activo
- **THEN** no aparecen KPIs, paneles, comandos ni resultados de búsqueda relacionados con productos o pedidos, y no se produce ningún aviso ni error

#### Scenario: WooCommerce con HPOS activado

- **WHEN** WooCommerce está activo con el almacenamiento de pedidos HPOS habilitado
- **THEN** la pantalla de estado de WooCommerce no marca a EscritorioWP como incompatible, y los pedidos se listan y buscan correctamente

#### Scenario: WooCommerce con almacenamiento clásico

- **WHEN** WooCommerce está activo con pedidos en `wp_posts`
- **THEN** los pedidos se listan y buscan correctamente

### Requirement: Internacionalización

Todos los textos visibles del plugin SHALL ser traducibles con el dominio de texto `escritoriowp`, con el español como idioma de origen, y el plugin SHALL distribuir una plantilla de traducción (`.pot`) en `escritoriowp/languages/`.

#### Scenario: Texto en JavaScript

- **WHEN** la interfaz del escritorio o del lanzador muestra un texto
- **THEN** ese texto proviene de una cadena traducible (pasada desde PHP o registrada para traducción en JS) y no está incrustado literalmente sin posibilidad de traducción

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

### Requirement: Seguridad básica

Toda ruta REST, acción de guardado y salida HTML del plugin SHALL comprobar capacidades, validar nonces cuando proceda, sanear la entrada y escapar la salida, y ningún fichero PHP del plugin SHALL ejecutar lógica si se accede a él directamente.

#### Scenario: Acceso directo a un fichero

- **WHEN** se solicita por HTTP directamente un fichero PHP del plugin
- **THEN** el fichero termina sin producir salida ni error

#### Scenario: Petición REST sin autenticación

- **WHEN** se llama a cualquier ruta REST del plugin sin usuario autenticado
- **THEN** la respuesta es un error de autorización (401 o 403) sin datos
