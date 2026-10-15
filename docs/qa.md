# Lista de comprobación manual de EscritorioWP

Deriva de los escenarios de `openspec/changes/create-escritoriowp-plugin/specs/`. Cada línea es un
escenario comprobable.

**Leyenda**

- `[S]` Verificado en servidor con el arnés de PHP (rutas REST y pintado del escritorio) sobre
  WordPress 7.1 + WooCommerce 11.1.0 con SQLite. No necesita repetirse a mano.
- `[N]` Cubierto por las pruebas unitarias (`php tests/ejecutar.php`, `node tests/ejecutar.js`).
- `[C]` Verificado en Chrome sin interfaz con eventos reales de ratón y teclado (protocolo
  DevTools), leyendo los estilos calculados y con capturas, sobre la misma instalación. No necesita
  repetirse a mano.
- `[M]` **Pendiente de comprobación manual en navegador.** Es lo único que hay que ejecutar.

## Entorno

Repite la lista con cada combinación:

1. WordPress 6.7 (mínimo) y la última estable.
2. Con WooCommerce y sin WooCommerce.
3. Con HPOS activado y con el almacenamiento clásico de pedidos.
4. Roles: Administrador, Editor, Autor, Colaborador y Gestor de tienda.
5. Navegadores: uno en macOS (⌘K) y uno en Windows o Linux (Ctrl+K).

## 1. Núcleo del plugin

- [x] `[S]` El plugin se activa copiando solo el directorio `escritoriowp/`, sin `vendor/` ni build.
- [x] `[S]` En la primera activación se crea la opción con los dieciocho valores por defecto.
- [x] `[N]` Un ajuste ausente tras una actualización toma su valor por defecto sin perder el resto.
- [x] `[N]` Un número de elementos por panel fuera de rango se ajusta al límite más cercano.
- [x] `[N]` Si los paneles activos llegan con un valor que no es una lista, quedan activos todos los
      propios; si a la lista le falta un panel propio, ese queda activo y el resto conserva el valor
      enviado.
- [x] `[N]` En una lista de casillas, un tipo de contenido personalizado marcado queda activo; uno
      desmarcado o ausente queda inactivo, y un identificador que WordPress no admitiría se descarta,
      sin comprobar si el tipo está registrado.
- [x] `[S]` La página EscritorioWP › Ajustes existe, exige `manage_options` y publica en `options.php`
      con nonce.
- [ ] `[M]` Guardar «Sustituir el escritorio nativo» en No devuelve el escritorio nativo al recargar.
- [x] `[S]` Sin WooCommerce ni tipos de contenido personalizados, la sección Escritorio de los ajustes
      ofrece, entre «Ocultar los widgets de otros plugins» y «Elementos por panel», «Indicadores» con
      cinco casillas (Entradas
      publicadas, Páginas publicadas, Usuarios registrados, Comentarios pendientes y Actualizaciones
      pendientes) y «Paneles» con tres (Entradas, Páginas y Usuarios), todas marcadas.
- [x] `[S]` Con WooCommerce aparecen además Productos publicados, Pedidos este mes e Ingresos este
      mes tras Usuarios registrados, y Productos y Pedidos tras Usuarios en «Paneles», antes de
      cualquier tipo de contenido personalizado.
- [x] `[S]` Con «Pedidos» desmarcado, guardar los ajustes sin WooCommerce y reactivarlo deja
      «Pedidos» desmarcado en los ajustes y fuera del escritorio.
- [x] `[S]` Un envío con una clave de panel inventada la descarta, guarda el resto y no muestra avisos.
- [x] `[S]` Cada grupo es un `fieldset` con `legend`, cada casilla tiene su `label` y su campo oculto
      con valor `0`, y la cabecera de la fila no es una etiqueta de un campo inexistente.
- [x] `[C]` El árbol de accesibilidad de Chrome pone cada casilla dentro de su grupo con nombre:
      «Entradas» en el grupo «Paneles» e «Ingresos este mes» en el grupo «Indicadores».
- [ ] `[M]` Con un lector de pantalla, al llegar a la primera casilla de «Paneles» se anuncia el
      nombre del grupo y el del panel.
- [x] `[C]` Los dos grupos de casillas quedan alineados con el resto de la tabla de ajustes.
- [x] `[C]` Desmarcar «Usuarios» e «Ingresos este mes» y guardar desde el formulario los deja
      desmarcados, con el aviso de ajustes guardados; volver a marcarlos y guardar los activa.
- [x] `[S]` Con un plugin que registra «Libros» y dos tipos «Eventos», «Indicadores» y «Paneles»
      terminan, desmarcados y después de sus opciones propias, en «Eventos (evento)», «Eventos
      (tribe_events)» y «Libros», y el escritorio no muestra nada de esos tipos.
- [x] `[S]` Un tipo que se registra después aparece desmarcado en los dos grupos y el resto de
      casillas conserva su valor.
- [x] `[S]` No se ofrecen como tipo personalizado los tipos del núcleo, los de WooCommerce
      (productos, pedidos, cupones) ni los registrados sin pantalla o sin entrada en el menú.
- [x] `[S]` Sin tipos personalizados, los dos grupos solo tienen sus opciones propias y su descripción
      dice que el sitio no tiene tipos de contenido personalizados.
- [x] `[S]` Un envío con «Libros» marcado y una clave de tipo mal formada guarda solo «Libros», sin
      avisos.
- [x] `[S]` Con «Libros» marcado en los dos grupos y su plugin desactivado, nadie ve su panel ni su
      tarjeta, el escritorio se pinta sin avisos de PHP, la ruta `recientes/cpt-libro` responde 404 y
      los ajustes no listan «Libros».
- [x] `[S]` Guardar los ajustes con el plugin de «Libros» desactivado y reactivarlo después devuelve
      «Libros» marcado a los dos grupos y su panel y su tarjeta al escritorio.
- [x] `[S]` Un usuario que tenía oculto «Libros» y guarda su tema mientras el plugin del tipo está
      desactivado lo sigue teniendo oculto al reactivarlo.
- [x] `[C]` Marcar «Libros» en «Paneles» y en «Indicadores» y guardar desde el formulario los deja
      marcados y los muestra en el escritorio; desmarcarlos y guardar los quita. Con WooCommerce y
      sin él, y sin errores en la consola, tampoco para un autor.
- [x] `[C]` Con tipos personalizados, los dos grupos de casillas siguen alineados con el resto de la
      tabla de ajustes y la descripción se lee completa.
- [ ] `[M]` Un usuario sin `manage_options` que abre la URL de ajustes recibe el aviso de permisos.
- [x] `[S]` Sin WooCommerce no aparecen indicadores, paneles ni resultados de productos o pedidos, y
      no se emite ningún aviso.
- [x] `[S]` El plugin aparece como compatible con `custom_order_tables` en el registro de
      características de WooCommerce.
- [ ] `[M]` Con HPOS activado los pedidos se listan y se buscan, y las URLs apuntan a
      `admin.php?page=wc-orders`. **No verificable en el entorno de pruebas con SQLite**, que no crea
      las tablas de pedidos de WooCommerce.
- [x] `[S]` Con el almacenamiento clásico, los pedidos se listan, se buscan y sus URLs apuntan a
      `post.php` y `edit.php?post_type=shop_order`.
- [ ] `[M]` Acceder por HTTP directamente a un fichero PHP del plugin no produce salida ni error.
- [x] `[S]` Una petición REST sin sesión responde 401 sin datos.
- [x] `[S]` Desactivar y reactivar el plugin conserva ajustes y preferencias.
- [x] `[S]` Con el aviso de cambios en los administradores activado, desactivar el plugin quita su
      tarea de WP-Cron y conserva la opción `escritoriowp_revision_administradores`.
