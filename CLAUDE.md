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
3. **Se actualiza solo desde GitHub**: WordPress avisa de las versiones nuevas y las instala con un
   clic, sin pasar por WordPress.org.

4. **Señala posibles puertas traseras**: la pantalla Administradores lista a los administradores y
   marca en rojo a quien tiene permisos de administrador sin el rol (§7 ter).
5. **Avisa por correo de los cambios en los plugins**: al activar, desactivar, instalar o borrar
   uno, con destinatarios y texto configurables en Ajustes (§7 quater). Y, con otra casilla, de los
   cambios en los administradores, con una revisión cada 6 horas (§7 quinquies).

Todo se gobierna desde el menú propio «EscritorioWP», con tres pantallas: Ajustes, Administradores y
Acerca de.

WooCommerce es opcional: si está activo añade productos y pedidos.

## 2. Estructura

```
<raíz>                          Material de desarrollo. NO se sube.
├── CLAUDE.md                   Este fichero.
├── README.md  CHANGELOG.md  BACKLOG.md
├── composer.json               Solo herramientas de desarrollo (phpcs, wpcs, i18n).
├── phpcs.xml.dist              Estándar WordPress + PHPCompatibility 8.1-.
├── .github/workflows/release.yml   Lint + pruebas + Plugin Check; release al subir la versión.
├── openspec/                   Specs y propuestas de cambio.
├── tests/                      Pruebas unitarias sin WordPress (php y node).
├── tools/generar-pot.php       Genera el .pot sin wp-cli.
├── tools/empaquetar.php        Crea el zip; con --wordpress-org, el del directorio oficial.
├── tools/notas-release.php     Extrae de CHANGELOG.md las notas de una versión.
├── tools/nueva-version.php     Sube la versión en los tres sitios y cierra [No publicado].
├── docs/qa.md                  Lista de comprobación manual.
├── docs/publicar-wordpress-org.md   Cómo enviar el plugin al directorio oficial.
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
    │   ├── class-acerca-de.php       Pantalla «Acerca de»: versión, enlaces y novedades.
    │   ├── class-changelog.php       Lee el historial de cambios del readme.txt.
    │   ├── class-menu.php            Posición del menú del plugin en la barra lateral.
    │   ├── actualizaciones/          ActualizadorGithub. Solo en la variante de GitHub.
    │   ├── avisos/                   Correo (envío en texto plano), Plantilla y CambiosAdministradores
    │   │                             (funciones puras), AvisosPlugins y AvisosAdministradores.
    │   ├── escritorio/               Escritorio, Resumen, Recientes, Preferencias.
    │   ├── lanzador/                 Lanzador y CatalogoComandos.
    │   ├── busqueda/                 Fuente (interfaz), Buscador, Grupo, Resultado, 4 fuentes.
    │   ├── creacion/                 Creador, Errores y un creador por tipo.
    │   ├── rest/                     Rest y cinco controladores.
    │   ├── seguridad/                Permisos (cálculo puro), Detector y la pantalla Administradores.
    │   └── woo/class-woo.php         Todo lo que depende de WooCommerce.
    ├── assets/css/  comun.css (tokens) · escritorio.css · lanzador.css · ajustes.css
    ├── assets/js/   comun.js (utilidades) · escritorio.js · lanzador.js · ajustes.js (selector de color)
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

Los ajustes «Indicadores» y «Paneles» (`indicadores_activos` y `paneles_activos`) **no son control de
acceso**: deciden qué muestra el escritorio a todo el sitio, y encima sigue mandando la capacidad.
Por eso se aplican en `Escritorio` (paneles) y en `Resumen::claves_visibles()` (indicadores), y nunca
en `Capacidades`. La ruta `recientes/{tipo}` sigue respondiendo a quien tiene la capacidad aunque su
panel esté desactivado, porque esos datos están igualmente en el listado nativo. La ruta `resumen`
sí los filtra, porque devuelve lo que pinta el escritorio y así no calcula lo que no se muestra.

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

## 7 bis. Menú propio y actualizaciones desde GitHub

**El menú.** `Ajustes::anadir_pagina()` crea el menú de primer nivel con `add_menu_page()` y añade
su primera entrada con `add_submenu_page()` compartiendo identificador, que es lo que evita que se
repita el nombre. `Administradores::anadir_pagina()` y `AcercaDe::anadir_pagina()` añaden las otras
dos en `admin_menu` con prioridad 11, para que el menú ya exista; Administradores pasa la posición `1`
a `add_submenu_page()`, que la deja justo detrás de «Ajustes» sin depender del orden en que se
registran los módulos. Administradores solo se registra si el usuario tiene también `list_users`. Cada clase encola su propio CSS comparando el identificador de
pantalla que le devolvió WordPress. La URL de ajustes es `Ajustes::url()`: nadie vuelve a escribirla
a mano.

**Dónde aparece.** Por defecto, justo debajo de «Escritorio»; el ajuste `menu_debajo_de` guarda el
identificador del menú bajo el que va (cadena vacía, «Al final»). `add_menu_page()` se llama **sin
posición** a propósito: `Menu` activa `custom_menu_order` y, en `menu_order` con prioridad
`PHP_INT_MAX`, recoloca el menú con `Menu::recolocar()`. Así funciona con menús de plugins que se
registran más tarde y se libra de que WordPress ordene las posiciones decimales como texto (`2.10`
antes que `2.5`). Si el menú de referencia no existe, vuelve bajo «Escritorio» sin tocar el ajuste,
para que reaparezca en su sitio cuando el menú vuelva. El desplegable de ajustes sale de
`Menu::opciones()`, que lee `$menu` ya ordenado.

**Color de la entrada.** El ajuste `menu_color_fondo` (vacío por defecto) pinta solo la entrada
«EscritorioWP». `Menu::css()` genera las reglas a partir del valor **vuelto a sanear** y
`Menu::encolar_color()` las añade con `wp_add_inline_style( 'admin-menu', … )`, la hoja del menú
lateral que carga toda pantalla del administrador; sin color no añade nada. El texto y el icono los
decide `Menu::color_texto()`: blanco si llega a 4,5:1 con el fondo, negro si no, y con eso nunca baja
de 4,5:1. Las reglas excluyen el ratón y el foco en reposo, y solo el foco con la entrada abierta,
para que en esos estados mande el esquema de colores de WordPress (y su indicador de foco); abierta
no se excluye el ratón porque WordPress tampoco cambia una entrada abierta al pasar por ella. El
campo es un texto que `assets/js/ajustes.js` convierte en el `wp-color-picker` de WordPress, encolado
solo en la pantalla de ajustes.

**Las actualizaciones.** La cabecera declara `Update URI: https://github.com/webprogramacion/escritoriowp`,
así que WordPress deja de preguntar por el plugin a WordPress.org y pasa la decisión al filtro
`update_plugins_github.com`, que responde `ActualizadorGithub`. El plugin solo devuelve los datos de
la release: comparar versiones, avisar e instalar lo hace WordPress. `plugins_api` se intercepta
para que «Ver detalles» muestre el changelog de la release en lugar de fallar contra WordPress.org.

