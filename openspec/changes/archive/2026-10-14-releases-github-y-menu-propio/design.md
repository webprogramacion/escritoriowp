## Context

Motivación en `proposal.md`. Estado actual relevante:

- `.github/workflows/release.yml` se dispara solo con tags `v*`, hace lint y pruebas y publica el zip con `softprops/action-gh-release@v2` y notas autogeneradas. No hay CI en pushes normales.
- `Ajustes` registra la pantalla con `add_options_page()` bajo Ajustes, comprueba el hook `settings_page_escritoriowp` para encolar `ajustes.css`, y `CatalogoComandos` enlaza a `options-general.php?page=escritoriowp`.
- El changelog vive por duplicado y a mano en `CHANGELOG.md` (formato Keep a Changelog, para desarrollo) y en `readme.txt` (formato WordPress, se distribuye). La versión vive en tres sitios que el workflow ya comprueba.
- Las pruebas unitarias no cargan WordPress: solo funciones puras. Existe un entorno local con SQLite para verificar el resto (`docs/qa.md`, memoria del proyecto).
- WordPress soporta actualizadores externos de forma oficial desde 5.8 con la cabecera `Update URI` y el filtro `update_plugins_{$hostname}`; WordPress decide por sí mismo si la versión devuelta es mayor que la instalada y la coloca en `response` o en `no_update`. El enlace «Ver detalles» pasa por `plugins_api`.
- Las directrices del directorio de WordPress.org (directriz 8) prohíben servir actualizaciones desde otros servidores, y exigen documentar los servicios externos en el `readme.txt`. Plugin Check (`wordpress/plugin-check-action`) es la revisión automática que aplica el directorio.

## Goals / Non-Goals

**Goals:**

- Una sola pieza de automatización: el mismo workflow hace CI en cada push y publica cuando detecta versión nueva.
- Actualizador mínimo, con la API oficial de WordPress, sin librerías de terceros, aislado para poder eliminarse.
- Menú propio sin duplicar la lógica de ajustes existente: se cambia el registro del menú, no el formulario.
- Una única fuente para el historial mostrado en Acerca de: el `readme.txt` distribuido.

**Non-Goals:**

- Publicar en WordPress.org ahora. Solo se deja el código y la documentación preparados.
- Traducir automáticamente las notas de release ni generar el changelog a partir de los commits.
- Actualizaciones desde ramas, prelanzamientos o repositorios privados con token.
- Multisitio (menú de red, actualización en red): se comporta como cualquier plugin sin soporte específico.

## Decisions

### D1. Detección del incremento de versión por ausencia de tag, en el propio workflow

El workflow se dispara en `push` a `main` (y `pull_request` a `main`, y `workflow_dispatch`). Un primer job `version` lee la versión de la cabecera con `grep`, hace `git fetch --tags` y publica dos salidas: `version` y `publicar` (`true` si no existe `refs/tags/vX.Y.Z`). El job `comprobar` (sintaxis, phpcs, pruebas, Plugin Check) corre siempre. El job `publicar` depende de ambos y solo corre con `publicar == 'true'` y evento `push`; revalida las tres versiones y la presencia del changelog, empaqueta, extrae las notas y llama a `softprops/action-gh-release@v2` con `tag_name: vX.Y.Z`, `target_commitish: ${{ github.sha }}`, `name: X.Y.Z`, `body_path` y `make_latest: true`. La acción crea el tag si no existe. `concurrency: { group: release-main, cancel-in-progress: false }` evita dos publicaciones simultáneas.

*Alternativas:* comparar la versión con el commit anterior (falla con squash o con varios commits en un push); disparar por tag manual como hasta ahora (es lo que se quiere eliminar); usar `release-please` o `semantic-release` (imponen convenciones de commit y Node, y el versionado aquí se hace a mano en tres ficheros).

### D2. Notas de la release desde CHANGELOG.md con una herramienta propia

`tools/notas-release.php X.Y.Z` imprime en Markdown la sección `## [X.Y.Z]` de `CHANGELOG.md` y termina con código 1 si no existe. La función de extracción es pura y se prueba en `tests/`. El workflow la usa tanto para validar como para generar `body_path`. Se elige `CHANGELOG.md` porque ya está en Markdown, que es lo que GitHub renderiza, mientras que `readme.txt` alimenta lo que ve el usuario dentro de WordPress. El workflow exige que ambos tengan la entrada, así no se desincronizan.

### D3. Empaquetado con exclusiones explícitas y reutilizable en local

