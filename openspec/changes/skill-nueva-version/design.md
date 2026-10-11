## Context

Motivación en `proposal.md`; comportamiento exigido en `specs/preparacion-versiones/spec.md`. Estado actual relevante:

- La versión vive en tres sitios: la cabecera `Version:` y la constante `VERSION` de `escritoriowp/escritoriowp.php`, y el `Stable tag` de `escritoriowp/readme.txt`. El workflow `.github/workflows/release.yml` los lee con `grep` y rechaza la publicación si no coinciden o si falta la entrada del changelog.
- Hay dos changelogs con formatos distintos: `CHANGELOG.md` (Keep a Changelog, con `## [No publicado]` arriba, del que salen las notas de la release) y `readme.txt` (`= X.Y.Z =` con líneas `* …` en `== Changelog ==`, más `== Upgrade Notice ==`, del que sale la pantalla Acerca de). El de `CHANGELOG.md` incluye cambios de herramientas y del workflow; el de `readme.txt` solo lo que nota quien usa el plugin.
- Ya existen herramientas que se reutilizan: `version_de_cabecera()` en `tools/empaquetar.php` y `extraer_seccion_changelog()` en `tools/notas-release.php`. Ambos ficheros protegen su punto de entrada con `realpath( $argv[0] ) === __FILE__`, así que se pueden incluir sin ejecutar nada. `tools/generar-pot.php` ya lee la versión de la cabecera.
- `tests/ejecutar.php` incluye las herramientas y ejecuta todos los `tests/prueba-*.php`, con `grupo()`, `comprobar()` y `comprobar_que()` de `tests/lib.php`. `phpcs` solo revisa `escritoriowp/`.
- Las skills de proyecto viven en `.claude/skills/<nombre>/SKILL.md` y están versionadas (las de OpenSpec ya lo están). Claude Code las ofrece como `/<nombre>`.
- El tag `vX.Y.Z` solo lo crea el workflow cuando todo pasa. Si el workflow falla, la versión no queda consumida y se puede volver a empujar con el mismo número.

## Goals / Non-Goals

**Goals:**

- Separar el criterio de la mecánica: el modelo redacta y propone; una herramienta probada escribe los números y reordena el changelog.
- Fallar en local, antes del commit, por los mismos motivos por los que fallaría el workflow.
- Que nada se publique sin un sí explícito del usuario en esa misma ejecución.

**Non-Goals:**

- Crear tags o releases desde la skill. Eso sigue siendo del workflow.
- Ejecutar Plugin Check en local: necesita una instalación de WordPress y ya lo hace el workflow.
- Versiones previas (`1.0.0-beta.1`) o ramas de mantenimiento: solo `X.Y.Z` desde `main`.
- Traducir el changelog o el readme a otros idiomas.
- Convenciones de commit obligatorias (Conventional Commits o similares): las novedades se infieren leyendo commits y diff, no analizando prefijos.

## Decisions

### D1. Skill para el criterio, herramienta para la mecánica

`/nueva-version` es una skill en Markdown que guía a Claude por el ciclo. Lo que tiene una respuesta exacta (calcular `0.2.1 + minor`, escribir el número en tres sitios, renombrar la sección del changelog, comprobar coherencia) lo hace `tools/nueva-version.php`, con funciones puras probadas. Lo que necesita juicio (agrupar commits en novedades, redactarlas para cada público, proponer el tipo de incremento) lo hace la skill y lo revisa el usuario.

*Alternativas:* solo skill, con Claude editando los números con Edit (no es reproducible ni se puede probar, y un error en la constante solo aparece en el workflow); solo herramienta (no puede redactar novedades a partir de un diff); un hook de git `pre-push` (no puede redactar nada y comprobaría demasiado tarde).

### D2. Frontmatter de la skill

```yaml
name: nueva-version
description: Prepara una versión nueva de EscritorioWP …
argument-hint: "[patch|minor|major|X.Y.Z]"
disable-model-invocation: true
allowed-tools: Bash(git status:*), Bash(git log:*), Bash(git diff:*), Bash(git fetch:*),
  Bash(git tag:*), Bash(git ls-remote:*), Bash(git rev-parse:*), Bash(git rev-list:*),
  Bash(git branch:*), Bash(git add:*), Bash(git commit:*), Bash(php:*), Bash(node:*),
  Bash(composer:*), Bash(date:*), Read, Edit, Grep, Glob
```