Tres reglas que no se pueden perder de vista:

- **Pintar una pantalla nunca consulta GitHub.** La pantalla Acerca de usa `guardada()`, que solo lee
  el transitorio. Quien pregunta es la comprobación periódica de WordPress o el botón, que exige
  capacidad `update_plugins` y nonce.
- **El fallo también se cachea**, una hora, para no insistir contra un servicio caído.
- **El directorio de WordPress.org prohíbe servir actualizaciones desde fuera** (directriz 8). Por
  eso todo el módulo vive aislado en `includes/actualizaciones/` y se apaga con vaciar la constante
  `REPOSITORIO`, que es justo lo que hace `php tools/empaquetar.php --wordpress-org`. Antes de
  enviar el plugin al directorio, lee `docs/publicar-wordpress-org.md`: hay dos requisitos
  pendientes que no son de código (el nombre no puede llevar «wp» y el readme tiene que estar en
  inglés).

## 7 ter. Detección de administradores

La pantalla EscritorioWP › Administradores busca puertas traseras: usuarios que, sin el rol
Administrador, tienen guardados en la base de datos permisos que dan control del sitio. Todo vive en
`includes/seguridad/`.

**Qué cuenta como permiso de administrador.** `Permisos::CRITICOS`, una lista cerrada de 30 permisos
que en una instalación estándar solo tiene el administrador (ajustes, usuarios y roles, plugins,
temas, ficheros, actualizaciones y los de la red). Quedan fuera los que WooCommerce da al gestor de
tienda (`import`, `export`, `list_users`, `edit_theme_options`, `level_9`) y `unfiltered_html`, que
tiene el editor. **No tiene filtro a propósito**: en una herramienta de seguridad, lo primero que
haría un atacante es vaciarla.

