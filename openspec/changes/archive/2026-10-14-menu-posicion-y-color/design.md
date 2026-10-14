## Context

Motivación en `proposal.md`; requisitos en `specs/menu-administracion/spec.md` y
`specs/nucleo-plugin/spec.md`. Estado actual relevante:

- `Ajustes::anadir_pagina()` registra el menú con `add_menu_page()` **sin posición**, a propósito: el
  comentario dice que así no desplaza los menús nativos. Por eso queda al final, detrás del separador
  `separator-last`.
- `Ajustes::defectos()` devuelve `array<string, bool|int>` y `Ajustes::sanear()` trata todo lo que no
  es booleano como el número de elementos por panel: lo convierte a entero y lo acota entre 3 y 20.
  Un ajuste de texto no cabe hoy sin tocar el saneado. `pintar_campo()` solo sabe pintar casillas y
  el campo numérico.
- WordPress construye la barra lateral en `wp-admin/includes/menu.php`, en este orden: dispara
  `admin_menu`, quita los menús que el usuario no puede ver, ordena `$menu` por clave con
  `strnatcasecmp`, aplica `menu_order` **solo si** `custom_menu_order` devuelve `true`, y por último
  elimina separadores contiguos. `menu_order` recibe la lista de identificadores (`$menu[n][2]`) en
  el orden final y lo que devuelve manda: los identificadores que no estén en la lista quedan detrás.
- `CatalogoComandos::limpiar_titulo()` ya quita los contadores (`<span class="awaiting-mod">…`) y el
  marcado de los títulos del menú, y es pública.
- Las pruebas unitarias no cargan WordPress: solo cubren funciones puras. El resto se comprueba con
  la instalación SQLite descrita en `CLAUDE.md` §10 y `docs/qa.md`.

## Goals / Non-Goals

**Goals:**

- Posición relativa a otro menú, resuelta cuando el menú ya está completo, para que funcione con
  menús de plugins que se registran después que EscritorioWP.
- Toda la lógica que decide algo (recolocar, color del texto, CSS, saneado) en funciones puras con
  pruebas.
- Ningún fichero ni paso de compilación nuevo salvo un script mínimo para iniciar el selector de
  color.

**Non-Goals:**

- Vista previa en vivo del color en la barra lateral antes de guardar: obligaría a duplicar en
  JavaScript el cálculo del contraste. Se ve al guardar, porque la página se recarga.
- Colores distintos para pasar el ratón o para el estado abierto: se usa uno solo y el resto lo pone
  WordPress.
- Posición o color por usuario. Son ajustes globales, como el resto de la pantalla de ajustes; y el
  menú solo lo ve quien tiene `manage_options`.
- Mover o colorear las entradas del submenú, o cualquier otro menú de WordPress.
- Multisitio: el menú de red no existe y no se añade.

## Decisions

### D1. Reordenar con `menu_order`, no con el parámetro `$position`

Una clase nueva, `EscritorioWP\Menu` (`includes/class-menu.php`), engancha
`custom_menu_order` con `__return_true` y `menu_order` con prioridad `PHP_INT_MAX`. El callback
delega en una función pura:

```php
Menu::recolocar( array $orden, string $propio, string $debajo_de ): array
```

que saca `$propio` (`Ajustes::PAGINA`) de la lista y lo inserta justo después de `$debajo_de`. Con
`$debajo_de === ''` lo pone al final. Si `$debajo_de` no está en la lista, usa `index.php`; si
tampoco está, devuelve la lista sin cambios. Si `$propio` no está (un usuario sin `manage_options`),
devuelve la lista sin cambios. Reindexa siempre con `array_values()`.

`add_menu_page()` sigue sin posición: con este enfoque no hace falta, y dejarla vacía mantiene el
comportamiento de 0.3.0 si otro plugin desactiva `custom_menu_order`.

*Por qué:* el ajuste guarda «debajo de qué menú», no un número. Con `$position` habría que conocer la
clave numérica del menú de referencia en el momento de registrar el propio, y los plugins que
registran su menú más tarde (o con prioridad mayor en `admin_menu`) todavía no estarían. Además,
WordPress ordena las claves como texto natural, así que posiciones decimales como `2.5` y `2.10`
quedan al revés de lo que dice el número. `menu_order` se ejecuta con el menú completo, ya filtrado
por capacidades.

*Prioridad `PHP_INT_MAX`:* WooCommerce y otros plugins también usan `menu_order`; ejecutarse el
último hace que la posición elegida en EscritorioWP sea la que se ve. Un editor de menús que
sustituya el menú entero por otro medio sigue ganando, y es lo esperable.

