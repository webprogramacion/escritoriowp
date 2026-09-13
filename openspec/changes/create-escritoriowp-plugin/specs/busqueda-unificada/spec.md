## Purpose

Definir la búsqueda unificada del servidor que alimenta el lanzador: qué fuentes consulta, con qué reglas de coincidencia, orden y límites, y cómo aplica el control de acceso por capacidades.

## ADDED Requirements

### Requirement: Ruta REST de búsqueda

El plugin SHALL exponer una ruta REST de búsqueda para usuarios autenticados que acepta el texto (`q`, obligatorio, entre 2 y 100 caracteres tras recortar espacios), una lista opcional de tipos a consultar y un límite por grupo (por defecto 5, máximo 20), y SHALL devolver los resultados agrupados por tipo, cada grupo con su total de coincidencias y la URL del listado nativo filtrado por el texto.

#### Scenario: Respuesta agrupada

- **WHEN** un administrador busca "camiseta" con WooCommerce activo
- **THEN** la respuesta contiene grupos para entradas, páginas, otros contenidos, usuarios, productos y pedidos, cada uno con sus elementos (hasta el límite), su total y la URL del listado nativo con la búsqueda aplicada

#### Scenario: Texto demasiado corto

- **WHEN** se busca "a"
- **THEN** la respuesta es un error 400 sin resultados

#### Scenario: Sin sesión

- **WHEN** se llama a la ruta sin usuario autenticado
- **THEN** la respuesta es 401 sin resultados

### Requirement: Formato de cada resultado

Cada resultado SHALL incluir: identificador, tipo, título, subtítulo, URL de edición, URL de vista en el sitio (si procede), icono y fecha relevante; todos los textos SHALL devolverse ya escapados para HTML o como texto plano seguro.

#### Scenario: Entrada en borrador

- **WHEN** un borrador titulado "Novedades" coincide
- **THEN** su resultado tiene título "Novedades", subtítulo con estado "Borrador" y autor, URL de edición y sin URL de vista pública

### Requirement: Búsqueda de entradas, páginas y otros contenidos

La búsqueda SHALL localizar entradas, páginas y cualquier otro tipo de contenido con interfaz de administración (excluyendo adjuntos, revisiones, menús y tipos internos de WooCommerce) cuyo título contenga el texto, en cualquier estado excepto papelera y borradores automáticos, limitado a los tipos que el usuario puede editar y, para contenidos ajenos privados o en borrador, solo si puede editarlos. El orden SHALL priorizar coincidencias al inicio del título y, a igualdad, la fecha de modificación más reciente.

#### Scenario: Coincidencia solo por título

- **WHEN** una entrada contiene "presupuesto" en el cuerpo pero no en el título
- **THEN** no aparece en los resultados

#### Scenario: Tipo de contenido personalizado

- **WHEN** existe un tipo "Pruebas" con interfaz de administración y una prueba titulada "Maratón 2026"
- **THEN** buscar "marat" la devuelve en el grupo "Otros contenidos" con subtítulo "Pruebas"

#### Scenario: Contribuidor y borradores ajenos

- **WHEN** un Colaborador busca un texto que coincide con un borrador de otro autor
- **THEN** ese borrador no aparece

#### Scenario: Orden de resultados

- **WHEN** existen "Contacto" y "Formulario de contacto" y se busca "cont"
- **THEN** "Contacto" aparece antes que "Formulario de contacto"

### Requirement: Búsqueda de usuarios

La búsqueda SHALL devolver usuarios cuyo nombre visible, nombre de usuario, alias o email contenga el texto, únicamente si el usuario actual tiene la capacidad `list_users`, con subtítulo compuesto por email y rol.

#### Scenario: Búsqueda por fragmento de email

- **WHEN** un administrador busca "@empresa.com"
- **THEN** aparecen todos los usuarios con email en ese dominio

#### Scenario: Sin capacidad

- **WHEN** un Editor sin `list_users` busca un nombre de usuario
- **THEN** el grupo de usuarios no aparece en la respuesta

### Requirement: Búsqueda de productos

Con WooCommerce activo y para usuarios con `edit_products`, la búsqueda SHALL devolver productos cuyo nombre, extracto o descripción contenga el texto, o cuyo SKU (propio o de alguna variación) contenga el texto, mostrando la variación coincidente como su producto padre, con subtítulo compuesto por precio, estado de stock y estado de publicación.

#### Scenario: Coincidencia por SKU de variación

- **WHEN** el producto variable "Camiseta" tiene una variación con SKU "CAM-ROJA-M" y se busca "cam-roja"
- **THEN** aparece "Camiseta" en el grupo de productos

#### Scenario: WooCommerce inactivo

- **WHEN** WooCommerce no está activo
- **THEN** la respuesta no incluye grupos de productos ni pedidos

### Requirement: Búsqueda de pedidos

Con WooCommerce activo y para usuarios con `edit_shop_orders`, la búsqueda SHALL devolver pedidos cuyo número coincida exactamente (admitiendo el prefijo "#") o cuyos datos de facturación (nombre, apellidos, empresa o email) contengan el texto, tanto con HPOS como con almacenamiento clásico, con subtítulo compuesto por cliente, total y estado, ordenados del más reciente al más antiguo.

#### Scenario: Por número con almohadilla

- **WHEN** se busca "#1043"
- **THEN** aparece el pedido 1043 en primer lugar

#### Scenario: Por email del cliente

- **WHEN** se busca "laura@"
- **THEN** aparecen los pedidos cuyo email de facturación empieza por "laura@"

### Requirement: Límites y rendimiento

Cada grupo SHALL devolver como máximo el límite solicitado y su total de coincidencias sin cargar todos los elementos, la ruta SHALL consultar solo los tipos solicitados y disponibles, y ninguna consulta SHALL usar SQL directo sobre tablas de WordPress o WooCommerce.

#### Scenario: Límite respetado

- **WHEN** hay 40 entradas que coinciden y el límite es 5
- **THEN** el grupo de entradas trae 5 elementos e indica total 40

#### Scenario: Tipos filtrados

- **WHEN** el cliente solicita solo el tipo "usuarios"
- **THEN** la respuesta no consulta ni devuelve otros grupos
