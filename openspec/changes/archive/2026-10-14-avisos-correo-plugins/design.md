## Context

Motivación en `proposal.md`; comportamiento exigido en `specs/avisos-plugins/spec.md` y en el delta de
`specs/nucleo-plugin/spec.md`.

Lo que condiciona el enfoque, comprobado en el código de WordPress 7.1.3:

- **Activar y desactivar.** `activate_plugin()` dispara `activated_plugin( $plugin, $network_wide )` y
  `deactivate_plugins()` dispara `deactivated_plugin( $plugin, $network_deactivating )`, salvo en modo
  silencioso. Las acciones en lote (`activate_plugins()`), la API REST (`WP_REST_Plugins_Controller`) y
  WP-CLI llaman a esas mismas funciones sin silencio.
- **Actualizar es silencioso.** `Plugin_Upgrader::deactivate_plugin_before_upgrade()` desactiva con
  `deactivate_plugins( $plugin, true )` y `update.php?action=activate-plugin` reactiva con
  `activate_plugin( …, true )`: ninguno dispara los enganches. Por eso basta con escucharlos para no
  avisar de actualizaciones.
- **Instalar.** `Plugin_Upgrader::install()` llama a `run()` con
  `hook_extra = { type: 'plugin', action: 'install' }`, y `run()` dispara `upgrader_process_complete`
  **también cuando la instalación falla**: hay que mirar `$upgrader->result`. El fichero principal sale
  de `Plugin_Upgrader::plugin_info()`. Lo usan el instalador (por AJAX), la subida de zip (también al
  sustituir uno instalado), la API REST y WP-CLI.
- **Borrar.** `delete_plugins()` dispara `delete_plugin( $archivo )` **antes** de borrar los ficheros y
  `deleted_plugin( $archivo, $borrado )` después. En el segundo los datos del plugin ya no existen.
  Con JavaScript, la pantalla Plugins borra **uno por petición** (`wp_ajax_delete_plugin`), también en
  lote.
- **El formato del correo.** `wp_mail()` lee la cabecera `Content-Type` y **después** aplica el filtro
  `wp_mail_content_type`: un plugin que pone todo en HTML gana a la cabecera.
- **El final de la petición.** `wp-settings.php` registra `shutdown_action_hook` con
  `register_shutdown_function()`, así que `shutdown` corre también tras `exit` (las redirecciones de
  Plugins) y tras `wp_die()` (AJAX).
- **El arranque.** El plugin arranca en `plugins_loaded`. Al activar el propio EscritorioWP ese
  momento ya pasó, así que en esa petición no hay módulos; al desactivarlo, sí.
- **Ajustes.** `Ajustes::sanear()` es pura y sanea por tipo con un `match`; `pintar_campo()` pinta según
  el `tipo` declarado (`casilla`, `numero`, `desplegable`, `color`). Las pruebas no cargan WordPress:
  sin `__()` ni `is_email()`.

## Goals / Non-Goals

**Goals:**

- Que la sustitución de marcadores, el resumen de nombres, la línea de cada plugin y el saneado de
  correos y textos sean funciones puras con pruebas.
- Que con los cuatro avisos apagados, que es el valor por defecto, el módulo no registre ningún
  enganche.
- Que un fallo de correo nunca afecte a la acción sobre el plugin.

**Non-Goals:**

- Cola, reintentos o registro de envíos: si `wp_mail()` falla, el aviso se pierde.
- Un registro de actividad consultable en el administrador.
- Vigilar la opción `active_plugins` directamente (ver Riesgos).
- Plantillas en HTML o una vista previa en la pantalla de ajustes.

## Decisions

### 1. Dos clases en `includes/avisos/`

| Clase | Responsabilidad | WordPress |
|-------|-----------------|-----------|
| `Avisos\Plantilla` | Lista de marcadores, sustitución, asunto en una línea, resumen de nombres y línea de cada plugin | No: funciones puras, probadas |
| `Avisos\AvisosPlugins` | Enganches, recogida de eventos, valores de los marcadores, textos predeterminados y envío | Sí |

El saneado de los tres ajustes de texto va en `Ajustes`, junto a `sanear_color()` y
`sanear_menu_debajo_de()`, porque lo llama `Ajustes::sanear()`.

Alternativa descartada: meterlo en `includes/seguridad/`. Aunque el aviso tiene uso de seguridad, no
calcula permisos ni se lee como la pantalla Administradores; una carpeta propia deja sitio a avisos de
otro tipo sin mezclarlos.

### 2. Escuchar los enganches de WordPress, no la opción `active_plugins`

| Evento | Enganche | Datos del plugin |
|--------|----------|------------------|
| activacion | `activated_plugin` | `get_plugin_data()` en el momento |
| desactivacion | `deactivated_plugin` | `get_plugin_data()` en el momento |
| instalacion | `upgrader_process_complete` | `plugin_info()` y `get_plugin_data()` |
| borrado | `delete_plugin` (lee) + `deleted_plugin` (anota si `$borrado`) | Leídos antes de borrar |