**Se calcula como WordPress, no como su interfaz.** `Permisos::evaluar()` replica
`WP_User::get_role_caps()` y `has_cap()`, y cada regla tiene su prueba en `tests/prueba-permisos.php`:

- Es rol cada clave del metadato de permisos que coincide con un rol existente, **sea cual sea su
  valor**: `{"administrator": false}` es un administrador.
- Los permisos de los roles se fusionan en orden (`array_merge`) y encima van los guardados en el
  usuario, que mandan: un `manage_options` en falso en el usuario anula el del rol.
- Un permiso cuenta si su valor no está vacío (`"1"` sí; `0`, `""` y `"0"` no).
- La clave de un rol que no existe es un permiso suelto más.
- El origen de cada permiso es `usuario` o el último rol que lo define, que es el que aporta el valor.

`Permisos::desserializar()` lee el metadato crudo con `allowed_classes => false`: un valor manipulado
no puede instanciar objetos, cosa que el `maybe_unserialize()` de WordPress no evita.

**Se lee la base de datos directamente.** `Detector` consulta `usermeta` y `users` con `$wpdb`, sin
`get_users()`, `WP_User_Query`, `get_user_meta()` ni `get_userdata()`: un código que quiera esconder
a un usuario filtra justo esas funciones (`pre_user_query`, `pre_get_users`, `get_user_metadata`) o
reemplaza la última, que es reemplazable. Cada consulta lleva su `phpcs:ignore` con este motivo. Va
en dos pasos: un filtro grueso con `LIKE` sobre el metadato serializado (`Permisos::patrones()`, que
puede traer candidatos de más pero nunca de menos) y la lectura de su detalle quedándose con la
primera fila de cada usuario, como WordPress cuando el metadato está repetido. Los roles sí salen de
`wp_roles()`, porque es lo que WordPress usa para conceder.

**El contador en el menú.** Con usuarios marcados, `Administradores::contador()` añade el mismo
marcado que los contadores de WordPress (`awaiting-mod`, con texto para lectores de pantalla) al
título de su entrada y, tocando `$menu`, al de «EscritorioWP», para que se vea con el menú cerrado.
`CatalogoComandos::limpiar_titulo()` y `Menu::opciones()` ya quitan esos `<span>`. En su propia
pantalla no se lee la caché: se analiza al registrar el menú (WordPress ya ha fijado `$plugin_page`)
y pintar reutiliza ese informe, para que el contador y el resumen coincidan.

**Usuarios ocultos.** Lo único que pasa a propósito por `get_users()`: los usuarios que se van a pintar
se piden con `login__in` y los que faltan están ocultos. Por nombre de usuario y no con `include`,
porque con `include` WordPress ignora `exclude`, que es otra forma de esconder a alguien.

**El contador** del menú lee el transitorio `escritoriowp_administradores` (15 minutos). Lo invalidan
los cambios del metadato de permisos, de la opción de roles y de los superadministradores, con
enganches que `Plugin::arrancar()` registra también fuera del administrador. Un alta de usuario solo
lo invalida si nace sospechosa: en una tienda, invalidar en cada registro obligaría a recalcular casi
en cada pantalla.

