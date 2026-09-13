# CLAUDE.md · Memoria del proyecto EscritorioWP

Este fichero es la referencia de arquitectura y convenciones del repositorio. Léelo antes de tocar
código.

## 0. Regla de oro

**Todo lo que se sube a WordPress vive en `escritoriowp/`.** La raíz del repositorio es material de
desarrollo y nunca se distribuye. Si creas un fichero nuevo, pregúntate si acompaña al plugin en
`wp-content/plugins/`: si no, va en la raíz.

## 1. Qué hace el plugin

1. **Sustituye la pantalla del Escritorio** por una interfaz propia: indicadores, paneles con los
   últimos elementos y creación rápida en ventana.
2. **Sustituye la paleta de comandos de wp-admin** por un lanzador propio (Comando+K / Control+K)
   que, además de las pantallas del menú, busca contenido real del sitio.

WooCommerce es opcional: si está activo añade productos y pedidos.

## 2. Estructura

```
<raíz>                          Material de desarrollo. NO se sube.
├── CLAUDE.md                   Este fichero.
├── README.md  CHANGELOG.md  BACKLOG.md
├── composer.json               Solo herramientas de desarrollo (phpcs, wpcs, i18n).
├── phpcs.xml.dist              Estándar WordPress + PHPCompatibility 8.1-.
├── .github/workflows/release.yml   Lint + pruebas + zip al empujar un tag «vX.Y.Z».
├── openspec/                   Specs y propuestas de cambio.
├── tests/                      Pruebas unitarias sin WordPress (php y node).
├── tools/generar-pot.php       Genera el .pot sin wp-cli.
├── docs/qa.md                  Lista de comprobación manual.
└── escritoriowp/               EL PLUGIN
    ├── escritoriowp.php        Cabecera, constantes, requisitos, autoloader, HPOS, arranque.
    ├── uninstall.php           Borra opción, metadatos y transitorios.
    ├── readme.txt              Ficha y changelog para WordPress.
    ├── includes/
    │   ├── class-autoloader.php      PSR-4 manual sobre includes/.
    │   ├── class-plugin.php          Singleton que compone los módulos.
    │   ├── class-ajustes.php         Opción, defaults, saneado y pantalla de ajustes.
    │   ├── class-capacidades.php     Mapa tipo → capacidad, URLs, etiquetas e iconos.
    │   ├── class-activos.php         Registro de CSS/JS y catálogo de textos traducibles.
    │   ├── escritorio/               Escritorio, Resumen, Recientes, Preferencias.
    │   ├── lanzador/                 Lanzador y CatalogoComandos.
    │   ├── busqueda/                 Fuente (interfaz), Buscador, Grupo, Resultado, 4 fuentes.
    │   ├── creacion/                 Creador, Errores y un creador por tipo.
    │   ├── rest/                     Rest y cinco controladores.
    │   └── woo/class-woo.php         Todo lo que depende de WooCommerce.
    ├── assets/css/  comun.css (tokens) · escritorio.css · lanzador.css · ajustes.css
    ├── assets/js/   comun.js (utilidades) · escritorio.js · lanzador.js
    └── languages/escritoriowp.pot
```

## 3. Convenciones de PHP

