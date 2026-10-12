---
name: nueva-version
description: Prepara una versión nueva de EscritorioWP. Sube el número en los tres sitios, redacta las novedades de CHANGELOG.md y readme.txt a partir de lo que ha cambiado desde el último tag, pasa las comprobaciones, hace el commit «Release X.Y.Z» en main y pregunta antes de empujar, que es lo que publica la release.
argument-hint: "[patch|minor|major|X.Y.Z]"
disable-model-invocation: true
allowed-tools:
  - Bash(git status:*)
  - Bash(git log:*)
  - Bash(git diff:*)
  - Bash(git show:*)
  - Bash(git fetch:*)
  - Bash(git tag:*)
  - Bash(git describe:*)
  - Bash(git ls-remote:*)
  - Bash(git rev-parse:*)
  - Bash(git rev-list:*)
  - Bash(git branch:*)
  - Bash(git add:*)
  - Bash(git commit:*)
  - Bash(php:*)
  - Bash(node:*)
  - Bash(vendor/bin/phpcs:*)
  - Bash(find:*)
  - Bash(date:*)
  - Read
  - Edit
  - Grep
  - Glob
---

# Nueva versión de EscritorioWP

Prepara en `main` el commit `Release X.Y.Z` que el workflow de GitHub convierte en release al
empujarlo. Argumento recibido: `$ARGUMENTS` (vacío, `patch`, `minor`, `major` o una versión `X.Y.Z`).

El reparto es fijo. Tú decides qué ha cambiado y lo redactas. `tools/nueva-version.php` escribe los
números y reordena el changelog. El usuario aprueba el borrador y decide si se publica.

Reglas que valen para todos los pasos:

- **No escribas ningún fichero antes de que el usuario acepte el borrador** (paso 5).
- **Nunca empujes sin un sí explícito** en esta misma ejecución (paso 9). Nunca uses `--force`.
- Nunca crees tags: el tag `vX.Y.Z` lo crea el workflow cuando todo pasa.
- Nunca uses `git add -A` ni `git add .`: solo las cuatro rutas de la versión.
- Si algo no encaja con lo que describe esta guía, detente y pregunta en lugar de improvisar.

Cuando un paso diga «detente», muestra el mensaje indicado (adaptado a los datos reales), explica
cómo resolverlo y termina sin tocar nada.

## 1. Estado del repositorio

1. `git rev-parse --abbrev-ref HEAD`. Si no es `main`, detente:
   > Las versiones se preparan en `main` y estás en `<rama>`. Cambia con `git switch main` y vuelve
   > a lanzar `/nueva-version`.
2. `git fetch origin main --tags`. Si falla, detente: sin ver `origin` no se puede saber si `main`
   está al día ni si el tag ya existe.
3. `git rev-list --count HEAD..origin/main`. Si es mayor que 0, detente:
   > `origin/main` tiene N commits que tu `main` no tiene. Actualiza primero con
   > `git pull --ff-only origin main` y vuelve a lanzar `/nueva-version`.
4. `git rev-list --count origin/main..HEAD`. Si es mayor que 0, apúntalo: esos commits saldrán en el
   mismo push que la versión y hay que decirlo en el resumen del paso 9.
5. `git status --porcelain`:
   - Si aparece `escritoriowp/escritoriowp.php` o `escritoriowp/readme.txt`, detente:
     > Hay cambios sin commitear en `<fichero>`, que la versión tiene que modificar. Commitéalos o
     > descártalos y vuelve a lanzar `/nueva-version`.
   - Si aparece `CHANGELOG.md`, lee `git diff -- CHANGELOG.md`. Si todos los cambios caen dentro de
     `## [No publicado]`, son novedades anotadas a mano: entran en la versión. Si alguno cae fuera,
     o no lo tienes claro, detente:
     > `CHANGELOG.md` tiene cambios sin commitear fuera de `## [No publicado]`. Commitéalos o
     > descártalos y vuelve a lanzar `/nueva-version`.
   - Cualquier otro fichero: enuméralos, avisa de que **no** formarán parte de la versión y pregunta
     si seguir. Si alguno es PHP de `escritoriowp/`, añade que el `.pot` regenerado puede recoger
     sus textos y que las pruebas se pasarán con esos cambios presentes; recomienda commitearlos o
     guardarlos con `git stash` antes. Sin un sí, termina.

## 2. Qué ha cambiado

1. Último tag: `git describe --tags --abbrev=0 --match 'v*'`. Si no hay ninguno, usa todo el
   historial y dilo.
2. Commits: `git log <tag>..HEAD --no-merges --name-only --format='--- %h %s%n%b'`.
3. Resumen del diff: `git diff --stat <tag>..HEAD`. Cuando el mensaje de un commit no basta para
   entender qué nota quien usa el plugin, lee su diff con `git show <hash>`.