- [x] `[S]` Borrar el plugin no deja en la base de datos ninguna fila con prefijo `escritoriowp` en
      `wp_options` ni en `wp_usermeta`, ni la tarea `escritoriowp_revisar_administradores` en WP-Cron,
      ni la tabla `wp_escritoriowp_avisos` (comprobado con `wp plugin uninstall --deactivate` y el aviso
      de administradores activado; la tabla, en §13).

## 2. Escritorio

- [x] `[S]` El escritorio muestra la interfaz propia y ninguno de los widgets nativos ni los de
      WooCommerce.
- [x] `[S]` Los avisos de administración de otros plugins siguen emitiéndose: la interfaz no toca el
      enganche `admin_notices` ni la estructura de la página.
- [x] `[S]` Con «Ocultar widgets de otros plugins» activo, el widget de otro plugin no se muestra.
- [x] `[S]` Con ese ajuste desactivado, los widgets de terceros aparecen bajo «Otros widgets», debajo
      de la interfaz.
- [x] `[S]` Con «Sustituir el escritorio nativo» desactivado, vuelven todos los widgets nativos y el
      panel de bienvenida.
- [x] `[S]` La cabecera muestra saludo con el nombre del usuario y la fecha del sitio.
- [x] `[S]` Un administrador con WooCommerce ve los cinco botones de creación; sin WooCommerce solo
      tres.
- [ ] `[M]` En macOS la pista del atajo es ⌘K y en Windows o Linux es Ctrl+K.
- [x] `[S]` Los ingresos del mes suman completados y en proceso y excluyen cancelados y reembolsados.
- [x] `[N]` El cálculo de ingresos ignora estados que no cuentan.
- [x] `[S]` Un usuario sin `list_users` no recibe la tarjeta de usuarios.
- [ ] `[M]` Recargar antes de cinco minutos sirve los indicadores de WooCommerce desde caché, y el
      botón «Actualizar» los recalcula.
- [x] `[S]` El panel de entradas muestra hasta N elementos con su estado, autor y fecha.
- [ ] `[M]` Una entrada publicada ofrece la acción «Ver», que abre el sitio en una pestaña nueva.
- [ ] `[M]` Un panel sin elementos muestra su texto de estado vacío y el botón de crear el primero.
- [ ] `[M]` Si falla la carga de un panel, ese panel muestra «Reintentar» y el resto sigue operativo.
- [x] `[S]` Un autor ve el panel de entradas y no ve los de páginas, usuarios, productos ni pedidos.
- [x] `[S]` Un autor que llama a `recientes/usuarios` recibe 403 sin datos.
- [x] `[S]` Tras actualizar sin haber guardado «Indicadores» ni «Paneles», el resumen de un
      administrador, un editor, un autor y un gestor de tienda y el escritorio pintado son idénticos
      a los de la versión anterior.
- [x] `[S]` Con «Usuarios» desmarcado en «Paneles», nadie recibe el panel de usuarios y la cabecera
      sigue ofreciendo «Nuevo usuario».
- [x] `[C]` Con «Usuarios» desmarcado, «Nuevo usuario» abre la ventana de creación; sin ningún panel
      activo, también la abren «Nueva entrada» y «Nueva página».
- [x] `[S]` Con «Ingresos este mes» desmarcado, falta solo esa tarjeta: «Pedidos este mes» sigue,
      también en la respuesta de la ruta REST `resumen`.
- [x] `[S]` Con «Usuarios registrados» marcado, un autor sigue sin recibir esa tarjeta.
- [x] `[S]` Sin ningún indicador activo no se pinta la fila de tarjetas; sin ningún panel activo no se
      pinta la zona de paneles.
- [x] `[S]` Con las tres tarjetas de WooCommerce desmarcadas, pedir el resumen no recorre los pedidos
      del mes (no se crea el transitorio `escritoriowp_resumen_woo`).
- [x] `[C]` Sin ningún indicador activo no hay hueco ni esqueleto de carga encima de los paneles (la
      separación con la cabecera es la misma), y no se hace ninguna petición a `resumen`, tampoco al
      pulsar «Actualizar».
- [x] `[C]` Sin ningún panel activo, «Personalizar» solo ofrece el tema y «Restablecer».
- [x] `[C]` Sin alguna de las dos zonas, el botón «Actualizar» y crear una entrada desde la cabecera
      funcionan sin errores en la consola, como administrador y como autor, con WooCommerce y sin él.
- [ ] `[M]` Ocultar el panel de páginas y recargar desde otro navegador lo mantiene oculto.
- [ ] `[M]` Reordenar paneles con las flechas persiste al recargar.
- [ ] `[M]` Con el tema en «automático» y el sistema en modo oscuro, la interfaz se ve oscura.
- [ ] `[M]` «Restablecer» devuelve paneles y tema a sus valores por defecto.
- [x] `[S]` Con «Páginas» desmarcado en «Paneles», la configuración del escritorio, de la que
      «Personalizar» saca su lista, no incluye las páginas.
- [x] `[S]` Un usuario que había ocultado «Páginas» lo sigue teniendo oculto, y en su posición, después
      de que el administrador lo desactive y lo vuelva a activar: su metadato no cambia.
- [ ] `[M]` A 400 px de ancho no hay desplazamiento horizontal y los paneles se apilan.
- [ ] `[M]` Recorrer la interfaz con Tab da foco visible a todos los botones y enlaces en orden.

### Bloque de últimos avisos

Deriva de `openspec/changes/registro-avisos/specs/escritorio-panel/`.

- [x] `[C]` Con avisos guardados, un administrador ve el bloque «Avisos por correo» debajo de las
      tarjetas y encima de los paneles, a todo el ancho, con los 5 avisos más recientes del más nuevo
      al más antiguo (asunto, estado, tipo y cuánto hace) y «Ver todos» hacia EscritorioWP › Avisos.
- [x] `[C]` Un aviso fallido sale con «Fallido» en una etiqueta roja, y su asunto enlaza a su detalle
      en la pantalla Avisos.
- [x] `[S]` Un editor no recibe el bloque ni ningún dato de avisos en la página ni en la
      configuración del escritorio, y no se consulta la tabla de avisos.
- [x] `[S]` Con «Sustituir el escritorio nativo» desactivado, el escritorio nativo no tiene el bloque.
- [x] `[C]` El árbol de accesibilidad de Chrome tiene una región llamada «Avisos por correo», entre
      «Resumen del sitio» y «Últimos elementos».
- [ ] `[M]` Con un lector de pantalla, la lista de regiones del escritorio anuncia «Avisos por
      correo».
- [x] `[S]` Sin avisos guardados y con las cinco casillas de avisos desmarcadas, el bloque no se
      pinta y la única consulta es la que comprueba si hay avisos.
- [x] `[S]` `[C]` Con «Avisar al activar un plugin» marcada y sin avisos, el bloque se pinta sin
      consultar la tabla y dice que todavía no se ha enviado ningún aviso.
- [x] `[S]` Con avisos guardados y todas las casillas desmarcadas, el bloque se sigue pintando.
- [x] `[S]` Con todas las casillas de «Paneles» desmarcadas y avisos guardados, el bloque se pinta y
      `config.paneles` no lo incluye, así que «Personalizar» no lo ofrece para ocultarlo ni moverlo.
- [x] `[C]` Si la ruta `avisos` falla, el bloque muestra el error con «Reintentar» y los indicadores y
      los paneles cargan con normalidad.
- [x] `[C]` Con el escritorio abierto, enviar un aviso y pulsar «Actualizar» lo pone el primero en el
      bloque sin recargar la página.
- [x] `[C]` Los paneles no cambian con el bloque: filas, estado vacío con su botón de crear y
      «Reintentar» cuando su ruta falla.
- [x] `[S]` Un editor que llama a la ruta `avisos` recibe 403 sin datos; sin sesión, 401 sin datos.
- [x] `[S]` Un administrador recibe 5 avisos por defecto y, con `limite=500` y 60 guardados, los 20
      más recientes.