En `upgrader_process_complete` se exige `$upgrader instanceof \Plugin_Upgrader`,
`'plugin' === $extra['type']`, `'install' === $extra['action']`, que `$upgrader->result` sea un array y
no un `WP_Error`, y que `plugin_info()` devuelva un fichero. `get_plugin_data()` se llama con
`$markup = false` y `$translate = false`, cargando `wp-admin/includes/plugin.php` si hiciera falta.
Si el plugin no tiene nombre, se usa su fichero.

Alternativa descartada: `update_option_active_plugins`. Vería también los cambios hechos con
`update_option()`, pero confunde la desactivación silenciosa de cada actualización con una
desactivación real, y para separarlas habría que seguir el estado del actualizador.

`registrar()` solo engancha los eventos cuya casilla está marcada; con las cuatro apagadas no engancha
nada. Se compone en `Plugin::arrancar()` fuera de `is_admin()`, porque la API REST y WP-CLI no pasan
por el administrador.

### 3. Recoger durante la petición y enviar en `shutdown`

Cada evento anota `pendientes[ evento ][ archivo ] = { nombre, version, red }` y, la primera vez,
engancha `enviar()` a `shutdown`. `enviar()` manda un correo por evento, en el orden en que apareció
cada uno. Indexar por fichero hace que un plugin anotado dos veces para el mismo evento cuente una.

Alternativas descartadas:

- **Enviar en el momento**: un lote de veinte plugins serían veinte correos y veinte envíos SMTP
  seguidos antes de la redirección.
- **Programar un evento de cron**: no espera al envío, pero depende del tráfico del sitio y, si lo
  desactivado es EscritorioWP, cuando corre el cron su callback ya no existe y el aviso se pierde.

El usuario, la IP y la fecha se leen al enviar; son los de la misma petición.

### 4. Marcadores y sus valores

`Plantilla::MARCADORES` es la lista cerrada. `Plantilla::rellenar()` usa `strtr()` con las claves
`{marcador}`: una sola pasada, así que el valor de un marcador nunca se vuelve a interpretar, y lo que
no está en la lista se queda igual.

| Marcador | Valor |
|----------|-------|
| `{evento}` | «Activación», «Desactivación», «Instalación» o «Borrado» |
| `{plugins}` | `Plantilla::resumir()`: hasta tres «Nombre versión»; «A, B y C» o «A, B, C y 2 más» |
| `{lista}` | `Plantilla::linea()` por plugin: `- Nombre versión (archivo)`, más « — en toda la red» si procede |
| `{cantidad}` | Número de plugins del correo |
| `{usuario}` | «Nombre visible (login)», solo el login si coinciden, «WP-CLI» o «Sin usuario identificado» |
| `{fecha}` | `wp_date()` con `date_format` y `time_format` del sitio |
| `{sitio}` | `blogname` sin entidades HTML |
| `{url}` | `home_url( '/' )` |
| `{url_plugins}` | `self_admin_url( 'plugins.php' )` (la de red en el administrador de red) |
| `{ip}` | `REMOTE_ADDR` validada con `FILTER_VALIDATE_IP`, o «—» |

Los sustantivos de `{evento}` evitan la concordancia: «Activación: Akismet» funciona igual con uno o con
cinco plugins. `resumir()` y `linea()` reciben ya traducidos los formatos (`%1$s y %2$s`,
`%1$s y %2$d más`, la marca de red): así son puras y la traducción queda en `AvisosPlugins`.

`{ip}` no lee `X-Forwarded-For`: lo puede escribir quien hace la petición.

### 5. Textos predeterminados: vacío en la opción, rellenado en el formulario

`aviso_asunto` y `aviso_mensaje` valen `''` por defecto, y `''` significa «el predeterminado»,
traducido en el momento del envío por `AvisosPlugins::asunto_por_defecto()` y `mensaje_por_defecto()`:

```
Asunto:  [{sitio}] {evento}: {plugins}

Mensaje: Ha habido un cambio en los plugins de {sitio} ({url}).

         Evento: {evento}
         Plugins ({cantidad}):
         {lista}

         Usuario: {usuario}
         IP: {ip}
         Fecha: {fecha}

         Revisa los plugins en {url_plugins}
```

Guardar el texto traducido en la opción lo congelaría en el idioma de la activación y en la versión
del plugin. Para que el formulario lo enseñe sin guardarlo:

- El campo declara un `predeterminado` y `pintar_campo()` lo muestra cuando el valor es `''`.
- Al guardar desde el formulario, un texto igual al predeterminado (tras normalizar saltos de línea y
  espacios de los extremos) se guarda como `''`. Así, quien guarda otro ajuste sin tocar estos sigue
  recibiendo el texto predeterminado actual.

### 6. Saneado

Funciones puras en `Ajustes`, con su rama en el `match` de `sanear()`:

