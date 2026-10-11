# Spec Delta

## Purpose

Definir cómo se prepara en local una versión nueva del plugin desde Claude Code con la skill `nueva-version`: número de versión, registro de novedades en los dos changelogs, comprobaciones previas, commit de release en `main` y push solo con confirmación del usuario.

## ADDED Requirements

### Requirement: Invocación de la skill

El repositorio SHALL incluir una skill de proyecto `nueva-version` que Claude Code ofrezca como `/nueva-version`. Acepta un argumento opcional: `patch`, `minor`, `major` o una versión explícita `X.Y.Z`. La skill no forma parte del plugin distribuido.

#### Scenario: Invocación con tipo de incremento

- **WHEN** el usuario ejecuta `/nueva-version minor` con la versión actual `0.2.1`
- **THEN** la skill prepara la versión `0.3.0`

#### Scenario: Invocación con versión explícita

- **WHEN** el usuario ejecuta `/nueva-version 1.0.0` con la versión actual `0.2.1`
- **THEN** la skill prepara la versión `1.0.0`

#### Scenario: La skill no viaja con el plugin

- **WHEN** se genera el zip del plugin después de añadir la skill
- **THEN** el zip no contiene la skill ni nada de `.claude/`

### Requirement: Cálculo del número de versión

La versión nueva SHALL calcularse a partir de la de la cabecera del plugin siguiendo versionado semántico: `patch` sube el tercer número, `minor` sube el segundo y pone el tercero a cero, `major` sube el primero y pone los otros dos a cero. Una versión que no sea mayor que la actual, que no tenga la forma `X.Y.Z` o cuyo tag `vX.Y.Z` ya exista SHALL rechazarse sin modificar ningún fichero.

#### Scenario: Incremento de parche

- **WHEN** la versión actual es `0.2.1` y se pide `patch`
- **THEN** la versión nueva es `0.2.2`

#### Scenario: Incremento mayor

- **WHEN** la versión actual es `0.2.1` y se pide `major`
- **THEN** la versión nueva es `1.0.0`

#### Scenario: Versión explícita que no avanza

- **WHEN** la versión actual es `0.2.1` y se pide `0.2.1` o `0.1.9`
- **THEN** la skill se detiene indicando que la versión nueva debe ser mayor que `0.2.1` y no toca ningún fichero

#### Scenario: Tag ya existente

- **WHEN** se pide una versión cuyo tag `vX.Y.Z` ya existe en local o en `origin`
- **THEN** la skill se detiene indicando que esa versión ya está publicada y no toca ningún fichero

### Requirement: Propuesta del tipo de incremento

Si no se indica argumento, la skill SHALL proponer el tipo de incremento según las novedades redactadas y pedir confirmación antes de seguir. Añadido o Cambiado proponen `minor`; solo Corregido, Seguridad o mantenimiento proponen `patch`. Un cambio incompatible propone `major` desde la 1.0.0 y `minor` mientras la versión sea 0.x. La skill nunca propone `major` por su cuenta en 0.x.

#### Scenario: Solo correcciones

- **WHEN** se invoca `/nueva-version` sin argumento y las novedades solo contienen entradas de Corregido
- **THEN** la skill propone `patch`, muestra la versión resultante y espera a que el usuario la acepte o elija otra

#### Scenario: Funcionalidad nueva

- **WHEN** se invoca `/nueva-version` sin argumento y las novedades contienen alguna entrada de Añadido
- **THEN** la skill propone `minor`

#### Scenario: Cambio incompatible en 0.x

- **WHEN** la versión actual es `0.2.1`, se invoca sin argumento y hay un cambio incompatible
- **THEN** la skill propone `minor`, no `major`, y señala el cambio incompatible en la propuesta

### Requirement: Estado del repositorio antes de empezar

Antes de modificar nada, la skill SHALL comprobar que la rama actual es `main` y que no está por detrás de `origin/main`; si falla cualquiera, se detiene sin tocar ficheros. Si hay cambios sin commitear en los ficheros que la versión modifica, también se detiene. Si hay otros cambios sin commitear, avisa de que no entrarán en la versión y pide confirmación para seguir.

#### Scenario: Rama distinta de main

- **WHEN** se invoca la skill desde la rama `feature/x`
- **THEN** la skill se detiene indicando que las versiones se preparan en `main` y no modifica ningún fichero

#### Scenario: main desactualizada

- **WHEN** `origin/main` tiene commits que la rama local no tiene
- **THEN** la skill se detiene pidiendo que se actualice `main` primero