- [x] `[C]` Un plugin llamado `<img src=x onerror=alert(1)>` sale tal cual, como texto, en el asunto
      del bloque, sin crear ninguna imagen ni abrir ningún diálogo.
- [x] `[C]` A 400 px no hay desplazamiento horizontal y el asunto largo se corta con puntos
      suspensivos; con el tema oscuro, el bloque y los títulos de los paneles se leen bien.

### Tipos de contenido personalizados

Con un mu-plugin que registre un tipo «Libros» (`libro`, con capacidades propias y `map_meta_cap`)
y lo marque en «Paneles».

- [x] `[C]` El panel «Libros» sale detrás de los paneles propios, con los libros por fecha de
      modificación descendente, «Borrador» en el borrador, «Ver» solo en los publicados y «Ver todos»
      hacia `edit.php?post_type=libro`.
- [x] `[C]` Su botón de crear es un enlace a `post-new.php?post_type=libro`, sin ventana de creación
      rápida.
- [x] `[C]` Sin libros, el panel muestra el texto `not_found` del tipo y un enlace con su
      `add_new_item` a la pantalla nativa de creación; los estados vacíos de los paneles propios no
      cambian.
- [x] `[C]` Quien puede editar libros pero no crearlos ve el panel sin botón de crear, ni en su
      cabecera ni en su estado vacío.
- [x] `[S]` Quien puede editar sus libros pero no los ajenos solo ve los suyos en el panel.
- [x] `[C]` Ocultar «Libros» desde «Personalizar», volver a mostrarlo y subirlo por encima de
      «Entradas» persiste al recargar: el orden se guarda en el metadato del usuario.
- [x] `[S]` La cabecera tiene los mismos botones de creación con «Libros» marcado y sin marcar.
- [x] `[S]` Un usuario sin la capacidad de editar libros no recibe el panel, y la ruta
      `recientes/cpt-libro` le responde 403 sin datos.
- [x] `[S]` Con «Libros» sin marcar en «Paneles», la ruta `recientes/cpt-libro` sigue respondiendo a
      quien puede editar libros.
- [x] `[S]` La ruta `recientes` responde 404 con la clave de un tipo no registrado
      (`cpt-noexiste`), de uno sin pantalla (`cpt-oculto`), de WooCommerce (`cpt-product`) y con una
      clave mal formada (`cpt-Libro`).
- [x] `[S]` Sin ninguna clave `cpt-…` guardada, las rutas `recientes` propias, la del resumen y el
      escritorio pintado son idénticos a los de la versión anterior, salvo los textos de estado vacío
      que ahora llegan con cada panel.
- [x] `[S]` Con «Libros» marcado en «Indicadores», 12 libros publicados y 3 en borrador, la última
      tarjeta del resumen es «Libros», con valor 12, detalle «3 en borrador», el icono del tipo y
      enlace a su listado.
- [x] `[S]` La tarjeta de un tipo sin borradores no lleva texto de detalle.
- [x] `[S]` Con «Libros» marcado en «Paneles» y sin marcar en «Indicadores», no hay tarjeta de libros
      en la respuesta de la ruta `resumen`.
- [x] `[S]` Con todos los indicadores propios desmarcados y solo «Libros» marcado, el escritorio pinta
      la fila de tarjetas con la de libros como única tarjeta.
- [x] `[S]` Un usuario sin la capacidad de editar libros no recibe su tarjeta.

## 3. Creación rápida

- [ ] `[M]` El modal se abre desde la cabecera y desde el botón del panel, se cierra con Escape y
      devuelve el foco al botón que lo abrió.
- [x] `[S]` Crear una entrada como borrador la crea y devuelve su enlace de edición.
- [ ] `[M]` Tras crear, el panel correspondiente se actualiza y la nueva fila aparece la primera.
- [x] `[S]` Crear una página publicada devuelve también su URL pública.
- [x] `[S]` Crear un usuario con rol Editor lo crea con contraseña generada.
- [ ] `[M]` Con el aviso activado, el usuario nuevo recibe el email para establecer contraseña.
- [x] `[S]` Crear un producto guarda su precio, su SKU y su estado.
- [x] `[S]` Un email ya registrado responde 400 con el error en el campo email.
- [ ] `[M]` Ese error se muestra junto al campo sin cerrar el modal y conservando el resto de valores.
- [x] `[S]` Un título en blanco responde 400 con el error en el campo título.
- [x] `[S]` Un SKU duplicado y un precio negativo responden 400 con su campo.
- [x] `[S]` Un colaborador que intenta publicar recibe 403 con el mensaje de solo borradores.
- [x] `[S]` Asignar un rol no editable responde 403 y no crea el usuario.
- [x] `[S]` Un editor sin `create_users` recibe 403 al crear usuarios.
- [x] `[S]` Sin WooCommerce, crear productos responde 404.
- [x] `[S]` El botón «Nuevo pedido» apunta a la pantalla nativa correcta con el almacenamiento
      clásico.
- [ ] `[M]` Lo mismo con HPOS activado (`admin.php?page=wc-orders&action=new`).

## 4. Lanzador

- [x] `[S]` En una pantalla del administrador distinta del editor, el hook de la paleta nativa se ha
      quitado y `wp-core-commands` no se encola.
- [ ] `[M]` Comando+K o Control+K en Entradas › Todas las entradas abre el lanzador propio.
- [ ] `[M]` Comando+K dentro del editor de una entrada abre la paleta nativa del editor.
- [ ] `[M]` Comando+K en el editor del sitio abre la paleta nativa.
- [ ] `[M]` Con ambos ajustes desactivados, la paleta nativa vuelve a funcionar.
- [ ] `[M]` Control+K con el foco en un campo de texto abre el lanzador y el navegador no hace nada.
- [ ] `[M]` El lanzador se abre desde el sitio público con la barra de administración visible.
- [x] `[S]` El botón «Buscar» está en la barra de administración y el nodo nativo de la paleta no.
- [ ] `[M]` Escape cierra el lanzador y devuelve el foco al elemento anterior.
- [ ] `[M]` Con el campo vacío se ven las acciones rápidas y una sugerencia.
- [ ] `[M]` Tras abrir un resultado, vuelve a aparecer en «Recientes».
- [x] `[N]` «plug» ordena «Plugins» antes que sus submenús.
- [x] `[N]` «pagina» encuentra «Páginas» sin escribir el acento.
- [x] `[S]` «WooCommerce › Ajustes» aparece en el catálogo sin configuración adicional.
- [x] `[S]` Las URLs de los comandos se resuelven bien, incluidos los slugs con parámetros.
- [x] `[S]` Un usuario sin `create_users` no recibe la acción «Nuevo usuario».
- [x] `[S]` Una búsqueda mixta devuelve los grupos en el orden de la especificación.
- [ ] `[M]` Sin resultados se muestra «Sin resultados para «…»».
- [ ] `[M]` Escribir rápido descarta las respuestas de las búsquedas anteriores.
- [ ] `[M]` Si la búsqueda falla, aparece un aviso y los comandos siguen funcionando.
- [ ] `[M]` El filtro «Usuarios» con «@gmail» lista solo usuarios.
- [ ] `[M]` Tab y Shift+Tab rotan el filtro activo.
- [ ] `[M]` Las flechas mueven la selección saltando cabeceras y desplazando la lista.
- [ ] `[M]` Enter abre, Comando+Enter abre en pestaña nueva sin cerrar el lanzador y Shift+Enter abre
      la vista pública.
- [ ] `[M]` Al llegar resultados, el primero queda seleccionado.
- [ ] `[M]` Con VoiceOver o NVDA, cambiar la selección anuncia el resultado activo.
- [ ] `[M]` A 360 px de ancho el lanzador es usable.

## 5. Búsqueda unificada