- `disable-model-invocation: true`: la skill solo arranca cuando el usuario escribe `/nueva-version`. Preparar una versión nunca debe dispararse porque el modelo lo deduzca de la conversación.
- `git push` queda fuera de `allowed-tools` a propósito. Además de la pregunta explícita que exige la spec, Claude Code pide permiso para el push. Son dos barreras independientes para el único paso que publica.
- El cuerpo de la skill está en español, como el resto del repositorio.

### D3. Interfaz de `tools/nueva-version.php`

```
php tools/nueva-version.php <patch|minor|major|X.Y.Z> [--fecha=AAAA-MM-DD] [--simular]
php tools/nueva-version.php --comprobar
```

- **Aplicar** (por defecto): lee los tres ficheros, exige que las tres versiones actuales coincidan, calcula la nueva, exige que `## [No publicado]` tenga contenido, escribe la versión en la cabecera, la constante y el `Stable tag`, y convierte `## [No publicado]` en `## [X.Y.Z] - fecha` con una `## [No publicado]` vacía encima. Imprime solo la versión nueva en la salida estándar. Cualquier error va a la salida de error con código 1 y sin escribir nada: primero se calculan los tres contenidos nuevos y solo después se escriben.
- **`--simular`**: calcula la versión y valida todo sin escribir. La skill lo usa para conocer el número antes de redactar las entradas `= X.Y.Z =` del readme.
- **`--fecha`**: por defecto `date( 'Y-m-d' )`; existe para que las pruebas y la skill fijen la fecha.
- **`--comprobar`**: no escribe nada. Lee la versión de la cabecera y verifica lo mismo que el job `publicar` del workflow: tres versiones iguales, sección con contenido en `CHANGELOG.md` y `= X.Y.Z =` en `readme.txt`. Imprime cada problema encontrado y termina con 1 si hay alguno.
- La herramienta **no llama a git**. Así las pruebas siguen siendo funciones puras sobre texto, igual que las del resto de `tools/`. Las comprobaciones de rama, tags y árbol de trabajo las hace la skill.

Funciones puras, incluidas en `tests/ejecutar.php`:

| Función | Contrato |
|---|---|
| `calcular_version( string $actual, string $peticion ): ?string` | `patch`/`minor`/`major` o `X.Y.Z` mayor que la actual. `null` si la petición o la versión actual no son válidas, o si la explícita no avanza. |
| `versiones_declaradas( string $php, string $readme ): array` | `cabecera`, `constante` y `estable`, cada una `?string`. Reutiliza `version_de_cabecera()`. |
| `fijar_version_plugin( string $php, string $version ): string` | Sustituye solo la primera cabecera `Version:` y la primera `const VERSION = '…';`. |
| `fijar_stable_tag( string $readme, string $version ): string` | Sustituye solo la línea `Stable tag:`. |
| `promover_no_publicado( string $changelog, string $version, string $fecha ): ?string` | `null` si no hay `## [No publicado]` o está vacía. |
| `problemas_de_coherencia( string $php, string $readme, string $changelog ): array` | Lista de mensajes; vacía si todo cuadra. Reutiliza `extraer_seccion_changelog()`. |

*Alternativa descartada:* que la herramienta también inserte las entradas de `readme.txt` a partir de argumentos o de un fichero temporal. Son prosa libre con comillas tipográficas y «›». Pasarlas por la línea de órdenes complica el escapado sin ganar nada, porque el punto de inserción (justo después de `== Changelog ==` y de `== Upgrade Notice ==`) es trivial para Edit y `--comprobar` verifica el resultado.

### D4. Orden de la skill