- `declare(strict_types=1);` en todos los ficheros; `defined( 'ABSPATH' ) || exit;` después.
- Espacio de nombres `EscritorioWP\`, con subespacios por carpeta.
- Autoloader propio: `EscritorioWP\Busqueda\FuenteUsuarios` →
  `includes/busqueda/class-fuente-usuarios.php`. CamelCase → kebab-case, prefijos `class-`,
  `interface-` o `trait-`. **Sin Composer en producción.**
- Prefijo `escritoriowp_` / `ESCRITORIOWP_` para todo lo global: opciones, metadatos, transitorios,
  filtros y constantes.
- Nombres de clases, métodos, variables y comentarios **en español**. Métodos en `snake_case`.
- Estándar de codificación: WordPress (tabuladores, comparaciones Yoda, escapado en la salida).
  `composer lint` lo comprueba.
- PHP 8.1: se usan `match`, propiedades promovidas, `readonly` y tipos de unión.

## 4. Convenciones de JavaScript y CSS

- **Sin paso de compilación.** Nada de npm, React ni bundlers: el CSS y el JS se escriben a mano y
  se sirven tal cual. Lo que hay en `assets/` es exactamente lo que llega al navegador.
- Scripts clásicos en ES2020 envueltos en una IIFE que recibe `window` (o `globalThis`, para poder
  cargarlos en Node desde las pruebas).
- **Ningún texto visible vive en el JavaScript.** Todos los textos llegan traducidos desde PHP en
  `Activos::textos()` y se leen con `EWP.t( 'clave' )` o `EWP.tf( 'clave', valor )`. Si añades un
  texto al JS, añade su clave al catálogo.
- El DOM se construye con `EWP.crear()`, que asigna el contenido como texto y nunca como HTML.
  No uses `innerHTML` con datos del sitio.
- CSS con tokens en `comun.css` (`--ewp-*`), definidos para el tema claro y redefinidos para
  `[data-tema="oscuro"]` y para `prefers-color-scheme: dark` cuando el tema es `auto`.

## 5. Control de acceso

`Capacidades` es la fuente única de verdad. Toda pantalla, panel, botón, comando y ruta REST
comprueba la capacidad del tipo antes de mostrar o devolver nada. **El servidor nunca confía en lo
que muestre la interfaz**: cada ruta REST repite la comprobación en su `permission_callback`.

| Tipo | Ver | Crear | Publicar |
|------|-----|-------|----------|
| entradas | `edit_posts` | `edit_posts` | `publish_posts` |
| paginas | `edit_pages` | `edit_pages` | `publish_pages` |
| usuarios | `list_users` | `create_users` | — |
| productos | `edit_products` | `edit_products` | `publish_products` |
| pedidos | `edit_shop_orders` | `edit_shop_orders` | — |
| comentarios | `moderate_comments` | — | — |
| actualizaciones | `update_core`, `update_plugins` o `update_themes` | — | — |

## 6. Cómo se sustituye el escritorio

En `wp_dashboard_setup` con prioridad `PHP_INT_MAX` (para llegar después de todos los plugins):

1. Se quita `wp_welcome_panel`.
2. Se vacía `$wp_meta_boxes['dashboard']`: siempre los widgets de WordPress y WooCommerce; los de
   terceros solo si el ajuste lo pide. Los que se conservan se mueven a `normal`/`low` para que
   queden juntos bajo el título «Otros widgets».
3. Se registra la interfaz como una caja (`add_meta_box`) en `normal`/`high`. **Se usa una caja del
   escritorio a propósito**: así los avisos de administración, el título de la pantalla y el resto
   de enganches de WordPress siguen funcionando sin tocar la estructura de la página. El CSS le
   quita el marco, la cabecera y los controles.
4. Se fuerza una columna con `get_user_option_screen_layout_dashboard` y se ocultan las opciones de
   pantalla.

## 7. Cómo se desactiva la paleta nativa

La paleta de todo wp-admin existe desde **WordPress 6.9**. La encola
`wp_enqueue_command_palette_assets()` en `admin_enqueue_scripts` con prioridad 10 y **no hay filtro
oficial para desactivarla**. El plugin la quita con `remove_action(...)` desde
`Lanzador::preparar_admin()`, enganchado con prioridad 1.

Dos detalles que no se pueden perder de vista:

- **Nunca dentro del editor de bloques.** Desde Gutenberg, el único sitio que monta la paleta es
  `initializeCommandPalette` del paquete `core-commands`; si se quita ahí, el editor se queda sin
  paleta. Por eso `en_editor_bloques()` corta antes.
- Al no encolarse `wp-core-commands`, el botón ⌘K nativo de la barra de administración (añadido en
  WordPress 7.0) desaparece solo, porque su callback comprueba `wp_script_is( 'wp-core-commands' )`.

## 8. Cómo añadir cosas

**Una fuente de búsqueda nueva**: implementa `EscritorioWP\Busqueda\Fuente` en
`includes/busqueda/class-fuente-x.php` y engánchala al filtro `escritoriowp_fuentes_busqueda`. El
orden del array es el orden de los grupos en el lanzador.

**Un panel nuevo en el escritorio**: añade el tipo a `Capacidades::MAPA` y a
`Capacidades::TIPOS_PANEL`, y devuélvelo en `Recientes::obtener()`. Añade sus textos de estado vacío
(`panelVacioX`, `crearPrimeroX`) al catálogo de `Activos::textos()`.

**Un comando fijo del lanzador**: añádelo en `CatalogoComandos::comandos_accion()`, siempre
condicionado a una capacidad. Para comandos desde otro plugin, usa el filtro `escritoriowp_comandos`.

**Un ajuste nuevo**: añádelo a `Ajustes::defectos()` (el saneado y el formulario lo recogen solos) y
declara su campo en `Ajustes::registrar_ajustes()`. Las casillas necesitan su campo oculto con
valor `0`, que ya pinta `pintar_campo()`.

## 9. Cachés

| Clave | Qué guarda | Duración | Se invalida |
|-------|-----------|----------|-------------|
| `escritoriowp_resumen_woo` | Productos, pedidos e ingresos del mes | 5 min | Botón «Actualizar», crear un producto |
| `escritoriowp_comandos_{id}` | Catálogo de comandos del usuario | 12 h | Cambio de versión del plugin |

El catálogo se cachea porque en el sitio público no existen `$menu` ni `$submenu`. Si no hay caché,
el lanzador del front-end ofrece solo las acciones fijas y la búsqueda de contenido.

## 10. Pruebas

```bash
php tests/ejecutar.php              # funciones puras de PHP
node tests/ejecutar.js              # funciones puras de JavaScript
node tools/comprobar-contraste.js   # contraste AA de la paleta en ambos temas
composer lint                       # phpcs con el estándar WordPress
find escritoriowp -name '*.php' -print0 | xargs -0 -n1 php -l
find escritoriowp/assets/js -name '*.js' -print0 | xargs -0 -n1 node --check
```

`composer test` ejecuta las tres primeras de una vez. `phpcs` debe terminar **sin errores ni
avisos**; si añades una consulta directa a la base de datos o tocas un global de WordPress, justifica
el `phpcs:ignore` con un comentario que explique por qué es la única vía.

Las pruebas unitarias **no cargan WordPress**: solo cubren funciones puras (saneado de ajustes y
preferencias, resolución de URLs del menú, cálculo de ingresos, agregación de fuentes, puntuación de
coincidencias). Todo lo demás se comprueba con `docs/qa.md` sobre una instalación real.

Para levantar una instalación de pruebas rápida no hace falta MySQL ni Docker: descarga WordPress y
el plugin `sqlite-database-integration`, copia su `db.copy` a `wp-content/db.php` sustituyendo los
dos marcadores, instala con `wp_install()` desde un script de PHP CLI y usa el servidor interno de
PHP. Con un script que haga `require wp-load.php`, fije el usuario con `wp_set_current_user()` y
llame a `rest_do_request()` se verifica toda la capa de servidor sin navegador: capacidades por rol,
códigos de estado y agregación de la búsqueda. La pantalla del escritorio se pinta llamando a
`wp_dashboard_setup()` y `do_meta_boxes( 'dashboard', 'normal', '' )`.

Limitación conocida de ese entorno: WooCommerce no crea sus tablas de pedidos sobre SQLite, así que
**el camino con HPOS activo no se puede probar ahí**; hay que usar una instalación con MySQL.

## 11. Publicar una versión

1. Actualiza la versión en **tres sitios**: la cabecera de `escritoriowp/escritoriowp.php`, la
   constante `VERSION` del mismo fichero y `Stable tag` en `escritoriowp/readme.txt`.
2. Añade el changelog en `CHANGELOG.md` y en `readme.txt`.
3. Regenera el `.pot`: `php tools/generar-pot.php`.
4. Commit `Release X.Y.Z` y empuja el tag `vX.Y.Z`. El workflow comprueba que las tres versiones
   coinciden, pasa lint y pruebas y publica el zip de `escritoriowp/`.
