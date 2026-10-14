## Why

Hoy publicar una versión exige empujar un tag a mano, y quien tiene el plugin instalado no se entera de que hay una versión nueva: tiene que ir a GitHub, bajar el zip y subirlo. Además la configuración vive escondida en Ajustes › EscritorioWP y el plugin no cuenta en ningún sitio qué ha cambiado con el tiempo. Queremos cerrar el ciclo completo de distribución (commit en `main` → release con zip → aviso y actualización desde el propio WordPress) y dejar el código en condiciones de enviarlo al directorio oficial de WordPress.org cuando se decida.

## What Changes

- **Release automática desde `main`.** El workflow de GitHub Actions deja de dispararse por tags manuales: en cada push a `main` comprueba lint, pruebas y Plugin Check; si la versión de la cabecera del plugin no tiene todavía su tag `vX.Y.Z`, verifica que las tres versiones coinciden y que hay entrada de changelog, crea el tag y publica una release con el zip `escritoriowp-X.Y.Z.zip` listo para subir a WordPress y con las notas tomadas de `CHANGELOG.md`.
- **Actualizaciones desde GitHub dentro de WordPress.** El plugin declara `Update URI: https://github.com/webprogramacion/escritoriowp` y se integra en el mecanismo estándar de actualizaciones de WordPress: consulta la última release pública del repositorio (con caché de 12 horas), muestra el aviso «Hay una nueva versión» en Plugins y en Actualizaciones, ofrece «Ver detalles» con el changelog y permite actualizar con un clic descargando el zip de la release. Nunca hace peticiones si no hay una nueva versión que ofrecer más allá de la comprobación periódica que ya hace WordPress.
- **Menú propio «EscritorioWP»** en la barra lateral del administrador con dos pantallas, ambas para `manage_options`:
  - **Ajustes**: la pantalla actual (con la casilla que activa o desactiva el lanzador, la paleta nativa, el escritorio, etc.) se traslada desde Ajustes › EscritorioWP al menú propio. **BREAKING** para quien tenga la URL antigua guardada: `options-general.php?page=escritoriowp` deja de existir y pasa a ser `admin.php?page=escritoriowp`.
  - **Acerca de**: versión instalada, enlaces al repositorio, estado de la actualización con botón «Buscar actualizaciones ahora» y la lista de novedades de todas las versiones, leída del changelog que el propio plugin distribuye en `readme.txt` (una única fuente de verdad).
- **Cumplimiento continuo de las normas de WordPress.org.** Plugin Check (la herramienta oficial de revisión del directorio) pasa a ejecutarse en CI junto a phpcs y no admite errores; el `readme.txt` documenta el servicio externo (la API de GitHub) como exigen las directrices; se documenta en `docs/` la lista de pasos para enviar el plugin al directorio oficial. El actualizador desde GitHub queda aislado en un módulo propio y se desactiva quitando una constante y una línea de cabecera, porque las directrices del directorio **prohíben servir actualizaciones desde fuera de WordPress.org** (directriz 8): la versión que se envíe allí no lo incluirá.
- Se sube la versión a **0.2.0**: esa publicación será la primera prueba real del flujo completo.

## Capabilities

### New Capabilities

- `publicacion-github`: flujo de integración continua y publicación automática de releases en GitHub al detectar un incremento de versión en `main`, con el zip instalable y las notas de la versión.
- `actualizaciones-github`: detección, presentación e instalación de versiones nuevas desde las releases públicas de GitHub a través del mecanismo de actualizaciones de WordPress.
- `menu-administracion`: menú propio del plugin en el administrador con las pantallas Ajustes y Acerca de, incluida la presentación del historial de cambios.
- `cumplimiento-wordpress-org`: requisitos que el código y el empaquetado deben cumplir de forma continua para poder enviarse al directorio oficial de WordPress.org.

### Modified Capabilities

- `nucleo-plugin`: el requisito «Página de ajustes» cambia de ubicación: deja de ser una subpágina de Ajustes y pasa a ser la primera pantalla del menú propio del plugin. Se añade la declaración `Update URI` a la cabecera. Nota: esta capacidad está definida en el cambio `create-escritoriowp-plugin`, todavía no archivado; ese cambio debe archivarse antes que este para que el delta MODIFIED tenga una spec principal sobre la que aplicarse.

## Impact

- **Repositorio (no distribuible):** `.github/workflows/release.yml` (reescrito), `tools/notas-release.php` (nuevo), `tests/prueba-changelog.php` y `tests/prueba-actualizador.php` (nuevos), `docs/qa.md`, `docs/publicar-wordpress-org.md` (nuevo), `CLAUDE.md`, `README.md`, `CHANGELOG.md`.
- **Plugin:** `escritoriowp.php` (cabecera `Update URI`, constante del repositorio, versión), `includes/class-plugin.php` (composición del actualizador y de Acerca de), `includes/class-ajustes.php` (menú de primer nivel en lugar de `add_options_page`), `includes/class-acerca-de.php` y `includes/class-changelog.php` (nuevos), `includes/actualizaciones/class-actualizador-github.php` (nuevo), `includes/lanzador/class-catalogo-comandos.php` (URL del comando de ajustes), `includes/class-activos.php` (encolado en las dos pantallas), `assets/css/ajustes.css`, `readme.txt` (sección de servicios externos, changelog, referencias al menú), `languages/escritoriowp.pot`, `uninstall.php` (borra el transitorio nuevo, ya cubierto por el prefijo).
- **Dependencias externas:** la API pública de GitHub (`api.github.com/repos/webprogramacion/escritoriowp/releases/latest`, sin autenticación, límite de 60 peticiones/hora por IP que la caché de 12 h hace irrelevante) y la acción `wordpress/plugin-check-action` en CI. Ninguna dependencia nueva en tiempo de ejecución.
- **Compatibilidad:** el filtro `update_plugins_{hostname}` existe desde WordPress 5.8; el plugin exige 6.7. Los ajustes guardados no cambian de nombre ni de formato. La URL antigua de la pantalla de ajustes deja de funcionar.
