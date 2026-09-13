## Context

Proyecto nuevo (greenfield): el repositorio solo contiene `openspec/` y la configuración de agentes. No hay código, ni specs previas, ni entorno WordPress local detectado en la máquina (no hay Docker, `wp-cli` ni Composer instalados; sí PHP 8.5 y Node 26).

Convenciones heredadas de los plugins hermanos del mismo autor (`inscripciones-deportivas`, `condiciones-contratacion-woocommerce`):

- Repositorio en dos niveles: raíz = material de desarrollo; subcarpeta con el slug = exactamente el plugin que se sube a WordPress (zip de esa carpeta en CI).
- PHP con `declare(strict_types=1)`, namespace propio, autoloader PSR-4 escrito a mano sobre `includes/`, singleton `Plugin` arrancado en `plugins_loaded`, sin Composer en producción.
- Sin herramientas de build de frontend (ni npm, ni React): CSS y JS escritos a mano y servidos tal cual.
- Español como idioma de origen de textos, comentarios y documentación; `CLAUDE.md` en raíz como memoria del proyecto; `readme.txt` con changelog; SemVer con versión en cabecera, constante y `Stable tag`.
- WooCommerce: declaración HPOS en `before_woocommerce_init`, acceso a pedidos solo mediante CRUD (`wc_get_order`, `wc_get_orders`).

Hechos de WordPress relevantes (verificados en `wordpress-develop` trunk, septiembre 2026; WP estable 7.1):

- La paleta de comandos de todo wp-admin existe desde WordPress 6.9. Se encola con `wp_enqueue_command_palette_assets()` enganchada a `admin_enqueue_scripts` (prioridad 10), que carga `wp-commands` + `wp-core-commands` y ejecuta `wp.coreCommands.initializeCommandPalette()`. En 7.0 se añadió el botón ⌘K de la barra de administración (`wp_admin_bar_command_palette_menu`, prioridad 55 en `admin_bar_menu`), que solo se pinta si `wp-core-commands` está encolado.
- No existe filtro ni opción oficial para desactivarla. La vía limpia (la que usa el propio Gutenberg) es `remove_action( 'admin_enqueue_scripts', 'wp_enqueue_command_palette_assets' )`.
- El único punto que renderiza la paleta en producción es `initializeCommandPalette` (paquete `core-commands`): el editor de entradas y el editor del sitio ya no montan la suya. Por tanto quitar el encolado en pantallas de editor eliminaría también la paleta del editor; hay que quitarlo solo fuera del editor.
- El atajo nativo es `primary+k` registrado como `core/commands` en `core/keyboard-shortcuts`.
- Búsqueda de usuarios: `WP_User_Query` con `search_columns` explícitas permite buscar por email sin depender de la heurística de la `@`. WooCommerce: `wc_order_search()` delega en el data store activo (funciona con HPOS y con CPT); `search_products()` del data store de productos busca por nombre, extracto, contenido y SKU (incluidas variaciones) usando la tabla `wc_product_meta_lookup`.
- `@wordpress/scripts` sigue siendo la herramienta estándar de build, pero no es necesaria: no vamos a usarla (ver decisiones).

## Goals / Non-Goals

**Goals:**

- Plugin instalable copiando `escritoriowp/`, sin build ni `vendor/`.
- Arquitectura modular en PHP que permita añadir nuevas fuentes de búsqueda y nuevos paneles con una clase por fuente.
- Interfaz rápida: HTML esqueleto servido por PHP, datos por REST en paralelo, sin parpadeo del escritorio nativo.
- Cero dependencias JS externas; código vanilla ES2020 legible.
- Desactivación de la paleta nativa sin romper el editor de bloques ni plugins que dependan de `wp-commands`.

**Non-Goals:**

- Sustituir la pantalla de escritorio en red multisitio (`network/index.php`).
- Reordenar paneles con arrastrar y soltar (la reordenación se hace con controles arriba/abajo en el menú Personalizar).
- Búsqueda de medios, comentarios y términos de taxonomía (posible fuente futura).
- Creación completa de pedidos desde el modal (se enlaza a la pantalla nativa).
- Traducciones distintas del español (se distribuye el `.pot`, no ficheros `.po`).
- Registrar nuestros comandos dentro del store `core/commands` del editor.

## Decisions

