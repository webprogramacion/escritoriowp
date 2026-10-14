# menu-administracion Specification

## Purpose
Dar al plugin un menú propio en la barra lateral del administrador que agrupe su configuración y una pantalla Acerca de con la información de la versión y el historial de cambios.

## Requirements

### Requirement: Menú de primer nivel

El plugin SHALL registrar un menú de primer nivel «EscritorioWP» en la barra lateral del administrador, visible solo para usuarios con `manage_options`, con dos entradas en este orden: «Ajustes» y «Acerca de». Las pantallas del menú SHALL aparecer en el lanzador como cualquier otra pantalla del administrador.

#### Scenario: Administrador

- **WHEN** un administrador carga cualquier pantalla del administrador
- **THEN** ve el menú «EscritorioWP» con las entradas «Ajustes» y «Acerca de», y al escribir «acerca» en el lanzador aparece la pantalla Acerca de

#### Scenario: Usuario sin permisos

- **WHEN** un editor carga el administrador o accede directamente a `admin.php?page=escritoriowp-acerca`
- **THEN** no ve el menú y WordPress deniega el acceso a la URL

### Requirement: Pantalla Acerca de

La pantalla Acerca de SHALL mostrar el nombre y la versión instalada del plugin, una descripción breve, enlaces al repositorio de GitHub, a sus releases y al sitio del autor, el estado de la actualización (versión más reciente conocida, o indicación de que está al día) y la sección «Novedades» con el historial de cambios.

#### Scenario: Contenido básico

- **WHEN** un administrador abre EscritorioWP › Acerca de
- **THEN** ve la versión instalada, los enlaces externos abriéndose en pestaña nueva y la lista de novedades

### Requirement: Historial de cambios desde el propio plugin

La sección «Novedades» SHALL construirse a partir de la sección Changelog del `readme.txt` que el plugin distribuye, mostrando cada versión con su lista de cambios, de la más reciente a la más antigua, sin duplicar el contenido en otro fichero.

#### Scenario: Varias versiones

- **WHEN** el `readme.txt` contiene entradas `= 0.2.0 =` y `= 0.1.0 =` con sus puntos
- **THEN** la pantalla muestra primero `0.2.0` con sus puntos y después `0.1.0` con los suyos, marcando cuál es la instalada

#### Scenario: Readme ilegible

- **WHEN** el `readme.txt` no existe o no contiene sección Changelog
- **THEN** la pantalla muestra un texto indicando que no hay historial disponible, sin errores ni avisos de PHP

### Requirement: Ajustes bajo el menú propio

La pantalla de ajustes SHALL ser la primera entrada del menú «EscritorioWP», accesible en `admin.php?page=escritoriowp`, y SHALL conservar todos los ajustes existentes, incluida la casilla que activa o desactiva el lanzador. Toda referencia interna a la ruta antigua (comandos del lanzador, textos de ayuda, documentación) SHALL apuntar a la nueva.

#### Scenario: Comando del lanzador

- **WHEN** un administrador ejecuta el comando «Ajustes de EscritorioWP» en el lanzador
- **THEN** llega a `admin.php?page=escritoriowp` y ve el formulario de ajustes

#### Scenario: Desactivar el lanzador

- **WHEN** un administrador desmarca «Activar el lanzador de EscritorioWP» en EscritorioWP › Ajustes y guarda
- **THEN** en la siguiente carga Comando+K no abre el lanzador y el botón de la barra de administración desaparece

### Requirement: Posición del menú

El menú «EscritorioWP» SHALL aparecer inmediatamente debajo del menú de primer nivel elegido en el ajuste «Posición en el menú», o en último lugar si se elige «Al final». Por defecto SHALL aparecer justo debajo de «Escritorio», antes del primer separador. Los demás menús SHALL conservar su orden relativo.

#### Scenario: Posición por defecto

- **WHEN** un administrador carga cualquier pantalla del administrador sin haber guardado nunca la posición del menú
- **THEN** la barra lateral muestra «Escritorio», justo después «EscritorioWP», y después el separador y «Entradas»

#### Scenario: Actualización desde una versión anterior

- **WHEN** se actualiza el plugin en un sitio cuya opción de ajustes guardada no tiene todavía la posición del menú
- **THEN** el menú pasa a aparecer debajo de «Escritorio» y el resto de ajustes guardados no cambia

#### Scenario: Debajo de otro menú

- **WHEN** un administrador elige «Debajo de Páginas» y guarda
- **THEN** en la siguiente carga «EscritorioWP» aparece inmediatamente después de «Páginas» y el resto de menús mantiene su orden

#### Scenario: Al final

- **WHEN** un administrador elige «Al final» y guarda
- **THEN** «EscritorioWP» es la última entrada de la barra lateral

#### Scenario: El menú de referencia desaparece

- **WHEN** la posición guardada es «Debajo de WooCommerce» y se desactiva WooCommerce
- **THEN** «EscritorioWP» aparece debajo de «Escritorio», sin errores ni avisos de PHP, y al reactivar WooCommerce vuelve a aparecer debajo de «WooCommerce» sin tocar los ajustes

