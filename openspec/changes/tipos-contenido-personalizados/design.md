## Context

Motivación y alcance en `proposal.md`; comportamiento en los deltas de `specs/`.

Estado actual que condiciona el diseño:

- **Los tipos del escritorio son fijos.** `Capacidades::MAPA` es una constante con siete tipos y
  `Capacidades::TIPOS_PANEL` lista los cinco paneles. Todas las consultas de `Capacidades`
  (`existe()`, `puede_ver()`, `url_listado()`, `etiqueta()`, `icono()`…) son `match` sobre esas
  claves. Los paneles se deciden en `Escritorio::paneles_visibles()` y los indicadores en
  `Resumen::claves_visibles()`, que recorre la constante `Resumen::INDICADORES`.
- **Las listas de casillas dan por activo lo que falta.** `Ajustes::sanear_activos()` es pura,
  recorre las claves conocidas y da `true` a la que no llega; descarta las demás.
  `Ajustes::activo()` hace lo mismo al leer. `sanear_formulario()` completa lo enviado con lo
  guardado (`+=`) para no perder las casillas que no se pintan, como las de WooCommerce.
- **Las preferencias se validan contra `TIPOS_PANEL`.** `Preferencias::sanear()` es pura y descarta
  las claves que no están en la lista que recibe.
- **La ruta `recientes/(?P<tipo>[a-z]+)`** solo admite letras minúsculas. Los identificadores de tipo
  de WordPress admiten además números, `-` y `_`, hasta 20 caracteres.
- **Los textos de estado vacío son claves del catálogo del JS**: `panelVacioEntradas`,
  `crearPrimeroEntradas`… El JS las compone con `capitalizar( clave )`, así que solo existen para los
  cinco tipos conocidos.
- **`FuenteContenidos` ya sabe qué tipos no son contenido**: su constante privada `TIPOS_EXCLUIDOS`
  quita adjuntos, estructuras del editor y los tipos de WooCommerce de la búsqueda «Otros
  contenidos».
- **La ventana de creación rápida** solo existe para los tipos de `Creador::CREADORES`.
  `Escritorio::configuracion()` decide `enModal` con `'pedidos' !== $tipo`, y `botonNuevo()` del JS
  ya pinta un enlace cuando `enModal` es falso.

## Goals / Non-Goals

**Goals:**

- Que un tipo personalizado sea un panel y un indicador más para todo lo que ya existe: ajustes,
  «Personalizar», ruta REST, capacidades, zonas vacías y botón «Actualizar».
- Una sola fuente que decide qué tipos personalizados se ofrecen, consultada por los ajustes, el
  escritorio, el resumen, la ruta REST y la búsqueda.
- Que `Ajustes::sanear()` y `Preferencias::sanear()` sigan siendo puras: ninguna consulta los tipos
  registrados.
- Ningún cambio visible para un sitio que actualiza sin tocar los ajustes.

**Non-Goals:**

- Cambiar `Capacidades::MAPA` o `TIPOS_PANEL`, que siguen describiendo los tipos propios. La
  cabecera y los comandos fijos del lanzador siguen recorriendo `TIPOS_PANEL`.
- Limpiar de la opción las claves de tipos que ya no existen (ver riesgos).
- Mostrar campos propios del tipo (metacampos, taxonomías, imagen destacada) en las filas.

## Decisions

### 1. Clave `cpt-{tipo}` para los tipos personalizados

Un tipo personalizado se identifica en todo el plugin con `cpt-` seguido de su identificador de
WordPress: `cpt-libro`, `cpt-tribe_events`. Es la clave del panel en los ajustes, en las
preferencias, en `config.paneles`, en el atributo `data-panel` y en la ruta `recientes/cpt-libro`.
`TiposContenido::es_clave()` la reconoce con `^cpt-[a-z0-9_-]{1,20}$`, que es justo lo que admite
WordPress para un identificador de tipo (`sanitize_key()` y 20 caracteres como máximo).

- El prefijo separa los dos espacios de nombres: un tipo personalizado llamado `pedidos` no puede
  pisar el panel propio de pedidos, y ninguna clave propia tiene guion.
- Con la clave en el mismo espacio que las propias, preferencias, orden, `data-panel` y
  `recientes/{tipo}` funcionan sin ramas aparte en el JS.

La ruta pasa a `recientes/(?P<tipo>[a-z0-9_-]+)`. La de creación (`crear/(?P<tipo>[a-z]+)`) no
cambia: los tipos personalizados no tienen creación rápida.

Alternativas descartadas:

- **El identificador sin prefijo.** Choca con las claves propias y obliga a consultar los tipos
  registrados para saber qué es cada clave, cosa que el saneado puro no puede hacer.