**Retirar permisos.** Solo los permisos críticos **sueltos** (`Detector::sueltos()`), nunca los que
vienen de un rol: el plugin no modifica roles. El botón lleva a una confirmación que pinta el servidor
(`&retirar={id}`), sin JavaScript, que no cambia nada y lista lo que se va a quitar; el formulario va
por POST a `admin-post.php` con un nonce ligado al identificador del usuario. La acción
(`Administradores::retirar()`) comprueba ella misma que el método es POST, porque `admin-post.php`
también dispara la acción por GET, y repite las comprobaciones de la confirmación
(`Administradores::motivo_rechazo()`): nadie se retira sus propios permisos, ni se tocan
administradores o superadministradores, y hace falta `promote_user` sobre ese usuario. Vuelve a leer
la base de datos y quita con `WP_User::remove_cap()`, no con SQL: así se disparan los enganches (la
caché del contador se invalida sola y los registros de actividad lo anotan), se limpia la caché de
objetos y WordPress recalcula `user_level`, el otro rastro que suele dejar una escalada a mano. El
mensaje final nombra los permisos retirados para poder devolverlos si eran legítimos.

**Qué no detecta.** Los permisos que un plugin concede solo en tiempo de ejecución (`user_has_cap`,
`map_meta_cap`): no están en la base de datos y cualquier plugin legítimo usa esos filtros. Tampoco
ve como oculto a un usuario que un código esconde solo cuando la página es `users.php`, aunque sí lo
marca si tiene permisos críticos.

## 7 quater. Avisos por correo

Todo vive en `includes/avisos/`: `Plantilla` (marcadores, resumen de nombres y línea de cada plugin,
funciones puras con pruebas) y `AvisosPlugins` (enganches y envío). Se compone en `Plugin::arrancar()`
fuera de `is_admin()`, porque la API REST y WP-CLI también activan, instalan y borran plugins.
`registrar()` solo engancha los eventos cuya casilla está marcada: con las cuatro apagadas, que es el
valor por defecto, no engancha nada.

**Qué se escucha** (comprobado en el código de WordPress 7.1):

| Evento | Enganche | Detalle |
|--------|----------|---------|
| Activación | `activated_plugin` | |
| Desactivación | `deactivated_plugin` | También la del propio EscritorioWP, que sigue cargado en esa petición. |
| Instalación | `upgrader_process_complete` (`plugin`, `install`) | Se dispara también si falla: solo cuenta si `Plugin_Upgrader::plugin_info()` devuelve el fichero. |
| Borrado | `delete_plugin` y `deleted_plugin` | El primero lee nombre y versión mientras el fichero existe; el segundo anota solo si se borró. |

**Las actualizaciones no avisan, por diseño.** Al actualizar desde la pantalla o con WP-CLI,
WordPress desactiva y reactiva el plugin en modo silencioso (`deactivate_plugins( …, true )`,
`activate_plugin( …, true )`), que no dispara esos enganches; en cron ni siquiera lo desactiva. Por eso
no se vigila la opción `active_plugins`: confundiría ese ciclo con un cambio real.

**Se envía en `shutdown`, un correo por evento.** Cada plugin se anota por evento y fichero, y el envío
espera al final de la petición (que corre también tras `exit` y `wp_die()`): una acción en lote manda
un solo correo. No se usa cron porque, si lo desactivado es EscritorioWP, su callback ya no existiría
al correr. La pantalla Plugins borra con AJAX de uno en uno, así que el borrado en lote manda un correo
por plugin. Cada envío va en `try`/`catch`: si `wp_mail()` falla, el aviso se pierde sin tocar la
acción.

**Siempre texto plano.** `wp_mail()` aplica `wp_mail_content_type` después de leer la cabecera, así
que durante el envío, y solo durante él, un filtro con prioridad `PHP_INT_MAX` devuelve `text/plain`;
se quita en un `finally`.