### Requirement: Elección de la posición en los ajustes

La sección «Menú» de la pantalla de ajustes SHALL ofrecer «Posición en el menú» como un desplegable «Debajo de…» con los menús de primer nivel que ve el administrador, en su orden actual, sin separadores ni el propio «EscritorioWP», más la opción «Al final». Los nombres SHALL mostrarse sin contadores. El servidor SHALL sustituir por el valor por defecto cualquier valor que no sea un identificador de menú válido.

#### Scenario: Opciones del desplegable

- **WHEN** un administrador abre EscritorioWP › Ajustes en un sitio con WooCommerce y comentarios pendientes
- **THEN** el desplegable lista «Escritorio», «Entradas», «Medios», «Páginas», «Comentarios», «WooCommerce» y el resto de menús en el orden de la barra lateral, termina con «Al final», no incluye «EscritorioWP» ni separadores, muestra «Comentarios» sin el número de pendientes y tiene «Escritorio» seleccionado

#### Scenario: Referencia guardada que ya no existe

- **WHEN** la posición guardada apunta a un menú que hoy no existe y un administrador abre los ajustes
- **THEN** el desplegable conserva ese valor seleccionado, marcado como no disponible, de modo que guardar otros ajustes no lo sustituye

#### Scenario: Valor manipulado

- **WHEN** se envía el formulario con una posición que contiene caracteres no válidos en un identificador de menú, como `<script>`
- **THEN** se guarda la posición por defecto (debajo de «Escritorio») sin mostrar ningún error

### Requirement: Color de fondo de la entrada del menú

Cuando el ajuste «Color de fondo en el menú» tiene un color, la entrada «EscritorioWP» de la barra lateral SHALL mostrar ese fondo en reposo, mientras está abierta y con el menú plegado, sin alterar ninguna otra entrada ni su submenú. Sin color, que es el valor por defecto, la entrada SHALL verse como las demás según el esquema de colores de cada usuario.

#### Scenario: Sin color

- **WHEN** el ajuste de color está vacío
- **THEN** la entrada «EscritorioWP» tiene el mismo aspecto que el resto de entradas y el plugin no añade estilos al menú

#### Scenario: Color elegido

- **WHEN** un administrador elige `#7a1f5c` y guarda
- **THEN** en la siguiente carga la entrada «EscritorioWP» tiene fondo `#7a1f5c` y las demás entradas no cambian

#### Scenario: Entrada abierta

- **WHEN** un administrador está en EscritorioWP › Ajustes con un color elegido
- **THEN** la entrada de primer nivel mantiene el color elegido y el submenú «Ajustes» / «Acerca de» conserva el aspecto de WordPress

#### Scenario: Menú plegado

- **WHEN** el administrador pliega la barra lateral
- **THEN** el cuadro del icono de «EscritorioWP» mantiene el color elegido

#### Scenario: Color inválido

- **WHEN** se envía el formulario con un color que no es hexadecimal, como `rojo` o `#12345`
- **THEN** el ajuste se guarda vacío y la entrada vuelve al aspecto por defecto, sin mostrar ningún error

#### Scenario: Formato corto

- **WHEN** se envía `#ABC`
- **THEN** se guarda como `#aabbcc`

### Requirement: Legibilidad de la entrada con color propio

Con un color de fondo propio, el texto y el icono de la entrada «EscritorioWP» SHALL ser blancos si su contraste con el fondo es de al menos 4,5:1 y negros en caso contrario, de modo que nunca baje de 4,5:1. Al pasar el ratón o al recibir el foco del teclado, la entrada SHALL mantener la respuesta visual de WordPress, incluido un indicador de foco visible.

#### Scenario: Fondo oscuro

- **WHEN** el color elegido es `#7a1f5c`
- **THEN** el texto y el icono de la entrada son blancos

#### Scenario: Fondo claro

- **WHEN** el color elegido es `#ffd60a`
- **THEN** el texto y el icono de la entrada son negros

#### Scenario: Ratón y teclado

- **WHEN** el administrador pasa el ratón por la entrada o llega a ella con el tabulador
- **THEN** la entrada cambia como cualquier otra entrada de WordPress y el foco se ve claramente

### Requirement: Selector de color en los ajustes

La sección «Menú» de la pantalla de ajustes SHALL ofrecer «Color de fondo en el menú» con el selector de color de WordPress, que permite elegir el color, escribir su valor hexadecimal y vaciarlo para volver al aspecto por defecto.

#### Scenario: Elegir y vaciar

- **WHEN** un administrador elige un color, guarda, vacía el campo con el botón del selector y vuelve a guardar
- **THEN** tras el primer guardado la entrada tiene el color elegido y tras el segundo recupera el aspecto por defecto

#### Scenario: Selector solo en su pantalla

- **WHEN** un administrador carga cualquier pantalla distinta de EscritorioWP › Ajustes
- **THEN** el selector de color de WordPress no se encola por cuenta del plugin
