## 1. Detección de tipos de contenido personalizados

- [x] 1.1 Crear `escritoriowp/includes/class-tipos-contenido.php` (`EscritorioWP\TiposContenido`, final, estática) con las funciones puras de la decisión 2 del diseño: `PREFIJO`, `es_clave()`, `clave()`, `tipo()`, `icono_menu()` y `desambiguar()`. Añadir su `require_once` a `tests/ejecutar.php` y crear `tests/prueba-tipos-contenido.php`, que compruebe:
  - `es_clave()` acepta `cpt-libro`, `cpt-tribe_events`, `cpt-a-b` y un identificador de 20 caracteres, y rechaza `entradas`, `cpt-`, `cpt-Libro`, `cpt-con espacio`, `cpt-` con 21 caracteres y `xcpt-libro`;
  - `clave( 'libro' )` es `cpt-libro` y `tipo()` la deshace, y `tipo( 'entradas' )` es `''`;
  - `icono_menu()` conserva `dashicons-book` y devuelve `dashicons-admin-post` para una URL, un `data:image/svg+xml;base64,…`, `none`, `''`, `null` y `dashicons-<script>`;
  - `desambiguar()` deja igual los nombres únicos y añade el identificador entre paréntesis a los dos nombres repetidos, sin cambiar el orden.

  Añadir a `tests/prueba-autoloader.php` que `Autoloader::ruta_relativa( 'EscritorioWP\\TiposContenido' )` es `tipos-contenido.php`. Verificar que `php tests/ejecutar.php` pasa.
- [x] 1.2 Añadir las funciones con WordPress: `detectados()` con el criterio, el filtro `escritoriowp_tipos_contenido`, el orden por nombre y la caché solo después de `init`; `objeto()`, `nombre()`, `icono()` y `opciones()`. Mover `TIPOS_EXCLUIDOS` de `busqueda/class-fuente-contenidos.php` a `TiposContenido::EXCLUIDOS` (pública) y hacer que `FuenteContenidos` la use desde ahí. Verificar en la instalación SQLite con WooCommerce (receta de `CLAUDE.md` §10), con un mu-plugin de prueba que registre `libro` («Libros»), `evento` y `tribe_events` (los dos «Eventos»), `interno` (`show_ui` sin `show_in_menu`) y `oculto` (sin `show_ui`):
  - `array_keys( TiposContenido::detectados() )` es `cpt-evento, cpt-tribe_events, cpt-libro` (orden por nombre), sin `product`, `shop_order`, `shop_coupon`, `interno` ni `oculto`;
  - `opciones()` da «Eventos (evento)», «Eventos (tribe_events)» y «Libros»;
  - el filtro puede quitar `libro` y añadir `interno`, y lo que añada como `product`, `post` u `oculto` se descarta;
  - llamar a `detectados()` desde `plugins_loaded` no impide que después de `init` devuelva la lista completa;
  - la ruta REST `buscar` con un término que coincide con un libro, un producto y una entrada devuelve los mismos grupos y resultados que antes del cambio.
- [x] 1.3 Documentar: en `CLAUDE.md` §2 añadir `class-tipos-contenido.php` a la estructura, y en §8 una receta «Ofrecer o quitar un tipo de contenido personalizado» con el filtro `escritoriowp_tipos_contenido` y qué descarta. Verificar releyendo que la estructura de §2 coincide con `ls escritoriowp/includes`.

## 2. Ajustes: claves de tipos personalizados en las listas de casillas

- [x] 2.1 En `includes/class-ajustes.php`, añadir `MAX_TIPOS_CONTENIDO` (100), ampliar `sanear_activos()` para que conserve las claves `cpt-…` válidas con valor verdadero, y cambiar `activo()` para que una clave ausente valga `! TiposContenido::es_clave( $clave )` (diseño, decisión 4). Actualizar el docblock de `defectos()`, `sanear_activos()`, `panel_activo()` e `indicador_activo()`. En `tests/prueba-ajustes.php`, comprobar:
  - `defectos()` no tiene ninguna clave `cpt-…`;
  - con `paneles_activos` `{ cpt-libro: '1', cpt-evento: '0', inventado: '1', 'cpt-Malo': '1' }`, el resultado tiene las cinco claves propias en `true`, `cpt-libro` en `true` y nada más;
  - las claves propias siguen saliendo primero y en su orden;
  - con 150 claves `cpt-…` marcadas se conservan las 100 primeras;
  - un valor que no es un array sigue dejando todas las propias en `true` y ninguna `cpt-…`;
  - los escenarios de «Lista de casillas» del cambio anterior siguen pasando sin tocarlos.

  Verificar que `php tests/ejecutar.php` pasa.