*Alternativa descartada:* registrar el menú en `admin_menu` con prioridad `PHP_INT_MAX` calculando
la clave a partir de `$menu`. Obliga a cambiar la prioridad de `AcercaDe` (que cuelga del menú) y
sigue sufriendo el orden natural de las claves decimales.

### D2. El valor guardado es el identificador del menú de referencia

Ajuste `menu_debajo_de`, texto. Valor por defecto `'index.php'` (Escritorio). Cadena vacía significa
«Al final». Se sanea con una función pura:

- No es texto, o tiene caracteres fuera de una lista blanca de los que aparecen en identificadores de
  menú (`A-Z a-z 0-9 . _ - / ? = & % :`), o supera 200 caracteres → valor por defecto.
- Es `escritoriowp` (el propio menú) → valor por defecto.
- Se recorta con `trim()`; la cadena vacía resultante se conserva como «Al final».

No se comprueba en el saneado que el menú exista: el saneado tiene que ser puro y el menú de
referencia puede desaparecer y volver (WooCommerce desactivado y reactivado). La comprobación se hace
al reordenar (D1) y al pintar el desplegable (D3).

*Alternativa descartada:* guardar la posición numérica. Era la opción descartada por el usuario al
plantear el cambio: exige saberse los números y se rompe cuando otro plugin cambia el suyo.

### D3. Desplegable construido desde `$menu` al pintar la pantalla

En la pantalla de ajustes `$menu` ya está completo y ordenado. `Menu::opciones( array $menu ): array`
devuelve `identificador => nombre` recorriéndolo en orden y saltándose los separadores (clase
`wp-menu-separator` en `$elemento[4]`), los elementos sin título o sin identificador y el propio
`escritoriowp`; el nombre pasa por `CatalogoComandos::limpiar_titulo()`. La opción «Al final» (valor
`''`) se añade la última.

Si el valor guardado no está entre las opciones, se añade una opción con ese valor seleccionada y la
etiqueta «%s (no disponible)», usando el identificador como nombre. Sin esto, el navegador
seleccionaría la primera opción y guardar cualquier otro ajuste perdería la referencia en silencio.

`opciones()` llama a una función de WordPress a través de `limpiar_titulo()`, así que no entra en las
pruebas unitarias: se comprueba en `docs/qa.md` con el entorno SQLite.

### D4. Color: selector de WordPress, CSS en línea sobre la hoja del menú

Ajuste `menu_color_fondo`, texto. Valor por defecto `''` (sin color). Saneado puro: acepta `#rgb` y
`#rrggbb` sin distinguir mayúsculas, devuelve siempre `#rrggbb` en minúsculas; cualquier otra cosa
se guarda como `''`.

**Campo.** `<input type="text">` con clase `escritoriowp-color` y `data-default-color=""`, que el
selector `wp-color-picker` de WordPress convierte en el selector con su botón «Borrar». Sin
JavaScript sigue siendo un campo de texto que acepta el hexadecimal, y el servidor lo sanea igual.
El selector se inicia desde `assets/js/ajustes.js`, un script nuevo de pocas líneas en IIFE, sin
textos (los del selector los traduce WordPress), registrado en `Activos` con dependencia de
`wp-color-picker`. `Ajustes::encolar()` lo encola, junto con el estilo `wp-color-picker`, solo en la
pantalla de ajustes.

*Por qué un fichero y no `wp_add_inline_script()`:* así pasa por `node --check` en CI como el resto
de `assets/js/`, y lo que hay en `assets/` sigue siendo lo que llega al navegador.

*Alternativa descartada:* `<input type="color">`. No admite estar vacío, que es el valor por defecto
y la forma de volver al aspecto del esquema de colores.

**Estilo.** `Menu::css( string $fondo ): string` devuelve `''` si no hay color y, si lo hay, las
reglas para `#adminmenu #toplevel_page_escritoriowp > a.menu-top`: `background-color` con el color
elegido y `color` con el de `Menu::color_texto()`, más la misma `color` para el icono
(`.wp-menu-image::before`) y el nombre (`.wp-menu-name`). En reposo, las reglas excluyen el ratón
sobre la entrada (`li:hover`, `.opensub`) y el foco del enlace, de modo que en esos estados se
aplican las reglas del esquema de colores de WordPress, incluido su indicador de foco. Con la entrada
abierta (`.wp-has-current-submenu`) solo se excluye el foco: WordPress no cambia una entrada abierta
al pasar el ratón, y quitar el color en ese caso la haría saltar al azul del esquema cada vez que el
ratón pasa por «Ajustes» o «Acerca de». El selector de ID cubre también el menú plegado, porque el
fondo visible es el del mismo enlace.

