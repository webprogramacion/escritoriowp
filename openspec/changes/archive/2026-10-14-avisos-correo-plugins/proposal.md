## Why

Activar, desactivar, instalar o borrar un plugin cambia lo que hace el sitio, y WordPress no avisa a
nadie: si otra persona con acceso, una agencia o un atacante con una cuenta comprometida lo hace, el
responsable del sitio se entera cuando algo falla. Desactivar el plugin de seguridad o subir un zip
con una puerta trasera son justo los pasos que interesa saber en el momento. Hoy hace falta un plugin
de registro de actividad completo solo para recibir ese aviso.

## What Changes

- **Sección nueva «Avisos por correo»** en EscritorioWP › Ajustes, con:
  - Cuatro casillas, una por evento: avisar al **activar**, al **desactivar**, al **instalar** y al
    **borrar** un plugin. Todas desmarcadas por defecto: ninguna instalación empieza a mandar correos
    al actualizar.
  - **Destinatarios**: una o varias direcciones separadas por comas. Vacío envía al correo de
    administración del sitio (Ajustes › Generales), que es lo que se muestra como ejemplo en el campo.
    Al guardar se descartan las direcciones no válidas y un aviso dice cuáles.
  - **Asunto** y **mensaje** comunes a los cuatro eventos, con un texto predeterminado ya rellenado.
    Vaciar un campo devuelve su texto predeterminado.
  - Una leyenda con los marcadores disponibles.
- **Marcadores** que se sustituyen al enviar: `{evento}` (Activación, Desactivación, Instalación o
  Borrado), `{plugins}` (nombres y versiones resumidos para el asunto), `{lista}` (un plugin por línea
  con su versión y su fichero), `{cantidad}`, `{usuario}`, `{fecha}`, `{sitio}`, `{url}`,
  `{url_plugins}` e `{ip}`. Un marcador que no existe se deja tal cual, para que se vea la errata.
- **Un correo por evento y petición.** Activar o desactivar varios plugins en lote manda un solo
  correo con todos; instalar y activar en la misma orden (por ejemplo, con WP-CLI) manda dos, uno por
  evento. Los correos salen al terminar la petición, así que un lote cuesta un solo envío y no uno
  por plugin.
- **Funciona por cualquier vía que use las funciones de WordPress**: la pantalla Plugins, sus acciones
  en lote, el instalador, la subida de un zip, la API REST y WP-CLI.
- **Las actualizaciones no avisan.** WordPress desactiva y reactiva el plugin en silencio al
  actualizarlo, y ese ciclo no cuenta como desactivación ni activación.
- Si alguien desactiva el propio EscritorioWP, el aviso de esa desactivación sí sale.
- El correo es de texto plano en UTF-8.

## Capabilities

### New Capabilities

- `avisos-plugins`: avisos por correo cuando se activa, desactiva, instala o borra un plugin: qué
  eventos se vigilan, a quién se envía, cómo se agrupan, la plantilla con sus marcadores y qué queda
  fuera.

### Modified Capabilities

- `nucleo-plugin`: el requisito «Ajustes globales con valores por defecto» incorpora los siete ajustes
  nuevos (cuatro casillas desmarcadas, destinatarios, asunto y mensaje vacíos, que significan «el
  predeterminado») y sus tipos de saneado. Nota: el cambio `menu-posicion-y-color`, completo pero sin
  archivar, ya modifica este mismo requisito; **tiene que archivarse antes que este**, porque este delta
  parte de su texto.

## Impact

- **Plugin:** carpeta nueva `includes/avisos/` con la plantilla (funciones puras) y el módulo que
  escucha los eventos y envía; `includes/class-ajustes.php` (claves nuevas, saneado, sección, tipos de
  campo `correo`, `texto` y `area`, aviso de direcciones descartadas); `includes/class-plugin.php` (lo
  compone, también fuera del administrador para cubrir REST y WP-CLI); `assets/css/ajustes.css` (la
  leyenda y el área de texto); `readme.txt`; `languages/escritoriowp.pot`.
- **Base de datos:** solo siete claves nuevas en la opción `escritoriowp_ajustes`. Sin transitorios ni
  metadatos: la desinstalación ya borra la opción entera.
- **Desarrollo:** `tests/prueba-ajustes.php` y `tests/prueba-plantilla.php` (nueva), `docs/qa.md`,
  `CLAUDE.md`, `README.md`, `CHANGELOG.md`, `BACKLOG.md`.
- **Dependencias:** ninguna nueva. Usa `wp_mail()`, así que el correo sale por donde el sitio lo tenga
  configurado (SMTP o el servidor).
- **Fuera de alcance:** avisar de actualizaciones (WordPress ya avisa de las automáticas), de temas, de
  plugins imprescindibles (`mu-plugins`) y de cambios hechos sin las funciones de WordPress (copiar
  ficheros por FTP o editar la opción `active_plugins` en la base de datos); un botón de correo de
  prueba y una plantilla en HTML, que quedan en `BACKLOG.md`.
