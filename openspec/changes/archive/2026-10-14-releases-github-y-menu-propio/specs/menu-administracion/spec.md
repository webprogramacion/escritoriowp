## Purpose

Dar al plugin un menú propio en la barra lateral del administrador que agrupe su configuración y una pantalla Acerca de con la información de la versión y el historial de cambios.

## ADDED Requirements

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