- **Una ruta propia (`recientes/contenido/{tipo}`).** Seguiría haciendo falta una clave única para
  las preferencias y el orden, y el JS necesitaría dos formas de pedir un panel.

### 2. `TiposContenido`, la fuente única de qué tipos se ofrecen

Clase nueva `EscritorioWP\TiposContenido` en `includes/class-tipos-contenido.php`, junto a
`Capacidades`, porque la usan los ajustes, el escritorio, el resumen, la REST y la búsqueda:

- **Puras** (con prueba): `PREFIJO`, `es_clave()`, `clave( $tipo )`, `tipo( $clave )`,
  `icono_menu( $menu_icon )` y `desambiguar( array $nombres )`.
- **Con WordPress**: `detectados()` (clave → `WP_Post_Type`, ordenados por nombre), `objeto( $clave )`,
  `nombre( $clave )`, `icono( $clave )` y `opciones()` (clave → texto de la casilla).

**Qué se detecta.** `get_post_types( array( '_builtin' => false, 'show_ui' => true ), 'objects' )`,
quitando los que tienen `show_in_menu` falso y los de `TiposContenido::EXCLUIDOS`. Con
`show_in_menu` se quedan fuera tipos internos con pantalla pero sin entrada en el menú, como los
grupos de campos de ACF o la biblioteca de Elementor. `EXCLUIDOS` es la constante que hoy es
privada en `FuenteContenidos` y pasa aquí; `FuenteContenidos` la usa desde `TiposContenido`, sin
cambiar qué busca. Así los productos y pedidos de WooCommerce no se ofrecen como tipo
personalizado.

**Filtro `escritoriowp_tipos_contenido`.** Recibe la lista de identificadores detectados. El
resultado se vuelve a filtrar: solo quedan tipos registrados con `show_ui`, que no sean entradas ni
páginas ni estén en `EXCLUIDOS`. Sirve para quitar un tipo o añadir uno que no está en el menú.
Activar un tipo nunca concede nada, así que el filtro no es una vía de acceso.

**Orden.** Por nombre (`labels->name`), sin distinguir mayúsculas ni tildes (`remove_accents()` y
`strnatcasecmp()`). Es el orden de las casillas, de las tarjetas y el orden por defecto de los paneles.

**Caché por petición.** `detectados()` guarda el resultado en una propiedad estática, pero solo si ya
ha terminado `init` (`did_action( 'wp_loaded' )`): durante `init` un plugin puede seguir registrando
tipos. Si alguien lo llama antes, no cachea, para no congelar una lista incompleta. Todos los
llamantes actuales corren después (`admin_init`, `rest_api_init`, `wp_dashboard_setup`).

**Nombre en las casillas.** `opciones()` usa `labels->name`. Si dos tipos detectados comparten
nombre, `desambiguar()` añade a los dos el identificador entre paréntesis: «Eventos (evento)» y
«Eventos (tribe_events)». El panel y la tarjeta usan el nombre tal cual.

**Icono.** `icono_menu()` devuelve el `menu_icon` del tipo si es una clase `dashicons-…`. Si no lo
es (una URL, un SVG en `data:`, `none`, vacío), devuelve `dashicons-admin-post`, el mismo que pone
WordPress a un tipo sin icono. `EWP.icono()` solo sabe pintar clases de Dashicons.

### 3. `Capacidades` entiende las claves `cpt-…`

Cada consulta empieza por `if ( TiposContenido::es_clave( $tipo ) )` y resuelve con el objeto del
tipo; si no es una clave de tipo personalizado, sigue como hoy:

| Consulta | Tipo personalizado |
|----------|--------------------|
| `existe()`, `disponible()` | `TiposContenido::objeto( $clave )` no es nulo |
| `puede_ver()` | `current_user_can( $objeto->cap->edit_posts )`, lo que exige `edit.php` |
| `puede_crear()` | `$objeto->cap->create_posts`, lo que exige `post-new.php` |
| `puede_publicar()` | `$objeto->cap->publish_posts` |
| `url_listado()` | `edit.php?post_type={tipo}` |
| `url_nuevo()` | `post-new.php?post_type={tipo}` |
| `etiqueta()` | `labels->name` |
| `etiqueta_nuevo()` | `labels->add_new_item` |
| `icono()` | `TiposContenido::icono()` |

Un tipo que no está en `detectados()` no existe para el plugin, y la ruta responde 404 como con
cualquier tipo desconocido, sin llegar a comprobar capacidades.

Nuevo `Capacidades::tipos_panel()`: `TIPOS_PANEL` seguido de las claves de `detectados()`.
`paneles_visibles()` lo recorre en lugar de la constante. Así `Escritorio` recibe también los tipos
personalizados que el usuario puede ver, y su filtro por `panel_activo()` decide cuáles se pintan.