4. Lee la sección `## [No publicado]` de `CHANGELOG.md`.
5. Si no hay commits desde el tag y `## [No publicado]` está vacía, detente:
   > No hay cambios desde `<tag>` ni novedades en `## [No publicado]`: no hay nada para una
   > versión nueva.

## 3. Borrador de novedades

**Clasifica cada commit por los ficheros que toca**:

| Ficheros | Dónde se anuncia |
|---|---|
| Solo `openspec/` o configuración del editor | En ningún sitio |
| `tools/`, `tests/`, `.github/`, `.claude/`, `docs/`, `CLAUDE.md`, `README.md`, `BACKLOG.md`, `composer.*`, `phpcs.xml.dist` | Solo en `CHANGELOG.md` |
| `escritoriowp/` | En `CHANGELOG.md` y en `readme.txt`, redactado para cada público |

Un commit que toca varias zonas se anuncia según la más visible.

**Las entradas que ya haya en `## [No publicado]` se conservan tal cual**, palabra por palabra. Añade
solo los commits que no estén ya cubiertos por ellas. Si una entrada describe un cambio que solo
está en ficheros sin commitear, avísalo: la versión saldría anunciando algo que no lleva.

Si después de clasificar no queda ninguna entrada, detente: los commits desde el tag no cambian nada
que haya que anunciar.

**`CHANGELOG.md`** sigue Keep a Changelog con estas categorías, en este orden y omitiendo las
vacías: `### Añadido`, `### Cambiado`, `### Corregido`, `### Eliminado`, `### Seguridad`. Una
entrada por cambio, en español, explicando qué pasaba y qué pasa ahora. Líneas de unos 100
caracteres, con la continuación sangrada dos espacios. Los nombres de ficheros y funciones van
entre comillas invertidas. Si la versión no cambia nada de lo que ve el usuario, puede abrir con
una línea como «Versión de mantenimiento, sin cambios en el comportamiento del plugin.». Así quedó
en la 0.2.1:

```markdown
### Corregido

- El generador de la plantilla de traducción llevaba la versión escrita a mano, así que el `.pot`
  distribuido anunciaba siempre la 0.1.0. Ahora la lee de la cabecera del plugin.
```

**Changelog de `readme.txt`**: una línea `* …` por novedad, en lenguaje de quien usa el plugin, sin
nombres de ficheros ni código. Solo lo que esa persona nota. Si no hay nada que note, una sola línea
como «* Versión de mantenimiento, sin cambios en el funcionamiento del plugin.», porque el workflow
exige la entrada aunque no haya novedades visibles. Así quedó en la 0.2.0:

```
= 0.2.0 =
* Menú propio «EscritorioWP» en el administrador, con las pantallas Ajustes y Acerca de.
* La pantalla de ajustes se traslada desde Ajustes › EscritorioWP al menú propio.
```

**Aviso de actualización de `readme.txt`**: una o dos frases sobre qué notará quien actualice, y
cualquier cosa que tenga que hacer. Así quedó en la 0.2.0:

```
= 0.2.0 =
Añade el menú propio del plugin, la pantalla Acerca de y las actualizaciones automáticas desde
GitHub. La pantalla de ajustes cambia de sitio: ahora está en EscritorioWP › Ajustes.
```

## 4. Número de versión

1. Si `$ARGUMENTS` trae `patch`, `minor`, `major` o `X.Y.Z`, úsalo. Si viene vacío, propón el tipo
   según las categorías del borrador de `CHANGELOG.md`:
   - Alguna entrada en Añadido, Cambiado o Eliminado → `minor`.
   - Solo Corregido, Seguridad o mantenimiento → `patch`.
   - Un cambio incompatible (una URL que deja de funcionar, un ajuste o un filtro que desaparece)
     → `minor` mientras la versión sea 0.x, y `major` desde la 1.0.0. Señálalo en la propuesta.
   - Nunca propongas `major` por tu cuenta en 0.x: pasar a 1.0.0 lo decide el usuario.

   Si cualquier otra cosa llega en `$ARGUMENTS`, detente y muestra el uso:
   `/nueva-version [patch|minor|major|X.Y.Z]`.
2. `php tools/nueva-version.php <tipo o versión> --simular` imprime la versión nueva sin escribir
   nada. Si termina con error (versiones desincronizadas, versión explícita que no avanza, petición
   no válida…), detente y muestra su mensaje tal cual.
3. Comprueba que el tag no existe: `git tag -l vX.Y.Z` y `git ls-remote --tags origin refs/tags/vX.Y.Z`
   deben salir vacíos. Si no, detente:
   > La versión X.Y.Z ya está publicada: existe el tag `vX.Y.Z`. Elige otra versión.

## 5. Revisión

Muestra al usuario, sin escribir nada todavía:

- La versión: `0.2.1 → 0.3.0 (minor)`, con el motivo si es propuesta tuya.
- El cuerpo completo de la sección `## [X.Y.Z]` de `CHANGELOG.md`.
- La entrada `= X.Y.Z =` del changelog de `readme.txt`.
- La entrada `= X.Y.Z =` del aviso de actualización.
- Los commits que has dejado sin anunciar, con su motivo.

