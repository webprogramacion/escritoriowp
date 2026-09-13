=== EscritorioWP ===
Contributors: webprogramacion
Tags: dashboard, escritorio, buscador, command palette, woocommerce
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sustituye el escritorio de WordPress por una interfaz propia y la paleta de comandos por un
lanzador que además busca entradas, páginas, usuarios, productos y pedidos.

== Description ==

EscritorioWP cambia dos cosas del día a día en el administrador de WordPress.

**Un escritorio útil.** Al entrar en el Escritorio, en lugar de los widgets de siempre verás:

* Indicadores del sitio: entradas publicadas y en borrador, páginas, usuarios, comentarios
  pendientes y actualizaciones pendientes. Con WooCommerce, además productos, pedidos del mes e
  ingresos del mes.
* Paneles con los últimos elementos de cada tipo, con enlace directo a editar o ver.
* Creación rápida en una ventana: entradas, páginas, usuarios y productos sin salir del escritorio.
* Personalización por usuario: oculta paneles, reordénalos y elige tema claro, oscuro o automático.

**Un buscador que encuentra contenido.** La paleta de comandos de WordPress solo navega por
pantallas del administrador. EscritorioWP la sustituye por un lanzador que se abre igual, con
Comando+K o Control+K, y que además busca:

* Entradas y páginas por su título.
* Cualquier otro tipo de contenido con interfaz de administración.
* Usuarios por nombre, nombre de usuario o email.
* Productos por nombre o SKU, incluido el SKU de las variaciones.
* Pedidos por número o por los datos de facturación del cliente.

Todo se resuelve en el servidor respetando las capacidades de cada usuario: nadie ve resultados de
contenido que no podría abrir en el administrador.

= Compatibilidad con WooCommerce =

WooCommerce es opcional. Si está activo, el plugin añade productos y pedidos al escritorio y al
buscador, y declara compatibilidad con el almacenamiento de pedidos de alto rendimiento (HPOS).

= Ajustes =

En Ajustes › EscritorioWP puedes desactivar cada parte por separado: sustituir o no el escritorio,
conservar los widgets de otros plugins, desactivar o no la paleta nativa y activar o desactivar el
lanzador, también en el sitio público.

== Installation ==

1. Sube la carpeta `escritoriowp` a `/wp-content/plugins/`, o instala el zip desde Plugins › Añadir
   nuevo › Subir plugin.
2. Actívalo desde la pantalla de Plugins.
3. Entra en el Escritorio: ya verás la interfaz nueva. Pulsa Comando+K o Control+K para el buscador.
4. Ajusta su comportamiento en Ajustes › EscritorioWP.

== Frequently Asked Questions ==

= ¿Desactiva también la paleta de comandos del editor de bloques? =

No. Solo se desactiva la paleta que WordPress carga en el resto del administrador. Dentro del
editor de entradas y del editor del sitio, Comando+K sigue abriendo la paleta nativa.

= ¿Qué pasa con los widgets de escritorio de otros plugins? =

Por defecto se ocultan. Si desactivas «Ocultar los widgets de otros plugins» en los ajustes, se
muestran debajo de la interfaz, bajo el título «Otros widgets».

= ¿Necesita WooCommerce? =

No. Sin WooCommerce el plugin funciona igual, simplemente sin productos ni pedidos.

= ¿Cada usuario ve lo mismo? =

No. Cada panel, indicador, acción y resultado de búsqueda se muestra solo si el usuario tiene la
capacidad que WordPress exige para la pantalla equivalente. Un autor ve sus entradas; un editor no
ve el panel de usuarios.

= ¿Se puede volver al escritorio de siempre? =

Sí. Desactiva «Sustituir el escritorio nativo» en Ajustes › EscritorioWP, o desactiva el plugin.

== Screenshots ==

1. El escritorio con los indicadores y los paneles de últimos elementos.
2. El lanzador abierto con resultados agrupados por tipo.
3. La ventana de creación rápida de una entrada.
4. La pantalla de ajustes.

== Changelog ==

= 0.1.0 =
* Primera versión.
* Escritorio propio con indicadores y paneles de entradas, páginas, usuarios, productos y pedidos.
* Creación rápida de entradas, páginas, usuarios y productos desde una ventana del escritorio.
* Lanzador propio con Comando+K o Control+K que sustituye a la paleta de comandos del administrador.
* Búsqueda unificada de contenido, usuarios, productos y pedidos respetando capacidades.
* Personalización por usuario: paneles ocultables y reordenables, y tema claro, oscuro o automático.
* Página de ajustes en Ajustes › EscritorioWP.
* Compatibilidad declarada con HPOS de WooCommerce.

== Upgrade Notice ==

= 0.1.0 =
Primera versión de EscritorioWP.
