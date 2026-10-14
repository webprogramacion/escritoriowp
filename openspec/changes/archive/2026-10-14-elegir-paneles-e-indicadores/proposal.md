## Why

El escritorio muestra a cada usuario todos los indicadores y paneles que su rol le permite ver, y el
administrador del sitio no puede quitar ninguno para todos. Si una tienda no quiere que el equipo vea
los ingresos del mes, o un sitio no necesita el panel de usuarios, hoy solo cabe pedir a cada persona
que lo oculte desde «Personalizar», y eso solo sirve para los paneles: las tarjetas de indicadores no
se pueden ocultar de ninguna forma.

## What Changes

- **Dos ajustes nuevos en la sección «Escritorio» de EscritorioWP › Ajustes**: «Indicadores» y
  «Paneles». Cada uno es un grupo de casillas, una por tarjeta o panel, en el mismo orden en que
  salen en el escritorio. Una casilla marcada significa que se ve. Por defecto están todas marcadas,
  así que un sitio que actualice ve exactamente lo mismo que antes.
- **Un panel o indicador desmarcado no lo ve nadie** en el escritorio, sea cual sea su rol. Marcarlo
  no concede nada: cada usuario sigue viendo solo lo que su rol le permite.
- **«Personalizar» solo ofrece los paneles activos en el sitio.** Las preferencias de cada usuario
  se conservan: un panel que el usuario había ocultado sigue oculto si el administrador lo desactiva
  y lo vuelve a activar.
- **Sin indicadores activos desaparece la fila de tarjetas**, y el escritorio deja de pedir el
  resumen al servidor. Lo mismo con la zona de paneles si no queda ninguno. Si se desactivan las tres
  tarjetas de WooCommerce, el resumen deja de recorrer los pedidos del mes.
- **Los indicadores y paneles de WooCommerce** (productos, pedidos e ingresos) solo aparecen en
  Ajustes con WooCommerce activo, y mientras no lo está conservan el valor que tenían.
- **Tipo de campo nuevo en los ajustes, `casillas`**, para los ajustes que son una lista de
  casillas. Es interno y sirve para futuros ajustes del mismo tipo.

**Fuera de alcance:**
- Los widgets de escritorio de WordPress y de otros plugins: siguen gobernados por la casilla
  «Ocultar los widgets de otros plugins», sin cambios.
- Los botones de creación rápida de la cabecera y el lanzador: desactivar el panel «Usuarios» no
  quita «Nuevo usuario» ni los comandos de usuarios. Son accesos para crear, no piezas del
  escritorio.
- La ruta REST `recientes/{tipo}`: sigue respondiendo a quien tiene la capacidad del tipo. Elegir los
  paneles es una decisión de presentación, no de acceso, y esos datos están en el listado nativo.
- Ajustes distintos para cada rol.

## Capabilities

### New Capabilities

Ninguna.

### Modified Capabilities

- `escritorio-panel`: se añaden dos requisitos, los paneles e indicadores activos en el sitio y su
  elección en los ajustes. Cambian tres requisitos: «Indicadores de resumen (KPIs)» y «Paneles de
  últimos elementos» pasan a mostrar solo los activos, y «Personalización por usuario» solo ofrece
  los paneles activos y conserva las preferencias de los desactivados.
- `nucleo-plugin`: el requisito «Ajustes globales con valores por defecto» añade los dos ajustes
  nuevos (todos activos por defecto) y el saneado de una lista de casillas. El delta parte del texto
  que deja el cambio `avisos-correo-plugins`, que aún no está archivado, así que hay que archivarlo
  antes que este.

## Impact

- `escritoriowp/includes/class-ajustes.php`: claves `indicadores_activos` y `paneles_activos` en
  `defectos()`, saneado puro de la lista, tipo de campo `casillas`, consultas tipadas para saber si
  un panel o indicador está activo, y conservación de los valores de WooCommerce al guardar sin
  WooCommerce. El tipo de los valores de ajustes se amplía para admitir listas.
- `escritoriowp/includes/escritorio/class-resumen.php`: lista fija de indicadores con su tipo y su
  etiqueta, y filtro por el ajuste antes de calcular.
- `escritoriowp/includes/escritorio/class-escritorio.php`: filtra los paneles por el ajuste y deja de
  pintar las zonas que se quedan vacías.
- `escritoriowp/assets/js/escritorio.js`: tolera que falten la fila de indicadores o la zona de
  paneles, ajusta el esqueleto de carga al número de tarjetas y oculta el grupo «Paneles» de
  «Personalizar» si no hay ninguno.
- `escritoriowp/assets/css/ajustes.css`: solo si el grupo de casillas descuadra la tabla.
- Pruebas: `tests/prueba-ajustes.php` y `tests/prueba-resumen.php`.
- Documentación: `CLAUDE.md` §8, `escritoriowp/readme.txt`, `docs/qa.md` y `CHANGELOG.md`.
- Sin cambios en la base de datos más allá de las dos claves nuevas en la opción
  `escritoriowp_ajustes`, que `uninstall.php` ya borra entera. Sin dependencias nuevas.
