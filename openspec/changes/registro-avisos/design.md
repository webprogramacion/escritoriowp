## Context

Motivación en `proposal.md`; comportamiento exigido en `specs/registro-avisos/spec.md` y en los deltas
de `escritorio-panel`, `menu-administracion` y `nucleo-plugin`.

Lo que condiciona el enfoque, comprobado en el código actual:

- **Un solo punto de envío.** Los dos avisos pasan por `Avisos\Correo::enviar( $asunto, $mensaje )`,
  que calcula los destinatarios, fuerza el texto plano con un filtro temporal quitado en un `finally` y
  devuelve lo que devuelve `wp_mail()`. `AvisosPlugins::enviar()` lo llama en `shutdown`, un correo por
  evento, dentro de `try`/`catch ( \Throwable )`. `AvisosAdministradores::revisar()` lo llama en
  WP-Cron y solo guarda la situación nueva si devuelve `true` (§7 quinquies). Ninguno de los dos sabe
  hoy por qué falla un envío: los comentarios remiten a `wp_mail_failed`.
- **El plugin no tiene tablas propias.** Todo vive en opciones, metadatos y transitorios. El gancho
  de activación de `escritoriowp.php` carga el autoloader y llama a `Ajustes::asegurar_defectos()`; el
  de desactivación solo quita la tarea de WP-Cron con su nombre literal. `uninstall.php` no carga
  clases y borra con nombres literales.
- **Las actualizaciones no ejecutan la activación.** Subir un zip, actualizar desde GitHub o la
  actualización automática sustituyen los ficheros sin pasar por `register_activation_hook()`. En
  multisitio, activar en la red solo ejecuta el gancho en el sitio actual.
- **El escritorio** pinta en PHP el esqueleto (`Escritorio::pintar_app()`: cabecera, sección de
  resumen y sección de paneles, cada una solo si tiene algo que mostrar) y `assets/js/escritorio.js`
  lo rellena por REST (`resumen`, `recientes/{tipo}`). Las filas de los paneles comparten forma
  (`titulo`, `urlEditar`, `urlVer`, `imagen`, `inicial`, `metas` con `texto` y `tono`) y se pintan con
  `pintarFilas()`, que además decide el estado vacío buscando la definición del panel. Las etiquetas
  de color (`.escritoriowp-etiqueta[data-tono]`: `verde`, `ambar`, `rojo`, `azul`, `morado`, `neutro`)
  están en `comun.css`. El botón «Actualizar» vuelve a pedir el resumen y cada panel.
- **Las pantallas del menú** se registran en `admin_menu` con prioridad 11; Administradores pasa la
  posición `1` para quedar detrás de «Ajustes» y solo existe con `list_users`. Pintan HTML en el
  servidor, sin JavaScript, con `ajustes.css`, la tabla `.escritoriowp-tabla` (con `data-columna` para
  pantallas estrechas) y el tema de las preferencias del usuario en `data-tema`. El lanzador recoge las
  subpáginas al leer `$submenu`, con el catálogo cacheado 12 horas e invalidado al cambiar de versión.
- **`$wpdb` imprime los errores de SQL** cuando `WP_DEBUG` y `WP_DEBUG_DISPLAY` están activos. Un error
  impreso en `shutdown` ensucia la respuesta de una petición AJAX o REST (la pantalla Plugins borra por
  AJAX).
- **Las pruebas no cargan WordPress**: solo funciones puras.

## Goals / Non-Goals

**Goals:**

- Que ningún aviso se pierda por el camino: el registro existe antes de llamar a `wp_mail()`.
- Que guardar no pueda romper el envío ni la acción que lo provoca, ni cambiar cuándo avanza la
  revisión de administradores.
- Que `AvisosPlugins` y `AvisosAdministradores` sigan comportándose exactamente igual, salvo por el
  tipo que pasan a `Correo`.
- Que con todos los avisos apagados y el registro vacío el escritorio no haga ninguna consulta más que
  la comprobación de si hay avisos, y solo para quien tiene `manage_options`.
- Que la validación de filtros, el límite, la paginación y el saneado del motivo sean funciones puras
  con pruebas.

**Non-Goals:**

- Cola de envío o reintentos: el registro guarda lo que pasó, no vuelve a enviar.
- Ver si el correo llegó al buzón (rebotes, spam): solo se sabe si `wp_mail()` lo aceptó.
- Registrar correos que no sean avisos del plugin.
- Borrar registros desde la interfaz.