Alternativa descartada: añadir los tipos detectados a `MAPA` en tiempo de ejecución. `MAPA` es una
constante con pruebas que fijan los siete tipos de la especificación, y las capacidades de un tipo
personalizado no son una cadena fija sino las de su objeto.

### 4. Mismas listas de casillas, con «ausente = desactivado» para los tipos personalizados

Los tipos personalizados se guardan en los mismos mapas `paneles_activos` e `indicadores_activos`,
con su clave `cpt-…`. No hay ajuste nuevo.

- `sanear_activos( $valor, $claves )` mantiene su firma y su contrato para las claves conocidas.
  Además, de lo recibido conserva las claves que cumplen `TiposContenido::es_clave()` y cuyo valor es
  verdadero, hasta `Ajustes::MAX_TIPOS_CONTENIDO` (100). Las desmarcadas no se guardan: un tipo
  personalizado ausente está desactivado, así que guardar `false` no añade nada. No comprueba si el
  tipo existe, como `sanear_menu_debajo_de()` no comprueba si existe el menú.
- `activo()` devuelve, para una clave que falta, `! TiposContenido::es_clave( $clave )`: verdadero
  para las propias, falso para los tipos personalizados.
- `sanear_formulario()` no cambia. Su `+=` con lo guardado ya conserva el `true` de un tipo que no se
  ha pintado porque no está registrado. Para uno pintado y desmarcado, el `0` enviado manda.
- `defectos()` no cambia: sin claves `cpt-…`, ningún tipo personalizado sale activo.

Por qué en los mismos grupos: es donde el administrador ya decide qué paneles e indicadores se ven, y
el escritorio y el resumen ya preguntan con `panel_activo()` e `indicador_activo()`. El resto del
recorrido (filtrar, no pintar zonas vacías, «Personalizar») funciona sin cambios.

Alternativas descartadas:

- **Un ajuste propio** (`paneles_contenido`, lista de identificadores activos). Sería otra fila en la
  pantalla, con un saneado y una conservación al guardar propios, y `Escritorio` y `Resumen`
  tendrían que mirar dos ajustes para la misma pregunta.
- **«Ausente = activo» también para los tipos personalizados.** Un plugin nuevo que registre un tipo
  lo sacaría en el escritorio de todo el sitio sin que nadie lo pidiera.

### 5. Opciones y descripciones de los dos campos

`opciones_paneles()` y `opciones_indicadores()` añaden `TiposContenido::opciones()` al final de las
propias. El marcado no cambia: `pintar_casillas()` ya pinta cualquier clave. La casilla de una clave
`cpt-…` sin valor guardado sale desmarcada: `pintar_casillas()` usa hoy `$valores[ $clave ] ?? true`,
así que el valor por defecto de cada opción pasa a salir de la misma regla que `activo()`.

Las descripciones de los dos campos ganan una frase según haya o no tipos detectados: «Al final
están los tipos de contenido personalizados de este sitio; salen desmarcados.» o «Este sitio no
tiene tipos de contenido personalizados; si un plugin añade alguno, aparecerá aquí, desmarcado.» Las
descripciones se calculan en `registrar_ajustes()`, que corre en `admin_init`, después de `init`.

### 6. Panel: mismas filas que las entradas, crear en la pantalla nativa

- `Recientes::obtener()` gana una rama para las claves `cpt-…` que llama a
  `contenidos( TiposContenido::tipo( $clave ), $limite )`. Esa función ya filtra por autor a quien no
  tiene `edit_others_posts` del tipo y ya usa `fila_contenido()`.
- `Escritorio::configuracion()` calcula `enModal` con `Creador::admite( $tipo ) &&
  Capacidades::puede_crear( $tipo )` en lugar de excluir los pedidos a mano. Pedidos y tipos
  personalizados quedan sin ventana por la misma razón: no tienen creador. El JS no cambia:
  `botonNuevo()` y el estado vacío ya pintan un enlace a `urlNuevo` cuando `enModal` es falso.
- La cabecera sigue recorriendo `TIPOS_PANEL`, así que no gana botones.

### 7. Los textos de estado vacío llegan con cada panel

Cada definición de `config.paneles` (y de `config.tipos`) añade `textoVacio` y `textoCrearPrimero`,
que salen de dos métodos nuevos de `Capacidades`:

- `texto_vacio( $tipo )`: para los propios, las mismas cadenas que hoy están en `Activos::textos()`
  («Todavía no hay entradas.»…); para un tipo personalizado, `labels->not_found`.
- `texto_crear_primero( $tipo )`: para los propios, las cadenas actuales («Crear la primera
  entrada»…); para un tipo personalizado, `labels->add_new_item`.

