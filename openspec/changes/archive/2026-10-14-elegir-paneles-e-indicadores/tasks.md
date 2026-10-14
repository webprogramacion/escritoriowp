## 1. Ajustes: claves nuevas y saneado

- [x] 1.1 En `includes/escritorio/class-resumen.php`, añadir la constante `INDICADORES` (clave → tipo de `Capacidades::MAPA`, en el orden de las tarjetas, con `'ingresos' => 'pedidos'`) y `etiqueta( string $clave ): string`, con un `match` de los ocho textos que hoy se escriben en cada tarjeta. Hacer que las tarjetas usen `etiqueta()` sin cambiar ningún texto (diseño, decisión 2). En `tests/prueba-resumen.php`, comprobar que las claves de `INDICADORES` son, en orden, `entradas, paginas, usuarios, productos, pedidos, ingresos, comentarios, actualizaciones`, que todos sus valores existen en `Capacidades::MAPA` y que `ingresos` depende de `pedidos`. Verificar que `php tests/ejecutar.php` pasa y que `php tools/generar-pot.php` no añade ni quita cadenas: comparar el número de `msgid` del `.pot` antes y después, sin guardar el `.pot` regenerado.
- [x] 1.2 En `includes/class-ajustes.php`, añadir a `defectos()` `indicadores_activos` y `paneles_activos`, con un `true` por cada clave de `Resumen::INDICADORES` y de `Capacidades::TIPOS_PANEL`. Añadir `sanear_activos()`, pública y pura, y sus dos ramas en el `match` de `sanear()` (decisión 3). Ampliar el tipo a `bool|int|string|array` en `defectos()`, `sanear()`, `todos()`, `obtener()` y `$cache`. Añadir `panel_activo()` e `indicador_activo()`, que devuelven `true` para una clave ausente. En `tests/prueba-ajustes.php`, comprobar:
  - por defecto, los dos mapas tienen todas sus claves en `true`;
  - `'0'` se guarda como `false` y `'1'` como `true`;
  - una clave ausente vale `true`;
  - `inventado` se descarta;
  - un valor que no es un array deja todas en `true`;
  - el resultado sigue el orden de la lista conocida aunque llegue desordenado;
  - con los mapas en la misma entrada, `elementos_por_panel` `50` se sigue acotando a `20`.

  Verificar que `php tests/ejecutar.php` pasa y que `grep -rn "obtener( '" escritoriowp/includes` no muestra ningún llamante que use el valor sin convertirlo.
- [x] 1.3 Actualizar `docs/qa.md` §1: «los quince valores por defecto» pasan a «diecisiete». Añadir una línea `[N]` para el escenario «Lista de casillas» de `nucleo-plugin`. Verificar releyendo que el escenario tiene su línea y que `composer lint` termina sin errores ni avisos.

## 2. Escritorio y resumen: mostrar solo lo activo

- [x] 2.1 En `Resumen`, añadir `claves_visibles()`, que devuelve las claves activas cuyo tipo puede ver el usuario. Reescribir `obtener()` para que cada bloque pregunte por su clave en esa lista (decisión 4). Llamar a `tarjetas_woo()` solo si la lista contiene `productos`, `pedidos` o `ingresos`, y separar dentro la tarjeta de ingresos de la de pedidos. Verificar sobre la instalación SQLite con WooCommerce (receta de `CLAUDE.md` §10) llamando a `rest_do_request()` sobre `/escritoriowp/v1/resumen` como administrador y como autor:
  - sin guardar los ajustes, las tarjetas son las mismas que antes del cambio;
  - con `ingresos` en `false` falta solo la de ingresos;
  - con `usuarios` activo, el autor no recibe la tarjeta de usuarios;
  - con `productos`, `pedidos` e `ingresos` en `false`, tras borrar el transitorio `escritoriowp_resumen_woo` y pedir el resumen, el transitorio sigue sin existir, porque no se han recorrido los pedidos.
- [x] 2.2 En `Escritorio::configuracion()`, filtrar los paneles con `panel_activo()` antes de ordenarlos y añadir `indicadores` (número de `claves_visibles()`). En `pintar_app()`, no pintar las secciones de resumen y de paneles cuando estén vacías (decisión 5). Verificar en la instalación SQLite, pintando el escritorio con `wp_dashboard_setup()` y `do_meta_boxes( 'dashboard', 'normal', '' )`:
  - con `usuarios` en `false` en `paneles_activos`, la configuración no tiene el panel de usuarios y la cabecera sigue teniendo el botón «Nuevo usuario»;
  - con todos los indicadores en `false`, no aparece `data-escritoriowp-resumen`;
  - con todos los paneles en `false`, no aparece `data-escritoriowp-paneles`;
  - con un usuario que había ocultado `paginas`, la desactivación y reactivación de `paginas` deja su metadato `escritoriowp_preferencias` sin cambios.
