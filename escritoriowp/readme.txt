=== EscritorioWP ===
Contributors: webprogramacion
Tags: dashboard, escritorio, buscador, command palette, woocommerce
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.4.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sustituye el escritorio de WordPress por una interfaz propia y la paleta de comandos por un lanzador que busca el contenido del sitio.

== Description ==

EscritorioWP cambia dos cosas del día a día en el administrador de WordPress y añade una revisión de
seguridad y avisos por correo de los cambios en los plugins y en los administradores.

**Un escritorio útil.** Al entrar en el Escritorio, en lugar de los widgets de siempre verás:

* Indicadores del sitio: entradas publicadas y en borrador, páginas, usuarios, comentarios
  pendientes y actualizaciones pendientes. Con WooCommerce, además productos, pedidos del mes e
  ingresos del mes.
* Paneles con los últimos elementos de cada tipo, con enlace directo a editar o ver.
* Tus tipos de contenido personalizados (libros, eventos, inmuebles…) con su propio panel y su
  propia tarjeta de indicador, si los activas en los ajustes.
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

**Una revisión de administradores.** Tras una intrusión es habitual dejar una puerta trasera
discreta: dar permisos de administrador a un usuario normal escribiendo directamente en la base de
datos, de modo que en la pantalla Usuarios aparece con su rol de siempre. La pantalla
EscritorioWP › Administradores lista a los administradores y marca en rojo:

* A los usuarios sin el rol Administrador que tienen algún permiso que da control del sitio
  (cambiar ajustes, gestionar usuarios, instalar o editar plugins y temas, actualizar…), diciendo
  de dónde les viene: asignado al propio usuario o heredado de un rol alterado.
* A los usuarios que algún código esconde de la pantalla Usuarios.
* Los roles, como «Suscriptor», a los que se han añadido permisos de administrador.

Lee la base de datos directamente y calcula los permisos igual que WordPress, así que no la engaña
el código que oculta usuarios. Si hay usuarios que revisar, un número rojo en el menú lo avisa. Los
permisos del gestor de tienda de WooCommerce no cuentan.

Si un usuario tiene permisos de administrador asignados directamente, el botón «Retirar permisos» se
los quita tras una confirmación que lista exactamente cuáles, sin tocar su rol. Si lo alterado es un
rol, la pantalla lo explica pero no lo modifica. Nadie puede retirarse sus propios permisos.

= Compatibilidad con WooCommerce =

WooCommerce es opcional. Si está activo, el plugin añade productos y pedidos al escritorio y al
buscador, y declara compatibilidad con el almacenamiento de pedidos de alto rendimiento (HPOS).

= El menú del plugin =

EscritorioWP añade su propio menú en el administrador, justo debajo del Escritorio, con tres
pantallas:

* **Ajustes**: desactiva cada parte por separado. Sustituir o no el escritorio, conservar los
  widgets de otros plugins, elegir qué indicadores y qué paneles muestra el escritorio a todos los
  usuarios (también los de los tipos de contenido personalizados que detecta en el sitio),
  desactivar o no la paleta de comandos nativa y activar o desactivar el lanzador, también
  en el sitio público. También eliges debajo de qué menú aparece EscritorioWP, o si va al final, un
  color de fondo para que su entrada destaque en la barra lateral y los avisos por correo.
* **Administradores**: quién es administrador y quién tiene permisos de administrador sin serlo.
  Solo para usuarios con `manage_options` y `list_users`.
* **Acerca de**: la versión instalada, los enlaces del proyecto, el estado de la actualización con
  un botón para comprobarla al momento, y las novedades de cada versión publicada.

= Avisos por correo =

En EscritorioWP › Ajustes puedes pedir un correo cada vez que alguien activa, desactiva, instala o
borra un plugin, con una casilla para cada caso. Vienen desactivados.

* Elige a quién se envía: una o varias direcciones separadas por comas. Si no pones ninguna, va al
  correo de administración del sitio.
* Escribe el asunto y el mensaje con marcadores como `{evento}`, `{plugins}`, `{lista}`,
  `{usuario}`, `{fecha}` o `{ip}`, que se sustituyen al enviar. La pantalla de ajustes los lista
  todos.