### D1. Estructura del repositorio y del plugin

```
<raíz>                         Material de desarrollo. No se sube.
├── CLAUDE.md  README.md  CHANGELOG.md  BACKLOG.md
├── composer.json  phpcs.xml.dist          (solo linting: phpcs + WPCS + PHPCompatibilityWP + wp-cli/i18n)
├── .github/workflows/release.yml          (zip de escritoriowp/ al hacer push de tag)
├── openspec/
└── escritoriowp/                          EL PLUGIN
    ├── escritoriowp.php                   cabecera, constantes, comprobación de requisitos, autoloader, arranque
    ├── uninstall.php
    ├── readme.txt
    ├── includes/
    │   ├── class-plugin.php               singleton, registra módulos
    │   ├── class-autoloader.php
    │   ├── class-ajustes.php              opción, defaults, saneado, página Ajustes › EscritorioWP
    │   ├── class-capacidades.php          mapa tipo → capacidad, helpers de acceso
    │   ├── class-activos.php              registro/encolado de CSS/JS y datos bootstrap
    │   ├── escritorio/
    │   │   ├── class-escritorio.php       sustitución del escritorio nativo, esqueleto HTML
    │   │   ├── class-resumen.php          KPIs (con caché)
    │   │   ├── class-recientes.php        últimos elementos por tipo
    │   │   └── class-preferencias.php     preferencias por usuario (user meta)
    │   ├── lanzador/
    │   │   ├── class-lanzador.php         desactivar paleta nativa, encolar lanzador, botón barra admin
    │   │   └── class-catalogo-comandos.php  menú admin + acciones fijas → JSON (+ caché por usuario)
    │   ├── busqueda/
    │   │   ├── interface-fuente.php       contrato: disponible(), puede_buscar(), buscar(q, limite): Resultado[]
    │   │   ├── class-buscador.php         agrega fuentes, aplica tipos/límites
    │   │   ├── class-resultado.php        DTO → array JSON
    │   │   ├── class-fuente-contenidos.php  entradas / páginas / otros CPT
    │   │   ├── class-fuente-usuarios.php
    │   │   ├── class-fuente-productos.php
    │   │   └── class-fuente-pedidos.php
    │   ├── creacion/
    │   │   ├── class-creador.php          despacha por tipo, valida, crea
    │   │   └── class-creador-{entrada,pagina,usuario,producto}.php
    │   ├── rest/
    │   │   ├── class-rest.php             registra rutas escritoriowp/v1
    │   │   └── class-controlador-{busqueda,resumen,recientes,creacion,preferencias}.php
    │   └── woo/
    │       └── class-woo.php              detección, declaración HPOS, helpers (URL nuevo pedido, formato precio)
    ├── assets/
    │   ├── css/  comun.css  escritorio.css  lanzador.css  ajustes.css
    │   └── js/   comun.js   escritorio.js   lanzador.js
    └── languages/escritoriowp.pot
```