`tools/empaquetar.php [--wordpress-org]` crea el zip en `dist/` (ignorado por git) a partir de `escritoriowp/`, excluyendo `.DS_Store`, `Thumbs.db` y `*.map`, y comprobando que la única entrada raíz es `escritoriowp/`. Con `--wordpress-org` además omite `includes/actualizaciones/`, elimina la línea `Update URI` de la cabecera y deja vacía la constante del repositorio en la copia empaquetada (nunca en el árbol de trabajo). El workflow usa la misma herramienta sin la opción, así lo que se publica en GitHub y lo que se prueba en local es idéntico. Se elige PHP y no `zip` de shell para que la variante y las comprobaciones sean portables y testables (la lista de exclusiones y la transformación de la cabecera son funciones puras).

### D4. Actualizador con `Update URI` y `update_plugins_github.com`, sin librerías

Cabecera `Update URI: https://github.com/webprogramacion/escritoriowp` y constante `ESCRITORIOWP_REPOSITORIO` con la misma URL en `escritoriowp.php`. `Plugin::arrancar()` instancia `Actualizaciones\ActualizadorGithub` solo si la constante no está vacía (esto es lo que hace trivial la variante de WordPress.org: D3 la vacía).

El módulo engancha:

- `update_plugins_github.com` (4 argumentos): si `$plugin_file === ESCRITORIOWP_BASENAME`, devuelve el array que WordPress espera (`id`, `slug: escritoriowp`, `plugin`, `version`, `url`, `package`, `requires`, `requires_php`, `tested`, `icons` vacío) a partir de la release cacheada; `false` si no hay datos. WordPress compara versiones, no el plugin.
- `plugins_api` para `plugin_information` con `slug === 'escritoriowp'`: devuelve el objeto con `name`, `slug`, `version`, `author`, `homepage`, `requires`, `requires_php`, `tested`, `last_updated`, `download_link` y `sections` (`description` desde la cabecera, `changelog` desde el cuerpo de la release pasado por `wp_kses_post( wpautop() )`). Se corta el flujo a WordPress.org con ese filtro, así «Ver detalles» funciona aunque exista otro slug igual allí.
- `admin_post_escritoriowp_buscar_actualizaciones`: comprueba `update_plugins` y nonce, borra el transitorio, llama a `wp_clean_plugins_cache( true )` y `wp_update_plugins()`, redirige a Acerca de con `&comprobado=1`.

Datos: una petición `wp_remote_get()` a `https://api.github.com/repos/webprogramacion/escritoriowp/releases/latest` con `Accept: application/vnd.github+json`, `User-Agent` con el nombre y versión del plugin, timeout 10 s. `/releases/latest` ya excluye borradores y prelanzamientos. La función pura `ActualizadorGithub::interpretar_release( array $json ): ?array` normaliza `tag_name` (quita la `v`), valida la versión con `version_compare`, elige el primer activo cuyo nombre termina en `.zip` y devuelve `version`, `paquete`, `url`, `notas`, `publicada`; `null` si falta algo. Se prueba en `tests/prueba-actualizador.php` con JSON de ejemplo.

Caché: transitorio `escritoriowp_actualizacion` con el array interpretado durante 12 h; ante error HTTP o JSON inválido se guarda `array( 'error' => true )` durante 1 h. Está cubierto por el borrado por prefijo de `uninstall.php`.

El zip publicado ya tiene `escritoriowp/` en la raíz (D3), así que no hace falta `upgrader_source_selection`: WordPress instala en la carpeta correcta y conserva ajustes y preferencias porque viven en la base de datos.

*Alternativas:* `plugin-update-checker` de YahnisElsts (dependencia de terceros que habría que copiar dentro del plugin, más de lo necesario); filtro `pre_set_site_transient_update_plugins` (la vía anterior a 5.8, sin `Update URI` WordPress.org podría ofrecer un plugin ajeno con el mismo slug).

### D5. Menú propio reutilizando `Ajustes` y una clase nueva `AcercaDe`

`Ajustes::anadir_pagina()` pasa de `add_options_page()` a `add_menu_page( 'EscritorioWP', 'EscritorioWP', 'manage_options', 'escritoriowp', pintar_pagina, 'dashicons-dashboard' )` seguido de `add_submenu_page( 'escritoriowp', …, 'escritoriowp' )` con etiqueta «Ajustes» (así la primera entrada no repite el nombre del menú) y `add_submenu_page( 'escritoriowp', …, 'escritoriowp-acerca', AcercaDe::pintar )`. Sin posición explícita: el menú queda al final, junto a los de otros plugins, y no desplaza los nativos. Los hooks devueltos por `add_menu_page`/`add_submenu_page` se guardan para encolar `ajustes.css` en ambas pantallas, sustituyendo la comparación con `settings_page_escritoriowp`. Se añade `Ajustes::url()` (`admin_url( 'admin.php?page=escritoriowp' )`) y `CatalogoComandos` la usa.