## Decisions

### D1. Una tabla propia, no un tipo de contenido ni una opción

`{$wpdb->prefix}escritoriowp_avisos`, una por sitio.

- **Tipo de contenido (`wp_insert_post`)**: descartado. Cada aviso dispararía `save_post` y compañía,
  y los plugins de SEO, caché, búsqueda o registro de actividad reaccionarían a algo que no es contenido;
  además ensucia `posts`, entra en las exportaciones de WordPress y en los recuentos.
- **Una opción con un array**: descartada. Crece sin límite (y aquí no hay caducidad), se reescribe
  entera en cada aviso y dos peticiones simultáneas (una acción en lote y la revisión de WP-Cron) se
  pisarían.
- **Tabla propia**: inserciones baratas, índices propios, consultas paginadas sin traer el mensaje y un
  `DROP TABLE` limpio al desinstalar. El coste es gestionar el esquema (D3).

### D2. Esquema y valores guardados

```sql
CREATE TABLE {prefijo}escritoriowp_avisos (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  fecha datetime NOT NULL,
  tipo varchar(20) NOT NULL,
  estado varchar(20) NOT NULL,
  asunto text NOT NULL,
  mensaje longtext NOT NULL,
  destinatarios text NOT NULL,
  motivo text NOT NULL,
  PRIMARY KEY  (id),
  KEY tipo (tipo),
  KEY estado (estado)
) {charset_collate};
```

- `fecha` en UTC (`gmdate( 'Y-m-d H:i:s' )`), en el momento de guardar, justo antes de enviar. Se
  muestra con `wp_date()` en el formato y la zona horaria del sitio, y en el escritorio con
  `Recientes::fecha_relativa()`.
- `tipo` y `estado` guardan **claves**, no textos: `activacion`, `desactivacion`, `instalacion` y
  `borrado` (las constantes de `AvisosPlugins`) y `administradores` (`Registro::ADMINISTRADORES`);
  `pendiente`, `enviado` y `fallido`. Los nombres se traducen al pintar, como hace el plugin con los
  textos predeterminados: guardar «Activación» congelaría el idioma del momento. `pendiente` se muestra
  como «Sin confirmar».
- `asunto` y `mensaje`, exactamente los que recibe `wp_mail()`; `destinatarios`, la lista que recibe
  `wp_mail()` unida con `, `.
- `motivo` vacío salvo en los fallidos con un motivo conocido (D5). Un fallido sin motivo muestra un
  texto genérico traducible.
- Ordenar por `id` y no por `fecha`: es el orden de inserción, no depende de la hora del servidor y usa
  la clave primaria.
- La versión del esquema va en la opción `escritoriowp_avisos_bd` (`'1'`), con carga automática porque
  es diminuta y se lee en cada aviso y en el escritorio de los administradores.

### D3. La tabla se crea al activar y, si falta, antes de guardar el primer aviso

`Registro::instalar()` ejecuta `dbDelta()` con el esquema de D2 y guarda la versión.
`Registro::asegurar_tabla()` lo llama si la opción no coincide con `Registro::VERSION_ESQUEMA`. Se
llama:

- Desde el gancho de activación, junto a `asegurar_defectos()`.
- Desde `Registro::guardar()`, antes de insertar. Es lo que cubre la actualización sin activación, la
  activación en toda la red (cada sitio crea su tabla con su primer aviso) y los sitios creados después.

Las lecturas **no** crean nada: con la opción ausente o desfasada, el registro se trata como vacío sin
consultar la base de datos. Así una pantalla nunca ejecuta `dbDelta()` y un sitio sin avisos no hace
consultas a una tabla que no existe.

Descartado comprobar la versión en `plugins_loaded` en cada petición: con la creación perezosa no hace
falta, y añadiría trabajo a todas las peticiones del sitio para algo que pasa una vez. Un cambio de
esquema futuro sube `VERSION_ESQUEMA` y `dbDelta()` hace el `ALTER`.

### D4. Se guarda antes de enviar y se completa después

`Correo::enviar()` pasa a:

1. Calcular los destinatarios una sola vez.
2. `Registro::guardar( $tipo, $asunto, $mensaje, $destinatarios )`, que inserta con estado
   `pendiente` y devuelve el identificador (0 si no pudo).