Las diez claves `panelVacio…` y `crearPrimero…` salen del catálogo, y `pintarFilas()` lee
`panel.textoVacio` y `panel.textoCrearPrimero`. Si `capitalizar()` se queda sin uso, se borra. Los
`msgid` no cambian, así que las traducciones existentes siguen valiendo.

Se usan las etiquetas del tipo porque son las que el usuario ve en el listado nativo y en la
pantalla de creación de ese tipo, ya traducidas por el plugin que lo registra.

Alternativa descartada: mantener el catálogo para los propios y añadir los textos solo a los tipos
personalizados. Serían dos caminos para el mismo dato, y la receta de §8 de `CLAUDE.md` tendría que
explicar los dos.

### 8. Tarjeta: nombre del tipo, publicados y borradores

`Resumen::claves_visibles()` añade, detrás de las de `INDICADORES` y en el orden de `detectados()`,
las claves `cpt-…` activas en «Indicadores» cuyo tipo puede ver el usuario. `Resumen::obtener()`
pinta al final una tarjeta por cada una con `wp_count_posts( $tipo )`, que WordPress cachea:

- **Nombre**: `labels->name` («Libros»). `Resumen::etiqueta()` lo devuelve para las claves `cpt-…`.
- **Valor**: los publicados.
- **Detalle**: «%s en borrador» si hay borradores, la misma cadena de la tarjeta de entradas.
- **Enlace, icono y tono**: el listado del tipo, el icono del tipo y `azul`, como entradas y páginas.

Alternativa descartada: «%s publicados» como nombre, siguiendo «Entradas publicadas». El nombre del
tipo es un sustantivo que no se conoce de antemano, y el participio no concuerda con todos
(«Recetas publicados»). Es lo que hace el widget «De un vistazo» de WordPress: número y nombre del
tipo.

El docblock de `INDICADORES` deja de decir que una tarjeta que no esté en la lista no se muestra:
pasa a decirlo solo de las propias.

### 9. Preferencias que aceptan cualquier clave `cpt-…` válida

`Preferencias::sanear()` acepta una clave si está en `$paneles_validos` o si cumple
`TiposContenido::es_clave()`, y deja como mucho `Preferencias::MAX_PANELES` (100) en cada lista. No
mira si el tipo existe: la preferencia sobre un panel cuyo plugin está desactivado se conserva para
cuando vuelva, igual que la de un panel desactivado en los ajustes. Sigue siendo pura.

## Risks / Trade-offs

- **[Un tipo sin `not_found` o sin `add_new_item` propios]** → WordPress los rellena con los de las
  entradas («No se han encontrado entradas.»), y el panel lo repite. Se acepta: es exactamente lo que
  muestran el listado nativo y la pantalla de creación de ese tipo, y lo corrige el plugin que lo
  registra.
- **[Claves de tipos que ya no existen se quedan en la opción]** → Un tipo activado cuyo plugin se
  borra para siempre deja su `cpt-…` en los dos mapas, sin casilla para desmarcarlo. No tiene efecto
  (el tipo no se detecta), ocupa unos bytes, está acotado por `MAX_TIPOS_CONTENIDO` y desaparece con
  la desinstalación. Limpiarlo exigiría distinguir un plugin desactivado de uno borrado.
- **[Muchos tipos activados]** → Cada panel es una petición a `recientes/{tipo}` y cada tarjeta, un
  `wp_count_posts()`. Lo decide el administrador casilla a casilla, y ambos son baratos.
- **[Un tipo con capacidades propias mal mapeadas]** → Se comprueba `cap->edit_posts` del tipo, la
  misma capacidad que exige su listado nativo. Si el plugin del tipo la configura mal, el escritorio
  falla igual que el listado nativo, ni más ni menos.
- **[Llamar a `detectados()` antes de `init`]** → Devolvería una lista incompleta. Por eso no se
  cachea antes de `init`. La única llamada temprana posible sería desde `Plugin::arrancar()`, que no
  la hace.
- **[El orden de un panel personalizado tras desactivarlo]** → El mismo riesgo aceptado en el cambio
  anterior: el JS guarda `orden_paneles` solo con los paneles que ve, así que un panel que vuelve
  queda el último para quien reordenó mientras no estaba.
- **[Volver a una versión anterior]** → Su `sanear_activos()` descarta las claves `cpt-…` al leer y
  su `Preferencias::sanear()` también. No hay paneles ni tarjetas de tipos personalizados y el
  siguiente guardado las borra. No hay nada más que deshacer.

## Migration Plan

Sin migración: un sitio que actualiza no tiene claves `cpt-…`, así que ningún tipo personalizado
sale activo y el escritorio es idéntico. `uninstall.php` ya borra la opción y el metadato de
preferencias enteros. Publicar como versión menor con `/nueva-version minor` cuando se decida.