Namespace `EscritorioWP\` con subespacios por carpeta (`EscritorioWP\Busqueda\FuenteUsuarios` → `includes/busqueda/class-fuente-usuarios.php`), siguiendo el autoloader de `inscripciones-deportivas`. Prefijo para hooks, opciones, metas y transitorios: `escritoriowp_`. Constantes: `ESCRITORIOWP_VERSION`, `_FILE`, `_DIR`, `_URL`, `_BASENAME`.

*Alternativa descartada*: plugin plano en la raíz (convención antigua de `matriculador-wp-moodle`); dificulta el empaquetado y contradice la petición del usuario.

### D2. Frontend vanilla sin paso de build

JS en ES2020 como scripts clásicos (IIFE) encolados con `wp_enqueue_script`, sin dependencias de paquetes `@wordpress/*` ni React. Cada script recibe su configuración (URLs, nonce REST, capacidades, textos ya traducidos con `__()`, preferencias, catálogo de comandos) mediante `wp_add_inline_script( ..., 'before' )` con un objeto `window.escritoriowpConfig`. Las llamadas REST usan `fetch` con cabecera `X-WP-Nonce`. `comun.js` aporta utilidades compartidas (peticiones, escape HTML, normalización sin acentos, detección de plataforma para ⌘/Ctrl, plantillas con `template` literals, gestión de foco).

*Por qué*: coherente con todos los repos del autor (ningún `package.json`), mantiene `escritoriowp/` = artefacto final y elimina el paso "compilar antes de subir". La complejidad de la UI (paneles, modales, paleta con teclado) es abordable con un patrón sencillo de "estado → render" por componente.

*Alternativa considerada*: React vía `wp-element` + `@wordpress/scripts`. Aporta componentes reutilizables y `@wordpress/components`, pero introduce build, `node_modules/` y un estilo visual difícil de personalizar; el autor lo tiene explícitamente vetado en otro proyecto.

### D3. Cómo se sustituye el escritorio nativo

En `wp_dashboard_setup` (prioridad `PHP_INT_MAX`, para ejecutarse después de que otros plugins registren sus widgets), si el ajuste está activo:

1. Se eliminan los metaboxes nativos (`dashboard_right_now`, `dashboard_activity`, `dashboard_quick_press`, `dashboard_primary`, `dashboard_site_health`, `dashboard_php_nag`, `dashboard_browser_nag`, `dashboard_incoming_links`, `dashboard_plugins`, `dashboard_recent_drafts`, `dashboard_recent_comments`, `wc_admin_dashboard_setup` y `woocommerce_dashboard_status`/`_recent_reviews`), y, si "Ocultar widgets de otros plugins" está activo, cualquier otro que quede en `$wp_meta_boxes['dashboard']`.
2. Se quita el panel de bienvenida (`remove_action( 'welcome_panel', 'wp_welcome_panel' )`).
3. Se fuerza el diseño a una columna (filtro `get_user_option_screen_layout_dashboard` → 1) y se oculta "Opciones de pantalla" (`screen_options_show_screen` → false) solo en esa pantalla.
4. Se registra la interfaz como una caja del propio escritorio (`add_meta_box( 'escritoriowp-app', ..., 'dashboard', 'normal', 'high' )`), que imprime el esqueleto HTML: cabecera ya renderizada en PHP, tarjetas y paneles en estado "cargando". Los widgets de terceros que se conserven se mueven al contexto `normal` con prioridad `low`, de modo que quedan juntos bajo la cabecera "Otros widgets" que imprime la propia caja. El CSS le quita el marco, la cabecera y los controles de la caja.
5. Se añade la clase `escritoriowp-activo` al `body` para el CSS de ancho completo.

*Por qué una caja y no un enganche de impresión directo*: `wp_dashboard()` no dispara ninguna acción entre sus contenedores (la acción `do_meta_boxes` la disparan otras pantallas, no el escritorio), así que la única forma de insertar contenido sin reescribir la plantilla es registrar una caja. Además, así los avisos de administración, el título de la pantalla y el resto de enganches de WordPress siguen funcionando sin tocar la estructura de la página.

Los avisos de administración siguen funcionando porque no tocamos `.wrap` ni el `<h1>` que WordPress usa como ancla para moverlos.

*Alternativa descartada*: redirigir `index.php` a una página propia `admin.php?page=escritoriowp`. Rompe el resaltado del menú, los enlaces "Escritorio" de otros plugins y la ubicación de los avisos.

### D4. Carga de datos del escritorio

La configuración y las capacidades se inyectan inline; los datos (resumen y cada panel de recientes) se piden por REST en paralelo tras el `DOMContentLoaded`, cada uno con su propio estado de carga, error y "Reintentar", de modo que un fallo en WooCommerce no bloquea el panel de entradas. Los KPIs de pedidos e ingresos se calculan con `wc_get_orders` (mes actual, estados `completed` y `processing`) y se guardan en el transitorio `escritoriowp_resumen_woo` durante 5 minutos; el botón "Actualizar" envía `?refrescar=1` que invalida el transitorio. El resto de KPIs usa `wp_count_posts`, `count_users` (o `wp_count_users` si existe), `wp_count_comments` y `wp_get_update_data`, que ya están cacheados o son baratos.

### D5. Rutas REST

Namespace `escritoriowp/v1`, autenticación por cookie + nonce `wp_rest`, `permission_callback` por ruta con la capacidad del tipo (tabla en `Capacidades`):

| Ruta | Método | Permiso | Devuelve |
|------|--------|---------|----------|
| `/buscar` | GET `q`, `tipos[]`, `limite` | usuario autenticado; cada fuente filtra por su capacidad | grupos de resultados |
| `/resumen` | GET `refrescar` | `read` (cada KPI se omite sin su capacidad) | KPIs |
| `/recientes/(entradas\|paginas\|usuarios\|productos\|pedidos)` | GET `limite` | capacidad del tipo | filas del panel |
| `/crear/(entrada\|pagina\|usuario\|producto)` | POST JSON | capacidad de creación del tipo | elemento creado + URLs |
| `/preferencias` | GET / POST | `read` | preferencias del usuario actual |

Errores con `WP_Error` y códigos HTTP 400 (validación, con `data.campos` por campo), 401/403 (acceso), 500 (inesperado). Nunca se devuelve HTML sin escapar: los títulos se pasan por `wp_strip_all_tags` + `html_entity_decode` y el JS los inserta como texto.

### D6. Fuentes de búsqueda

Cada fuente implementa `Fuente` (`clave()`, `etiqueta()`, `disponible()`, `puede_buscar()`, `buscar( string $q, int $limite ): Grupo`). `Buscador` recorre las fuentes solicitadas y disponibles, ejecuta las que superan `puede_buscar()` y devuelve los grupos en el orden fijo de la spec.

- **Contenidos** (tres instancias: entradas, páginas, otros): `WP_Query` con `post_type` según la instancia (para "otros": tipos con `show_ui` excluyendo `attachment`, `revision`, `nav_menu_item`, `wp_block`, `wp_template*`, `wp_navigation`, `wp_font_*`, `product*`, `shop_*`), `post_status` = estados con `show_in_admin_all_list` excepto `trash`/`auto-draft`, `posts_per_page = limite`, `no_found_rows = false` para el total. La coincidencia solo por título y la restricción "borradores ajenos solo si `edit_others_posts`" se implementan con un filtro `posts_where` propio (con `$wpdb->prepare` y `$wpdb->esc_like`) que se añade y quita alrededor de la consulta; se ordena con `posts_orderby` para priorizar `post_title LIKE 'q%'` y después `post_modified DESC`. Es un uso sancionado de la API de consulta, no SQL directo sobre tablas.
- **Usuarios**: `WP_User_Query` con `search => "*{$q}*"`, `search_columns => [ user_login, user_email, user_nicename, display_name ]`, `number = limite`, `count_total = true`, `orderby => display_name`.
- **Productos**: `WC_Data_Store::load( 'product' )->search_products( $q, '', true, true, $limite * 3 )` → IDs (variaciones incluidas), se mapean a su padre con `wp_get_post_parent_id`, se deduplican, se recortan a `limite` y se hidratan con `wc_get_product`. El total es el número de IDs únicos.
- **Pedidos**: si `q` (sin `#`) es numérico se intenta `wc_get_order( (int) $q )` y se antepone; después `wc_order_search( $q )` → IDs → `wc_get_orders([ 'post__in'/'id' => ids, 'orderby' => 'date', 'order' => 'DESC', 'limit' => limite ])` (`wc_get_orders` acepta `post__in` en ambos almacenamientos vía `WC_Order_Query`). Funciona con HPOS y con CPT sin ramificar.

### D7. Desactivación de la paleta nativa y carga del lanzador

`Lanzador` engancha `admin_enqueue_scripts` en prioridad 1:

```php
$en_editor = ( $screen && $screen->is_block_editor() ) || 'site-editor.php' === $GLOBALS['pagenow'];
if ( $ajustes->paleta_nativa_desactivada() && ! $en_editor && function_exists( 'wp_enqueue_command_palette_assets' ) ) {
    remove_action( 'admin_enqueue_scripts', 'wp_enqueue_command_palette_assets' );
}
if ( $ajustes->lanzador_activo() && ! $en_editor ) { /* encolar comun.js + lanzador.js + css */ }
```

`is_block_editor()` ya está fijado cuando se dispara `admin_enqueue_scripts` (lo hace `edit-form-blocks.php` antes de `admin-header.php`). Al no encolarse `wp-core-commands`, el botón ⌘K nativo de la barra de administración (7.0+) desaparece solo; nuestro botón se registra en `admin_bar_menu` prioridad 56 con `id = escritoriowp-lanzador`, tanto en admin como en el front-end. En el front-end el lanzador se encola en `wp_enqueue_scripts` cuando `is_user_logged_in() && is_admin_bar_showing()` y el ajuste lo permite.

El atajo se captura con un `keydown` en fase de captura sobre `document`: `(e.metaKey || e.ctrlKey) && !e.altKey && !e.shiftKey && e.key.toLowerCase() === 'k'` → `preventDefault()` + `stopPropagation()` + alternar. Se acepta desde campos de texto (comportamiento habitual de las paletas). En versiones anteriores a 6.9 no hay nada que desactivar y el bloque `remove_action` es inocuo.

*Alternativa considerada*: `wp_deregister_script( 'wp-core-commands' )`. Descartada porque rompe plugins que declaran esa dependencia (WooCommerce registra comandos propios) y porque `remove_action` es lo que hace el propio Gutenberg.

### D8. Catálogo de comandos

`CatalogoComandos` construye la lista en admin a partir de `$menu` y `$submenu` (ya poblados antes de `admin_enqueue_scripts`), replicando la lógica de `wp_enqueue_command_palette_assets`: descarta separadores, comprueba `current_user_can( $item[1] )`, limpia el título con `wp_strip_all_tags` (quita contadores `<span class="update-plugins">`), resuelve la URL (`.php` directo vs `admin.php?page=`, respetando `$item[2]` de submenús con `parent_file`) y construye la ruta "Padre › Hijo" con el icono Dashicon del padre. Añade las acciones fijas condicionadas a capacidades. El resultado se serializa en el bootstrap y se guarda en el transitorio `escritoriowp_comandos_{user_id}` (12 h) para reutilizarlo en el front-end, donde `$menu` no existe; si no hay caché en el front-end el lanzador ofrece solo acciones fijas y búsqueda de contenido, y se hidrata en la siguiente visita al admin.

Dos detalles de la resolución de URLs, aprendidos al contrastarla con el menú real de WooCommerce: el slug se concatena **sin codificar**, igual que hace `menu_page_url()`, porque algunos plugins registran slugs que ya incluyen parámetros (`wc-admin&path=/customers`); y la URL resultante se pasa por `html_entity_decode()` antes de serializarla, porque otros registran el slug con `&amp;` y el navegador la asigna sin decodificar.

La coincidencia de comandos es en el cliente: normalización NFD sin diacríticos y en minúsculas, y puntuación por (a) prefijo de palabra, (b) subcadena, (c) todas las palabras del query presentes. El contexto (el menú padre) puntúa un 10 % menos que el título, de modo que "Plugins" siempre gana a "Plugins › Añadir nuevo". Los recientes se guardan en `localStorage` bajo `escritoriowp:recientes:{user_id}`.

### D9. Creación rápida

`Creador` recibe `tipo` + payload, elige el `Creador{Tipo}` y devuelve `Resultado` o `WP_Error` con errores por campo. Entradas/páginas: `wp_insert_post` con `post_status` validado contra `publish_*`; usuarios: `wp_insert_user` con contraseña `wp_generate_password( 24 )`, rol validado contra `get_editable_roles()` y `wp_new_user_notification( $id, null, 'user' )` si se pidió el email; productos: `WC_Product_Simple` con `set_name/set_regular_price/set_sku/set_status` y `save()`, validando SKU con `wc_product_has_unique_sku`. Tras crear, el JS invalida el panel correspondiente y lo recarga.

### D10. Preferencias y tema

Preferencias por usuario en el meta `escritoriowp_preferencias` (array: `paneles_ocultos`, `orden_paneles`, `tema` ∈ {claro, oscuro, auto}). El tema se aplica con `data-tema` en `#escritoriowp-app` y en el contenedor del lanzador; `auto` usa `@media (prefers-color-scheme: dark)`. Los tokens de diseño (colores, radios, sombras, tipografía del sistema) viven en `comun.css` como custom properties, con paleta clara/oscura completa y contraste AA. El estilo visual: tarjetas con radio 12 px, sombras suaves, cabecera con degradado sutil, etiquetas de estado con color semántico (publicado verde, borrador ámbar, pendiente azul, pedidos según estado WooCommerce), animaciones cortas (150–200 ms) respetando `prefers-reduced-motion`.

