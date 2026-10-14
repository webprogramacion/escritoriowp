## Why

El menú «EscritorioWP» se registra sin posición, así que acaba al fondo de la barra lateral, debajo de
Ajustes y de los menús de otros plugins, lejos del escritorio que el propio plugin sustituye. Cuesta
encontrarlo y no hay forma de cambiarlo ni de hacerlo destacar sin instalar otro plugin para editar el
menú.

## What Changes

- **Posición por defecto justo debajo de «Escritorio».** El menú pasa de quedar al final a aparecer
  inmediatamente después del «Escritorio» de WordPress, antes del primer separador. Las instalaciones
  que actualicen verán el menú moverse arriba, porque el ajuste nuevo aún no está guardado y toma su
  valor por defecto.
- **Ajuste «Posición en el menú».** Un desplegable «Debajo de…» con los menús de primer nivel que
  existen en el sitio (Escritorio, Entradas, Medios, Páginas, WooCommerce, los de otros plugins…) y la
  opción «Al final». Se guarda el menú de referencia, no un número, así que la posición se mantiene
  aunque otro plugin cambie la suya. Si el menú elegido deja de existir (por ejemplo, se desactiva
  WooCommerce), EscritorioWP vuelve a su posición por defecto.
- **Ajuste «Color de fondo en el menú».** Un selector de color pinta el fondo de la entrada
  «EscritorioWP» de la barra lateral; el resto del menú de WordPress no cambia. Vacío por defecto, que
  conserva el aspecto del esquema de colores de cada usuario. El color del texto y del icono se elige
  solo para que el contraste no baje de 4,5:1. Al pasar el ratón o enfocar con el teclado se mantiene
  la respuesta visual de WordPress.
- Los dos ajustes se agrupan en una sección nueva, «Menú», de la pantalla EscritorioWP › Ajustes, y
  siguen las reglas del resto: solo `manage_options`, nonce de la Settings API y saneado en el servidor.
- El saneado de ajustes deja de suponer que todo lo que no es una casilla es el número de elementos por
  panel: cada ajuste se sanea según su tipo. Es un cambio interno, sin efecto en los ajustes guardados.

## Capabilities

### New Capabilities

Ninguna.

### Modified Capabilities

- `menu-administracion`: se añaden dos requisitos, la posición del menú (por defecto bajo
  «Escritorio», configurable) y el color de fondo de su entrada. Nota: esta capacidad la crea el cambio
  `releases-github-y-menu-propio`, que aún no está archivado; conviene archivarlo antes que este. Como
  este delta solo añade requisitos, se aplica igual en cualquier orden.
- `nucleo-plugin`: el requisito «Ajustes globales con valores por defecto» incorpora los dos ajustes
  nuevos y sus valores por defecto (debajo de «Escritorio», sin color propio).

## Impact

- **Plugin:** `includes/class-ajustes.php` (defectos, saneado por tipo, sección «Menú», campos de
  desplegable y de color, encolado del selector de color de WordPress), `includes/class-menu.php`
  (nuevo: reordenación y estilo de la entrada), `includes/class-plugin.php` (lo compone),
  `assets/css/ajustes.css` si hace falta ajustar el campo, `readme.txt` (sección del menú y changelog),
  `languages/escritoriowp.pot`.
- **Desarrollo:** `tests/prueba-ajustes.php` y `tests/prueba-menu.php` (nuevo), `docs/qa.md`,
  `CLAUDE.md` (§7 bis y la receta «Un ajuste nuevo» de §8), `README.md`, `CHANGELOG.md`.
- **Dependencias:** solo componentes de WordPress ya incluidos: los filtros `custom_menu_order` y
  `menu_order`, y el selector de color `wp-color-picker`. Nada de compilación ni librerías nuevas.
- **Compatibilidad:** la opción `escritoriowp_ajustes` conserva su nombre y formato; solo gana dos
  claves. Un editor de menús de terceros (Admin Menu Editor y similares) que fije su propio orden
  tiene la última palabra, como con cualquier otro menú.