`AcercaDe` (`includes/class-acerca-de.php`) recibe `Ajustes` y, opcionalmente, el `ActualizadorGithub` (null en la variante de WordPress.org, con lo que no pinta el bloque de actualizaciones). Pinta: cabecera con nombre y versión, enlaces, bloque de estado de actualización con el botón (formulario `admin-post.php` con nonce), y «Novedades». El lanzador recoge las pantallas nuevas automáticamente porque lee `$submenu`; hay que invalidar el catálogo cacheado, cosa que el cambio de versión ya hace.

### D6. Historial desde `readme.txt` con un analizador puro

`Changelog::desde_readme( string $contenido ): array` (en `includes/class-changelog.php`) localiza la sección `== Changelog ==`, la corta en la siguiente cabecera `== … ==`, y devuelve una lista ordenada de `array( 'version' => '0.2.0', 'cambios' => array( … ) )` leyendo los bloques `= X.Y.Z =` y sus líneas `* `. Es pura y se prueba en `tests/prueba-changelog.php`. `AcercaDe` la llama sobre `file_get_contents( ESCRITORIOWP_DIR . 'readme.txt' )` y muestra el aviso «no hay historial» si devuelve vacío. El `readme.txt` ya se distribuye y ya es obligatorio para WordPress.org, así que no añade peso ni duplicidad.

*Alternativa:* distribuir `CHANGELOG.md` dentro del plugin y renderizar Markdown (harían falta un conversor y una fuente más dentro del zip).

### D7. Plugin Check en CI con exclusiones justificadas

Se añade al job `comprobar` el paso `wordpress/plugin-check-action@v1` sobre `escritoriowp/` con `ignore-warnings: false` y una lista `ignore-codes` que empieza vacía y solo crece con un comentario por código. Si Plugin Check señala la cabecera `Update URI` o la llamada a la API de GitHub, esos códigos se excluyen aquí porque la variante de WordPress.org (D3) no los contiene. La comprobación se hace también en local antes de abrir PR con el plugin Plugin Check instalado en el entorno SQLite (`wp plugin check` no está disponible sin wp-cli; se usa su clase desde un script PHP como se hace con el resto de verificaciones).

## Risks / Trade-offs

- [El zip de la release se descarga de `objects.githubusercontent.com` tras una redirección] → `download_url()` de WordPress sigue redirecciones; se verifica en la instalación local antes de publicar 0.2.0.
- [Límite de 60 peticiones/hora sin autenticación a la API de GitHub] → caché de 12 h por sitio; el botón manual borra la caché pero requiere clic humano con nonce, y el fallo se cachea 1 h.
- [Plugin Check puede marcar avisos en código ya existente (consultas directas de `uninstall.php`)] → se revisan uno a uno; los que no se puedan corregir se excluyen con justificación, nunca en bloque.
- [Si `create-escritoriowp-plugin` no se archiva antes, el delta MODIFIED de `nucleo-plugin` no tiene spec principal] → archivar primero aquel cambio; se avisa en la propuesta.
- [Un push a `main` con la versión subida pero el changelog olvidado] → el workflow falla en `publicar` antes de crear el tag; el arreglo es un commit más, sin tags que borrar.
- [Enlaces guardados a `options-general.php?page=escritoriowp`] → coste asumido; el comando del lanzador y toda la documentación se actualizan en este cambio.
- [Otro plugin en WordPress.org con slug `escritoriowp`] → la cabecera `Update URI` hace que WordPress.org ignore este plugin en las comprobaciones de actualización.

## Migration Plan

1. Fusionar el cambio en `main` con la versión ya subida a `0.2.0` en los tres sitios y el changelog escrito: ese mismo push crea el tag `v0.2.0` y la release con `escritoriowp-0.2.0.zip`.
2. Las instalaciones con `0.1.0` no se enteran solas (no tienen actualizador): se actualizan una vez a mano subiendo el zip. A partir de `0.2.0` reciben avisos.
3. Marcha atrás: borrar la release y el tag en GitHub y empujar un commit con la versión corregida. Los ajustes guardados no cambian de formato, así que volver a `0.1.0` a mano no pierde datos.