* Si se cambian varios plugins a la vez, llega un solo correo con todos. La pantalla Plugins borra
  los plugins de uno en uno, así que un borrado en lote manda un correo por plugin.
* Funciona igual desde la pantalla Plugins, la API REST o WP-CLI, y avisa también si alguien
  desactiva EscritorioWP.

El correo incluye el nombre de usuario y la IP de quien hizo el cambio, y solo se envía a las
direcciones que configures. No avisa de los cambios hechos sin pasar por WordPress, como copiar
ficheros por FTP.

También puedes pedir un aviso de los **cambios en los administradores**, con otra casilla, también
desactivada al principio. Cada 6 horas, EscritorioWP revisa con el mismo criterio que la pantalla
Administradores quién es administrador y quién tiene permisos de administrador sin el rol, y si algo
ha cambiado desde la revisión anterior te envía un correo con:

* Los administradores nuevos y los usuarios nuevos con permisos de administrador sin el rol, con sus
  permisos y una señal si algún código los esconde de la pantalla Usuarios.
* Los que han dejado de ser administradores o de tener esos permisos, diciendo si el usuario se ha
  borrado o sigue existiendo.

Lo que ya existe al marcar la casilla no avisa: la pantalla Administradores ya lo enseña. Como compara
la situación de ahora con la de hace unas horas, descubre también los cambios hechos directamente en
la base de datos, pero no sabe quién los hizo ni ve un administrador que se crea y se borra entre dos
revisiones. Si el correo no se puede enviar, la revisión siguiente vuelve a avisar. Va a los mismos
destinatarios que los avisos de plugins, con un texto fijo, e incluye nombres de usuario y correos.
La revisión usa WP-Cron, así que depende de que el sitio reciba visitas o de que el servidor ejecute
WP-Cron por su cuenta; junto a la casilla verás cuándo fue la última y cuándo toca la próxima.

Cada aviso, de plugins o de administradores, queda además **guardado en el sitio** con su fecha, su
asunto, su mensaje, sus destinatarios y el resultado del envío: «Enviado» si el sitio lo aceptó (que no
es lo mismo que entregado), «Fallido» con el motivo que dé WordPress, o «Sin confirmar» si la petición
terminó antes de saberlo. Así no se pierde aunque el correo falle o acabe en spam. Los encontrarás en
EscritorioWP › Avisos, con filtros por tipo y por estado y el mensaje completo de cada uno, y los cinco
últimos arriba a la derecha del escritorio. Los que todavía no has visto se marcan en rojo: una
campana con el número en el escritorio, un punto en cada aviso y un contador en el menú lateral. Al
abrir la pantalla Avisos, o con «Marcar como leídos», dejan de estarlo; cada administrador tiene los
suyos.

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

= ¿Puedo ocultar un panel o un indicador a todos los usuarios? =

Sí. En EscritorioWP › Ajustes, sección Escritorio, desmarca lo que no quieras mostrar en
«Indicadores» o en «Paneles» y nadie lo verá en el escritorio. Lo que dejes marcado lo sigue viendo
cada usuario solo si su rol se lo permite. Es distinto de «Personalizar», en el propio escritorio:
ahí cada usuario oculta o reordena paneles solo para sí, y entre los paneles que el sitio tenga
activos. Los tipos de contenido personalizados aparecen al final de los dos grupos y salen
desmarcados: no se ven hasta que los marcas.

= ¿Puedo ver mis tipos de contenido personalizados en el escritorio? =

Sí. EscritorioWP detecta los tipos de contenido personalizados que tienen entrada en el menú del
administrador y los lista al final de «Indicadores» y de «Paneles», en EscritorioWP › Ajustes,
desmarcados. Marca un tipo en «Paneles» para ver sus últimos elementos, y en «Indicadores» para ver
cuántos hay publicados y en borrador. El botón de crear del panel lleva a la pantalla de creación del
propio tipo, con todos sus campos. Cada usuario solo ve el panel y la tarjeta de los tipos que su rol
le deja editar, y si desactivas el plugin que registra un tipo, su panel desaparece y vuelve al
reactivarlo.

