## Purpose

Ayudar a detectar puertas traseras en el sitio: mostrar quién es administrador y señalar a los usuarios que, sin tener ese rol, tienen en la base de datos permisos que dan control del sitio, para poder revisarlos y retirarlos.

## ADDED Requirements

### Requirement: Acceso a la pantalla Administradores

La pantalla EscritorioWP › Administradores SHALL estar disponible solo para usuarios con `manage_options` y `list_users`. Cualquier otro usuario SHALL recibir la denegación de acceso de WordPress, también si llega por la URL directa `admin.php?page=escritoriowp-administradores`.

#### Scenario: Administrador

- **WHEN** un administrador abre EscritorioWP › Administradores
- **THEN** ve la pantalla con el estado general, la lista de administradores y, si los hay, los usuarios marcados

#### Scenario: Editor

- **WHEN** un editor accede directamente a `admin.php?page=escritoriowp-administradores`
- **THEN** WordPress deniega el acceso y no se muestra ningún dato de usuarios

#### Scenario: Gestor de tienda

- **WHEN** un gestor de tienda de WooCommerce, que tiene `list_users` pero no `manage_options`, accede a la URL directa
- **THEN** WordPress deniega el acceso

### Requirement: Detección sobre los datos guardados

La detección SHALL partir de los permisos guardados en la base de datos para cada usuario existente en el sitio actual y de las definiciones de roles vigentes, y SHALL NOT depender de consultas de listado que el código de terceros pueda filtrar. Los permisos concedidos solo en tiempo de ejecución por código quedan fuera.

#### Scenario: Usuario escondido por código

- **WHEN** un plugin filtra las consultas de usuarios para que un usuario con `manage_options` no aparezca en ningún listado
- **THEN** la pantalla Administradores lo muestra igualmente

#### Scenario: Permisos huérfanos

- **WHEN** la base de datos contiene permisos guardados para un identificador de usuario que ya no existe
- **THEN** la pantalla no muestra ese identificador ni produce errores o avisos de PHP

#### Scenario: Permiso concedido por un filtro

- **WHEN** un plugin concede `manage_options` a un suscriptor solo mediante el filtro `user_has_cap`, sin guardarlo en la base de datos
- **THEN** ese usuario no se marca

### Requirement: Cálculo de roles y permisos como WordPress

La detección SHALL calcular los roles y permisos de cada usuario con las mismas reglas que WordPress: es rol cada clave de sus permisos guardados que coincide con un rol existente, sea cual sea su valor; los permisos de sus roles se combinan; los permisos guardados en el propio usuario prevalecen sobre los del rol; y un permiso solo cuenta si su valor no está vacío.

#### Scenario: Rol con valor falso

- **WHEN** los permisos guardados de un usuario son `{"administrator": false}`
- **THEN** el usuario aparece en la lista de administradores, porque WordPress lo trata como administrador

#### Scenario: Denegación explícita

- **WHEN** un usuario tiene un rol que incluye `manage_options` y en sus permisos guardados figura `manage_options` con valor falso, sin ningún otro permiso crítico
- **THEN** el usuario no se marca por `manage_options`

#### Scenario: Valor no booleano

- **WHEN** un suscriptor tiene guardado `edit_users` con el valor `"1"`
- **THEN** el usuario se marca por `edit_users`

#### Scenario: Clave de un rol inexistente

- **WHEN** los permisos guardados de un usuario incluyen la clave de un rol que no existe en el sitio
- **THEN** esa clave se trata como un permiso suelto y no como un rol, sin errores ni avisos de PHP

### Requirement: Permisos críticos

Un permiso SHALL considerarse crítico solo si está en una lista cerrada: los que en una instalación estándar de WordPress solo tiene el administrador y dan control del sitio o de la red, excluidos los que WooCommerce concede al gestor de tienda. La lista SHALL ser la misma en todas las instalaciones y no SHALL poder ampliarse ni reducirse desde fuera del plugin.

#### Scenario: Lista completa

- **WHEN** se consulta qué permisos son críticos
- **THEN** son exactamente `manage_options`, `edit_users`, `create_users`, `delete_users`, `remove_users`, `promote_users`, `install_plugins`, `activate_plugins`, `edit_plugins`, `update_plugins`, `delete_plugins`, `install_themes`, `switch_themes`, `edit_themes`, `update_themes`, `delete_themes`, `edit_files`, `update_core`, `unfiltered_upload`, `level_10`, `manage_network`, `manage_sites`, `manage_network_users`, `manage_network_plugins`, `manage_network_themes`, `manage_network_options`, `upgrade_network`, `setup_network`, `create_sites` y `delete_sites`

#### Scenario: Gestor de tienda

- **WHEN** un usuario tiene el rol «Gestor de tienda» de WooCommerce sin modificar, con `import`, `export`, `list_users` y `edit_theme_options`
- **THEN** el usuario no se marca

#### Scenario: Editor

- **WHEN** un usuario tiene el rol «Editor» sin modificar, que incluye `unfiltered_html`
- **THEN** el usuario no se marca