Se añade con `wp_add_inline_style( 'admin-menu', … )` en `admin_enqueue_scripts`. `admin-menu` es la
hoja del menú lateral de WordPress y se carga en todas las pantallas del administrador; la
especificidad del ID hace que el orden de carga no importe. Con el color vacío no se añade nada.

El CSS se construye solo con el valor ya saneado y con los dos colores de texto fijos; nunca con lo
que llega del formulario.

### D5. Color del texto: blanco si llega a 4,5:1, negro si no

`Menu::color_texto( string $fondo ): string` calcula la luminancia relativa de WCAG 2 (canales sRGB
linealizados con el umbral 0,04045 y pesos 0,2126 / 0,7152 / 0,0722) y el contraste con el blanco,
`1,05 / (L + 0,05)`. Si es ≥ 4,5 devuelve `#ffffff`; si no, `#000000`.

*Por qué basta:* el blanco no llega a 4,5:1 cuando `L > 0,1833`, y en ese caso el contraste con el
negro es `(L + 0,05) / 0,05 > 4,67`. Así cualquier fondo queda por encima de 4,5:1 sin más casos. Se
prefiere el blanco porque es el texto habitual del menú lateral de WordPress.

*Alternativa descartada:* el gris oscuro de WordPress (`#1d2327`) en lugar del negro. Con algunos
grises medios ninguno de los dos llega a 4,5:1 (el peor caso queda en torno a 4:1).

### D6. Saneado por tipo en `Ajustes`

`defectos()` pasa a `array<string, bool|int|string>` y gana `menu_debajo_de` y `menu_color_fondo`.
`sanear()` decide por clave con `match`: los booleanos con `a_booleano()`, `elementos_por_panel` con
el acotado de siempre, y los dos nuevos con `sanear_menu_debajo_de()` y `sanear_color()`, ambas
puras y públicas para poder probarlas. `obtener()` pasa a devolver `bool|int|string`; sus usos
actuales ya convierten el valor (`(bool)`, `(int)` o negación), así que no cambian.

Los campos declaran su tipo en el array `$campos` de `registrar_ajustes()` (`casilla`, `numero`,
`desplegable`, `color`) y `pintar_campo()` decide con `match` sobre ese tipo en lugar de deducirlo
del valor por defecto. Se añade la sección «Menú» después de «Lanzador».

La receta «Un ajuste nuevo» de `CLAUDE.md` §8 deja de decir que el saneado y el formulario lo recogen
solos: hay que darle un tipo al campo y, si es de texto, su rama en `sanear()`.

## Risks / Trade-offs

- [Un plugin devuelve en `menu_order` una lista parcial, sin `escritoriowp` ni el menú de referencia]
  → `recolocar()` no inventa posiciones: si falta el propio menú devuelve la lista tal cual, y si
  falta la referencia prueba con `index.php`. Como se ejecuta el último, ve la lista ya modificada
  por los demás.
- [Activar `custom_menu_order` cambia algo para los demás menús] → No: la lista que devuelve
  `recolocar()` contiene todos los identificadores en el mismo orden salvo el propio, así que el
  `usort()` de WordPress deja el resto donde estaba. Se comprueba en QA con WooCommerce activo, que
  también usa `menu_order`.
- [Un identificador de menú real con caracteres fuera de la lista blanca] → No se podría elegir como
  referencia y el ajuste volvería al valor por defecto. Los identificadores de WordPress, WooCommerce
  y los plugins habituales usan solo esos caracteres; si aparece un caso, se amplía la lista.
- [Las reglas de color del esquema de un usuario usan selectores más específicos que los previstos]
  → El ID `#toplevel_page_escritoriowp` dentro de `#adminmenu` supera a las reglas de los esquemas
  de WordPress. Se revisa en QA con al menos dos esquemas (Predeterminado y Claro).
- [Las instalaciones existentes ven el menú moverse arriba al actualizar] → Es el comportamiento
  pedido. Se cuenta en el changelog y en el aviso de actualización, con la forma de volver a dejarlo
  al final.

## Migration Plan

Sin migración de datos: las claves nuevas no existen en la opción guardada y `sanear()` las completa
con sus valores por defecto al leer, como ya hace con cualquier ajuste ausente. Volver a 0.3.0 es
seguro: aquella versión ignora las claves que no conoce y las descarta en el siguiente guardado.
`uninstall.php` ya borra la opción entera.