- [x] `[S]` La respuesta trae grupos con elementos, total y URL del listado nativo.
- [x] `[S]` Un texto de un carácter responde 400.
- [x] `[N]` Más de 100 caracteres también se rechaza.
- [x] `[S]` Sin sesión responde 401.
- [x] `[S]` Un borrador devuelve su estado en el subtítulo y no trae URL pública.
- [x] `[S]` Una entrada que contiene el texto solo en el cuerpo no aparece.
- [x] `[S]` Un tipo de contenido personalizado aparece en «Otros contenidos» con su etiqueta.
- [x] `[S]` Un colaborador no ve los borradores de otros.
- [x] `[S]` «Contacto» aparece antes que «Formulario de contacto» al buscar «cont».
- [x] `[S]` Buscar «@empresa.com» devuelve los usuarios de ese dominio.
- [x] `[S]` Un editor sin `list_users` no recibe el grupo de usuarios.
- [x] `[S]` El SKU de una variación devuelve su producto padre.
- [x] `[S]` Sin WooCommerce no hay grupos de productos ni de pedidos.
- [x] `[S]` «#26» devuelve ese pedido en primer lugar.
- [x] `[S]` «laura@» devuelve los pedidos con ese email de facturación.
- [x] `[S]` Lo mismo con el almacenamiento clásico de pedidos.
- [x] `[S]` Con límite 2 cada grupo trae 2 elementos e informa del total real.
- [x] `[S]` Pedir solo el tipo «usuarios» no consulta ni devuelve otros grupos.

## 6. Menú del plugin

- [x] `[S]` El menú «EscritorioWP» aparece en la barra lateral con el icono del escritorio.
- [x] `[S]` Su submenú tiene «Ajustes», «Administradores», «Avisos» y «Acerca de», en ese orden, las
      cuatro con `manage_options` (Administradores, además, con `list_users`). Sin `list_users`, el
      orden es «Ajustes», «Avisos» y «Acerca de», y no depende del orden en que se componen los módulos.
- [x] `[S]` La subpágina antigua bajo Ajustes ya no existe.
- [x] `[S]` `options-general.php?page=escritoriowp` ya no resuelve a ninguna pantalla del plugin.
- [x] `[S]` Un usuario sin `manage_options` recibe el aviso de permisos al abrir cualquiera de las dos.
- [x] `[S]` El comando «Ajustes de EscritorioWP» del lanzador apunta a `admin.php?page=escritoriowp`.
- [x] `[S]` Las dos pantallas aparecen en el catálogo del lanzador leídas del menú.
- [ ] `[M]` Buscar «acerca» en el lanzador lleva a la pantalla Acerca de.
- [ ] `[M]` Desmarcar «Activar el lanzador» y guardar deja Comando+K sin efecto en la siguiente carga.
- [x] `[S]` `ajustes.css` se encola en las dos pantallas del menú y en ninguna otra.
- [ ] `[M]` Las dos pantallas se ven bien con tema claro, oscuro y automático.
- [ ] `[M]` La pantalla Acerca de se lee bien a 400 px de ancho.

### Posición del menú

- [x] `[S]` Sin ajuste guardado, «EscritorioWP» aparece justo después de «Escritorio» y antes del
  primer separador.
- [x] `[N]` Una opción guardada por una versión anterior, sin la posición, toma «debajo de
  Escritorio» sin cambiar el resto de ajustes.
- [x] `[S]` Con «Debajo de Páginas» aparece inmediatamente después de «Páginas» y el resto de menús
  mantiene su orden.
- [x] `[S]` Con «Al final» es la última entrada de la barra lateral.
- [x] `[S]` Con «Debajo de WooCommerce» aparece tras «WooCommerce», y el separador de WooCommerce
  sigue en su sitio.
- [x] `[S]` Al desactivar WooCommerce vuelve bajo «Escritorio» sin avisos de PHP y el ajuste sigue
  guardado; al reactivarlo vuelve bajo «WooCommerce».
- [x] `[S]` La sección «Menú» de Ajustes tiene el desplegable «Posición en el menú»: menús agrupados
  bajo «Debajo de…» en el orden de la barra lateral, sin separadores ni «EscritorioWP», con
  «Comentarios» sin el número de pendientes, «Escritorio» seleccionado y «Al final» aparte.
- [x] `[S]` Si el menú guardado ya no existe, el desplegable lo conserva seleccionado como
  «identificador (no disponible)».
- [x] `[S]` Guardar desde el formulario un identificador con `&` (el de «Pagos» de WooCommerce) lo
  conserva y lo aplica; `<script>` se guarda como «debajo de Escritorio».
- [x] `[C]` Con la barra lateral plegada, el orden es el mismo que desplegada.

### Color de la entrada del menú

- [x] `[S]` Sin color, ninguna pantalla del administrador lleva reglas para
  `#toplevel_page_escritoriowp` y la entrada se ve como las demás.
- [x] `[S]` Con `#7a1f5c`, la hoja `admin-menu` lleva en línea el fondo `#7a1f5c` y el texto
  `#ffffff` en el Escritorio y en Entradas.
- [x] `[N]` Fondo oscuro (`#7a1f5c`, `#767676`) da texto blanco; fondo claro (`#ffd60a`, `#777777`)
  da texto negro.
- [x] `[N]` `rojo` y `#12345` se guardan vacíos; `#ABC` se guarda como `#aabbcc`.
- [x] `[S]` La sección «Menú» tiene «Color de fondo en el menú» con el selector de WordPress, que se
  encola en EscritorioWP › Ajustes y en ninguna otra pantalla.
- [x] `[C]` En reposo, la entrada tiene el fondo elegido y el texto y el icono legibles; el resto de
  entradas no cambia. Comprobado con los esquemas Predeterminado y Claro.
- [x] `[C]` En EscritorioWP › Ajustes la entrada abierta conserva el color, también al pasar el
  ratón por «Ajustes» y «Acerca de», y el submenú conserva el aspecto de WordPress.
- [x] `[C]` Con la barra plegada, el cuadro del icono conserva el color.
- [x] `[C]` Al pasar el ratón por la entrada en reposo cambia como cualquier otra entrada (mismos
  estilos calculados que «Entradas»).
- [x] `[C]` Al llegar con el tabulador, en reposo y abierta, la entrada cambia y el foco se ve
  claramente (mismos estilos calculados que «Entradas» con foco).
- [x] `[C]` El botón «Borrar» del selector vacía el campo y, al guardar, la entrada vuelve al aspecto
  por defecto.

## 7. Pantalla Acerca de

- [x] `[S]` Muestra el nombre del plugin y la versión instalada.
- [x] `[S]` Enlaza al repositorio, a sus releases y al sitio del autor, con `rel="noopener"`.
- [ ] `[M]` Los tres enlaces externos abren en una pestaña nueva.
- [x] `[S]` La sección «Novedades» lista las versiones del `readme.txt`, de la más reciente a la más antigua.
- [x] `[S]` La versión instalada aparece marcada como «Instalada».
- [x] `[S]` Con el `readme.txt` ausente o sin changelog, muestra el texto alternativo y ningún aviso de PHP.
- [x] `[S]` Pintar la pantalla no provoca ninguna petición a GitHub.
- [x] `[S]` Sin ninguna comprobación previa, dice que todavía no se ha comprobado.
- [x] `[S]` Con una versión mayor conocida, la anuncia y enlaza a la pantalla Plugins.
- [x] `[S]` Con la instalación al día, dice que es la última versión publicada.
- [x] `[S]` Un usuario con `manage_options` pero sin `update_plugins` no ve el bloque de actualización.

## 8. Actualizaciones desde GitHub