Pregunta si lo acepta, si quiere cambios o si cancela.

- Si pide cambios (reformular una entrada, otro tipo de incremento…), rehaz el borrador, vuelve al
  paso 4 si cambia el número y enséñalo entero otra vez. Repite hasta que lo acepte.
- Si cancela, termina diciendo que no se ha modificado ningún fichero.

## 6. Escritura

1. Con Edit, sustituye el contenido de `## [No publicado]` de `CHANGELOG.md` por el cuerpo aceptado.
   Deja una línea en blanco tras la cabecera y otra antes de la sección siguiente.
2. Obtén la fecha con `date +%F` y ejecuta `php tools/nueva-version.php X.Y.Z --fecha=AAAA-MM-DD`
   con la versión ya confirmada, explícita, para que no se recalcule. La herramienta escribe la
   versión en la cabecera, en la constante `VERSION` y en el `Stable tag`, y convierte
   `## [No publicado]` en `## [X.Y.Z] - AAAA-MM-DD` con una `## [No publicado]` vacía encima. Si
   falla, detente, muestra el error y avisa de que `CHANGELOG.md` ya lleva el borrador en
   `## [No publicado]`.
3. Con Edit, en `escritoriowp/readme.txt`, inserta la entrada `= X.Y.Z =` aceptada como **primera**
   entrada de `== Changelog ==` y la del aviso como **primera** de `== Upgrade Notice ==`, justo
   detrás de la línea en blanco que sigue a cada cabecera y con una línea en blanco antes de la
   entrada anterior.

## 7. Comprobaciones

Ejecuta en este orden. **En el primer fallo, detente**: no hagas commit, muestra la salida y deja los
cambios en el árbol de trabajo para revisarlos con `git diff`.

1. `php tools/generar-pot.php`
2. `php tools/nueva-version.php --comprobar`
3. Pruebas: `php tests/ejecutar.php`, `node tests/ejecutar.js` y `node tools/comprobar-contraste.js`.
   Es lo que ejecuta `composer test`, pero sin depender de que Composer esté en el PATH.
4. `vendor/bin/phpcs`, que es lo que ejecuta `composer lint`. Si no existe (compruébalo con Glob),
   dilo y pregunta si seguir sin él. Advierte de que el workflow lo pasa igualmente y fallaría
   después del push.
5. `find escritoriowp -name '*.php' -print0 | xargs -0 -n1 php -l` y
   `find escritoriowp/assets/js -name '*.js' -print0 | xargs -0 -n1 node --check`
6. `php tools/notas-release.php X.Y.Z`. Debe imprimir exactamente las novedades aceptadas.
7. `php tools/empaquetar.php`

Si te piden deshacer después de un fallo:
`git checkout -- escritoriowp/escritoriowp.php escritoriowp/readme.txt escritoriowp/languages/escritoriowp.pot`
deja esos tres ficheros como estaban. `CHANGELOG.md` hay que revisarlo a mano si tenía novedades sin
commitear de antes, porque un `checkout` las perdería.

## 8. Commit

1. `git add CHANGELOG.md escritoriowp/escritoriowp.php escritoriowp/readme.txt escritoriowp/languages/escritoriowp.pot`
2. `git commit -m "Release X.Y.Z"`
3. Verifica con `git show --stat --format=%s HEAD` que el commit contiene solo esos cuatro ficheros, y
   con `git tag -l vX.Y.Z` que no se ha creado ningún tag. Los cambios ajenos que el usuario dejó
   fuera siguen sin commitear.

## 9. Push

Muestra un resumen:

- La versión, el hash corto del commit y las novedades de `CHANGELOG.md`.
- Los commits que también saldrán en el push, si los apuntaste en el paso 1.4.
- **El aviso**: empujar a `main` publica la release y avisa de la actualización a todos los sitios
  que tienen el plugin instalado. Plugin Check solo se pasa en el workflow; si falla allí, no se
  crea el tag, así que se puede corregir y volver a empujar con el mismo número.

Pregunta explícitamente si empujar. Si puedes, usa AskUserQuestion con las opciones «Sí, publicar» y
«No, dejarlo en local». Solo un sí claro cuenta como sí.

- **Sí**: `git push origin main`. Después indica que el workflow corre en
  https://github.com/webprogramacion/escritoriowp/actions y que la release aparecerá cuando
  terminen las comprobaciones.
- **No, o cualquier respuesta que no sea un sí**: no empujes. Explica que el commit queda solo en
  local, que `git push origin main` lo publica cuando quiera y que `git reset --soft HEAD~1` lo
  deshace conservando los cambios.
- **Push rechazado** porque `origin/main` ha avanzado: muestra el error y no fuerces nada. Explica
  que hay que integrar `origin/main` (`git pull --rebase origin main`), repetir las comprobaciones
  del paso 7 y volver a empujar.
