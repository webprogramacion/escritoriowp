# Registro de cambios

Todos los cambios relevantes de este proyecto se documentan en este fichero.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y el proyecto utiliza [versionado semántico](https://semver.org/lang/es/).

## [No publicado]

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
