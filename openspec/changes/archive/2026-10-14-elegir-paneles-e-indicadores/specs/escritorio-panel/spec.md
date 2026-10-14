## ADDED Requirements

### Requirement: Paneles e indicadores activos en el sitio

El escritorio SHALL mostrar solo los indicadores marcados en el ajuste «Indicadores» y los paneles marcados en «Paneles», para todos los usuarios. Activar uno no SHALL conceder acceso: cada usuario sigue viendo solo lo que su capacidad le permite. Una zona sin nada que mostrar SHALL omitirse sin reservar espacio. Los botones de creación de la cabecera no SHALL depender de estos ajustes.

#### Scenario: Sitio actualizado sin los ajustes guardados

- **WHEN** se actualiza el plugin en un sitio cuya opción de ajustes no tiene todavía «Indicadores» ni «Paneles»
- **THEN** cada usuario ve en el escritorio exactamente los mismos indicadores y paneles que antes de actualizar

#### Scenario: Panel desactivado

- **WHEN** un administrador desmarca «Usuarios» en «Paneles» y guarda
- **THEN** ningún usuario ve el panel de usuarios en el escritorio, y quien puede crear usuarios sigue viendo el botón «Nuevo usuario» en la cabecera

#### Scenario: Indicador desactivado

- **WHEN** un administrador desmarca «Ingresos este mes» en «Indicadores» y guarda
- **THEN** ningún usuario ve la tarjeta de ingresos, y quien puede ver pedidos sigue viendo la tarjeta «Pedidos este mes»

#### Scenario: La capacidad sigue mandando

- **WHEN** el panel «Usuarios» y el indicador «Usuarios registrados» están activos y un usuario con rol Autor abre el Escritorio
- **THEN** no ve ni el panel ni la tarjeta de usuarios

#### Scenario: Ningún indicador activo

- **WHEN** todas las casillas de «Indicadores» están desmarcadas y un usuario abre el Escritorio
- **THEN** no aparece la fila de tarjetas ni su esqueleto de carga, el escritorio no pide el resumen al servidor y los paneles empiezan justo debajo de la cabecera

#### Scenario: Ningún panel activo

- **WHEN** todas las casillas de «Paneles» están desmarcadas y un usuario abre el Escritorio
- **THEN** no aparece la zona de paneles, y el menú «Personalizar» solo ofrece el tema y «Restablecer»

#### Scenario: Indicadores de WooCommerce desactivados

- **WHEN** «Productos publicados», «Pedidos este mes» e «Ingresos este mes» están desmarcados y un administrador de tienda abre el Escritorio
- **THEN** el servidor calcula el resumen sin consultar los pedidos del mes

#### Scenario: Resumen pedido directamente

- **WHEN** un usuario pide la ruta REST del resumen con «Ingresos este mes» desmarcado
- **THEN** la respuesta no incluye la tarjeta de ingresos

### Requirement: Elección de paneles e indicadores en los ajustes

La sección «Escritorio» de los ajustes SHALL ofrecer «Indicadores» y «Paneles» como dos grupos de casillas con nombre accesible, una casilla por tarjeta o panel, con su nombre en el escritorio y en su orden. Los de WooCommerce SHALL listarse solo con WooCommerce activo y conservar su valor mientras no lo está. El servidor SHALL descartar las claves desconocidas.

#### Scenario: Opciones sin WooCommerce

- **WHEN** un administrador abre EscritorioWP › Ajustes en un sitio sin WooCommerce y sin haber guardado nunca estos ajustes
- **THEN** «Indicadores» lista, todas marcadas, «Entradas publicadas», «Páginas publicadas», «Usuarios registrados», «Comentarios pendientes» y «Actualizaciones pendientes», y «Paneles» lista, todas marcadas, «Entradas», «Páginas» y «Usuarios»

#### Scenario: Opciones con WooCommerce

- **WHEN** un administrador abre EscritorioWP › Ajustes con WooCommerce activo
- **THEN** «Indicadores» incluye además «Productos publicados», «Pedidos este mes» e «Ingresos este mes» después de «Usuarios registrados», y «Paneles» incluye además «Productos» y «Pedidos» al final

#### Scenario: Valor de WooCommerce conservado

- **WHEN** el panel «Pedidos» está desmarcado, se desactiva WooCommerce, un administrador guarda los ajustes y después se reactiva WooCommerce
- **THEN** el panel «Pedidos» sigue desmarcado en los ajustes y no se muestra en el escritorio

#### Scenario: Valor manipulado

- **WHEN** se envía el formulario con una clave que no es un panel, como `inventado`, junto a «Páginas» desmarcado
- **THEN** la clave desconocida no se guarda, «Páginas» se guarda desmarcado y no se muestra ningún error

#### Scenario: Lector de pantalla

- **WHEN** un usuario de lector de pantalla llega a la primera casilla del grupo «Paneles»
- **THEN** el lector anuncia el nombre del grupo, «Paneles», y el nombre del panel de esa casilla

## MODIFIED Requirements

### Requirement: Indicadores de resumen (KPIs)

La interfaz SHALL mostrar tarjetas de resumen con: entradas publicadas y borradores, páginas publicadas, usuarios totales, comentarios pendientes de moderación, actualizaciones pendientes (núcleo, plugins y temas) y, con WooCommerce activo, productos publicados, pedidos del mes actual e ingresos del mes actual en la moneda de la tienda. Cada tarjeta SHALL mostrarse solo si está activa en el ajuste «Indicadores» y el usuario tiene capacidad para ver la pantalla correspondiente, y SHALL enlazar a ella.

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

La interfaz SHALL mostrar un panel por cada tipo (entradas, páginas, usuarios, productos y pedidos) activo en el ajuste «Paneles», con los N elementos más recientes (N = ajuste "elementos por panel"), ordenados del más reciente al más antiguo, con enlace "Ver todos" al listado nativo y botón "Nuevo". Cada fila SHALL enlazar a la pantalla de edición del elemento y mostrar la información propia de su tipo:

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

### Requirement: Personalización por usuario

Cada usuario SHALL poder ocultar y volver a mostrar paneles, cambiar el orden de los paneles y elegir tema claro, oscuro o automático (según el sistema); estas preferencias SHALL persistir en el servidor asociadas al usuario y aplicarse en cualquier navegador, con una acción "Restablecer" que recupera los valores por defecto. El menú "Personalizar" SHALL ofrecer solo los paneles activos en el sitio, y la preferencia de un usuario sobre un panel desactivado en el sitio SHALL conservarse para cuando se reactive.

#### Scenario: Ocultar un panel

- **WHEN** el usuario oculta el panel de páginas y recarga el Escritorio desde otro navegador
- **THEN** el panel de páginas sigue oculto y aparece en el menú "Personalizar" para reactivarlo

#### Scenario: Tema automático

- **WHEN** la preferencia de tema es "automático" y el sistema del usuario está en modo oscuro
- **THEN** la interfaz se muestra en tema oscuro

#### Scenario: Panel desactivado en el sitio

- **WHEN** un administrador desmarca «Páginas» en el ajuste «Paneles»
- **THEN** el menú "Personalizar" de cada usuario deja de listar «Páginas»

#### Scenario: Preferencia conservada al reactivar

- **WHEN** un usuario había ocultado el panel de páginas, y un administrador desmarca «Páginas» en «Paneles», guarda, lo vuelve a marcar y guarda
- **THEN** el panel de páginas sigue oculto para ese usuario y aparece en su menú "Personalizar" para reactivarlo