### Requirement: Lista de administradores

La pantalla SHALL listar a todos los usuarios con el rol Administrador con su nombre visible, nombre de usuario, correo, fecha de alta y un enlace a su ficha de usuario. En multisitio SHALL listar también a los superadministradores de la red, con la etiqueta «Superadministrador». La pantalla SHALL NOT listar a los usuarios que no son administradores ni están marcados.

#### Scenario: Dos administradores

- **WHEN** el sitio tiene dos administradores, un editor y cien clientes
- **THEN** la lista de administradores muestra exactamente esos dos usuarios con sus datos, y el editor y los clientes no aparecen en la pantalla

#### Scenario: Superadministrador en multisitio

- **WHEN** en un multisitio un superadministrador no tiene rol en el sitio actual
- **THEN** aparece en la lista con la etiqueta «Superadministrador»

### Requirement: Usuarios con permisos de administrador sin el rol

La pantalla SHALL mostrar, en una sección propia y con una etiqueta roja «Permisos de administrador», a cada usuario que no tiene el rol Administrador ni es superadministrador y tiene al menos un permiso crítico. Para cada uno SHALL indicar qué permisos críticos tiene y su origen: «Asignado al usuario» o «Del rol «Nombre del rol»».

#### Scenario: Permiso suelto

- **WHEN** un suscriptor tiene guardado `manage_options` con valor verdadero en sus propios permisos
- **THEN** aparece en la sección con la etiqueta roja, su rol «Suscriptor» y `manage_options` con origen «Asignado al usuario»

#### Scenario: Rol alterado

- **WHEN** el rol «Suscriptor» incluye `install_plugins` y un usuario tiene ese rol sin permisos sueltos
- **THEN** aparece en la sección con `install_plugins` con origen «Del rol «Suscriptor»»

#### Scenario: Administrador con permisos sueltos

- **WHEN** un administrador tiene además permisos críticos sueltos
- **THEN** aparece solo en la lista de administradores, sin la etiqueta roja

### Requirement: Orden y límite de los usuarios marcados

La sección de usuarios con permisos de administrador sin el rol SHALL mostrar primero a los que tienen permisos críticos asignados al usuario y, dentro de cada grupo, a los dados de alta más recientemente. SHALL mostrar como mucho 100 usuarios; si hay más, SHALL indicar cuántos quedan sin mostrar y remitir a la sección de roles alterados.

#### Scenario: Orden

- **WHEN** hay un suscriptor con `manage_options` suelto dado de alta en 2024 y dos usuarios marcados solo por su rol, dados de alta en 2025 y 2026
- **THEN** la sección muestra primero al suscriptor de 2024 y después a los de 2026 y 2025, en ese orden

#### Scenario: Rol alterado con muchos usuarios

- **WHEN** el rol «Suscriptor» incluye `manage_options` y lo tienen 5.000 usuarios
- **THEN** la sección muestra 100 usuarios, indica que hay 4.900 más, y el estado general y el contador cuentan los 5.000

### Requirement: Roles alterados

Cuando algún rol distinto de Administrador incluye permisos críticos, la pantalla SHALL mostrar una sección con cada uno de esos roles: su nombre, su identificador, los permisos críticos que incluye y cuántos usuarios lo tienen. Sin roles alterados, la sección SHALL NOT mostrarse.

#### Scenario: Suscriptor alterado

- **WHEN** el rol «Suscriptor» incluye `manage_options` y lo tienen 340 usuarios
- **THEN** la sección muestra «Suscriptor», `subscriber`, `manage_options` y 340 usuarios

#### Scenario: Rol que imita otro nombre

- **WHEN** existe un rol con nombre visible «Suscriptor», identificador `subscriber_2` y todos los permisos críticos
- **THEN** la sección lo muestra con su identificador `subscriber_2`, distinguible del rol `subscriber` original

### Requirement: Usuarios ocultos en la pantalla Usuarios

Cualquier usuario que se muestra en la pantalla Administradores y que la consulta de usuarios de WordPress no devuelve, porque algún código lo oculta, SHALL llevar la etiqueta roja «Oculto en Usuarios», tanto si es administrador como si está marcado por sus permisos. Los superadministradores que no son miembros del sitio actual SHALL comprobarse contra el listado de usuarios de la red.

#### Scenario: Administrador oculto

- **WHEN** un plugin hace que un administrador no aparezca en la pantalla Usuarios de WordPress
- **THEN** la pantalla Administradores lo muestra en la lista de administradores con la etiqueta «Oculto en Usuarios»

#### Scenario: Nada oculto

- **WHEN** ningún código filtra las consultas de usuarios
- **THEN** ningún usuario lleva la etiqueta «Oculto en Usuarios»

### Requirement: Estado general

La pantalla SHALL empezar con un resumen: en rojo, cuántos usuarios están marcados (por tener permisos de administrador sin el rol o por estar ocultos en Usuarios) y qué significa; o, si no hay ninguno, un mensaje en verde que lo confirma. Sin usuarios marcados, la sección de usuarios con permisos de administrador sin el rol SHALL indicar que está vacía.