### D11. Calidad y verificación sin entorno WordPress local

- Sintaxis: `php -l` sobre todos los ficheros PHP (PHP 8.5 local) y `node --check` sobre los JS.
- Estilo: `phpcs.xml.dist` con WordPress + PHPCompatibilityWP (`testVersion 8.1-`), prefijo `escritoriowp` y dominio de texto obligatorio; se ejecuta cuando Composer esté disponible (CI lo instala).
- Pruebas unitarias ligeras sin WordPress para la lógica pura (normalización, puntuación de comandos, resolución de URLs de menú, saneado de ajustes): pequeños scripts PHP/JS ejecutables con `php` y `node` sin dependencias, en `tests/` de la raíz.
- CI (`release.yml`): lint + zip de `escritoriowp/` publicado como release al empujar un tag `vX.Y.Z`.
- Verificación de integración sobre una instalación real levantada sin MySQL: WordPress + el plugin `sqlite-database-integration` + WooCommerce, cargada desde un script de PHP CLI que fija el usuario actual y ejecuta las rutas con `rest_do_request()`, además de pintar la pantalla del escritorio con `do_meta_boxes()`. Cubre las capacidades por rol, los códigos de estado, la agregación de la búsqueda y la sustitución del escritorio.
- QA funcional manual con una lista de comprobación en `docs/qa.md` derivada de los escenarios de las specs, para lo que solo se puede comprobar en navegador (atajos de teclado, foco, tema y diseño adaptable).