- [x] 2.2 Añadir a `docs/qa.md` §1 una línea `[N]` para el escenario «Tipos de contenido personalizados en una lista de casillas» de `nucleo-plugin` y ajustar la de «Lista de casillas» a «paneles propios». Verificar releyendo que los dos escenarios tienen su línea.

## 3. Capacidades y preferencias

- [x] 3.1 En `includes/class-capacidades.php`, añadir la rama de las claves `cpt-…` a `existe()`, `disponible()`, `puede_ver()`, `puede_crear()`, `puede_publicar()`, `url_listado()`, `url_nuevo()`, `etiqueta()`, `etiqueta_nuevo()` e `icono()` según la tabla de la decisión 3, y los métodos nuevos `tipos_panel()`, `texto_vacio()` y `texto_crear_primero()` (decisión 7), con los textos propios copiados tal cual de `Activos::textos()`. `paneles_visibles()` pasa a recorrer `tipos_panel()`. En `tests/prueba-capacidades.php`, comprobar que `tipos()` y `TIPOS_PANEL` no cambian y que `existe( 'inventado' )` sigue siendo falso sin llamar a WordPress. Verificar `php tests/ejecutar.php` y, en la instalación SQLite con el mu-plugin de 1.2 y `libro` con `capability_type => 'libro'` y `map_meta_cap`:
  - un administrador con las capacidades de libros tiene `puede_ver`, `puede_crear` y `puede_publicar` verdaderos para `cpt-libro`, y un editor sin ellas, los tres falsos;
  - `url_listado( 'cpt-libro' )` y `url_nuevo( 'cpt-libro' )` llevan a `edit.php?post_type=libro` y `post-new.php?post_type=libro`;
  - `existe()` es falso para `cpt-product`, `cpt-oculto` y `cpt-noexiste`;
  - `paneles_visibles()` del administrador son los cinco propios seguidos de `cpt-evento, cpt-tribe_events, cpt-libro`.
- [x] 3.2 En `includes/escritorio/class-preferencias.php`, aceptar en `sanear()` las claves que cumplen `TiposContenido::es_clave()` además de las de `$paneles_validos`, con `MAX_PANELES` (100) por lista (decisión 9). En `tests/prueba-preferencias.php`, comprobar que `cpt-libro` se conserva en `paneles_ocultos` y en `orden_paneles` aunque no esté en la lista válida, que `cpt-Malo` e `inventado` se descartan, que los duplicados se quitan y que una lista de 150 claves válidas se corta en 100. Verificar que `php tests/ejecutar.php` pasa.

## 4. Paneles de tipos personalizados

- [x] 4.1 En `Recientes::obtener()`, añadir la rama de las claves `cpt-…` que reutiliza `contenidos()` (decisión 6). En `Rest::registrar_rutas()`, cambiar el patrón de `recientes` a `[a-z0-9_-]+`. Verificar con `rest_do_request()` en la instalación SQLite, con tres libros (dos publicados y un borrador, uno de otro autor):
  - un administrador recibe 200 en `recientes/cpt-libro` con los tres libros por fecha de modificación descendente, el borrador con la etiqueta «Borrador» y `urlVer` solo en los publicados;
  - un usuario que solo puede editar sus libros recibe solo los suyos;
  - un usuario sin capacidad sobre libros recibe 403, y cualquiera recibe 404 en `recientes/cpt-product`, `recientes/cpt-oculto` y `recientes/cpt-noexiste`;
  - con `cpt-libro` desmarcado en `paneles_activos`, el administrador sigue recibiendo 200;
  - `recientes/entradas` y el resto de rutas propias responden igual que antes.