#### Scenario: Cambios ajenos sin commitear

- **WHEN** hay cambios sin commitear en `.claude/` o en código del plugin
- **THEN** la skill los enumera, avisa de que no formarán parte de la versión y solo continúa si el usuario lo confirma

#### Scenario: Ficheros de la versión ya modificados

- **WHEN** `CHANGELOG.md` tiene cambios sin commitear fuera de la sección `## [No publicado]`, o `escritoriowp/readme.txt` o `escritoriowp/escritoriowp.php` tienen cambios sin commitear
- **THEN** la skill se detiene y pide que se commiteen o descarten antes

### Requirement: Novedades a partir de lo que ha cambiado

La skill SHALL redactar las novedades a partir de los commits y del diff desde el último tag `vX.Y.Z`, más lo que ya contenga `## [No publicado]` en `CHANGELOG.md`, sin perder ni duplicar nada de esa sección. Describe los cambios por lo que nota quien usa o mantiene el plugin, no por el mensaje literal del commit. Sin commits ni novedades pendientes, se detiene.

#### Scenario: Novedades ya anotadas a mano

- **WHEN** `## [No publicado]` contiene una entrada de Corregido escrita a mano y hay tres commits nuevos desde el último tag
- **THEN** el borrador conserva la entrada escrita a mano y añade las de los commits que no estén ya cubiertas por ella

#### Scenario: Nada que publicar

- **WHEN** no hay commits desde el último tag y `## [No publicado]` está vacía
- **THEN** la skill se detiene indicando que no hay cambios para una versión nueva y no modifica ningún fichero

#### Scenario: Commits que no afectan a nadie

- **WHEN** entre los commits hay cambios solo de specs de OpenSpec o de configuración del editor
- **THEN** esos commits no generan entradas en el changelog de `readme.txt`

### Requirement: Revisión antes de escribir

Antes de modificar ningún fichero, la skill SHALL mostrar al usuario la versión nueva, el borrador de la sección de `CHANGELOG.md`, las líneas del changelog de `readme.txt` y el aviso de actualización, y esperar a que los acepte o pida cambios.

#### Scenario: El usuario corrige el borrador

- **WHEN** el usuario pide reformular una entrada o cambiar el tipo de incremento
- **THEN** la skill rehace el borrador, lo vuelve a mostrar y no escribe nada hasta que el usuario lo acepte

#### Scenario: El usuario cancela

- **WHEN** el usuario rechaza el borrador
- **THEN** la skill termina sin haber modificado ningún fichero

### Requirement: Versión coherente en los tres sitios

Al aplicar la versión, la skill SHALL escribir el mismo número en la cabecera `Version:` de `escritoriowp/escritoriowp.php`, en su constante `VERSION` y en el `Stable tag` de `escritoriowp/readme.txt`, sin alterar nada más de esos ficheros.

#### Scenario: Subida de 0.2.1 a 0.3.0

- **WHEN** se aplica la versión `0.3.0` sobre ficheros que declaran `0.2.1` en los tres sitios
- **THEN** los tres sitios dicen `0.3.0` y el diff de esos ficheros no muestra ninguna otra línea cambiada fuera del changelog

#### Scenario: Versiones desincronizadas de partida

- **WHEN** la cabecera dice `0.2.1` y el `Stable tag` dice `0.2.0`
- **THEN** la skill se detiene antes de escribir, indicando los tres valores leídos

### Requirement: Registro en CHANGELOG.md

`CHANGELOG.md` SHALL quedar con una sección `## [X.Y.Z] - AAAA-MM-DD`, con la fecha del día, en el lugar de `## [No publicado]`, y una sección `## [No publicado]` vacía encima. El contenido sigue las categorías de Keep a Changelog que ya usa el fichero (Añadido, Cambiado, Corregido, Eliminado, Seguridad) y omite las vacías.

#### Scenario: Sección de versión creada

- **WHEN** se aplica la versión `0.3.0` el 2026-10-11
- **THEN** `CHANGELOG.md` contiene `## [No publicado]` vacía seguida de `## [0.3.0] - 2026-10-11` con las novedades aceptadas, y las secciones anteriores no cambian

#### Scenario: Notas extraíbles para la release

- **WHEN** se ejecuta la extracción de notas de release para `0.3.0` sobre el `CHANGELOG.md` resultante
- **THEN** devuelve exactamente las novedades aceptadas

### Requirement: Registro en readme.txt