## Risks / Trade-offs

- [WordPress cambia el nombre o el hook de `wp_enqueue_command_palette_assets`] → guardado con `function_exists`, ajuste para desactivar nuestra intervención y aviso en `readme.txt` de la versión "Tested up to"; si en el futuro core añade un filtro oficial, se usará con preferencia.
- [Quitar la paleta nativa fuera del editor elimina también los comandos que otros plugins registran allí (p. ej. WooCommerce)] → es el comportamiento pedido; el lanzador propio indexa sus pantallas de menú. Se documenta y el ajuste permite restaurar la paleta nativa.
- [Otro plugin o navegador captura Cmd/Ctrl+K antes que nosotros] → listener en fase de captura y `stopPropagation`; el botón de la barra de administración es la vía alternativa.
- [Consultas `LIKE '%q%'` sobre `post_title` en sitios muy grandes] → título es una columna corta, límite por grupo, debounce 200 ms en el cliente y cancelación de peticiones obsoletas; posible transitorio por query en el futuro.
- [`search_products` también coincide por descripción larga] → aceptado y reflejado en la spec (nombre, extracto o SKU); el resultado siempre muestra el nombre real para que la coincidencia sea comprensible.
- [Ocultar widgets de terceros por defecto sorprende a usuarios de plugins que dependen del escritorio] → ajuste "Ocultar widgets de otros plugins" y sección "Otros widgets" cuando se conservan.
- [Sin entorno local no se puede probar de extremo a extremo durante la implementación] → lint estricto, pruebas unitarias de lógica pura y lista de QA manual; ver pregunta abierta.
- [Requisito PHP 8.1 por encima del mínimo de WordPress (7.4)] → coherente con los plugins hermanos y con la propuesta de WooCommerce 11.5; el fichero principal comprueba la versión y muestra aviso en lugar de fallar.
- [El catálogo de comandos en el front-end depende de una caché creada al visitar el admin] → degradación elegante a acciones fijas + búsqueda; la mayoría de sesiones empiezan en el admin.

## Migration Plan

Instalación nueva, sin datos previos que migrar:

1. Copiar `escritoriowp/` a `wp-content/plugins/` (o instalar el zip de la release) y activar.
2. Revisar Ajustes › EscritorioWP y, si el sitio depende de widgets de terceros, desactivar "Ocultar widgets de otros plugins".
3. Rollback: desactivar el plugin devuelve el escritorio y la paleta nativos de inmediato; borrar el plugin elimina la opción, los metas de usuario y los transitorios (`uninstall.php`).

## Open Questions

- ~~Entorno WordPress local para la QA manual~~. **Resuelto durante la implementación**: se levanta un WordPress completo sin Docker ni MySQL con el plugin `sqlite-database-integration` y el servidor interno de PHP, que basta para la verificación de servidor. Queda pendiente decidir en qué instalación se ejecuta la parte de `docs/qa.md` que exige navegador (atajos, foco, tema y diseño adaptable): sirve cualquier staging del autor.
