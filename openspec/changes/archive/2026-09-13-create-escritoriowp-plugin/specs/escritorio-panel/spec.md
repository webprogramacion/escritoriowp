## Purpose

Definir la pantalla de escritorio que sustituye al escritorio nativo de WordPress: cabecera, indicadores, paneles de últimos elementos, personalización por usuario y control de acceso por capacidades.

## ADDED Requirements

### Requirement: Sustitución del escritorio nativo

Cuando el ajuste "Sustituir el escritorio nativo" esté activo, la pantalla Escritorio (`wp-admin/index.php`) SHALL mostrar la interfaz de EscritorioWP ocupando el ancho completo, ocultando los widgets nativos de WordPress y el panel de bienvenida, y manteniendo visibles los avisos de administración (`admin_notices`) de WordPress y de otros plugins.

#### Scenario: Escritorio sustituido

- **WHEN** un usuario con el ajuste activo abre el Escritorio
- **THEN** ve la interfaz de EscritorioWP y no ve los widgets "De un vistazo", "Actividad", "Borrador rápido", "Eventos y noticias" ni el panel de bienvenida

#### Scenario: Avisos de administración visibles

- **WHEN** otro plugin emite un aviso de administración en el Escritorio
- **THEN** el aviso se muestra encima de la interfaz de EscritorioWP

#### Scenario: Widgets de otros plugins ocultos por defecto

- **WHEN** otro plugin registra un widget de escritorio y el ajuste "Ocultar widgets de otros plugins" está activo
- **THEN** el widget no se muestra

#### Scenario: Widgets de otros plugins conservados

- **WHEN** el ajuste "Ocultar widgets de otros plugins" está desactivado
- **THEN** los widgets de otros plugins se muestran en una sección "Otros widgets" debajo de la interfaz de EscritorioWP

#### Scenario: Sustitución desactivada

- **WHEN** el ajuste "Sustituir el escritorio nativo" está desactivado
- **THEN** el Escritorio nativo se muestra exactamente como sin el plugin

### Requirement: Cabecera con saludo y accesos rápidos

La interfaz SHALL mostrar una cabecera con saludo personalizado (nombre visible del usuario), la fecha actual en el idioma y zona horaria del sitio, botones de creación rápida para cada tipo que el usuario pueda crear y una pista del atajo del lanzador adaptada al sistema operativo (⌘K en macOS, Ctrl+K en el resto).

#### Scenario: Cabecera para un administrador de tienda

- **WHEN** un administrador con WooCommerce activo abre el Escritorio en macOS
- **THEN** ve su nombre, la fecha, botones "Nueva entrada", "Nueva página", "Nuevo usuario", "Nuevo producto" y "Nuevo pedido", y la pista "⌘K"

#### Scenario: Cabecera para un editor

- **WHEN** un usuario con rol Editor (sin `create_users`) abre el Escritorio
- **THEN** no ve el botón "Nuevo usuario"

### Requirement: Indicadores de resumen (KPIs)

La interfaz SHALL mostrar tarjetas de resumen con: entradas publicadas y borradores, páginas publicadas, usuarios totales, comentarios pendientes de moderación, actualizaciones pendientes (núcleo, plugins y temas) y, con WooCommerce activo, productos publicados, pedidos del mes actual e ingresos del mes actual en la moneda de la tienda. Cada tarjeta SHALL mostrarse solo si el usuario tiene capacidad para ver la pantalla correspondiente y SHALL enlazar a ella.

#### Scenario: Ingresos del mes

- **WHEN** en el mes actual hay pedidos en estado "completado" o "procesando" por un total de 1.250,00 € y otros pedidos cancelados o reembolsados
- **THEN** la tarjeta de ingresos muestra 1.250,00 € con el formato de moneda de la tienda

#### Scenario: Usuario sin permiso sobre usuarios

- **WHEN** el usuario carece de la capacidad `list_users`
- **THEN** no ve la tarjeta de usuarios totales

#### Scenario: Caché de los indicadores costosos

- **WHEN** se recarga el Escritorio menos de cinco minutos después de la carga anterior
- **THEN** los indicadores de pedidos e ingresos pueden servirse desde caché, y el botón "Actualizar" de la interfaz fuerza su recálculo