El changelog de `escritoriowp/readme.txt` SHALL recibir una entrada `= X.Y.Z =` como primera de su sección, con una línea por novedad en lenguaje de usuario. La sección Upgrade Notice SHALL recibir otra `= X.Y.Z =` también la primera, con una o dos frases sobre qué notará quien actualice.

#### Scenario: Entradas en el readme

- **WHEN** se aplica la versión `0.3.0`
- **THEN** `== Changelog ==` empieza por `= 0.3.0 =` con sus líneas `* …`, `== Upgrade Notice ==` empieza por `= 0.3.0 =` con su aviso, y la pantalla Acerca de muestra la versión `0.3.0` como la más reciente

### Requirement: Comprobaciones antes del commit

Tras escribir los ficheros, la skill SHALL regenerar la plantilla de traducción y ejecutar la coherencia de versiones y changelogs, las pruebas unitarias, `phpcs` y el empaquetado. Si alguna falla, se detiene sin commit, deja los ficheros modificados y muestra el fallo. Si `phpcs` no está instalado, lo dice y pide confirmación para seguir.

#### Scenario: Todo correcto

- **WHEN** las comprobaciones pasan
- **THEN** la skill continúa al commit y el `.pot` declara la versión nueva

#### Scenario: Prueba fallida

- **WHEN** una prueba unitaria falla
- **THEN** la skill no hace commit, muestra la salida del fallo y deja los cambios de la versión en el árbol de trabajo para revisarlos

#### Scenario: Coherencia rota

- **WHEN** la entrada `= X.Y.Z =` falta en `readme.txt` o las tres versiones no coinciden
- **THEN** la comprobación de coherencia termina con error indicando qué falta, igual que lo haría el workflow de publicación

### Requirement: Comprobación de coherencia sin la skill

Las herramientas de desarrollo SHALL ofrecer una orden de línea de órdenes que compruebe, sin modificar nada, que las tres versiones coinciden y que hay sección para esa versión en `CHANGELOG.md` y en el changelog de `readme.txt`. Termina con código distinto de cero e indica qué falta si algo no cuadra.

#### Scenario: Repositorio coherente

- **WHEN** se ejecuta la comprobación sobre el repositorio tal como está tras publicar la `0.2.1`
- **THEN** termina con código cero

#### Scenario: Falta el changelog

- **WHEN** las tres versiones dicen `0.3.0` pero `CHANGELOG.md` no tiene `## [0.3.0]`
- **THEN** termina con código distinto de cero indicando que falta la sección de `CHANGELOG.md`

### Requirement: Commit de release

Si las comprobaciones pasan, la skill SHALL hacer un único commit en `main` con el mensaje `Release X.Y.Z` que incluya solo los ficheros de la versión: `CHANGELOG.md`, `escritoriowp/escritoriowp.php`, `escritoriowp/readme.txt` y `escritoriowp/languages/escritoriowp.pot`. No SHALL crear tags: el tag lo crea el workflow de publicación.

#### Scenario: Commit limpio

- **WHEN** hay cambios ajenos sin commitear que el usuario decidió dejar fuera
- **THEN** el commit `Release X.Y.Z` contiene solo los cuatro ficheros de la versión y los cambios ajenos siguen sin commitear

#### Scenario: Sin tag local

- **WHEN** termina el commit
- **THEN** no existe ningún tag `vX.Y.Z` nuevo en el repositorio local

### Requirement: Push solo con confirmación

Tras el commit, la skill SHALL mostrar un resumen (versión, commit y novedades) y preguntar si empujar a `origin/main`, recordando que el push publica la release y avisa a los sitios instalados. Solo con un sí explícito empuja e indica dónde seguir el workflow. Si no, deja el commit en local y explica cómo empujarlo o deshacerlo.

#### Scenario: El usuario confirma

- **WHEN** el usuario responde que sí al push
- **THEN** la skill empuja `main` a `origin`, muestra el enlace a las ejecuciones de GitHub Actions del repositorio y recuerda que la release aparecerá cuando terminen las comprobaciones

#### Scenario: El usuario no confirma

- **WHEN** el usuario responde que no o no responde que sí
- **THEN** la skill no empuja, el commit queda solo en local y se indican las órdenes para empujarlo más tarde o deshacerlo conservando los cambios

#### Scenario: El push falla

- **WHEN** el push es rechazado porque `origin/main` ha avanzado entretanto
- **THEN** la skill muestra el error, no fuerza el push y explica que hay que integrar `origin/main` y repetir las comprobaciones antes de volver a empujar