- [x] `[S]` WordPress lee la cabecera `Update URI` y deriva el filtro `update_plugins_github.com`.
- [x] `[S]` Con una release mayor, el plugin queda en `response` del transitorio de actualizaciones.
- [x] `[S]` Con una release igual o menor, queda en `no_update` y no se anuncia nada.
- [x] `[S]` Una release sin ningún activo `.zip` no se ofrece como actualización.
- [x] `[N]` Un borrador, un prelanzamiento o un tag que no es una versión se descartan.
- [x] `[S]` Dos comprobaciones seguidas hacen una sola petición: la respuesta se guarda 12 horas.
- [x] `[S]` Un fallo de red no muestra nada al usuario y se guarda una hora.
- [x] `[S]` La petición no lleva datos del sitio ni del usuario, solo el agente «EscritorioWP/versión».
- [x] `[S]` «Ver detalles» devuelve nombre, versión, autor, requisitos, fecha y changelog de la release.
- [x] `[S]` «Ver detalles» no consulta WordPress.org.
- [ ] `[M]` La ventana de detalles se ve correctamente y ofrece el botón de instalar.
- [x] `[S]` «Buscar actualizaciones ahora» con nonce válido consulta, refresca y vuelve con `comprobado=1`.
- [x] `[S]` Sin nonce o con uno caducado, la petición se rechaza y no sale ninguna consulta a GitHub.
- [ ] `[M]` «Actualizar ahora» desde Plugins descarga el zip de GitHub y deja el plugin activo y actualizado.
- [ ] `[M]` Lo mismo desde Escritorio › Actualizaciones marcando el plugin.
- [ ] `[M]` Tras actualizar, los ajustes y las preferencias de usuario siguen igual.
- [ ] `[M]` Subir el zip a mano desde Plugins › Añadir nuevo ofrece reemplazar la versión instalada.
- [x] `[S]` Con la constante del repositorio vacía no se registra ningún filtro ni se consulta GitHub.

## 9. Publicación y cumplimiento

- [x] `[S]` El zip de la release tiene `escritoriowp/` como única entrada raíz y no lleva `.DS_Store`.
- [x] `[S]` El zip no incluye `vendor/`, `tests/` ni `openspec/`.
- [x] `[S]` `php tools/empaquetar.php --wordpress-org` quita el módulo de actualizaciones, la cabecera
      `Update URI` y vacía la constante, sin tocar el árbol de trabajo.
- [x] `[S]` Plugin Check sobre esa variante no encuentra el error del actualizador.
- [x] `[S]` El zip publicado en la release tiene `escritoriowp/` como única entrada raíz y la versión correcta.
- [x] `[S]` Contra la API real de GitHub, el plugin lee la release publicada y concluye bien si está al día.
- [x] `[S]` `readme.txt` declara el servicio externo, con qué se envía y los enlaces legales de GitHub.
- [x] `[S]` La descripción corta del `readme.txt` no pasa de 150 caracteres.
- [x] `[N]` `php tools/notas-release.php X.Y.Z` extrae la sección del changelog y falla si no existe.
- [x] `[S]` Un push a `main` con la versión subida crea el tag y publica la release con su zip.
- [ ] `[M]` Un push a `main` sin subir la versión pasa las comprobaciones y no publica nada.
- [ ] `[M]` Un push con las versiones descuadradas o sin changelog falla antes de crear el tag.
- [ ] **Pendiente de decisión, no de comprobación:** el nombre y el slug llevan «wp», prohibido en el
      directorio de WordPress.org, y el `readme.txt` tendría que estar en inglés. Ver
      `publicar-wordpress-org.md`.

## 10. Pantalla Administradores

Deriva de `openspec/changes/pantalla-administradores/specs/`. Lo marcado `[S]` se verificó sobre
WordPress 7.1.3 + WooCommerce 11.2.1 con SQLite, sembrando los casos con escrituras directas en la
base de datos y un mu-plugin que esconde usuarios como lo haría un código malicioso.

### Acceso y detección

- [x] `[S]` Un administrador abre EscritorioWP › Administradores y ve el estado general, la lista de
      administradores y los usuarios marcados.
- [x] `[S]` Un editor recibe la denegación de WordPress (403) en `admin.php?page=escritoriowp-administradores`.
- [x] `[S]` Un gestor de tienda, con `list_users` pero sin `manage_options`, recibe la misma denegación.
- [x] `[S]` Un usuario escondido con `pre_user_query`, o con `exclude` desde `pre_get_users`, sigue
      apareciendo en la pantalla.
- [x] `[S]` Unos permisos guardados para un identificador de usuario que no existe no aparecen ni
      producen avisos en `debug.log`.
- [x] `[S]` Un suscriptor al que un mu-plugin concede `manage_options` solo con `user_has_cap` no se marca.
- [x] `[S]` Un usuario con una segunda fila de permisos de administrador (`umeta_id` mayor) no cuenta:
      WordPress usa la primera.

### Cálculo de roles y permisos

- [x] `[N]` `[S]` `{"administrator": false}` hace administrador, como en WordPress.
- [x] `[N]` `[S]` `manage_options` en falso en el usuario anula el que concede su rol.
- [x] `[N]` `edit_users` con el valor `"1"` cuenta.
- [x] `[N]` La clave de un rol inexistente cuenta como permiso suelto, sin avisos.
- [x] `[N]` La lista de permisos críticos es exactamente la de la especificación (30).
- [x] `[N]` `[S]` El gestor de tienda real de WooCommerce y el editor sin modificar no se marcan.

### Lista de administradores y usuarios marcados

- [x] `[S]` Con dos administradores, un editor, un gestor de tienda y 145 suscriptores, la lista de
      administradores tiene exactamente los dos y nadie más aparece en la pantalla.
- [ ] `[M]` En un multisitio, un superadministrador sin rol en el sitio aparece con la etiqueta
      «Superadministrador» y sin la de «Oculto en Usuarios».
- [x] `[S]` Un suscriptor con `manage_options` suelto sale con la etiqueta roja, su rol «Suscriptor» y
      `manage_options` «Asignado al usuario».
- [x] `[S]` Con un rol alterado, su usuario sale con el permiso «Del rol «Editor»» y la explicación de
      que EscritorioWP no modifica roles, sin botón de retirada.
- [x] `[S]` Un administrador con permisos críticos sueltos sale solo en la lista de administradores.
- [x] `[N]` `[S]` Orden: primero los que tienen permisos sueltos; dentro, del alta más reciente a la
      más antigua.
- [x] `[S]` Con el rol Suscriptor alterado y 150 suscriptores, se pintan 100, el aviso dice que hay 49
      más (uno tiene el permiso denegado) y remite a «Roles alterados», y el resumen y el contador
      cuentan 149.

### Roles alterados y usuarios ocultos

- [x] `[S]` El rol Suscriptor alterado sale con su nombre traducido, `subscriber`, sus permisos
      críticos y su número de usuarios; sin roles alterados la sección no aparece.
- [x] `[N]` Un rol `subscriber_2` con nombre visible «Subscriber» sale con su propio identificador.
- [x] `[S]` Un administrador oculto con un mu-plugin lleva «Oculto en Usuarios» y cuenta en el resumen.
- [x] `[S]` Sin código que filtre las consultas de usuarios, nadie lleva «Oculto en Usuarios».

### Estado general y contador

- [x] `[S]` Con usuarios marcados, el resumen en rojo dice «Hay N usuarios que requieren revisión» y
      explica que pueden ser una puerta trasera.
- [x] `[S]` Sin marcados, el resumen en verde lo confirma y la sección de marcados dice que está vacía.
- [x] `[S]` Con marcados, «EscritorioWP» y «Administradores» muestran el contador `awaiting-mod` con
      el número y el texto «N usuarios que requieren revisión» para lectores de pantalla.
- [x] `[S]` Sin marcados no hay contador.
- [x] `[S]` En el catálogo del lanzador la pantalla se llama «Administradores», sin número.
- [ ] `[M]` Escribir «administradores» en el lanzador lleva a la pantalla.
- [x] `[S]` `set_role()`, `add_cap()`, `remove_cap()`, `add_role()`, `remove_role()`,
      `wp_delete_user()` y el alta de un administrador invalidan la caché; el alta de un cliente no.