**Marcadores.** `Plantilla::rellenar()` sustituye en una sola pasada con `strtr()`: el nombre de un
plugin que contenga `{sitio}` no se vuelve a interpretar, y un marcador desconocido se queda tal cual.
`{ip}` es solo `REMOTE_ADDR`, porque las cabeceras de proxy las escribe el cliente, y «—» con WP-CLI,
que rellena `REMOTE_ADDR` con `127.0.0.1`.

**Textos predeterminados vacíos.** `aviso_asunto` y `aviso_mensaje` valen `''` y se traducen al
enviar. El formulario los muestra rellenados (argumento `predeterminado`) y
`Ajustes::sanear_formulario()` guarda `''` si llegan iguales al predeterminado: guardarlos los
congelaría en el idioma y la versión del momento.

**Qué no detecta.** La activación del propio EscritorioWP (en esa petición todavía no ha arrancado),
los cambios hechos sin las funciones de WordPress (copiar ficheros por FTP, editar `active_plugins` en
la base de datos), los `mu-plugins`, los complementos (`drop-ins`) y los temas.

## 7 quinquies. Aviso de cambios en los administradores

`AvisosAdministradores` revisa cada 6 horas a los administradores y a los usuarios marcados (§7 ter) y
avisa de lo que haya cambiado desde la revisión anterior. Se compone en `Plugin::arrancar()` fuera de
`is_admin()`, porque la revisión corre en WP-Cron. El envío en texto plano es el de `Correo`, que
comparte con `AvisosPlugins` (§7 quater); los destinatarios son los mismos y el texto, fijo.

**La clasificación es la de la pantalla.** `Detector::usuarios_evaluados()` es la primera mitad de
`analizar()` (candidatos, detalle, `Permisos::evaluar()` y superadministradores), sin el límite de 100,
sin buscar ocultos y sin tocar el transitorio; `Detector::estado()` dice si cada uno es
`administrador`, `marcado` o nada, y `analizar()` reparte con esa misma función. Así la pantalla y el
aviso no pueden discrepar. Los ocultos (`Detector::ocultos()`) y los borrados (`Detector::existentes()`,
que lee `users` directamente) solo se consultan para los 50 usuarios que se listan en cada apartado.

**Qué se guarda.** La opción `escritoriowp_revision_administradores`, sin carga automática, con la
fecha de la revisión y, por usuario, solo su nombre de usuario y su estado. No es un transitorio a
propósito: si desapareciera, la revisión siguiente lo tomaría como punto de partida y perdería sin
avisar los cambios de ese intervalo. La comparación es pura (`CambiosAdministradores::comparar()`) y
deja a cada usuario en un solo apartado: quien pasa de marcado a administrador solo es administrador
nuevo, y quien pierde el rol pero conserva permisos críticos solo es marcado nuevo.

**La programación.** WordPress no trae un intervalo de 6 horas: el filtro `cron_schedules` añade
`escritoriowp_seis_horas` en toda petición, porque WP-Cron lo necesita para reprogramar. Con la casilla
marcada, `init` programa la tarea `escritoriowp_revisar_administradores` si no existe, como hace el
núcleo con `wp_schedule_update_checks()`; es lo que la recupera tras reactivar el plugin. El cambio de
la casilla se ve en `update_option_escritoriowp_ajustes`: al marcarla se guarda la situación de ese
momento sin avisar (lo que ya existía no avisa) y se programa la primera revisión para dentro de 6
horas; al desmarcarla se quita la tarea y se borra la situación. Guardar otro ajuste no toca nada.

**La situación solo avanza con el correo aceptado.** Si `wp_mail()` devuelve falso o lanza una
excepción, no se guarda la situación nueva y la revisión siguiente vuelve a contar los mismos cambios
con la misma fecha de comparación. Al revés que los avisos de plugins: allí el evento ya pasó; aquí
la situación sigue ahí y perder el aviso de un administrador nuevo es justo lo que se quiere evitar.

**El ciclo de vida.** El gancho de desactivación de `escritoriowp.php` quita la tarea con su nombre
escrito tal cual (sin cargar clases, por si no se cumplen los requisitos de PHP) y conserva la
situación: al reactivar, la primera revisión avisa de lo que cambió entre medias. `uninstall.php`
borra la opción y la tarea.

