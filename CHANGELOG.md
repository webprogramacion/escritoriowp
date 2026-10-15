# Registro de cambios

Todos los cambios relevantes de este proyecto se documentan en este fichero.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y el proyecto utiliza [versionado semántico](https://semver.org/lang/es/).

## [No publicado]

### Añadido

- Aviso por correo de los cambios en los administradores, con la casilla nueva «Avisar de cambios en
  los administradores» de la sección «Avisos por correo» (desmarcada por defecto). Mientras está
  marcada, una revisión de WP-Cron cada 6 horas clasifica a los usuarios con el mismo criterio que la
  pantalla Administradores y, si algo ha cambiado desde la revisión anterior, envía un solo correo
  con los administradores nuevos, los usuarios nuevos con permisos de administrador sin el rol (con
  sus permisos y su origen), los que dejan de ser administradores y los que dejan de tener esos
  permisos, diciendo si el usuario se ha borrado y señalando a los que algún código esconde de la
  pantalla Usuarios. Cada apartado lista como mucho 50 usuarios. Lo que ya existe al marcar la
  casilla no avisa, y si el correo no se puede enviar, la revisión siguiente vuelve a avisar. Usa los
  mismos destinatarios que los avisos de plugins, con un texto fijo, y la casilla muestra cuándo fue
  la última revisión y cuándo toca la próxima.
- Tipos de contenido personalizados en el escritorio. Los grupos «Indicadores» y «Paneles» de
  EscritorioWP › Ajustes detectan los tipos de contenido personalizados con entrada en el menú del
  administrador y los listan al final, desmarcados. Un tipo marcado en «Paneles» tiene su panel con
  los últimos elementos modificados, como el de entradas, y un botón de crear que lleva a su pantalla
  de creación nativa; marcado en «Indicadores», su tarjeta con los publicados y los borradores. Cada
  usuario solo los ve si su rol le deja editar ese tipo. Si el plugin que registra el tipo se
  desactiva, el panel y la tarjeta desaparecen y vuelven al reactivarlo, con las preferencias de cada
  usuario intactas. Otro plugin puede quitar o añadir tipos con el filtro nuevo
  `escritoriowp_tipos_contenido`.

### Cambiado

- Desactivar el plugin quita su tarea de WP-Cron, y desinstalarlo borra también la última revisión
  guardada de los administradores.
- El asunto y el mensaje configurables de los avisos por correo dicen que solo se aplican a los
  avisos de plugins.
- Los textos del estado vacío de cada panel llegan desde el servidor con el panel, en lugar de estar
  en el catálogo de textos del JavaScript. No cambia ningún texto visible.

## [0.4.0] - 2026-10-14

### Añadido

- Ajuste «Posición en el menú», en la sección nueva «Menú» de EscritorioWP › Ajustes: un
  desplegable con los menús de primer nivel del sitio para elegir debajo de cuál aparece
  EscritorioWP, o «Al final». Si el menú elegido deja de existir, por ejemplo al desactivar
  WooCommerce, EscritorioWP vuelve bajo el Escritorio y recupera su sitio cuando el menú vuelve.
- Ajuste «Color de fondo en el menú», con el selector de color de WordPress: pinta el fondo de la
  entrada EscritorioWP de la barra lateral para que destaque, sin tocar el resto del menú. El texto
  y el icono se ponen en blanco o en negro para que el contraste nunca baje de 4,5:1, y al pasar el
  ratón o con el foco del teclado la entrada responde como cualquier otra. Vacío por defecto.
- Pantalla EscritorioWP › Administradores, para detectar puertas traseras. Lista a los
  administradores (y a los superadministradores en multisitio) y marca en rojo a los usuarios sin el
  rol Administrador que tienen guardado algún permiso que da control del sitio, diciendo si lo tienen
  asignado o lo heredan de un rol alterado; a los usuarios que algún código esconde de la pantalla
  Usuarios; y los roles alterados. Lee la base de datos directamente y calcula los permisos como
  WordPress, así que no la engaña el código que oculta usuarios. Solo para quien tiene
  `manage_options` y `list_users`.
- Contador rojo en el menú EscritorioWP y en su entrada Administradores cuando hay usuarios que
  revisar.
- Botón «Retirar permisos» en la pantalla Administradores: tras una confirmación que lista
  exactamente qué se va a quitar, retira a un usuario los permisos de administrador que tiene
  asignados directamente, sin tocar su rol ni el resto de sus permisos. Exige poder cambiar el rol de
  ese usuario y nadie puede retirarse sus propios permisos.
- Avisos por correo, en la sección nueva «Avisos por correo» de EscritorioWP › Ajustes: un correo
  cada vez que alguien activa, desactiva, instala o borra un plugin, con una casilla por evento
  (todas apagadas por defecto). Se eligen los destinatarios (el correo de administración si no se
  pone ninguno) y un asunto y un mensaje comunes con marcadores: `{evento}`, `{plugins}`, `{lista}`,
  `{cantidad}`, `{usuario}`, `{fecha}`, `{sitio}`, `{url}`, `{url_plugins}` e `{ip}`. Un cambio en
  lote manda un solo correo con todos los plugins. Funciona desde la pantalla Plugins, la API REST y
  WP-CLI, avisa también si se desactiva EscritorioWP y no avisa de las actualizaciones.
- Ajustes «Indicadores» y «Paneles», en la sección «Escritorio» de EscritorioWP › Ajustes: una
  casilla por cada tarjeta de resumen y por cada panel de últimos elementos para elegir qué muestra
  el escritorio a todos los usuarios. Vienen todas marcadas, así que nada cambia hasta que desmarques
  alguna. Lo marcado lo sigue viendo cada usuario solo si su rol se lo permite, y «Personalizar» solo
  ofrece los paneles activos, sin perder lo que cada usuario había ocultado. Sin indicadores activos
  desaparece la fila de tarjetas, y con las tres de WooCommerce desmarcadas el escritorio deja de
  recorrer los pedidos del mes. Las casillas de WooCommerce solo aparecen con WooCommerce activo y
  mientras no lo está conservan su valor.

### Cambiado

- El menú «EscritorioWP» aparece por defecto justo debajo del Escritorio en lugar de al final de la
  barra lateral. Para dejarlo donde estaba, elige «Al final» en «Posición en el menú».
- El saneado de ajustes trata cada ajuste según su tipo, en lugar de suponer que todo lo que no es
  una casilla es el número de elementos por panel. Los ajustes guardados no cambian.
- `tools/comprobar-contraste.js` comprueba también los colores que estrena la pantalla
  Administradores: el texto secundario sobre el fondo, el texto de los resúmenes de alerta y de todo
  correcto, y el del botón de peligro.

### Corregido

- La pantalla EscritorioWP › Ajustes vuelve a mostrar el aviso «Ajustes guardados» al guardar: desde
  que vive en el menú propio, WordPress ya no lo pintaba por su cuenta.

### Eliminado

- Se retiran la orden `/opsx:update` de Claude Code y su skill `openspec-update-change`, que
  llegaron en la 0.3.0 con las skills de OpenSpec.

## [0.3.0] - 2026-10-12

### Añadido

- `tools/nueva-version.php` sube la versión en los tres sitios donde vive y convierte
  `## [No publicado]` de este fichero en la sección de la versión. Con `--simular` dice qué número
  saldría sin escribir nada, y con `--comprobar` repite en local las comprobaciones de coherencia
  que hace el workflow antes de publicar.
- Skill `/nueva-version` para Claude Code. Prepara una versión entera en `main`: redacta las
  novedades a partir de los commits desde el último tag, las enseña antes de escribir nada, sube la
  versión, actualiza los dos changelogs, regenera el `.pot`, pasa las comprobaciones y hace el commit
  `Release X.Y.Z`. Pregunta antes de empujar, porque el push es lo que publica la release.

### Cambiado

- Las skills y los comandos de OpenSpec para Claude Code se regeneran con OpenSpec 1.14.1, que
  añade `/opsx:update` para revisar los artefactos de un cambio ya propuesto sin tocar el código.

### Corregido

- En pantallas de entre 800 y 1800 píxeles de ancho, que es donde caen casi todos los portátiles,
  el escritorio se quedaba encajonado en la mitad o en un tercio de la pantalla. Las reglas de
  columnas de WordPress ganaban a las del plugin; ahora la interfaz ocupa todo el ancho.

## [0.2.1] - 2026-09-13

Versión de mantenimiento, sin cambios en el comportamiento del plugin.

### Corregido

- El generador de la plantilla de traducción llevaba la versión escrita a mano, así que el `.pot`
  distribuido anunciaba siempre la 0.1.0. Ahora la lee de la cabecera del plugin.
- Las dependencias de desarrollo se resuelven contra PHP 8.1, la versión mínima que declara el
  plugin, para que la integración continua pueda instalarlas.

## [0.2.0] - 2026-09-13

### Añadido

- Menú propio «EscritorioWP» en el administrador, con dos pantallas: Ajustes y Acerca de.
- Pantalla Acerca de con la versión instalada, los enlaces del proyecto, el estado de la
  actualización con un botón para comprobarla al momento, y el historial de novedades leído del
  `readme.txt` que el propio plugin distribuye.
- Actualizaciones desde las releases públicas de GitHub mediante la cabecera `Update URI` y el
  filtro `update_plugins_github.com`: aviso en la pantalla Plugins, «Ver detalles» con el changelog
  de la release y actualización con un clic. La consulta se guarda en caché doce horas, y una hora
  si falla, de modo que pintar una pantalla nunca provoca una petición de red.
- Publicación automática: cada push a `main` que suba el número de versión crea el tag `vX.Y.Z` y
  publica la release con `escritoriowp-X.Y.Z.zip` y las notas de este changelog.
- `tools/empaquetar.php` genera el zip instalable y, con `--wordpress-org`, la variante sin el
  actualizador que exige el directorio oficial.
- `tools/notas-release.php` extrae de este fichero las notas de una versión.
- Plugin Check, la revisión oficial del directorio de WordPress.org, se ejecuta en cada push.
- `docs/publicar-wordpress-org.md` con la lista de comprobación para enviar el plugin al directorio.

### Cambiado

- La pantalla de ajustes deja de estar en Ajustes › EscritorioWP y pasa a ser la primera entrada del
  menú propio, en `admin.php?page=escritoriowp`. La URL antigua deja de funcionar.
- El workflow de GitHub Actions se ejecuta en cada push y en cada pull request a `main`, no solo al
  empujar un tag. Ya no se empujan tags a mano.

## [0.1.0] - 2026-09-13

### Añadido

- Escritorio propio que sustituye al nativo: cabecera con saludo y accesos rápidos, indicadores de
  resumen y paneles con los últimos elementos de entradas, páginas, usuarios, productos y pedidos.
- Creación rápida de entradas, páginas, usuarios y productos mediante modal, con validación en el
  servidor y control de capacidades.
- Lanzador propio con ⌘K / Ctrl+K que sustituye a la paleta de comandos de wp-admin, con búsqueda
  de pantallas del menú, acciones rápidas y contenido (títulos, emails, SKU y números de pedido).
- Búsqueda unificada en el servidor mediante la ruta REST `escritoriowp/v1/buscar`.
- Personalización por usuario: paneles ocultables y reordenables, y tema claro, oscuro o automático.
- Página de ajustes en Ajustes › EscritorioWP.
- Integración opcional con WooCommerce, compatible con HPOS y con el almacenamiento clásico.
