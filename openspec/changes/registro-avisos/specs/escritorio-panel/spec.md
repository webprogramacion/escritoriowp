## ADDED Requirements

### Requirement: Bloque de últimos avisos

Para los usuarios con `manage_options`, el escritorio SHALL mostrar un bloque «Avisos por correo», entre los indicadores y los paneles, con los cinco avisos guardados más recientes y un enlace «Ver todos» a la pantalla Avisos. Cada fila SHALL mostrar el asunto como enlace a su detalle, el tipo, el estado con texto y color, y cuánto hace que se envió.

#### Scenario: Administrador con avisos

- **WHEN** hay 8 avisos guardados y un administrador abre el Escritorio
- **THEN** ve, debajo de las tarjetas de indicadores y encima de los paneles, el bloque «Avisos por correo» con los 5 avisos más recientes, del más nuevo al más antiguo, y el enlace «Ver todos» a EscritorioWP › Avisos

#### Scenario: Aviso fallido en el bloque

- **WHEN** uno de los cinco avisos más recientes es «Fallido»
- **THEN** su fila muestra el texto «Fallido» en una etiqueta roja, y al pulsar su asunto se abre su detalle en la pantalla Avisos

#### Scenario: Usuario sin manage_options

- **WHEN** un editor abre el Escritorio de un sitio con avisos guardados
- **THEN** no ve el bloque «Avisos por correo» y la página no incluye ningún dato de los avisos

#### Scenario: Escritorio nativo

- **WHEN** el ajuste «Sustituir el escritorio nativo» está desactivado
- **THEN** el Escritorio nativo no muestra el bloque de avisos

#### Scenario: Lector de pantalla

- **WHEN** un usuario de lector de pantalla recorre las regiones del Escritorio
- **THEN** el lector anuncia una región llamada «Avisos por correo»

### Requirement: Cuándo aparece el bloque de avisos

El bloque de avisos SHALL aparecer si hay algún aviso guardado o alguna casilla de aviso marcada, y si no SHALL omitirse sin reservar espacio ni pedir datos al servidor. NO SHALL depender del ajuste «Paneles» NI ofrecerse en el menú «Personalizar». Con alguna casilla marcada y ningún aviso guardado, SHALL mostrar un estado vacío.

#### Scenario: Sin avisos ni casillas

- **WHEN** no hay ningún aviso guardado, todas las casillas de avisos están desmarcadas y un administrador abre el Escritorio
- **THEN** no aparece el bloque de avisos y el escritorio no pide los avisos al servidor

#### Scenario: Avisos activados y todavía sin enviar

- **WHEN** la casilla «Avisar al activar un plugin» está marcada, no hay ningún aviso guardado y un administrador abre el Escritorio
- **THEN** el bloque de avisos muestra un texto que dice que todavía no se ha enviado ningún aviso

#### Scenario: Casillas desmarcadas con avisos guardados

- **WHEN** hay avisos guardados y todas las casillas de avisos están desmarcadas
- **THEN** el bloque de avisos se sigue mostrando con los últimos avisos guardados

#### Scenario: Independiente de los paneles

- **WHEN** todas las casillas de «Paneles» están desmarcadas, hay avisos guardados y un administrador abre el Escritorio
- **THEN** ve el bloque de avisos, y el menú «Personalizar» no lo ofrece para ocultarlo ni para moverlo

### Requirement: Carga del bloque de avisos

El bloque de avisos SHALL cargar sus datos por separado del resto del escritorio. Si la carga falla, SHALL mostrar un mensaje de error con «Reintentar» sin afectar al resto de la interfaz, y el botón «Actualizar» de la cabecera SHALL volver a cargarlo.

#### Scenario: Error de carga

- **WHEN** la petición de los avisos falla
- **THEN** el bloque muestra un mensaje de error con el botón «Reintentar», y los indicadores y los paneles se cargan con normalidad

#### Scenario: Actualizar

- **WHEN** con el Escritorio abierto se envía un aviso nuevo y el administrador pulsa «Actualizar»
- **THEN** el aviso nuevo aparece el primero en el bloque sin recargar la página

### Requirement: Ruta REST de los últimos avisos

La ruta REST que alimenta el bloque SHALL devolver los avisos guardados más recientes, como mucho 20 aunque se pidan más, solo a usuarios con `manage_options`, y SHALL responder a los demás con un error de autorización sin datos.

#### Scenario: Petición sin capacidad

- **WHEN** un editor llama directamente a la ruta REST de los últimos avisos
- **THEN** recibe un error 403 sin datos

#### Scenario: Petición sin sesión

- **WHEN** se llama a la ruta REST de los últimos avisos sin usuario autenticado
- **THEN** recibe un error 401 sin datos

#### Scenario: Límite acotado

- **WHEN** un administrador pide la ruta con un límite de 500 y hay 60 avisos guardados
- **THEN** recibe los 20 más recientes