**Qué no detecta.** Quién hizo el cambio ni desde qué IP (compara estados, no ve acciones), un cambio
que se deshace entre dos revisiones, más permisos críticos para quien ya estaba marcado y los permisos
que solo se conceden en tiempo de ejecución. Depende de WP-Cron: sin visitas o con `DISABLE_WP_CRON`
sin tarea en el servidor, la revisión se retrasa, y la casilla lo delata con una próxima revisión
pasada. En multisitio, cada sitio con la casilla marcada avisa de los cambios de superadministradores,
y al desactivar en toda la red solo se quita la tarea del sitio actual.

## 8. Cómo añadir cosas

**Una fuente de búsqueda nueva**: implementa `EscritorioWP\Busqueda\Fuente` en
`includes/busqueda/class-fuente-x.php` y engánchala al filtro `escritoriowp_fuentes_busqueda`. El
orden del array es el orden de los grupos en el lanzador.

**Un panel nuevo en el escritorio**: añade el tipo a `Capacidades::MAPA` y a
`Capacidades::TIPOS_PANEL`, y devuélvelo en `Recientes::obtener()`. Añade sus textos de estado vacío
(`panelVacioX`, `crearPrimeroX`) al catálogo de `Activos::textos()`. No hace falta nada más para el
ajuste «Paneles»: el panel aparece solo en Ajustes y sale activo, también en los sitios que ya
habían guardado el ajuste.

**Un indicador nuevo**: añade su clave a `Resumen::INDICADORES`, con el tipo de `Capacidades::MAPA`
que decide quién lo ve, su nombre a `Resumen::etiqueta()` y su tarjeta a `Resumen::obtener()`,
condicionada a que la clave esté en `claves_visibles()`. Una tarjeta que no esté en `INDICADORES` no
se muestra nunca. Como el panel, aparece sola en el ajuste «Indicadores» y sale activa.

**Un comando fijo del lanzador**: añádelo en `CatalogoComandos::comandos_accion()`, siempre
condicionado a una capacidad. Para comandos desde otro plugin, usa el filtro `escritoriowp_comandos`.

**Una pantalla nueva en el menú del plugin**: crea su clase, registra la subpágina en `admin_menu`
con prioridad 11 o posterior y `Ajustes::PAGINA` como padre, y guarda el identificador que devuelve
`add_submenu_page()` para encolar su CSS. El lanzador la recoge sola al leer `$submenu`.

**Un ajuste nuevo**: añádelo a `Ajustes::defectos()` y declara su campo en
`Ajustes::registrar_ajustes()` con su `tipo` (`casilla`, `casillas`, `numero`, `desplegable`, `color`,
`correo`, `texto` o `area`), que es lo que `pintar_campo()` usa para decidir cómo pintarlo. Una casilla
no necesita nada más: `sanear()` la interpreta como booleano y `pintar_campo()` le pone el campo oculto
con valor `0`. Cualquier otro tipo necesita su rama en el `match` de `Ajustes::sanear()`; sin ella el
ajuste conserva siempre su valor por defecto, para que nunca se guarde mal interpretado. El tipo
`casillas` es una lista `clave => bool` (como `paneles_activos`): su rama llama a
`sanear_activos()` con las claves conocidas, que da `true` a la que falta y descarta las
desconocidas, y el campo declara sus casillas en el argumento `opciones` (`clave => texto`). Se pinta
como un `fieldset` con su `legend` y sin `label_for`. Si alguna casilla puede no pintarse, como las de
WooCommerce, completa lo enviado con lo guardado en `sanear_formulario()`, como hacen los dos
ajustes de paneles e indicadores. El campo admite además tres argumentos opcionales: `predeterminado`
(lo que se muestra mientras el valor guardado está vacío), `ejemplo` (el `placeholder`) y `leyenda`
(términos con su significado bajo la descripción).
`sanear()` es pura; lo que necesita WordPress al guardar desde el formulario, como un aviso con
`add_settings_error()` o guardar vacío un texto igual al predeterminado, va en
`Ajustes::sanear_formulario()`, que es el `sanitize_callback`. La pantalla pinta esos avisos con
`settings_errors()`, porque WordPress solo lo hace bajo Ajustes.