#### Scenario: Con usuarios marcados

- **WHEN** hay dos usuarios marcados
- **THEN** el resumen en rojo dice que hay 2 usuarios que requieren revisión y explica que pueden ser una puerta trasera

#### Scenario: Sin usuarios marcados

- **WHEN** no hay ningún usuario marcado
- **THEN** el resumen en verde dice que no hay usuarios con permisos de administrador fuera del rol Administrador ni administradores ocultos

### Requirement: Contador en el menú

Cuando hay usuarios marcados, la entrada «Administradores» y la entrada de primer nivel «EscritorioWP» SHALL mostrar su número en un contador con el aspecto del de actualizaciones de WordPress y un texto alternativo para lectores de pantalla. Sin usuarios marcados, o para usuarios que no pueden ver la pantalla, no SHALL mostrarse contador.

#### Scenario: Dos marcados

- **WHEN** hay dos usuarios marcados y un administrador carga cualquier pantalla del administrador
- **THEN** «EscritorioWP» y «Administradores» muestran un contador con el 2, y un lector de pantalla anuncia «2 usuarios que requieren revisión»

#### Scenario: Ninguno marcado

- **WHEN** no hay usuarios marcados
- **THEN** ninguna de las dos entradas muestra contador

#### Scenario: Lanzador

- **WHEN** hay usuarios marcados y un administrador escribe «administradores» en el lanzador
- **THEN** aparece la pantalla Administradores con su nombre, sin el número del contador

### Requirement: Actualidad del contador

El contador SHALL reflejar en la siguiente carga cualquier cambio de roles o permisos hecho con las funciones de WordPress y, como mucho a los 15 minutos, los cambios escritos directamente en la base de datos. Abrir la pantalla Administradores SHALL actualizar el contador en el momento.

#### Scenario: Cambio desde WordPress

- **WHEN** un administrador cambia a un usuario marcado al rol Administrador desde su ficha
- **THEN** en la siguiente carga el contador ha bajado en uno

#### Scenario: Cambio directo en la base de datos

- **WHEN** se escribe `manage_options` directamente en los permisos guardados de un suscriptor
- **THEN** el contador lo incluye como mucho 15 minutos después, o en cuanto alguien abre la pantalla Administradores

### Requirement: Retirar permisos sueltos

Cada usuario marcado con permisos críticos asignados al usuario SHALL tener un botón «Retirar permisos». El botón SHALL llevar a una confirmación que lista exactamente los permisos que se quitarán; al confirmar, SHALL quitar solo los permisos críticos sueltos de ese usuario, sin tocar sus roles ni el resto de sus permisos, y volver a la pantalla con un mensaje del resultado.

#### Scenario: Confirmar

- **WHEN** un administrador pulsa «Retirar permisos» en un suscriptor con `manage_options` y `edit_users` sueltos y confirma
- **THEN** el usuario conserva el rol «Suscriptor» y sus demás permisos, ya no tiene `manage_options` ni `edit_users`, deja de aparecer marcado y la pantalla muestra que se han retirado 2 permisos

#### Scenario: Cancelar

- **WHEN** un administrador pulsa «Retirar permisos» y en la confirmación elige «Cancelar»
- **THEN** vuelve a la pantalla y el usuario conserva todos sus permisos

#### Scenario: Solo por el rol

- **WHEN** un usuario está marcado únicamente por permisos de su rol
- **THEN** no tiene botón «Retirar permisos» y la pantalla explica que el permiso viene del rol, que el plugin no modifica

#### Scenario: Permisos sueltos y de rol

- **WHEN** un usuario tiene `manage_options` suelto y `install_plugins` de su rol alterado, y se confirma la retirada
- **THEN** se quita `manage_options`, `install_plugins` se mantiene y el usuario sigue marcado por su rol

### Requirement: Seguridad de la retirada de permisos

Retirar permisos SHALL exigir una petición POST con un nonce válido ligado a ese usuario, que quien la hace tenga `manage_options`, `list_users` y permiso para cambiar el rol de ese usuario, y que el usuario no sea quien la hace, ni administrador, ni superadministrador. Si falla cualquier condición, no SHALL cambiar nada.

#### Scenario: Uno mismo

- **WHEN** un usuario sin el rol Administrador pero con `manage_options` y `list_users` sueltos abre la pantalla
- **THEN** aparece marcado sin botón «Retirar permisos», y una petición directa para retirarse sus propios permisos se rechaza sin cambios

#### Scenario: Nonce inválido

- **WHEN** llega una petición de retirada con un nonce caducado o generado para otro usuario
- **THEN** WordPress la rechaza y los permisos no cambian

#### Scenario: Petición GET

- **WHEN** se abre por GET la URL de la acción de retirada con parámetros válidos
- **THEN** no cambia ningún permiso

#### Scenario: Usuario ya corregido

- **WHEN** se confirma la retirada de un usuario cuyos permisos sueltos ya se habían quitado en otra pestaña
- **THEN** no se modifica nada y la pantalla indica que no había permisos que retirar