3. Enviar como ahora, escuchando además `wp_mail_failed` (D5).
4. `Registro::terminar( $id, $enviado, $motivo )`, que pasa a `enviado` o `fallido`. Con `$id` 0 no
   hace nada.

Si `wp_mail()` lanza una excepción, se termina el registro como `fallido` con su mensaje y se vuelve a
lanzar: los dos llamantes ya la capturan y su comportamiento no cambia.

Descartado guardar solo al final: si el envío rompe la petición (un servidor SMTP que no responde y
agota `max_execution_time`, un error fatal en un filtro de otro plugin), el aviso se perdería, que es
justo lo que pide evitar el usuario. Con dos consultas por aviso, y muy pocos avisos, el coste es
despreciable. Un registro que se queda en `pendiente` para siempre dice la verdad: no se sabe si salió.

`tipo` se añade como primer parámetro de `Correo::enviar( string $tipo, string $asunto, string
$mensaje ): bool`. `AvisosPlugins::enviar_evento()` pasa su `$evento` y `AvisosAdministradores::revisar()`
pasa `Registro::ADMINISTRADORES`. Nada más cambia en ellos: `revisar()` sigue guardando la situación
solo si el envío devuelve `true`, y un registro fallido no lo altera.

### D5. El motivo del fallo sale de `wp_mail_failed`, solo durante el envío

Igual que el filtro de texto plano: un cierre que escucha `wp_mail_failed` se añade antes de
`wp_mail()` y se quita en el `finally`, así que solo recoge el error de este envío. Guarda
`WP_Error::get_error_message()`. Si `wp_mail()` lanza una excepción, el motivo es su mensaje.

`Registro::motivo( string $texto ): string` (pura) lo deja en una línea, sin etiquetas y en 500
caracteres como mucho. Sin motivo (por ejemplo, `pre_wp_mail` devuelve `false` o un plugin que sustituye
`wp_mail()` falla sin disparar el enganche), se guarda vacío y se pinta el texto genérico.

### D6. El registro nunca rompe nada

- Cada operación de `Registro` va en `try`/`catch ( \Throwable )` y devuelve su valor neutro (0,
  `false`, lista vacía o `null`).
- Durante las consultas, `$wpdb->hide_errors()` y después se restaura el valor anterior con
  `$wpdb->show_errors( $previo )`. Así un error de SQL sigue llegando al `error_log` (`hide_errors()`
  solo evita imprimirlo, al contrario que `suppress_errors()`) pero no ensucia una respuesta AJAX o
  REST ni una redirección.
- Cada consulta directa lleva su `phpcs:ignore` justificado: es una tabla propia y WordPress no ofrece
  otra API. Sin caché de objetos: los avisos se escriben y se leen en peticiones distintas, el bloque
  tiene que reflejar el último aviso y el volumen es mínimo.

### D7. `Avisos\Registro`: datos, consultas y funciones puras

Clase final con métodos estáticos, como `Seguridad\Detector`.

| Método | Qué hace | WordPress |
|--------|----------|-----------|
| `tabla()`, `instalar()`, `asegurar_tabla()`, `instalada()` | Nombre con `$wpdb->prefix`, `dbDelta()`, versión | Sí |
| `guardar()`, `terminar()` | Insertar `pendiente` y completar (D4) | Sí |
| `recientes( int $limite )` | `id`, `fecha`, `tipo`, `estado`, `asunto`, sin el mensaje | Sí |
| `listar( array $filtros, int $pagina )`, `contar( array $filtros )` | Página de 20 y total, con `destinatarios` y sin el mensaje | Sí |
| `obtener( int $id )` | Un aviso completo, o `null` | Sí |
| `hay_avisos()` | `SELECT 1 … LIMIT 1` | Sí |
| `filtros( mixed $tipo, mixed $estado )` | Solo valores de `TIPOS` y `ESTADOS`; lo demás se ignora | No: pura |
| `limite( mixed $valor )` | Entero entre 1 y 20, 5 si no es válido | No: pura |
| `total_paginas( int $total )`, `pagina( mixed $valor, int $total_paginas )` | Paginación acotada | No: pura |
| `motivo( string $texto )` | D5 | No: pura |
| `tono( string $estado )` | `enviado` → `verde`, `fallido` → `rojo`, `pendiente` → `ambar` | No: pura |

Los filtros ya validados llegan al `WHERE` con `$wpdb->prepare()`: nunca se interpola nada que venga de
la petición.

### D8. Pantalla EscritorioWP › Avisos

