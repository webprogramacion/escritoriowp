## Why

Los administradores y editores de los sitios que gestionamos aterrizan en el escritorio nativo de WordPress, que es genérico, está lleno de widgets irrelevantes y no da acceso rápido a lo que realmente se gestiona a diario (entradas, páginas, usuarios y, en tiendas WooCommerce, productos y pedidos). Además, la paleta de comandos que WordPress abre con Cmd/Ctrl+K solo navega por pantallas de administración y no encuentra contenido concreto (un post por su título, un usuario por su email, un pedido por su número).

EscritorioWP sustituye esa experiencia por un escritorio propio, visual y accionable, y por un lanzador (launcher) unificado que busca tanto pantallas del admin como contenido real del sitio.

## What Changes

- **Nuevo plugin `escritoriowp`**, con el código distribuible dentro del subdirectorio `escritoriowp/` del repositorio (raíz = material de desarrollo, subcarpeta = exactamente lo que se sube a `wp-content/plugins/`), siguiendo la convención de `inscripciones-deportivas` y `condiciones-contratacion-woocommerce`.
- **Escritorio renovado** en la pantalla de inicio del admin (`Escritorio`): la interfaz nativa (widgets de WordPress y panel de bienvenida) se sustituye por una interfaz propia con:
  - Cabecera con saludo, accesos rápidos de creación y recordatorio del atajo del lanzador.
  - Tarjetas de resumen (KPIs): entradas, páginas, usuarios, productos, pedidos e ingresos del mes (estas tres últimas solo con WooCommerce activo), comentarios pendientes y actualizaciones pendientes.
  - Paneles de "Últimos elementos" para entradas, páginas, usuarios, productos y pedidos, con enlace a editar/ver y acceso al listado completo.
  - Creación rápida sin salir del escritorio (modal) para entradas, páginas, usuarios y productos; para pedidos, acceso directo a la pantalla nativa de nuevo pedido.
  - Personalización por usuario: mostrar/ocultar paneles y tema claro/oscuro/automático.
  - Cada panel y KPI respeta las capacidades del usuario (quien no puede listar usuarios no ve el panel de usuarios, etc.).
- **Lanzador propio (Cmd+K / Ctrl+K)** que reemplaza la paleta de comandos nativa de wp-admin:
  - Se desactiva la paleta nativa del administrador (sin tocar la del editor de bloques).
  - Se abre con Cmd+K o Ctrl+K en cualquier pantalla del admin y también desde un botón en la barra de administración.
  - Busca pantallas y elementos del menú de administración (Plugins, Páginas, Ajustes › Lectura, etc.), acciones rápidas (nueva entrada, nueva página, ver sitio, cerrar sesión…) y, además, contenido: entradas y páginas por título, cualquier tipo de contenido público por título, usuarios por nombre, login y email, productos por título y SKU, y pedidos por número, nombre o email del cliente.
  - Resultados agrupados por tipo, navegables con teclado, con filtros por tipo, elementos recientes y acciones secundarias (ver en el sitio, abrir en pestaña nueva).
  - Toda búsqueda se resuelve en el servidor respetando capacidades: nadie ve resultados de contenido que no podría abrir en el admin.
- **Página de ajustes** (Ajustes › EscritorioWP) para: sustituir o no el escritorio nativo, conservar widgets de otros plugins, desactivar la paleta nativa y activar/desactivar el lanzador propio.
- **Compatibilidad WooCommerce opcional**: el plugin funciona sin WooCommerce; con él activo añade productos y pedidos, y declara compatibilidad con HPOS (tablas de pedidos personalizadas).

## Capabilities

### New Capabilities

- `nucleo-plugin`: arranque del plugin, requisitos mínimos (WordPress, PHP), estructura distribuible, ajustes y su página de administración, detección de WooCommerce y declaración de compatibilidad HPOS, internacionalización y desinstalación limpia.
- `escritorio-panel`: la pantalla de escritorio sustituta: cabecera, KPIs, paneles de últimos elementos, personalización por usuario, control por capacidades y comportamiento cuando WooCommerce no está activo.
- `creacion-rapida`: creación de entradas, páginas, usuarios y productos desde el escritorio mediante modal, con validación, control de capacidades y redirección al editor.
- `lanzador-comandos`: desactivación de la paleta nativa de wp-admin, atajo Cmd/Ctrl+K, interfaz del lanzador (búsqueda, navegación por teclado, filtros, recientes, acciones) y catálogo de comandos de navegación/acciones.
- `busqueda-unificada`: endpoint REST de búsqueda agregada (entradas, páginas, otros tipos de contenido, usuarios, productos, pedidos) con reglas de coincidencia, límites, orden y control de acceso por capacidades.

### Modified Capabilities

(Ninguna: el proyecto no tiene specs previas.)

## Impact

- **Código nuevo**: todo el plugin (`escritoriowp/`): fichero principal, `includes/` (núcleo, ajustes, escritorio, lanzador, REST), `assets/css` y `assets/js` (sin paso de compilación), `languages/`, `readme.txt`, `uninstall.php`.
- **Material de desarrollo en raíz**: `CLAUDE.md`, `README.md`, `CHANGELOG.md`, `composer.json` + `phpcs.xml.dist` (solo herramientas de linting), `.github/workflows/release.yml` (zip de `escritoriowp/`), `openspec/`.
- **Pantallas de WordPress afectadas**: `wp-admin/index.php` (escritorio), barra de administración (botón del lanzador), todas las pantallas del admin (script del lanzador y desactivación de la paleta nativa), Ajustes (nueva subpágina).
- **APIs**: nuevo espacio REST `escritoriowp/v1` (búsqueda, resumen del escritorio, últimos elementos, creación rápida, preferencias de usuario). Se apoya en las APIs de WordPress (`WP_Query`, `WP_User_Query`) y WooCommerce (`wc_get_products`, `wc_get_orders`) sin consultas SQL directas.
- **Dependencias**: WordPress ≥ 6.7 y PHP ≥ 8.1; WooCommerce opcional (≥ 8.x, con HPOS). Sin dependencias de Composer en producción ni de npm.
- **Datos**: una opción de ajustes (`escritoriowp_ajustes`) y metadatos de usuario para preferencias; ambos se eliminan en la desinstalación.
- **Riesgo funcional**: otros plugins que añadan widgets al escritorio quedan ocultos por defecto (configurable); usuarios acostumbrados a la paleta nativa pasan al lanzador propio (configurable).