- [x] 4.2 En `Escritorio::configuracion()`, añadir `textoVacio` y `textoCrearPrimero` a cada tipo y calcular `enModal` con `Creador::admite()` (decisiones 6 y 7). Quitar las diez claves `panelVacio…` y `crearPrimero…` de `Activos::textos()`. En `assets/js/escritorio.js`, hacer que `pintarFilas()` use `panel.textoVacio` y `panel.textoCrearPrimero` y borrar `capitalizar()` si queda sin uso. Verificar con `node --check escritoriowp/assets/js/escritorio.js`, `node tests/ejecutar.js`, que `php tools/generar-pot.php` no cambia el número de `msgid` (comparar antes y después sin guardar el `.pot` regenerado) y, pintando el escritorio en la instalación SQLite con `wp_dashboard_setup()` y `do_meta_boxes( 'dashboard', 'normal', '' )`:
  - sin claves `cpt-…` guardadas, `config.paneles` y el HTML son idénticos a los de antes del cambio salvo los dos textos nuevos de cada panel;
  - con `cpt-libro` en `true`, `config.paneles` termina en `cpt-libro` con `enModal` falso, `urlNuevo` a `post-new.php?post_type=libro` y los textos `not_found` y `add_new_item` del tipo;
  - la cabecera tiene los mismos botones con y sin `cpt-libro` activo;
  - con `cpt-libro` como único panel activo se pinta la zona de paneles, y un usuario sin capacidad sobre libros no la recibe.
- [x] 4.3 Recorrer en Chrome sin interfaz, como administrador: el panel «Libros» se pinta con sus filas, «Ver todos» y el botón de crear llevan a las pantallas nativas, el estado vacío (sin libros) muestra los textos del tipo y su botón, y los estados vacíos de entradas y páginas muestran los mismos textos que antes. Ocultar, mostrar y subir «Libros» desde «Personalizar» persiste al recargar. Verificar que la consola no tiene errores.
- [x] 4.4 Documentar los paneles:
  - en `CLAUDE.md` §5, una fila «tipos personalizados» en la tabla (`cap->edit_posts`, `cap->create_posts`, `cap->publish_posts` del tipo) y una nota de que la ruta `recientes/cpt-…` responde aunque el panel esté desmarcado;
  - en `CLAUDE.md` §8, la receta «Un panel nuevo» pasa a pedir sus textos en `Capacidades::texto_vacio()` y `texto_crear_primero()` en lugar del catálogo de `Activos::textos()`;
  - en `docs/qa.md` §2, una línea por cada escenario de «Paneles de tipos de contenido personalizados» y de los nuevos de «Control de acceso por capacidades», marcada `[S]` o `[C]` según cómo se verificó en 4.1 a 4.3.

  Verificar releyendo que cada escenario tiene su línea.

## 5. Indicadores de tipos personalizados

- [x] 5.1 En `Resumen`, añadir a `claves_visibles()` las claves `cpt-…` activas y visibles, pintar sus tarjetas al final de `obtener()` con `wp_count_posts()`, dar su nombre en `etiqueta()` y actualizar el docblock de `INDICADORES` (decisión 8). Verificar con `rest_do_request()` sobre `/escritoriowp/v1/resumen` en la instalación SQLite, con 12 libros publicados y 3 en borrador:
  - sin claves `cpt-…` guardadas, la respuesta es idéntica a la de antes del cambio para un administrador, un editor y un autor;
  - con `cpt-libro` en `true` en `indicadores_activos`, la última tarjeta es «Libros» con valor `12`, detalle «3 en borrador», enlace al listado de libros y el icono del tipo; sin borradores, el detalle está vacío;
  - con `cpt-libro` en `true` en `paneles_activos` y sin él en `indicadores_activos`, no hay tarjeta de libros;
  - con todos los indicadores propios en `false` y solo `cpt-libro` activo, `claves_visibles()` es `[ 'cpt-libro' ]` y el escritorio pinta `data-escritoriowp-resumen`;
  - un usuario sin capacidad sobre libros no recibe la tarjeta.
