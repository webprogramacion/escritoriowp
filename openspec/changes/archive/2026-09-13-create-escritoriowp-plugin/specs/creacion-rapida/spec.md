## Purpose

Definir la creación de entradas, páginas, usuarios y productos desde el escritorio de EscritorioWP mediante un modal, sin abandonar la pantalla, con validación y control de acceso en el servidor.

## ADDED Requirements

### Requirement: Modal de creación rápida

Al pulsar un botón "Nuevo/Nueva" del escritorio, la interfaz SHALL abrir un modal con el formulario mínimo del tipo elegido, que se cierra con Escape o con el botón de cerrar y devuelve el foco al botón que lo abrió. Los campos por tipo son:

- Entrada: título (obligatorio), contenido (opcional), categoría (opcional), estado (borrador o publicada).
- Página: título (obligatorio), estado (borrador o publicada).
- Usuario: nombre de usuario (obligatorio), email (obligatorio), nombre y apellidos (opcionales), rol (limitado a los roles que el usuario actual puede asignar), envío de email de bienvenida (activado por defecto).
- Producto: nombre (obligatorio), precio normal (opcional), SKU (opcional), estado (borrador o publicado).

#### Scenario: Crear una entrada como borrador

- **WHEN** el usuario introduce el título "Novedades de otoño" y pulsa "Guardar borrador"
- **THEN** se crea una entrada en estado borrador con ese título, el modal muestra confirmación con acciones "Editar", "Crear otra" y "Cerrar", y el panel de entradas se actualiza mostrando la nueva entrada en primer lugar

#### Scenario: Crear una página publicada

- **WHEN** un usuario con `publish_pages` elige estado "Publicada" y guarda
- **THEN** la página queda publicada y la confirmación ofrece también la acción "Ver"

#### Scenario: Crear un usuario

- **WHEN** un administrador introduce nombre de usuario, email válido y rol "Editor" y guarda con el envío de email activado
- **THEN** se crea el usuario con una contraseña generada, se le envía el email de bienvenida con enlace para establecer contraseña, y el panel de usuarios se actualiza

#### Scenario: Crear un producto

- **WHEN** un usuario con `edit_products` introduce nombre "Camiseta" y precio 19,90 y guarda como borrador
- **THEN** se crea un producto simple en borrador con ese precio, y la acción "Editar" abre la ficha del producto

### Requirement: Validación en el servidor

Toda creación SHALL validarse en el servidor: campos obligatorios presentes, email válido y no registrado, nombre de usuario válido y no registrado, precio numérico no negativo, SKU no duplicado, estado permitido. Los errores SHALL devolverse por campo y mostrarse junto al campo correspondiente sin cerrar el modal.

#### Scenario: Email duplicado

- **WHEN** se intenta crear un usuario con un email ya registrado
- **THEN** la respuesta es un error 400 y el modal muestra "Ya existe un usuario con este email" junto al campo email, conservando el resto de valores introducidos

#### Scenario: Título vacío

- **WHEN** se envía el formulario de entrada con el título en blanco
- **THEN** no se crea nada y el campo título muestra un error

### Requirement: Control de capacidades en la creación

El servidor SHALL rechazar con 403 cualquier creación para la que el usuario no tenga capacidad: `edit_posts` para borradores de entradas y `publish_posts` para publicarlas; `edit_pages`/`publish_pages` para páginas; `create_users` para usuarios; `edit_products`/`publish_products` para productos. El rol asignable a un usuario nuevo SHALL estar entre los roles editables por el usuario actual.

#### Scenario: Colaborador intenta publicar directamente

- **WHEN** un Colaborador, cuyo rol no permite `publish_posts`, intenta crear una entrada con estado "publicada"
- **THEN** la respuesta es 403 y la interfaz explica que solo puede guardar borradores

#### Scenario: Asignación de rol no permitida

- **WHEN** un usuario intenta crear un usuario con un rol que no está entre sus roles editables
- **THEN** la respuesta es 403 y no se crea el usuario

#### Scenario: Botones ocultos sin capacidad

- **WHEN** el usuario carece de `create_users`
- **THEN** ni la cabecera ni el panel de usuarios muestran el botón "Nuevo usuario"

### Requirement: Nuevo pedido

El botón "Nuevo pedido" SHALL enlazar a la pantalla nativa de WooCommerce para crear pedidos, correcta tanto con HPOS como con almacenamiento clásico, y SHALL mostrarse solo a usuarios con `edit_shop_orders`.

#### Scenario: HPOS activo

- **WHEN** WooCommerce usa HPOS y el usuario pulsa "Nuevo pedido"
- **THEN** se abre la pantalla nativa de nuevo pedido de WooCommerce sin error