`Avisos\PantallaAvisos` (`includes/avisos/class-pantalla-avisos.php`), con `PAGINA =
'escritoriowp-avisos'`, `CAPACIDAD = 'manage_options'`, `puede_ver()`, `url()` y `url_detalle( int $id
)`. `PantallaAvisos::puede_ver()` es la única comprobación de acceso a los avisos: la usan la
pantalla, el bloque del escritorio y la ruta REST. No se añade a `Capacidades::MAPA`, que describe
tipos de contenido con «ver», «crear» y «publicar»: igual que Administradores, la capacidad vive en su
pantalla.

- **Menú.** `admin_menu` con prioridad **12** y posición `Administradores::puede_ver() ? 2 : 1`. Con
  prioridad 12, «Administradores» y «Acerca de» (prioridad 11) ya están registradas, así que la
  posición cae siempre justo antes de «Acerca de», sea cual sea el orden en que `Plugin::arrancar()`
  compone los módulos. Con prioridad 11 dependería de ese orden: si «Acerca de» se registrara antes que
  las otras dos, «Avisos» acabaría la última.
- **Listado** (sin JavaScript): título «Avisos por correo», una introducción que dice que se guardan
  todos los avisos y que «Enviado» significa aceptado por el servidor del sitio, no entregado; un
  formulario GET con `page`, los desplegables «Tipo» y «Estado», «Filtrar» y, con filtros, «Quitar
  filtros»; el total; la tabla `.escritoriowp-tabla` con Fecha, Tipo, Asunto (enlace al detalle),
  Destinatarios y Estado (`.escritoriowp-etiqueta` con `data-tono`); y `paginate_links()` con los
  filtros en `add_args`. Sin avisos, el texto de vacío y el enlace a `Ajustes::url()`; con filtros sin
  coincidencias, su texto y «Quitar filtros».
- **Detalle** con `&aviso={id}` en la misma pantalla: fecha, tipo, estado, destinatarios, asunto,
  motivo si falló y el mensaje en un bloque con `white-space: pre-wrap`. Las URL del mensaje se quedan
  como texto. El enlace al detalle conserva `tipo`, `estado` y `paged` del listado, y el de vuelta los
  recupera.
- Los parámetros de la URL solo eligen qué se ve y no cambian nada: sin nonce, con su `phpcs:ignore
  WordPress.Security.NonceVerification.Recommended` justificado, como la confirmación de
  Administradores. Todo pasa por `Registro::filtros()`, `absint()` y `esc_html()`/`esc_attr()`/`esc_url()`.
- Encola `escritoriowp-ajustes` solo en su pantalla, comparando el identificador que devuelve
  `add_submenu_page()`.

### D9. Bloque del escritorio y su ruta REST

- **Ruta** `escritoriowp/v1/avisos` (GET) en `Rest::registrar_rutas()`, con
  `permission_callback` `Rest::exigir_ver_avisos()` (identificado y `PantallaAvisos::puede_ver()`, con
  `error_acceso()`: 401 sin sesión y 403 con ella) y el argumento `limite` (por defecto 5, saneado con
  `Registro::limite()`). Controlador `Rest\ControladorAvisos::obtener()` → `{ elementos: [...] }`.
- **Forma de cada fila**, la misma que la de los paneles para reutilizar su pintado: `titulo` (asunto),
  `urlEditar` (`PantallaAvisos::url_detalle()`), `urlVer` vacía, `imagen` vacía, `icono`
  (`dashicons-admin-plugins` para los cuatro tipos de plugins, `dashicons-shield` para administradores)
  y `metas`: el estado con su `tono`, el tipo y la fecha relativa. La construye
  `PantallaAvisos::fila()`, con los nombres traducidos en PHP.
- **Esqueleto** en `Escritorio::pintar_app()`, entre la sección de resumen y la de paneles, solo si
  `PantallaAvisos::puede_ver()` y (alguna casilla `aviso_*` está marcada o `Registro::hay_avisos()`;
  la consulta solo se hace si todas están apagadas). Una `<section data-escritoriowp-avisos
  aria-labelledby>` con la cabecera de panel (`h3` «Avisos por correo» y «Ver todos» a
  `PantallaAvisos::url()`) pintada en PHP y un cuerpo vacío. Ocupa el ancho completo, fuera de la
  rejilla de paneles, y no entra en `config.paneles` ni en las preferencias: «Personalizar» no lo ve.