- [x] 2.3 En `assets/js/escritorio.js`, hacer que `cargarResumen()`, `pintarPaneles()` y `cargarPanelPorClave()` salgan si falta su nodo. Pintar el esqueleto con `config.indicadores` tarjetas. Omitir el título «Paneles» y sus filas en `pintarPersonalizar()` si `config.paneles` está vacío. No añadir textos nuevos. Verificar con `node --check escritoriowp/assets/js/escritorio.js` y `node tests/ejecutar.js`.
- [x] 2.4 Documentar el filtro:
  - en `CLAUDE.md` §5, una nota de que «Indicadores» y «Paneles» son presentación y no control de acceso, y de que se filtran en `Escritorio` y `Resumen`, nunca en `Capacidades`;
  - en `CLAUDE.md` §8, la receta «Un panel nuevo» (sale activo y aparece solo en Ajustes) y una receta nueva «Un indicador nuevo» (añadirlo a `Resumen::INDICADORES` y a `Resumen::etiqueta()`; si no está en la lista, no se muestra);
  - en `docs/qa.md` §2, una línea por cada escenario de «Paneles e indicadores activos en el sitio» y de los dos nuevos de «Personalización por usuario», marcada `[S]` o `[N]` según cómo se verificó en 2.1 y 2.2. Quedan como `[M]`: que sin indicadores no hay hueco ni esqueleto, que «Personalizar» solo ofrece el tema y «Restablecer» sin paneles, y que el botón «Actualizar» y crear un elemento desde la cabecera no dan errores en la consola sin alguna de las dos zonas.

  Verificar releyendo que cada escenario tiene su línea.
- [x] 2.5 Añadida durante la 4.3. Hacer que la creación rápida de la cabecera no dependa de los paneles activos: `Escritorio::configuracion()` pasa también `tipos` (todos los tipos que el usuario puede ver) y `abrirModal()` busca el tipo con un `tipoPorClave()` nuevo en lugar de `panelPorClave()` (diseño, decisión 5). Añadir su línea a `docs/qa.md` §2. Verificar en Chrome sin interfaz que, con «Usuarios» desactivado en «Paneles», «Nuevo usuario» abre la ventana (antes del arreglo no la abría) y que, sin ningún panel activo, también la abren «Nueva entrada» y «Nueva página».

## 3. Pantalla de ajustes: los dos grupos de casillas

- [x] 3.1 Añadir el tipo `casillas` a `pintar_campo()`, con el argumento `opciones` en la lista de argumentos opcionales de `registrar_ajustes()` (decisión 6). `add_settings_field()` se llama sin `label_for` para este tipo. Declarar los campos `indicadores_activos` («Indicadores») y `paneles_activos` («Paneles») en la sección «Escritorio», entre «Ocultar los widgets de otros plugins» y «Elementos por panel». Sus opciones se filtran con `Capacidades::disponible()` y llevan una descripción que diga que afectan a todos los usuarios y que cada uno sigue viendo solo lo que su rol le permite. Retocar `assets/css/ajustes.css` solo si el grupo descuadra la tabla. Verificar pintando la pantalla de ajustes en la instalación SQLite:
  - sin WooCommerce, «Indicadores» tiene las cinco casillas del escenario «Opciones sin WooCommerce» y «Paneles», tres, todas marcadas;
  - con WooCommerce aparecen las de productos, pedidos e ingresos en su sitio;
  - cada grupo es un `<fieldset>` con `<legend>`, cada casilla tiene su `<label for>` y su campo oculto `0`, y la cabecera de la fila no es un `<label for>`.
- [x] 3.2 En `sanear_formulario()`, completar los dos mapas enviados con los guardados antes de sanear (decisión 7). Verificar en la instalación SQLite llamando a `sanear_formulario()` con lo que enviaría el formulario:
  - con `pedidos` guardado en `false` y sin WooCommerce, un envío sin las claves de WooCommerce conserva `pedidos` en `false`;
  - un envío con `paneles_activos[inventado]=1` y `paginas=0` guarda `paginas` en `false` sin `inventado` ni avisos en `get_settings_errors()`;
  - una segunda llamada con la salida de la primera devuelve lo mismo.
- [x] 3.3 Documentar los ajustes:
  - en `CLAUDE.md` §8, la receta «Un ajuste nuevo»: tipo `casillas` con su argumento `opciones` y su saneado con `sanear_activos()`;
  - en `escritoriowp/readme.txt`, el párrafo de **Ajustes** de «= El menú del plugin =» y una pregunta frecuente sobre cómo ocultar a todos un panel o un indicador, distinguiéndolo de «Personalizar»;
  - en `CHANGELOG.md`, «Añadido» en `## [No publicado]`;
  - en `docs/qa.md` §1, una línea por escenario de «Elección de paneles e indicadores en los ajustes», marcada según 3.1 y 3.2. El del lector de pantalla queda como `[M]`.

  Verificar releyendo que cada escenario tiene su línea y que `composer lint` termina sin errores ni avisos.

## 4. Comprobación de conjunto

- [x] 4.1 Ejecutar `composer test`, `composer lint`, `find escritoriowp -name '*.php' -print0 | xargs -0 -n1 php -l` y `find escritoriowp/assets/js -name '*.js' -print0 | xargs -0 -n1 node --check`. Verificar que todo termina sin errores ni avisos.
- [x] 4.2 Empaquetar con `php tools/empaquetar.php`, instalar el zip en la instalación SQLite y pasar Plugin Check con wp-cli desde fuera del repositorio. Verificar que no hay errores.
- [x] 4.3 Recorrer en navegador las líneas `[M]` nuevas de `docs/qa.md` §1 y §2, con WooCommerce activo y desactivado, como administrador y como autor. Guardar en Ajustes desmarcando y volviendo a marcar paneles e indicadores, y marcar cada línea comprobada en `docs/qa.md`.

## Workflow follow-up

- Archivar antes `releases-github-y-menu-propio`, `menu-posicion-y-color` y `avisos-correo-plugins`: el delta de `nucleo-plugin` parte del texto que deja este último.
- Archivar este cambio con `/opsx:archive` cuando esté revisado.
- Publicar con `/nueva-version minor` cuando el usuario decida; esa skill escribe las entradas de `readme.txt` y regenera el `.pot` con los textos nuevos.