- `sanear_correos()`: parte por comas, punto y coma o espacios; pasa a minúsculas; valida con
  `filter_var( …, FILTER_VALIDATE_EMAIL )`; quita repetidas; conserva las diez primeras
  (`MAX_DESTINATARIOS`) y las une con `, `. `correos_descartados()` devuelve las no válidas.
- `sanear_asunto()`: quita etiquetas, sustituye controles y saltos de línea por un espacio, colapsa
  espacios y recorta a 200 caracteres.
- `sanear_mensaje()`: quita etiquetas, normaliza los saltos a `\n`, quita controles salvo `\n` y `\t`,
  recorta los extremos y a 5.000 caracteres.

Se valida con `filter_var` y no con `is_email()` para que la función sea pura. `wp_mail()` vuelve a
validar al enviar.

`register_setting()` pasa a usar `sanear_formulario()`, un envoltorio no puro que llama a `sanear()`,
aplica la regla de los predeterminados de la decisión 5 y añade el aviso de direcciones descartadas
con `add_settings_error()`, solo si no está ya (WordPress puede llamar dos veces al saneado). Lo que
viene de la base de datos sigue pasando por `sanear()`.

### 7. Tres tipos de campo nuevos

| Tipo | HTML | Uso |
|------|------|-----|
| `correo` | `<input type="text" class="regular-text">` con `placeholder` = correo de administración | Destinatarios |
| `texto` | `<input type="text" class="large-text">` | Asunto |
| `area` | `<textarea rows="10" class="large-text">` | Mensaje |

`correo` es de texto y no `type="email" multiple`: el navegador bloquearía el envío por una dirección
mal escrita y el usuario no vería el aviso del servidor que nombra cuál era.

La leyenda de marcadores la pinta `pintar_campo()` bajo el mensaje cuando el campo trae `leyenda`
(marcador ⇒ significado), que `AvisosPlugins::marcadores()` devuelve traducida en el orden de
`Plantilla::MARCADORES`. La sección «Avisos por correo» va detrás de «Menú».

### 8. Envío

```
wp_mail( $destinatarios, Plantilla::una_linea( $asunto ), $mensaje,
         array( 'Content-Type: text/plain; charset=UTF-8' ) )
```

Durante la llamada se engancha `wp_mail_content_type` con prioridad `PHP_INT_MAX` devolviendo
`text/plain`, y se quita justo después, también si `wp_mail()` lanza una excepción (`finally`). Los
destinatarios vacíos usan `get_option( 'admin_email' )`. `enviar()` envuelve cada envío en
`try`/`catch ( \Throwable )`: estamos en `shutdown` y un error ahí puede ensuciar la respuesta.

## Risks / Trade-offs

- **[El envío alarga la petición]** `shutdown` corre antes de cerrar la respuesta, así que el SMTP
  añade su tiempo a la redirección → una sola vez por evento, no por plugin. No se usa
  `fastcgi_finish_request()`: cortaría la salida de otros enganches de `shutdown`.
- **[Un error fatal antes de `shutdown`]** PHP ejecuta igualmente las funciones de cierre, pero
  WordPress puede quedar a medias → el aviso de esa petición puede perderse. Asumible: una petición
  rota no completa la acción.
- **[Borrado en lote con JavaScript]** La pantalla Plugins borra uno por petición, así que un borrado
  en lote manda un correo por plugin → se documenta en `readme.txt` y `docs/qa.md`; agrupar entre
  peticiones necesitaría guardar estado.
- **[Cambios que no pasan por WordPress]** Un atacante con acceso a la base de datos puede activar un
  plugin editando `active_plugins` sin disparar nada, y uno con acceso a ficheros puede copiar uno →
  límite declarado en la spec («Cambios que no avisan»). La pantalla Administradores y otras
  herramientas cubren otras vías.
- **[Activar EscritorioWP no avisa]** En esa petición el plugin aún no ha arrancado; y cuando se borra,
  ya está desactivado → se documenta. Su desactivación sí avisa, que es la que importa para seguridad.
- **[IP detrás de un proxy o CDN]** `{ip}` será la del proxy → preferible a una IP falsificable; se
  explica en la leyenda.
- **[Correos con datos personales]** El correo incluye nombre de usuario e IP → va solo a los
  destinatarios que el administrador configura; se menciona en `readme.txt`.
- **[`filter_var` y `is_email()` no coinciden del todo]** Una dirección rara puede pasar el saneado y
  fallar en `wp_mail()` → el envío falla sin afectar a la acción; las direcciones normales coinciden.

## Migration Plan

- Sin migración: las siete claves nuevas toman su valor por defecto (avisos apagados) al leer la opción.
- Desinstalar ya borra la opción entera; no se añaden transitorios ni metadatos.
- Volver a una versión anterior deja claves que nadie lee, inofensivas.
- Orden de archivado: `releases-github-y-menu-propio`, después `menu-posicion-y-color` y después este,
  porque el delta de `nucleo-plugin` parte del texto que deja `menu-posicion-y-color`.