## 9. Cachés

| Clave | Qué guarda | Duración | Se invalida |
|-------|-----------|----------|-------------|
| `escritoriowp_resumen_woo` | Productos, pedidos e ingresos del mes | 5 min | Botón «Actualizar», crear un producto |
| `escritoriowp_comandos_{id}` | Catálogo de comandos del usuario | 12 h | Cambio de versión del plugin |
| `escritoriowp_actualizacion` | Última release publicada en GitHub | 12 h (1 h si falla) | Botón «Buscar actualizaciones ahora» |
| `escritoriowp_administradores` | Número de usuarios marcados, para el contador del menú | 15 min | Cambios de permisos, roles o superadministradores; abrir la pantalla Administradores |

El catálogo se cachea porque en el sitio público no existen `$menu` ni `$submenu`. Si no hay caché,
el lanzador del front-end ofrece solo las acciones fijas y la búsqueda de contenido.

La opción `escritoriowp_revision_administradores` no es una caché y no va en esta tabla: es la
situación con la que se compara la revisión siguiente (§7 quinquies), y perderla haría perder avisos.

## 10. Pruebas

```bash
php tests/ejecutar.php              # funciones puras de PHP (incluye las de tools/)
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

**No se empujan tags a mano.** Publica el push a `main`: el workflow detecta que la versión de la
cabecera no tiene todavía su tag `vX.Y.Z`, lo crea y publica la release con el zip.

**El camino habitual es `/nueva-version [patch|minor|major|X.Y.Z]`** en Claude Code. La skill
(`.claude/skills/nueva-version/`) comprueba que `main` está al día, redacta las novedades a partir de
los commits desde el último tag y las enseña antes de escribir nada, propone el tipo de incremento si
no se indica, hace los pasos 1 a 4 de abajo y el commit, y **pregunta antes del push**, que es lo que
publica. Los números y la sección del changelog los escribe `tools/nueva-version.php`; la prosa de
`readme.txt` la escribe la skill.

A mano sigue valiendo:

1. Anota las novedades en `## [No publicado]` de `CHANGELOG.md` y ejecuta
   `php tools/nueva-version.php <patch|minor|major|X.Y.Z>`. Sube la versión en **los tres sitios**
   (la cabecera de `escritoriowp/escritoriowp.php`, la constante `VERSION` del mismo fichero y
   `Stable tag` en `escritoriowp/readme.txt`) y convierte `## [No publicado]` en `## [X.Y.Z] - fecha`.
   Con `--simular` solo dice qué versión saldría.
2. Añade las entradas `= X.Y.Z =` de `escritoriowp/readme.txt`, en `== Changelog ==` (de donde sale
   la pantalla Acerca de) y en `== Upgrade Notice ==`. El workflow falla si falta el changelog en
   `CHANGELOG.md` (de donde salen las notas de la release de GitHub) o en `readme.txt`.
3. Regenera el `.pot`: `php tools/generar-pot.php`.
4. Comprueba en local: `php tools/nueva-version.php --comprobar` (las mismas comprobaciones de
   coherencia que hace el workflow antes de publicar), `composer test`, `composer lint` y
   `php tools/empaquetar.php`.
5. Commit `Release X.Y.Z` con solo los cuatro ficheros de la versión (`CHANGELOG.md`,
   `escritoriowp/escritoriowp.php`, `escritoriowp/readme.txt` y `escritoriowp/languages/escritoriowp.pot`)
   y push a `main`. El workflow pasa sintaxis, `phpcs`, pruebas y Plugin Check; después comprueba que
   las tres versiones coinciden y que hay changelog, crea el tag `vX.Y.Z` y publica la release con
   `escritoriowp-X.Y.Z.zip`.

Un push a `main` cuya versión ya tiene tag solo ejecuta las comprobaciones: no publica nada, así
que se puede empujar tantas veces como haga falta entre versiones.