- [x] 5.2 Añadir a `docs/qa.md` §2 una línea por cada escenario de «Indicadores de tipos de contenido personalizados», marcada según 5.1. Verificar releyendo que cada escenario tiene su línea.

## 6. Pantalla de ajustes

- [x] 6.1 Añadir `TiposContenido::opciones()` al final de `opciones_paneles()` y `opciones_indicadores()`, hacer que `pintar_casillas()` pinte desmarcada una clave `cpt-…` sin valor guardado y añadir a las descripciones de los dos campos la frase de la decisión 5 según haya o no tipos detectados. Verificar pintando la pantalla de ajustes en la instalación SQLite:
  - sin tipos personalizados ni WooCommerce, las opciones son las del escenario «Opciones sin WooCommerce» y la descripción dice que el sitio no tiene tipos de contenido personalizados;
  - con el mu-plugin de 1.2 y WooCommerce, los dos grupos terminan, desmarcados, en «Eventos (evento)», «Eventos (tribe_events)» y «Libros», detrás de «Productos» y «Pedidos» en «Paneles», y cada casilla tiene su campo oculto `0` y su `<label for>`;
  - con `cpt-libro` guardado en `true`, su casilla sale marcada.
- [x] 6.2 Verificar la conservación al guardar llamando a `sanear_formulario()` con lo que enviaría el formulario en la instalación SQLite:
  - con `cpt-libro` guardado en `true` y el mu-plugin desactivado, un envío sin claves `cpt-…` conserva `cpt-libro` en `true`, y al reactivar el mu-plugin su casilla sale marcada y su panel vuelve al escritorio;
  - un envío con `cpt-libro=0` lo quita del mapa;
  - un envío con `cpt-libro=1` y `cpt-Malo=1` guarda solo `cpt-libro`, sin avisos en `get_settings_errors()`;
  - con el mu-plugin desactivado, el escritorio de un administrador con `cpt-libro` activo se pinta sin el panel ni la tarjeta y sin avisos de PHP;
  - un usuario con `cpt-libro` en `paneles_ocultos` que guarda su tema mientras el mu-plugin está desactivado conserva `cpt-libro` en su metadato `escritoriowp_preferencias`.
- [x] 6.3 Documentar los ajustes:
  - en `escritoriowp/readme.txt`, el párrafo de **Ajustes** de «= El menú del plugin =» y la pregunta frecuente «¿Puedo ocultar un panel o un indicador a todos los usuarios?», con los tipos personalizados y que salen desmarcados; una pregunta nueva «¿Puedo ver mis tipos de contenido personalizados en el escritorio?»;
  - en `CHANGELOG.md`, «Añadido» en `## [No publicado]`;
  - en `docs/qa.md` §1, una línea por escenario de «Detección de tipos de contenido personalizados en los ajustes», de «Tipo de contenido personalizado que deja de existir» y de los dos escenarios de opciones que cambian, marcada según 6.1 y 6.2.

  Verificar releyendo que cada escenario tiene su línea y que `composer lint` termina sin errores ni avisos.

## 7. Comprobación de conjunto

- [x] 7.1 Ejecutar `composer test`, `composer lint`, `find escritoriowp -name '*.php' -print0 | xargs -0 -n1 php -l` y `find escritoriowp/assets/js -name '*.js' -print0 | xargs -0 -n1 node --check`. Verificar que todo termina sin errores ni avisos.
- [x] 7.2 Empaquetar con `php tools/empaquetar.php`, instalar el zip en la instalación SQLite y pasar Plugin Check con wp-cli desde fuera del repositorio. Verificar que no hay errores.
- [x] 7.3 Recorrer en navegador las líneas `[M]` nuevas de `docs/qa.md` §1 y §2 con el mu-plugin de tipos, con WooCommerce activo y desactivado, como administrador y como autor: marcar y desmarcar tipos en los dos grupos, guardar, desactivar y reactivar el mu-plugin. Marcar cada línea comprobada en `docs/qa.md`.

## Workflow follow-up

- Archivar el cambio con `/opsx:archive` cuando esté implementado y revisado.
- Publicar como versión menor con `/nueva-version minor` cuando se decida.
