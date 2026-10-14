## Purpose

Definir cómo se comprueba y se publica automáticamente cada versión del plugin en GitHub a partir de la rama `main`, de forma que el zip instalable en WordPress esté siempre disponible como release sin pasos manuales.

## ADDED Requirements

### Requirement: Comprobaciones en cada push a main

Cada push a la rama `main` y cada pull request hacia ella SHALL ejecutar en integración continua la comprobación de sintaxis de PHP y JavaScript, el estándar de codificación de WordPress (phpcs sin errores ni avisos), las pruebas unitarias y Plugin Check sobre el directorio `escritoriowp/`. Un fallo en cualquiera de ellas SHALL impedir la publicación de la release.

#### Scenario: Push sin cambio de versión

- **WHEN** se empuja a `main` un commit cuya versión de cabecera ya tiene tag publicado
- **THEN** se ejecutan todas las comprobaciones y no se crea ningún tag ni release

#### Scenario: Comprobación fallida con cambio de versión

- **WHEN** se empuja a `main` un commit que incrementa la versión pero phpcs, las pruebas o Plugin Check fallan
- **THEN** el workflow termina en error y no se crea ni el tag ni la release

### Requirement: Release automática al incrementar la versión

Cuando un push a `main` contenga una versión de cabecera `X.Y.Z` sin tag `vX.Y.Z` en el repositorio y todas las comprobaciones pasen, el workflow SHALL crear el tag `vX.Y.Z` sobre ese commit y publicar una release de GitHub marcada como última con: el título `X.Y.Z`, las notas tomadas de la sección `[X.Y.Z]` de `CHANGELOG.md` y el fichero `escritoriowp-X.Y.Z.zip` como activo descargable.

#### Scenario: Incremento de versión correcto

- **WHEN** se empuja a `main` un commit con versión `0.2.0` en cabecera, constante y `Stable tag`, con sección `## [0.2.0]` en `CHANGELOG.md` y `= 0.2.0 =` en el changelog de `readme.txt`, y no existe el tag `v0.2.0`
- **THEN** aparece en GitHub el tag `v0.2.0`, una release `0.2.0` marcada como última cuyas notas son el contenido de esa sección del changelog, y el activo `escritoriowp-0.2.0.zip`

#### Scenario: Dos pushes seguidos con la misma versión nueva

- **WHEN** se empujan a `main` dos commits consecutivos con la misma versión nueva
- **THEN** solo se crea una release; el segundo push ejecuta las comprobaciones y termina sin publicar nada

### Requirement: Coherencia de versiones y changelog antes de publicar

El workflow SHALL rechazar la publicación si la versión de la cabecera, la constante `VERSION` y el `Stable tag` de `readme.txt` no coinciden, o si falta la sección de esa versión en `CHANGELOG.md` o en el changelog de `readme.txt`.

#### Scenario: Versiones desincronizadas

- **WHEN** la cabecera dice `0.2.0` y el `Stable tag` sigue en `0.1.0`
- **THEN** el workflow falla con un mensaje que indica los tres valores leídos y no se crea el tag

#### Scenario: Changelog sin entrada

- **WHEN** las tres versiones coinciden en `0.2.0` pero `CHANGELOG.md` no tiene la sección `## [0.2.0]`
- **THEN** el workflow falla indicando que falta la entrada del changelog y no se crea el tag

### Requirement: Zip instalable en WordPress

El zip publicado SHALL contener únicamente el directorio `escritoriowp/` en su raíz, con todo el contenido del plugin y sin ficheros del sistema ni material de desarrollo, de modo que WordPress lo instale en `wp-content/plugins/escritoriowp/` tanto desde «Subir plugin» como desde el actualizador automático.

#### Scenario: Estructura del zip

- **WHEN** se descomprime el zip de una release
- **THEN** su única entrada de primer nivel es `escritoriowp/`, contiene `escritoriowp/escritoriowp.php` y no contiene `.DS_Store`, `vendor/`, `tests/` ni `openspec/`

#### Scenario: Instalación manual del zip

- **WHEN** se sube el zip desde Plugins › Añadir nuevo › Subir plugin en una instalación con la versión anterior activa
- **THEN** WordPress ofrece reemplazar la versión instalada y, tras hacerlo, el plugin queda activo con la versión nueva y los ajustes conservados