- [x] `[S]` Tras una escritura directa en la base de datos, el escritorio sigue mostrando el número
      cacheado (hasta 15 minutos) y abrir la pantalla Administradores lo actualiza en el menú y en el
      resumen a la vez.

### Retirar permisos

- [x] `[S]` Un suscriptor con `manage_options` y `edit_users` sueltos tiene el botón «Retirar
      permisos»; su confirmación lista los dos, dice que su rol y el resto de permisos no cambian, y
      pintarla no cambia nada.
- [x] `[S]` Al confirmar, el usuario conserva «Suscriptor» y `edit_posts`, pierde los dos permisos,
      `user_level` pasa de 10 a 0, la caché del contador se invalida y la pantalla dice «Se han
      retirado 2 permisos a sub_suelto: manage_options, edit_users».
- [x] `[S]` «Cancelar» es un enlace de vuelta a la pantalla, sin formulario ni cambios.
- [x] `[S]` Un usuario marcado solo por su rol no tiene botón y la pantalla explica que el permiso
      viene del rol; pedir su confirmación por URL dice que no tiene permisos asignados que retirar.
- [x] `[S]` Con `manage_options` suelto e `install_plugins` del rol, la confirmación avisa de que
      seguirá teniendo `install_plugins`, y al confirmar solo se quita `manage_options` y el usuario
      sigue marcado por su rol.
- [x] `[S]` Un usuario con `manage_options` y `list_users` sueltos ve la pantalla sin ningún botón, y
      una petición POST con un nonce válido de su sesión para retirarse sus propios permisos se
      rechaza sin cambios.
- [x] `[S]` Sin `promote_users`, pedir la confirmación de otro usuario dice que no puedes cambiar su rol.
- [x] `[S]` Pedir la confirmación de un administrador dice que no está marcado.
- [x] `[S]` El nonce de un usuario usado para otro da 403 («El enlace que has seguido ha caducado») sin
      cambios.
- [x] `[S]` La URL de la acción abierta por GET con un nonce válido redirige a la pantalla sin cambios.
- [x] `[S]` Repetir la retirada de un usuario ya corregido no cambia nada y dice que no tiene
      permisos asignados que retirar.
- [ ] `[M]` Recorrer en navegador el flujo completo: botón, confirmación, «Retirar permisos», mensaje
      de resultado y contador del menú actualizado.

### Aspecto

- [x] `[C]` Con tema claro, oscuro y automático (sistema en oscuro) la pantalla se lee bien
      (capturas con Chrome sin interfaz).
- [x] `[C]` A 400 px de ancho las tablas se apilan con la etiqueta de cada dato y nada se sale.

## 11. Avisos por correo

Deriva de `openspec/changes/avisos-correo-plugins/specs/`. Lo marcado `[S]` se verificó sobre
WordPress 7.1.3 con SQLite, con el servidor interno de PHP, sesiones de `curl` y un mu-plugin que
captura cada correo en `phpmailer_init` y después hace fallar el envío.

### Ajustes

- [x] `[S]` La sección «Avisos por correo» aparece en EscritorioWP › Ajustes después de «Menú», con
      las cuatro casillas desmarcadas.
- [x] `[N]` `[S]` Una opción sin las claves nuevas (instalación anterior) toma los avisos apagados, los
      destinatarios vacíos y el asunto y el mensaje vacíos.
- [x] `[S]` El campo «Destinatarios» vacío muestra el correo de administración como ejemplo.
- [x] `[S]` El asunto y el mensaje salen rellenados con su texto predeterminado y bajo el mensaje se
      listan los diez marcadores con su significado.
- [x] `[S]` Guardar el formulario sin tocar nada deja el asunto y el mensaje vacíos en la opción
      (siguen el predeterminado de cada versión e idioma).
- [x] `[S]` Vaciar el mensaje y guardar vuelve a mostrar el mensaje predeterminado.
- [x] `[N]` `[S]` Guardar `ana@ejemplo.es, no-es-un-correo, ana@ejemplo.es` guarda `ana@ejemplo.es` y
      muestra un único aviso que nombra `no-es-un-correo`, con el HTML escapado.
- [x] `[N]` `[S]` Guardar solo `no-es-un-correo` deja el ajuste vacío y muestra el aviso.
- [x] `[N]` Más de diez direcciones válidas se quedan en las diez primeras, en minúsculas.
- [x] `[N]` Un asunto con salto de línea y etiquetas se guarda en una línea y sin etiquetas; un
      mensaje de varias líneas conserva sus saltos.
- [x] `[S]` Guardar sin direcciones descartadas muestra el aviso «Ajustes guardados» de WordPress.
- [x] `[C]` La sección se ve bien con los esquemas de color Predeterminado y Claro del administrador y
      a 400 px de ancho, donde la leyenda pasa a una columna (capturas con Chrome sin interfaz).

### Interruptores y destinatarios

- [x] `[S]` Con las cuatro casillas apagadas, activar un plugin no envía nada y el módulo no engancha
      ningún evento.
- [x] `[S]` Con solo «Avisar al activar un plugin» marcada, activar y desactivar un plugin envía un
      solo correo, el de la activación.
- [x] `[S]` Con `ana@ejemplo.es, luis@ejemplo.es` como destinatarios, el correo va a las dos.
- [x] `[S]` Con los destinatarios vacíos, el correo va al correo de administración vigente, también
      después de cambiarlo en Ajustes › Generales.

### Activar, desactivar, instalar y borrar

- [x] `[S]` Activar un plugin desde Plugins envía un correo de «Activación» con su nombre y versión.
- [x] `[S]` Activar por la API REST (`POST /wp/v2/plugins/<plugin>` con `status=active`) y con WP-CLI
      también avisa.
- [x] `[S]` Actualizar un plugin activo con `wp plugin update`, desde la pantalla Plugins (con la
      reactivación de su iframe) y de forma automática en cron no envía ningún aviso de
      desactivación ni de activación, con las dos casillas marcadas.
- [x] `[S]` Desactivar EscritorioWP envía el correo de su propia desactivación.
- [x] `[S]` En un multisitio, activar un plugin para toda la red desde el administrador de red añade
      «— en toda la red» a su línea y `{url_plugins}` apunta a la pantalla Plugins de la red.
- [x] `[S]` Instalar desde Plugins › Añadir plugin (AJAX), con `wp plugin install <zip>` y con
      `wp plugin install <slug>` envía un correo de «Instalación» con el nombre y la versión.
- [x] `[S]` Subir un zip de un plugin ya instalado no avisa mientras WordPress dice que la carpeta
      existe; al confirmar «Sustituir la versión actual» avisa con la versión del zip.
- [x] `[S]` Subir un zip sin ningún plugin válido no envía nada.
- [x] `[S]` Borrar un plugin con WP-CLI o por AJAX desde Plugins envía un correo de «Borrado» con el
      nombre y la versión que tenía.
- [x] `[S]` Si WordPress no puede borrar los ficheros (carpeta sin permiso de escritura), no se envía
      nada.

### Agrupación, envío y formato

- [x] `[S]` Activar y desactivar cinco plugins en lote envía un solo correo por cada acción, con los
      cinco en la lista y `{cantidad}` 5.
- [x] `[S]` `wp plugin install <slug> --activate` envía dos correos: «Instalación» y después
      «Activación».
- [x] `[S]` Con el envío de correo fallando (SMTP a un puerto cerrado), activar, desactivar, instalar
      y borrar terminan con su redirección o respuesta habitual y sin avisos en `debug.log`.
- [x] `[S]` Con otro plugin que filtra `wp_mail_content_type` a `text/html`, el aviso sale como
      `text/plain` y conserva sus saltos de línea.
- [x] `[S]` Con `{lista}` en el asunto, el asunto llega en una sola línea.

### Marcadores

- [x] `[N]` `[S]` `{evento}`, `{plugins}`, `{lista}`, `{cantidad}`, `{usuario}` («Nombre (login)»),
      `{fecha}` (formato y zona del sitio), `{sitio}`, `{url}`, `{url_plugins}` e `{ip}` se rellenan.
