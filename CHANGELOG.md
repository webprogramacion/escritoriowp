# Registro de cambios

Todos los cambios relevantes de este proyecto se documentan en este fichero.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y el proyecto utiliza [versionado semántico](https://semver.org/lang/es/).

## [No publicado]

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