### Requirement: Paneles de últimos elementos

La interfaz SHALL mostrar un panel por cada tipo (entradas, páginas, usuarios, productos y pedidos) con los N elementos más recientes (N = ajuste "elementos por panel"), ordenados del más reciente al más antiguo, con enlace "Ver todos" al listado nativo y botón "Nuevo". Cada fila SHALL enlazar a la pantalla de edición del elemento y mostrar la información propia de su tipo:

- Entradas y páginas: título (o "(sin título)"), estado, autor y fecha de modificación.
- Usuarios: avatar, nombre visible, email, rol y fecha de registro.
- Productos: imagen, nombre, precio, estado de stock y estado de publicación.
- Pedidos: número, cliente, total, estado con color distintivo y fecha.

#### Scenario: Panel de entradas con borradores

- **WHEN** existen 3 entradas publicadas y 2 borradores y N = 5
- **THEN** el panel muestra las 5, con la etiqueta de estado "Borrador" en las dos correspondientes, ordenadas por fecha de modificación descendente

#### Scenario: Acción ver en el sitio

- **WHEN** una entrada listada está publicada
- **THEN** su fila ofrece una acción secundaria "Ver" que abre la entrada en el sitio en una pestaña nueva

#### Scenario: Panel vacío

- **WHEN** no existe ningún producto
- **THEN** el panel de productos muestra un estado vacío con texto explicativo y el botón "Crear el primer producto"

#### Scenario: Error de carga de un panel

- **WHEN** la petición de datos de un panel falla
- **THEN** ese panel muestra un mensaje de error con botón "Reintentar" y el resto de la interfaz sigue operativa

### Requirement: Control de acceso por capacidades

La interfaz SHALL mostrar cada panel e indicador únicamente a usuarios con la capacidad que WordPress o WooCommerce exigen para la pantalla nativa equivalente: `edit_posts` (entradas), `edit_pages` (páginas), `list_users` (usuarios), `edit_products` (productos), `edit_shop_orders` (pedidos), `moderate_comments` (comentarios), `update_core` o `update_plugins` (actualizaciones). Las rutas REST que alimentan cada panel SHALL aplicar la misma comprobación en el servidor.

#### Scenario: Autor

- **WHEN** un usuario con rol Autor abre el Escritorio
- **THEN** ve el panel de entradas y no ve los paneles de páginas, usuarios, productos ni pedidos

#### Scenario: Petición REST directa sin capacidad

- **WHEN** un Autor llama directamente a la ruta REST de últimos usuarios
- **THEN** recibe un error 403 sin datos

### Requirement: Personalización por usuario

Cada usuario SHALL poder ocultar y volver a mostrar paneles, cambiar el orden de los paneles y elegir tema claro, oscuro o automático (según el sistema); estas preferencias SHALL persistir en el servidor asociadas al usuario y aplicarse en cualquier navegador, con una acción "Restablecer" que recupera los valores por defecto.

#### Scenario: Ocultar un panel

- **WHEN** el usuario oculta el panel de páginas y recarga el Escritorio desde otro navegador
- **THEN** el panel de páginas sigue oculto y aparece en el menú "Personalizar" para reactivarlo

#### Scenario: Tema automático

- **WHEN** la preferencia de tema es "automático" y el sistema del usuario está en modo oscuro
- **THEN** la interfaz se muestra en tema oscuro

### Requirement: Diseño adaptable y accesible

La interfaz SHALL ser usable desde 360 px de ancho (paneles apilados en una columna) hasta pantallas anchas (rejilla de varias columnas), y SHALL ser operable con teclado, con regiones y controles etiquetados para lectores de pantalla y contraste suficiente en ambos temas.

#### Scenario: Pantalla estrecha

- **WHEN** la ventana tiene 400 px de ancho
- **THEN** no aparece desplazamiento horizontal y los paneles se apilan en una columna

#### Scenario: Navegación por teclado

- **WHEN** el usuario recorre la interfaz con Tab
- **THEN** todos los botones y enlaces reciben foco visible en un orden lógico