- [x] `[N]` `[S]` Con cinco plugins, `{plugins}` nombra tres y termina en «y 2 más»; `{lista}` tiene cinco
      líneas.
- [x] `[S]` Con WP-CLI, `{usuario}` es «WP-CLI» e `{ip}` es «—».
- [x] `[N]` Un marcador desconocido como `{plugn}` se envía tal cual.
- [x] `[N]` `[S]` Un plugin llamado «Prueba {sitio}» aparece con ese nombre literal.

### Lo que no avisa

- [x] `[S]` Copiar la carpeta de un plugin en `wp-content/plugins/` sin pasar por WordPress no envía
      nada, con las cuatro casillas marcadas.

### En un buzón real

- [ ] `[M]` Con SMTP configurado, llegan los correos de activar, desactivar, instalar y borrar.
- [ ] `[M]` En el cliente de correo, el texto se lee bien: tildes, la raya de «— en toda la red» y los
      saltos de línea.

## 12. Avisos de cambios en los administradores

Deriva de `openspec/changes/avisos-correo-administradores/specs/`. Lo marcado `[S]` se verificó sobre
WordPress 7.1.3 con SQLite, wp-cli, el servidor interno de PHP con sesiones de `curl` y un mu-plugin
que captura cada correo en `phpmailer_init` y hace que el envío falle o salga bien según una opción.
La revisión se lanza sin esperar 6 horas con `wp cron event run escritoriowp_revisar_administradores`.
Las comprobaciones de desactivar y desinstalar con la revisión programada están en §1.

### Interruptor y programación

- [x] `[N]` `[S]` En una instalación nueva o con una opción guardada sin la clave, el aviso está
      apagado, no hay tarea `escritoriowp_revisar_administradores` en WP-Cron ni opción
      `escritoriowp_revision_administradores`.
- [x] `[S]` La casilla «Avisar de cambios en los administradores» aparece desmarcada en la sección
      «Avisos por correo», detrás de «Avisar al borrar un plugin», y las descripciones de «Asunto» y
      «Mensaje» dicen que solo se aplican a los avisos de plugins.
- [x] `[S]` Marcarla y guardar programa la tarea con el intervalo `escritoriowp_seis_horas` (21.600
      segundos) para dentro de 6 horas.
- [x] `[S]` Guardar otro ajuste con la casilla marcada no cambia la próxima ejecución.
- [x] `[S]` Si se borra la tarea con la casilla marcada, la siguiente carga de WordPress la vuelve a
      programar.
- [x] `[S]` Desactivar EscritorioWP quita la tarea; reactivarlo y cargar una página la vuelve a
      programar para dentro de 6 horas.
- [x] `[S]` Desmarcar la casilla y guardar quita la tarea y borra la opción con la situación guardada.

### Punto de partida

- [x] `[S]` Marcar la casilla guarda en `escritoriowp_revision_administradores`, sin carga
      automática, la fecha y el nombre de usuario y el estado de cada administrador y usuario marcado,
      sin enviar ningún correo.
- [x] `[S]` Con administradores y un suscriptor con `manage_options` que ya existían al marcarla, la
      primera revisión sin más cambios no envía nada y avanza la fecha guardada.
- [x] `[S]` Desmarcar la casilla, crear un administrador y volver a marcarla: la primera revisión no
      avisa de ese administrador.
- [x] `[S]` Con la casilla marcada, desactivar el plugin, crear un administrador y reactivarlo: la
      primera revisión lo nombra como nuevo.
- [x] `[N]` Una situación guardada mal formada (sin fecha, o con usuarios que no son una lista) se
      trata como si no existiera: la revisión la sustituye por la actual sin avisar.

### Qué avisa

- [x] `[N]` `[S]` Un administrador nuevo sale en «Administradores nuevos» con su nombre visible, su
      nombre de usuario y su correo.
- [x] `[N]` `[S]` Un suscriptor con `manage_options` que recibe el rol Administrador sale solo en
      «Administradores nuevos», con «Antes tenía permisos de administrador sin el rol».
- [x] `[N]` `[S]` Una administradora que pasa a Suscriptora con `install_plugins` suelto sale solo en
      «Usuarios nuevos con permisos de administrador sin el rol», con «install_plugins (asignado al
      usuario)» y «Antes era administrador».
- [x] `[N]` `[S]` Un administrador borrado sale en «Ya no son administradores» con «Usuario borrado»;
      uno que pasa a Editor, con «Sigue existiendo, sin permisos de administrador».
- [x] `[S]` Retirar los permisos de un marcado desde la pantalla Administradores lo pone en «Ya no
      tienen permisos de administrador».
- [x] `[S]` Un administrador nuevo y un marcado nuevo que un código esconde en `pre_user_query` salen
      con «Oculto en la pantalla Usuarios».
- [x] `[S]` En multisitio, un superadministrador nuevo sin rol en el sitio sale en «Administradores
      nuevos» con «Superadministrador de la red».
- [x] `[N]` `[S]` Crear un administrador y borrar otro entre dos revisiones envía un solo correo con
      los dos apartados; los apartados vacíos no aparecen.
- [x] `[N]` `[S]` Una revisión sin cambios no envía nada.

### Contenido y formato del correo

- [x] `[S]` El asunto es «[Nombre del sitio] Cambios en los administradores» aunque el asunto
      configurable de los avisos de plugins sea `Cambio en {plugins}`.
- [x] `[S]` El correo dice la fecha de la revisión anterior en el formato del sitio y termina con el
      enlace a la pantalla Administradores y la advertencia de que no dice quién hizo los cambios.
- [x] `[N]` `[S]` Con el rol Suscriptor alterado con `manage_options` y 301 suscriptores, el apartado
      dice «(301)», lista 50 usuarios con «manage_options (del rol «Subscriber»)» y termina en «… y 251
      más»; al quitar el permiso, el apartado «Ya no tienen permisos de administrador» hace lo mismo.
- [x] `[S]` Con los destinatarios vacíos va al correo de administración; con dos direcciones, a las
      dos.
- [x] `[S]` Con otro plugin forzando `text/html`, el aviso llega como `text/plain`.

### Envío fallido y lo que no avisa

- [x] `[S]` Con el envío fallando, la revisión que encuentra un administrador nuevo no cambia la
      situación guardada; al recuperar el envío, la siguiente nombra a ese administrador y a un
      marcado nuevo posterior, con la fecha de la última revisión guardada.
- [x] `[S]` Un administrador creado y borrado entre dos revisiones no avisa.
- [x] `[S]` Dar `install_plugins` a un usuario que ya tenía `manage_options` sin el rol no avisa.
- [x] `[S]` La revisión no deja avisos de PHP del plugin en `debug.log`.

### Estado en los ajustes

- [x] `[S]` Con la casilla marcada, su descripción muestra «Última revisión» con el momento de
      guardar y «Próxima» unas 6 horas después, en el formato de fecha y hora del sitio. Desmarcada,
      no muestra fechas.
- [ ] `[M]` La casilla y su descripción se ven bien con los esquemas de color Predeterminado y Claro
      del administrador y a 400 px de ancho.

### En un buzón real

- [ ] `[M]` Con SMTP configurado y WP-Cron con visitas reales, el aviso de un administrador nuevo llega
      unas 6 horas después de crearlo, no antes.
- [ ] `[M]` En el cliente de correo, el aviso se lee bien: tildes, saltos de línea, las comillas
      angulares de «del rol «…»» y los puntos suspensivos de «… y N más».

## 13. Registro de avisos

Deriva de `openspec/changes/registro-avisos/specs/`.

### Qué se guarda

- [x] `[S]` Con «Desactivar» marcada, desactivar cinco plugins con la acción en lote de Plugins guarda
      un solo registro de «Desactivación» cuyo mensaje lista los cinco.
