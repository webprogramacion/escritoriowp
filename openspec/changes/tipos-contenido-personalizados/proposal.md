## Why

Muchos sitios guardan su contenido principal en tipos de contenido personalizados (libros, eventos,
inmuebles, cursos…), pero el escritorio solo sabe mostrar entradas, páginas, usuarios y, con
WooCommerce, productos y pedidos. Para ver lo último que se ha publicado de esos tipos hay que salir
del escritorio e ir a su listado. Ahora que el administrador ya elige qué paneles e indicadores se
ven, es el momento de que pueda añadir también los de sus propios tipos de contenido.

## What Changes

- **Los ajustes detectan los tipos de contenido personalizados del sitio.** Los grupos de casillas
  «Indicadores» y «Paneles» de EscritorioWP › Ajustes añaden al final una casilla por cada tipo
  personalizado que tenga pantalla en el menú del administrador, con su nombre. Se detectan cada vez
  que se abre la pantalla, así que un tipo que añada un plugin aparece sin hacer nada más.
- **Salen desmarcados.** Al revés que los paneles e indicadores propios, que salen activos, un tipo
  personalizado no aparece en el escritorio hasta que el administrador lo marca. Un sitio que
  actualice no ve ningún cambio.
- **Un panel por tipo activado**, con los últimos elementos modificados, igual que el de entradas:
  título, estado, autor, fecha, «Ver» si está publicado, «Ver todos» y su estado vacío. Su botón de
  crear enlaza a la pantalla nativa de ese tipo, porque muchos tipos personalizados necesitan sus
  propios campos para crear un elemento válido. Se oculta y se reordena desde «Personalizar» como
  cualquier otro panel.
- **Una tarjeta de indicador por tipo activado**, con el número de publicados y los borradores,
  que enlaza a su listado. Va después de las tarjetas propias.
- **Las mismas capacidades que el listado nativo del tipo.** El panel, la tarjeta y la ruta REST de
  últimos elementos exigen la capacidad de editar ese tipo; activarlo no concede nada.
- **Si el tipo desaparece** (se desactiva el plugin que lo registra), su panel y su tarjeta dejan de
  mostrarse y su casilla de Ajustes desaparece, pero se recuerda que estaba activado: al volver el
  plugin, vuelve el panel. Las preferencias de cada usuario sobre ese panel también se conservan.
- **Los textos de estado vacío de los paneles pasan a llegar desde el servidor con cada panel**, en
  lugar de estar en el catálogo de textos del JavaScript con una clave por tipo. Es lo que permite
  pintar el de un tipo que no se conoce de antemano. No cambia ningún texto visible de los paneles
  existentes.

**Fuera de alcance:**
- Botones de creación en la cabecera del escritorio y comandos fijos en el lanzador para los tipos
  personalizados: la cabecera sigue igual, y el lanzador ya los ofrece al leer el menú.
- Creación rápida en ventana para los tipos personalizados.
- Taxonomías personalizadas y campos propios (ACF, metacampos) en las filas del panel.
- Los productos y pedidos de WooCommerce, que siguen teniendo sus paneles e indicadores propios y no
  se ofrecen como tipos personalizados.
- La búsqueda del lanzador, que ya incluye los tipos personalizados en «Otros contenidos».

## Capabilities

### New Capabilities

Ninguna.

### Modified Capabilities

- `escritorio-panel`: se añaden dos requisitos, los tipos de contenido personalizados en el
  escritorio (panel y tarjeta) y su detección en los ajustes. Cambian dos: «Control de acceso por
  capacidades» añade la capacidad de los tipos personalizados, y «Elección de paneles e indicadores
  en los ajustes» precisa que sus escenarios de opciones son para un sitio sin tipos personalizados.
- `nucleo-plugin`: el requisito «Ajustes globales con valores por defecto» precisa que los tipos
  personalizados empiezan desactivados y cómo se sanean sus claves en las listas de casillas.

## Impact

- Nuevo `escritoriowp/includes/class-tipos-contenido.php`: detección de los tipos personalizados,
  clave de panel (`cpt-{tipo}`), nombre e icono. Absorbe la lista de tipos excluidos que hoy vive en
  `busqueda/class-fuente-contenidos.php`.
- `escritoriowp/includes/class-capacidades.php`: las consultas de capacidad, URLs, etiquetas e
  iconos entienden las claves de tipos personalizados; textos de estado vacío de cada panel.
- `escritoriowp/includes/class-ajustes.php`: opciones de los dos grupos de casillas, saneado de las
  claves de tipos personalizados (ausente = desactivado) y descripciones de los campos.
- `escritoriowp/includes/escritorio/`: `Recientes` (filas del tipo), `Resumen` (tarjetas del tipo),
  `Escritorio` (definición de los paneles) y `Preferencias` (acepta las claves nuevas).
- `escritoriowp/includes/rest/class-rest.php`: la ruta `recientes/{tipo}` admite claves con guiones,
  guiones bajos y números.
- `escritoriowp/includes/class-activos.php` y `escritoriowp/assets/js/escritorio.js`: los textos de
  estado vacío salen del catálogo y llegan con cada panel.
- Pruebas: nuevo `tests/prueba-tipos-contenido.php`; cambios en `tests/prueba-ajustes.php`,
  `tests/prueba-preferencias.php` y `tests/prueba-capacidades.php`.
- Documentación: `CLAUDE.md` (§5 y §8), `docs/qa.md`, `escritoriowp/readme.txt` y `CHANGELOG.md`.
- Filtro nuevo `escritoriowp_tipos_contenido` para que otro plugin quite o añada tipos a la lista.
- Sin cambios en la base de datos más allá de nuevas claves dentro de `indicadores_activos` y
  `paneles_activos`, en la opción `escritoriowp_ajustes`, que `uninstall.php` ya borra entera. Sin
  dependencias nuevas.
