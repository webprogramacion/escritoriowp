=== EscritorioWP ===
Contributors: webprogramacion
Tags: dashboard, escritorio, buscador, command palette, woocommerce
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sustituye el escritorio de WordPress por una interfaz propia y la paleta de comandos por un lanzador que busca el contenido del sitio.

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

= El menú del plugin =

EscritorioWP añade su propio menú en el administrador, con dos pantallas:

* **Ajustes**: desactiva cada parte por separado. Sustituir o no el escritorio, conservar los
  widgets de otros plugins, desactivar o no la paleta de comandos nativa y activar o desactivar el
  lanzador, también en el sitio público.
* **Acerca de**: la versión instalada, los enlaces del proyecto, el estado de la actualización con
  un botón para comprobarla al momento, y las novedades de cada versión publicada.

= Actualizaciones =

El plugin se distribuye desde su repositorio público de GitHub. WordPress avisa cuando hay una
versión nueva en la pantalla Plugins, igual que con cualquier otro plugin, y se actualiza con un
clic. Consulta la sección «External services» para saber qué se consulta y cuándo.

== Installation ==

1. Sube la carpeta `escritoriowp` a `/wp-content/plugins/`, o instala el zip desde Plugins › Añadir
   nuevo › Subir plugin.
2. Actívalo desde la pantalla de Plugins.
3. Entra en el Escritorio: ya verás la interfaz nueva. Pulsa Comando+K o Control+K para el buscador.
4. Ajusta su comportamiento en EscritorioWP › Ajustes.

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

Sí. Desactiva «Sustituir el escritorio nativo» en EscritorioWP › Ajustes, o desactiva el plugin.

== External services ==

Este plugin se conecta a la API pública de GitHub para saber si existe una versión más reciente de
EscritorioWP y, cuando decides actualizar, para descargar el paquete de esa versión.

Qué se consulta: la última versión publicada del repositorio
`https://github.com/webprogramacion/escritoriowp`, mediante la dirección
`https://api.github.com/repos/webprogramacion/escritoriowp/releases/latest`.

Cuándo se consulta: durante la comprobación periódica de actualizaciones que WordPress hace por su
cuenta (como mucho una vez cada doce horas, porque la respuesta se guarda en caché), y cuando pulsas
«Buscar actualizaciones ahora» en la pantalla Acerca de. Al instalar la actualización se descarga el
fichero zip de esa versión desde GitHub.

Qué datos se envían: ninguno del sitio ni de las personas que lo usan. La petición no lleva
parámetros, ni cookies, ni la dirección del sitio: solo identifica al cliente como «EscritorioWP» y
su número de versión, como exige GitHub.

Servicio: GitHub, Inc. Condiciones de uso: https://docs.github.com/site-policy/github-terms/github-terms-of-service
Política de privacidad: https://docs.github.com/site-policy/privacy-policies/github-general-privacy-statement

== Screenshots ==

1. El escritorio con los indicadores y los paneles de últimos elementos.
2. El lanzador abierto con resultados agrupados por tipo.
3. La ventana de creación rápida de una entrada.
4. La pantalla de ajustes.
5. La pantalla Acerca de con el historial de novedades.

== Changelog ==

= 0.2.0 =
* Menú propio «EscritorioWP» en el administrador, con las pantallas Ajustes y Acerca de.
* La pantalla de ajustes se traslada desde Ajustes › EscritorioWP al menú propio.
* Pantalla Acerca de con la versión instalada, los enlaces del proyecto y las novedades de cada versión.
* Aviso de versión nueva dentro de WordPress y actualización con un clic desde el repositorio de GitHub.
* Botón para buscar actualizaciones al momento desde la pantalla Acerca de.
* Cada versión se publica sola como release de GitHub al subir el número de versión.

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

= 0.2.0 =
Añade el menú propio del plugin, la pantalla Acerca de y las actualizaciones automáticas desde
GitHub. La pantalla de ajustes cambia de sitio: ahora está en EscritorioWP › Ajustes.

= 0.1.0 =
Primera versión de EscritorioWP.
