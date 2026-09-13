# Lista de comprobación manual de EscritorioWP

Deriva de los escenarios de `openspec/changes/create-escritoriowp-plugin/specs/`. Cada línea es un
escenario comprobable.

**Leyenda**

- `[S]` Verificado en servidor con el arnés de PHP (rutas REST y pintado del escritorio) sobre
  WordPress 7.1 + WooCommerce 11.1.0 con SQLite. No necesita repetirse a mano.
- `[N]` Cubierto por las pruebas unitarias (`php tests/ejecutar.php`, `node tests/ejecutar.js`).
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
- [x] `[S]` En la primera activación se crea la opción con los seis valores por defecto.
- [x] `[N]` Un ajuste ausente tras una actualización toma su valor por defecto sin perder el resto.
- [x] `[N]` Un número de elementos por panel fuera de rango se ajusta al límite más cercano.
- [x] `[S]` La página EscritorioWP › Ajustes existe, exige `manage_options` y publica en `options.php`
      con nonce.
- [ ] `[M]` Guardar «Sustituir el escritorio nativo» en No devuelve el escritorio nativo al recargar.
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
- [x] `[S]` Borrar el plugin no deja en la base de datos ninguna fila con prefijo `escritoriowp` en
      `wp_options` ni en `wp_usermeta`.

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
- [ ] `[M]` Ocultar el panel de páginas y recargar desde otro navegador lo mantiene oculto.
- [ ] `[M]` Reordenar paneles con las flechas persiste al recargar.
- [ ] `[M]` Con el tema en «automático» y el sistema en modo oscuro, la interfaz se ve oscura.
- [ ] `[M]` «Restablecer» devuelve paneles y tema a sus valores por defecto.
- [ ] `[M]` A 400 px de ancho no hay desplazamiento horizontal y los paneles se apilan.
- [ ] `[M]` Recorrer la interfaz con Tab da foco visible a todos los botones y enlaces en orden.

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
- [x] `[S]` Su submenú tiene «Ajustes» primero y «Acerca de» después, ambas con `manage_options`.
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
- [x] `[S]` `readme.txt` declara el servicio externo, con qué se envía y los enlaces legales de GitHub.
- [x] `[S]` La descripción corta del `readme.txt` no pasa de 150 caracteres.
- [x] `[N]` `php tools/notas-release.php X.Y.Z` extrae la sección del changelog y falla si no existe.
- [ ] `[M]` Un push a `main` con la versión subida crea el tag y publica la release con su zip.
- [ ] `[M]` Un push a `main` sin subir la versión pasa las comprobaciones y no publica nada.
- [ ] `[M]` Un push con las versiones descuadradas o sin changelog falla antes de crear el tag.
- [ ] **Pendiente de decisión, no de comprobación:** el nombre y el slug llevan «wp», prohibido en el
      directorio de WordPress.org, y el `readme.txt` tendría que estar en inglés. Ver
      `publicar-wordpress-org.md`.