- [x] `[S]` Con «Instalar» y «Activar» marcadas, `wp plugin install hello-dolly --activate` guarda dos
      registros: «Instalación» y «Activación».
- [x] `[S]` Con el aviso de administradores marcado, la revisión que encuentra un administrador nuevo
      guarda un registro de tipo «Administradores» con el correo de esa revisión.
- [x] `[S]` Una revisión sin cambios no guarda nada, y activar un plugin con «Activar» desmarcada
      tampoco.
- [x] `[S]` Con el envío fallando, la revisión que encuentra un administrador nuevo guarda un registro
      «Fallido» con él; al recuperar el envío y añadir un marcado, la siguiente guarda otro «Enviado»
      con los dos, y la situación de la revisión solo avanza con la segunda.
- [x] `[S]` El registro de una activación con dos destinatarios en el ajuste tiene el tipo
      «Activación», el asunto y el mensaje idénticos a los del correo y las dos direcciones.
- [x] `[S]` Con los destinatarios vacíos, el registro guarda el correo de administración.
- [x] `[S]` Cambiar el asunto y el mensaje configurables después de un envío no cambia su registro.

### Estado del envío

- [x] `[S]` Un envío que `wp_mail()` acepta queda «Enviado».
- [x] `[S]` Un envío que `wp_mail()` rechaza queda «Fallido» con el motivo de PHPMailer (comprobado con
      SMTP en `127.0.0.1:1`: «SMTP Error: Could not connect to SMTP host. Failed to connect to server»).
- [x] `[S]` Con `pre_wp_mail` devolviendo `false`, el registro queda «Fallido» con el motivo vacío.
- [x] `[S]` Con un `exit` en `phpmailer_init`, el registro existe con su asunto, su mensaje y sus
      destinatarios, y queda «Sin confirmar» (`pendiente`).
- [x] `[S]` Con una excepción en `phpmailer_init`, el registro queda «Fallido» con el mensaje de la
      excepción y la revisión de administradores no guarda la situación nueva.
- [ ] `[M]` Con SMTP real, un aviso queda «Enviado» y llega al buzón; con el servidor SMTP apagado,
      queda «Fallido» con el motivo que dé el servidor.

### El registro no interfiere con el envío

- [x] `[S]` Con la tabla borrada (y la versión de su esquema intacta), activar un plugin con «Activar»
      marcada envía el correo, deja la redirección de Plugins intacta y no muestra errores aunque
      `WP_DEBUG_DISPLAY` esté activo; el error de SQL queda en `debug.log`.
- [x] `[S]` En esa misma situación, borrar un plugin por AJAX desde Plugins devuelve un JSON válido.

### Registro disponible tras actualizar

- [x] `[S]` Tras instalar el zip sobre una versión sin registro (la 0.4.0, sin tabla ni opción), sin
      pasar por la activación (`wp plugin install <zip> --force`), la tabla no existe hasta que el
      primer aviso posterior la crea, se guarda y aparece en la pantalla Avisos.
- [ ] `[M]` En un multisitio con el plugin activado en la red, un aviso enviado desde el sitio 2
      aparece en la pantalla Avisos del sitio 2 y no en la del sitio principal; cada sitio crea su tabla
      con su primer aviso.
- [ ] `[M]` En MySQL, la tabla se crea con `dbDelta()` con las columnas y los índices del esquema.

### Pantalla Avisos

- [x] `[S]` El menú «EscritorioWP» de un administrador tiene «Ajustes», «Administradores», «Avisos» y
      «Acerca de», en ese orden; con `manage_options` y sin `list_users`, «Ajustes», «Avisos» y «Acerca
      de». Mover la composición de `PantallaAvisos` antes o después de `AcercaDe` no cambia el orden.
- [x] `[S]` Un editor no ve el menú y, al abrir `admin.php?page=escritoriowp-avisos`, recibe la
      denegación de WordPress (403) sin ningún aviso en la página.
- [x] `[S]` Tras borrar su transitorio `escritoriowp_comandos_{id}`, el catálogo del lanzador de un
      administrador incluye la pantalla «Avisos» con su URL.
- [ ] `[M]` Escribir «avisos» en el lanzador muestra la pantalla Avisos y al elegirla se abre el
      listado; escribir «acerca» lleva a Acerca de.
- [x] `[S]` La introducción de la sección «Avisos por correo» de Ajustes enlaza a EscritorioWP ›
      Avisos y dice que cada aviso queda guardado, también si el correo falla.
- [x] `[S]` Con 45 avisos, el listado muestra los 20 más recientes, «45 avisos guardados» y tres
      páginas; cada fila tiene la fecha en el formato del sitio, el tipo, el asunto enlazado a su
      detalle, los destinatarios y el estado.
- [x] `[S]` «Fallido» sale en una etiqueta roja, «Enviado» en verde y «Sin confirmar» en ámbar.
- [x] `[S]` Sin ningún aviso, el listado dice que todavía no se ha guardado ninguno y enlaza a los
      ajustes, sin formulario de filtros.
- [x] `[S]` El filtro de estado «Fallido» deja solo los fallidos, con su total.
- [x] `[S]` Con 30 avisos de tipo «Administradores», la página 2 del filtro muestra los 10 restantes y
      los enlaces de la paginación y del detalle conservan el filtro.
- [x] `[S]` Un filtro sin coincidencias dice que ningún aviso coincide y ofrece «Quitar filtros».
- [x] `[S]` Con `estado=<script>` en la URL se ven todos los avisos, sin errores, y la página no
      repite el valor en ninguna forma (tampoco codificado en los enlaces de la paginación).
- [x] `[S]` El detalle de un aviso fallido de tipo «Administradores» muestra fecha, tipo, estado,
      destinatarios, el motivo del fallo y el mensaje completo con sus apartados en líneas separadas.
- [x] `[S]` Un aviso fallido sin motivo muestra que la función de correo no aceptó el mensaje y no dio
      ningún motivo.
- [x] `[S]` `&aviso=999999` dice que el aviso no existe y enlaza al listado, sin avisos de PHP.
- [x] `[S]` El enlace de vuelta de un detalle abierto desde la página 2 del filtro «Administradores»
      vuelve a esa página con el filtro.
- [x] `[S]` Activar con el aviso marcado un plugin llamado `<img src=x onerror=alert(1)>` deja ese
      nombre como `&lt;img …&gt;` en el listado y en el detalle, nunca como etiqueta.
- [x] `[S]` Desmarcar todas las casillas de avisos y guardar desde el formulario no borra ningún aviso:
      la pantalla los sigue mostrando todos.
- [x] `[S]` Desactivar y reactivar EscritorioWP no borra ningún aviso: la pantalla los sigue mostrando
      todos.
- [x] `[C]` La pantalla Avisos (listado y detalle) se ve bien con los esquemas de color Predeterminado
      y Claro del administrador, con el tema oscuro de las preferencias y a 400 px de ancho, sin
      desplazamiento horizontal: a 400 px la tabla pasa a una ficha por aviso y los filtros ocupan el
      ancho; el desplegable dibuja su flecha con el color del texto en los dos temas.

### Conservación y desinstalación

- [x] `[S]` Con avisos guardados, desactivar y reactivar EscritorioWP conserva todos los avisos, la
      tabla `wp_escritoriowp_avisos` y la opción `escritoriowp_avisos_bd`.
- [x] `[S]` Borrar el plugin elimina la tabla `wp_escritoriowp_avisos` y la opción
      `escritoriowp_avisos_bd` (comprobado con `wp plugin uninstall --deactivate` en una copia de la
      instalación).
- [ ] `[M]` En un multisitio con MySQL y el plugin activado en la red, con avisos en el sitio principal
      y en el sitio 2, borrar el plugin elimina la tabla y la opción de los dos sitios, y no toca la
      tabla de otra instalación que comparta la base de datos con otro prefijo.