= ¿Necesita WooCommerce? =

No. Sin WooCommerce el plugin funciona igual, simplemente sin productos ni pedidos.

= ¿Cada usuario ve lo mismo? =

No. Cada panel, indicador, acción y resultado de búsqueda se muestra solo si el usuario tiene la
capacidad que WordPress exige para la pantalla equivalente. Un autor ve sus entradas; un editor no
ve el panel de usuarios.

= ¿Se puede volver al escritorio de siempre? =

Sí. Desactiva «Sustituir el escritorio nativo» en EscritorioWP › Ajustes, o desactiva el plugin.

= ¿Me avisa también de las actualizaciones de plugins? =

No. Para actualizar un plugin, WordPress puede desactivarlo y volver a activarlo por dentro, y
EscritorioWP no lo cuenta como una desactivación ni una activación. De las actualizaciones
automáticas ya avisa WordPress por correo.

= ¿Por qué el aviso de un administrador nuevo llega horas después? =

Porque no se envía al crear el usuario, sino en la revisión siguiente, que se hace cada 6 horas.
Así descubre también los cambios que no pasan por las funciones de WordPress, como escribir
directamente en la base de datos. Si la próxima revisión que muestra la casilla ya ha pasado, WP-Cron
no se está ejecutando en tu sitio: suele pasar en sitios con pocas visitas o con `DISABLE_WP_CRON` y
sin una tarea programada en el servidor.

= ¿Qué guarda EscritorioWP en la base de datos? =

Los ajustes, las preferencias del escritorio de cada usuario, unas cachés temporales y, si activas
alguno de los avisos por correo, la última revisión de los administradores, una tabla propia con cada
aviso enviado y, por cada administrador, hasta qué aviso ha visto. Esos avisos guardan lo mismo que el correo, incluidas las IPs, los nombres de usuario y
las direcciones de correo, y se conservan hasta que desinstalas el plugin: desactivarlo no borra nada,
y borrarlo desde la pantalla Plugins lo elimina todo.

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

= 0.4.0 =
* Nueva pantalla EscritorioWP › Administradores, que señala a los usuarios con permisos de administrador sin tener ese rol, a los que algún código oculta y los roles alterados, y permite retirar esos permisos.
* Un número rojo en el menú avisa cuando hay usuarios que revisar.
* Avisos por correo cuando alguien activa, desactiva, instala o borra un plugin, con destinatarios, asunto y mensaje configurables.
* Elige qué indicadores y qué paneles muestra el escritorio a todos los usuarios.
* Elige debajo de qué menú aparece EscritorioWP en la barra lateral, o al final, y dale un color de fondo para que destaque.
* El menú EscritorioWP aparece ahora justo debajo del Escritorio.
* La pantalla de ajustes vuelve a mostrar el aviso «Ajustes guardados» al guardar.

= 0.3.0 =
* El escritorio ocupa todo el ancho de la pantalla en portátiles y monitores de entre 800 y 1800 píxeles, donde antes se quedaba en la mitad o en un tercio.

= 0.2.1 =
* Corrige la versión que se anunciaba en la plantilla de traducción, que se quedaba en la anterior.

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

= 0.4.0 =
Añade la pantalla Administradores para detectar puertas traseras, avisos por correo de los cambios en
los plugins y ajustes nuevos para el menú y el escritorio. El menú EscritorioWP pasa a aparecer
justo debajo del Escritorio; si lo prefieres al final, elígelo en Ajustes › Posición en el menú.

= 0.3.0 =
Corrige el escritorio, que en la mayoría de portátiles ocupaba solo una parte de la pantalla. No
tienes que hacer nada al actualizar.

= 0.2.1 =
Versión de mantenimiento. No cambia nada de lo que ves ni de cómo funciona el plugin.

= 0.2.0 =
Añade el menú propio del plugin, la pantalla Acerca de y las actualizaciones automáticas desde
GitHub. La pantalla de ajustes cambia de sitio: ahora está en EscritorioWP › Ajustes.

= 0.1.0 =
Primera versión de EscritorioWP.