1. **Estado del repositorio.** `git rev-parse --abbrev-ref HEAD` debe ser `main`. Después `git fetch origin main --tags` y `git rev-list --count HEAD..origin/main` debe valer 0. Se revisa `git status --porcelain`: cambios en `escritoriowp/escritoriowp.php`, `escritoriowp/readme.txt` o en `CHANGELOG.md` fuera de `## [No publicado]` (se mira `git diff -- CHANGELOG.md`) detienen la skill; el resto se enumera y se pide confirmación. Si `main` local va por delante de `origin/main`, se avisa de que esos commits también se empujarán.
2. **Qué ha cambiado.** Último tag con `git describe --tags --abbrev=0 --match 'v*'`. Se leen `git log <tag>..HEAD --no-merges` con mensaje completo y `git diff --stat <tag>..HEAD`, y el diff concreto cuando el mensaje no basta para entender el cambio. Se lee `## [No publicado]`. Si no hay ni commits ni contenido pendiente, la skill se detiene.
3. **Clasificación por público.** Commits que solo tocan `openspec/` o configuración del editor no generan entradas. Los de `tools/`, `tests/`, `.github/`, `.claude/skills/` o documentación de desarrollo van solo a `CHANGELOG.md`. Los de `escritoriowp/` van a los dos, redactados para cada público. Las entradas ya presentes en `## [No publicado]` se conservan tal cual y los commits que ya cubren no se duplican.
4. **Número.** Con el argumento, o con la propuesta según la spec. Se obtiene con `--simular`, que además valida coherencia y formato. Se comprueba que `vX.Y.Z` no existe con `git tag -l` y `git ls-remote --tags origin`.
5. **Revisión.** Se muestran versión, sección de `CHANGELOG.md`, líneas de `readme.txt` y aviso de actualización, y se espera la respuesta. Nada se escribe antes.
6. **Escritura.** Edit sustituye el contenido de `## [No publicado]` por el aceptado. Después se ejecuta `php tools/nueva-version.php X.Y.Z --fecha=$(date +%F)`, pasando la versión explícita ya confirmada para que no se recalcule. Por último, Edit inserta las dos entradas `= X.Y.Z =` en `readme.txt`.
7. **Comprobaciones**, en este orden y deteniéndose en el primer fallo: `php tools/generar-pot.php`, `php tools/nueva-version.php --comprobar`, `composer test`, `composer lint` (si no existe `vendor/bin/phpcs`, se avisa y se pregunta), sintaxis de PHP y JS del plugin, `php tools/notas-release.php X.Y.Z` y `php tools/empaquetar.php`.
8. **Commit.** `git add` con las cuatro rutas explícitas, nunca `-A` ni `.`, y `git commit -m "Release X.Y.Z"`.
9. **Push.** Resumen y pregunta. Con un sí, `git push origin main` y el enlace `https://github.com/webprogramacion/escritoriowp/actions`. Si no, se dan `git push origin main` para más tarde y `git reset --soft HEAD~1` para deshacer conservando los cambios. Si el push se rechaza, nunca se usa `--force`.

### D5. Regla de propuesta del incremento

Se decide sobre las categorías del borrador de `CHANGELOG.md`, no sobre los commits: es lo que el usuario acaba de revisar. Mientras la versión sea 0.x, un cambio incompatible propone `minor`. Así lo hizo la 0.2.0 con el cambio de URL de los ajustes, y es lo que permite el versionado semántico antes de la 1.0.0. Pasar a `major` es siempre una decisión explícita del usuario.

## Risks / Trade-offs

- [El borrador describe mal un cambio o se le escapa uno] → La revisión es obligatoria antes de escribir, la skill lee el diff cuando el mensaje del commit no basta y el usuario puede corregir antes de aceptar.
- [Plugin Check no se ejecuta en local y el workflow falla después del push] → El tag solo se crea si todo pasa, así que la versión no se pierde: se corrige y se vuelve a empujar con el mismo número. La skill lo recuerda en el resumen previo al push.
- [Se invoca por error] → `disable-model-invocation: true` y dos confirmaciones antes de publicar: la pregunta de la skill y el permiso de Claude Code para `git push`.
- [La comprobación de `CHANGELOG.md` «fuera de `[No publicado]`» depende de que el modelo lea bien el diff] → Si hay duda, la skill se detiene y pregunta. `--comprobar` vuelve a validar la estructura después de escribir.
- [Cambios ajenos sin commitear acaban en el commit de release] → `git add` solo con las cuatro rutas explícitas; la spec lo exige y la skill lo enumera antes de commitear.
- [El `.pot` regenerado cambia `POT-Creation-Date` aunque no haya textos nuevos] → Es el comportamiento actual del proceso manual. Entra en el commit de release a propósito, para que el `.pot` distribuido anuncie la versión correcta.

## Migration Plan

Sin migración: son ficheros nuevos de desarrollo que no se distribuyen. El proceso manual de `CLAUDE.md` §11 sigue siendo válido y se documenta como alternativa. Para retirar la skill basta con borrar `.claude/skills/nueva-version/`. La herramienta y sus pruebas pueden quedarse, porque `--comprobar` sirve también sin la skill.
