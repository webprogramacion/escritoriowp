## Why

Publicar una versión exige hoy cinco pasos manuales descritos en `CLAUDE.md` §11: subir la versión en tres sitios, escribir el changelog en dos ficheros con formatos distintos, regenerar el `.pot`, comprobar el paquete y hacer el commit. Si se olvida cualquiera, el workflow de `main` falla y la release no sale, y el error se descubre después del push. Queremos que una sola orden en Claude Code (`/nueva-version`) haga ese trabajo de forma repetible, redacte las novedades a partir de lo que de verdad ha cambiado y deje la publicación en manos del usuario.

## What Changes

- **Skill de proyecto `nueva-version`** en `.claude/skills/nueva-version/SKILL.md`, invocable como `/nueva-version [patch|minor|major|X.Y.Z]`. Guía a Claude por todo el ciclo:
  - Comprueba que se está en `main`, al día con `origin/main`, y avisa de los cambios sin commitear que no entrarán en la versión.
  - Reúne lo que ha cambiado desde el último tag `vX.Y.Z`: los commits, el resumen del diff y lo que ya haya en la sección `## [No publicado]` de `CHANGELOG.md`.
  - Propone el tipo de incremento si no se indica (Añadido o Cambiado → menor; solo Corregido o mantenimiento → parche) y redacta las entradas para `CHANGELOG.md`, el changelog de `readme.txt` y su aviso de actualización. Lo enseña todo y espera confirmación antes de escribir nada.
  - Sube la versión, registra el changelog en los dos ficheros, regenera el `.pot`, pasa las pruebas, `phpcs` y el empaquetado, y hace el commit `Release X.Y.Z` en `main` con solo los ficheros de la versión.
  - **Pregunta antes del push.** Empujar es lo que publica la release y avisa a todos los sitios instalados, así que la skill nunca empuja sin un sí explícito.
- **Herramienta `tools/nueva-version.php`** con la parte mecánica, para que no dependa del criterio del modelo: calcula la versión nueva, la escribe en la cabecera, en la constante `VERSION` y en `Stable tag`, convierte `## [No publicado]` en `## [X.Y.Z] - AAAA-MM-DD` dejando una sección vacía nueva encima, y con `--comprobar` repite en local las comprobaciones de coherencia que hace el workflow antes de publicar.
- **Pruebas** de las funciones puras de la herramienta en `tests/prueba-nueva-version.php`.
- **Documentación**: `CLAUDE.md` §11 y `README.md` explican que `/nueva-version` es el camino habitual y que los pasos manuales siguen valiendo.

No cambia nada del plugin distribuido ni del workflow de GitHub Actions.

## Capabilities

### New Capabilities

- `preparacion-versiones`: cómo se prepara en local una versión nueva del plugin antes de publicarla: cálculo del número, coherencia de las tres versiones, registro de novedades en los dos changelogs, comprobaciones previas, commit de release y confirmación del push.

### Modified Capabilities

Ninguna. El requisito de publicación automática (`publicacion-github`, todavía en el cambio sin archivar `releases-github-y-menu-propio`) no cambia: esta capacidad prepara exactamente lo que ese workflow ya exige.

## Impact

- **Repositorio (no distribuible):** `.claude/skills/nueva-version/SKILL.md` (nuevo), `tools/nueva-version.php` (nuevo), `tests/prueba-nueva-version.php` (nuevo), `tests/ejecutar.php` (incluye la herramienta), `CLAUDE.md`, `README.md`.
- **Plugin:** ninguno. La skill modifica en cada ejecución `escritoriowp/escritoriowp.php`, `escritoriowp/readme.txt` y `escritoriowp/languages/escritoriowp.pot`, igual que el proceso manual.
- **Dependencias:** ninguna nueva. Usa `git`, PHP 8.1 y Node, que ya hacen falta para las pruebas; `composer lint` necesita `vendor/` instalado.
- **Riesgo:** el push a `main` publica. Se mitiga con la confirmación explícita y con las comprobaciones locales previas, que adelantan los fallos que hoy solo se ven en el workflow.