- **JavaScript.** Se extrae de `pintarFilas()` el pintado de una lista de filas (`pintarElementos(
  cuerpo, elementos )`), que aprende a pintar `icono` en el lugar de la inicial. `cargarAvisos()` pide
  `avisos?limite=5` con el esqueleto de carga de los paneles, pinta con `pintarElementos()`, muestra
  `EWP.t( 'avisosVacio' )` si no hay filas y `bloqueError()` con reintento si falla. «Actualizar»
  también lo llama. Si el nodo no existe, no se pide nada.
- **Textos.** `avisosVacio` en `Activos::textos()`; `errorCarga` y `reintentar` ya existen. El resto
  de textos del bloque llega pintado por PHP o en las filas.

Descartado convertirlo en un panel más de `Capacidades::TIPOS_PANEL`: el usuario lo pidió como bloque
propio, y como panel entraría en el ajuste «Paneles», en «Personalizar» y en el catálogo de comandos
de creación, que no tienen sentido para un registro.

### D10. Conservación y desinstalación

Sin caducidad, por decisión del usuario. Volumen esperado: los avisos de plugins nacen de acciones
humanas y la revisión de administradores avisa como mucho cuatro veces al día y solo con cambios; diez
avisos diarios durante diez años son unos 36.500 registros de pocos kilobytes. El listado pagina por
la clave primaria y nunca trae el mensaje, así que el tamaño no afecta a las pantallas.

`uninstall.php` borra, con nombres literales como el resto del fichero, la tabla y la opción de versión
de cada sitio: en multisitio recorre `get_sites( array( 'fields' => 'ids', 'number' => 0 ) )` y forma el
nombre con `$wpdb->get_blog_prefix( $id )` (sin `switch_to_blog()`); fuera de multisitio, solo el sitio
actual. `DROP TABLE IF EXISTS` con su `phpcs:ignore` de `SchemaChange` justificado. Descartado buscar
las tablas con `SHOW TABLES LIKE '{base_prefix}%escritoriowp_avisos'`: en una base de datos compartida
por dos instalaciones (`wp_` y `wp_tienda_`) borraría la tabla de la otra. Las demás opciones siguen
borrándose solo en el sitio actual, como hasta ahora.

La desactivación no toca la tabla.

## Risks / Trade-offs

- [Datos personales sin caducidad: IPs, nombres de usuario y correos se quedan para siempre] → Es la
  decisión del usuario. `readme.txt` lo explica en una pregunta frecuente y dice que desinstalar los
  borra. La caducidad configurable y el exportador y borrador de datos personales de WordPress quedan en
  `BACKLOG.md`.
- [«Enviado» no quiere decir entregado: un correo aceptado puede rebotar o acabar en spam] → La
  introducción de la pantalla Avisos lo dice. El registro conserva el mensaje igualmente, que es lo
  que importa.
- [Un plugin de SMTP que sustituye `wp_mail()` puede no disparar `wp_mail_failed`, o encolar y
  devolver `true`] → Fallido con el motivo genérico; Enviado significa aceptado por su cola.
- [La base de datos falla al guardar] → D6: el correo sale igual, nada se imprime y el error queda en
  `error_log`. Ese aviso no tendrá registro.
- [Un registro se queda en «Sin confirmar» para siempre] → Es correcto: la petición murió sin saberlo.
  No se limpia.
- [`dbDelta()` sobre SQLite] → La creación se verifica en la instalación SQLite; la de MySQL, en la
  comprobación manual con una instalación MySQL.
- [Bajar a una versión anterior] → La versión antigua ignora la tabla; si después se desinstala con
  esa versión, la tabla se queda en la base de datos.
- [El catálogo del lanzador está cacheado 12 horas] → Se invalida al cambiar de versión, que es como
  llega el cambio. En desarrollo hay que borrar el transitorio `escritoriowp_comandos_{id}` para ver la
  pantalla nueva en el lanzador.
- [Orden de archivado] → El delta de `menu-administracion` parte del texto que deja
  `pantalla-administradores`: hay que archivar ese cambio (y `tipos-contenido-personalizados`, que toca
  `escritorio-panel`) antes que este.

## Migration Plan

No hay datos que migrar. Al actualizar, la tabla se crea con el primer aviso (D3) o al reactivar el
plugin. Los avisos anteriores a la actualización no existen en el registro. Para volver atrás basta
con instalar la versión anterior: no lee la tabla y todo lo demás sigue igual.
