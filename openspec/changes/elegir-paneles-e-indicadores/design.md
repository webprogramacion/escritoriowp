## Context

Motivación y alcance en `proposal.md`; comportamiento en los deltas de `specs/`.

Estado actual que condiciona el diseño:

- **Los ajustes son escalares.** `Ajustes::defectos()`, `sanear()`, `todos()` y `obtener()` trabajan
  con `bool|int|string`. Una casilla se pinta con un campo oculto `0` delante de la casilla `1`, para
  que desmarcarla viaje en el envío. `sanear()` es pura y la prueban `tests/prueba-ajustes.php`.
- **Los paneles salen de `Capacidades::TIPOS_PANEL`**, filtrados por capacidad en
  `Capacidades::paneles_visibles()`. Solo los usa `Escritorio::configuracion()`, que los ordena con
  las preferencias del usuario y los pasa al JS en `config.paneles`. El menú «Personalizar» lista
  solo `config.paneles`.
- **Los indicadores no tienen lista.** `Resumen::obtener()` construye las ocho tarjetas una a una,
  cada una tras su `Capacidades::puede_ver()`. La de ingresos va dentro del mismo bloque que la de
  pedidos, y las tres de WooCommerce salen de `tarjetas_woo()`, que recorre los pedidos del mes (con
  transitorio de 5 minutos). La ruta REST `resumen` devuelve lo que da `Resumen::obtener()`.
- **`Preferencias::sanear()` valida contra `TIPOS_PANEL`**, no contra lo visible: las preferencias
  de un panel que el usuario no ve se conservan.
- **El JS supone que existen las dos zonas.** `cargarResumen()`, `pintarPaneles()` y
  `cargarPanelPorClave()` usan `nodos.resumen` y `nodos.paneles` sin comprobarlos. El esqueleto de
  carga del resumen pinta siempre cuatro tarjetas.
- **Hay un cambio sin archivar que toca el mismo requisito**: `avisos-correo-plugins` modifica
  «Ajustes globales con valores por defecto» de `nucleo-plugin`, y el delta de este cambio parte de
  ese texto.

## Goals / Non-Goals

**Goals:**

- Un solo sitio que decide si un panel o un indicador está activo, consultado por el escritorio, el
  resumen y su ruta REST.
- Que el saneado siga siendo puro y probado, y que el mismo saneado sirva para lo guardado y para lo
  enviado por el formulario.
- Ningún cambio visible para un sitio que actualiza sin tocar los ajustes.
- No calcular lo que no se va a mostrar, en particular los pedidos del mes.

**Non-Goals:**

- Que estos ajustes sean control de acceso. `Capacidades` y los `permission_callback` no cambian.
- Reordenar paneles o indicadores desde Ajustes: el orden de los paneles sigue siendo una
  preferencia de cada usuario, y el de los indicadores, fijo.
- Arreglar que un usuario que reordena sus paneles mientras uno está desactivado pierda la posición
  de ese panel (ver riesgos).

## Decisions

### 1. Un mapa `clave → bool` por ajuste, con «ausente = activo»

Dos claves nuevas en la opción `escritoriowp_ajustes`:

```php
'indicadores_activos' => array( 'entradas' => true, 'paginas' => true, … ),  // todas true
'paneles_activos'     => array( 'entradas' => true, 'paginas' => true, … ),  // todas true
```

Cada casilla del formulario envía `escritoriowp_ajustes[paneles_activos][paginas]` con `0` (campo
oculto) o `1` (casilla), igual que el tipo `casilla`. Así `sanear()` recibe la misma forma de lo
guardado y de lo enviado. Una clave que falta vale `true`: los paneles o indicadores que traiga una
versión futura salen activos, como pide «Ajuste ausente tras actualización».

Alternativas descartadas:

- **Trece casillas sueltas** (`panel_entradas`, `indicador_ingresos`…). Encajan sin tocar los tipos,
  pero llenan `defectos()` y la tabla de ajustes con una fila por elemento. Además, cada panel nuevo
  obligaría a añadir un ajuste.
- **Lista de ocultos** (`paneles_ocultos => ['usuarios']`). Es la forma de las preferencias, pero
  una casilla desmarcada no se envía. El formulario tendría que mandar los visibles, y lo enviado y
  lo guardado tendrían formas opuestas para el mismo saneado. Los de WooCommerce sin pintar se
  perderían.

### 2. Las listas de claves salen de las clases que pintan cada cosa

- **Paneles**: `Capacidades::TIPOS_PANEL`, como ya hacen las preferencias.
- **Indicadores**: constante nueva `Resumen::INDICADORES`, en el orden en que salen las tarjetas,
  con el tipo de `Capacidades::MAPA` que da su capacidad:

  ```php
  public const INDICADORES = array(
  	'entradas' => 'entradas', 'paginas' => 'paginas', 'usuarios' => 'usuarios',
  	'productos' => 'productos', 'pedidos' => 'pedidos', 'ingresos' => 'pedidos',
  	'comentarios' => 'comentarios', 'actualizaciones' => 'actualizaciones',
  );
  ```

  Si un indicador es de WooCommerce sale de `Capacidades::MAPA[ $tipo ]['woo']`, sin otra lista. Su
  nombre sale de un `Resumen::etiqueta( $clave )` nuevo, que usan tanto las tarjetas como los
  ajustes. Así el texto de la casilla es el de la tarjeta y no hay dos copias de la misma cadena que
  traducir.

Alternativa descartada: añadir los indicadores a `Capacidades`. `Capacidades` describe tipos de
contenido y su acceso. «Ingresos» no es un tipo, sino una tarjeta que hereda la capacidad de pedidos.

### 3. Saneado puro y consultas tipadas en `Ajustes`

- `Ajustes::sanear_activos( mixed $valor, array $claves ): array<string, bool>`, pública y pura.
  Recorre `$claves` en su orden y devuelve un booleano por cada una: `a_booleano()` si viene en
  `$valor`, `true` si no. Descarta las claves desconocidas. Si `$valor` no es un array, todas son
  `true`. `sanear()` gana dos ramas en su `match`.
- Los tipos se amplían a `bool|int|string|array` en `defectos()`, `sanear()`, `todos()`, `obtener()`
  y `$cache`. Todos los llamantes actuales ya convierten el valor con `(bool)`, `(int)` o `(string)`.
- `panel_activo( string $tipo ): bool` e `indicador_activo( string $clave ): bool` son las únicas
  formas de leer los mapas. Una clave que no está en el mapa devuelve `true`, coherente con la
  decisión 1. Nadie lee el array crudo con `obtener()`.

### 4. Dónde se filtra: en `Escritorio` y `Resumen`, no en `Capacidades`

- **Paneles**: `Escritorio::configuracion()` filtra `Capacidades::paneles_visibles()` con
  `panel_activo()` antes de ordenar. Como «Personalizar» se construye con `config.paneles`, deja de
  ofrecer los desactivados sin tocar el JS.
- **Indicadores**: `Resumen::claves_visibles()` nuevo, público, devuelve las claves de `INDICADORES`
  activas y cuyo tipo puede ver el usuario. Es barato: solo hace `current_user_can()`, sin contar.
  `Resumen::obtener()` lo calcula al principio y cada bloque pregunta si su clave está en esa lista,
  en lugar de llamar a `puede_ver()`. `tarjetas_woo()` solo se llama si en la lista hay
  `productos`, `pedidos` o `ingresos`, y dentro separa la tarjeta de ingresos de la de pedidos. La
  ruta REST `resumen` filtra sin cambios propios, porque usa `Resumen::obtener()`.
- **Preferencias**: sin cambios. `Preferencias::sanear()` sigue validando contra `TIPOS_PANEL`. Eso
  conserva lo que el usuario decidió sobre un panel desactivado y cumple «Preferencia conservada al
  reactivar».

Alternativa descartada: filtrar en `Capacidades::paneles_visibles()` o en `puede_ver()`. Convertiría
un ajuste de presentación en control de acceso: `recientes/{tipo}` respondería 403 a quien sí tiene
la capacidad y ve esos datos en el listado nativo. Además, `puede_ver()` dejaría de significar «puede
ver».

### 5. Zonas vacías: no se pintan

- `Escritorio::pintar_app()` no pinta `<section data-escritoriowp-resumen>` si
  `Resumen::claves_visibles()` está vacío, ni `<section data-escritoriowp-paneles>` si la lista de
  paneles de `configuracion()` está vacía. Esto cubre también el caso de hoy de un usuario sin
  ningún panel por capacidad, que deja una región vacía con nombre.
- `configuracion()` añade `indicadores` (número de claves visibles) para que el esqueleto de carga
  pinte tantas tarjetas como llegarán, en lugar de cuatro fijas.
- `configuracion()` añade también `tipos`: la definición de todos los tipos que el usuario puede
  ver, estén o no activos en el sitio. La ventana de creación rápida buscaba el tipo en
  `config.paneles` y no hacía nada si no estaba, así que con un panel desactivado su botón de la
  cabecera dejaba de funcionar. Ahora lo busca en `config.tipos` con `tipoPorClave()`. Lo destapó
  el recorrido en navegador de la tarea 4.3. Se descartó guardar todos los tipos en `paneles` con
  una marca de activo, porque obligaba a filtrar en cada uso de `paneles` del JS.
- En el JS, `cargarResumen()`, `pintarPaneles()` y `cargarPanelPorClave()` salen pronto si falta su
  nodo. Así siguen funcionando el botón «Actualizar» y el refresco tras crear un elemento desde la
  cabecera. `pintarPersonalizar()` omite el título «Paneles» y sus filas si `config.paneles` está
  vacío. No hacen falta textos nuevos en el JS.

### 6. Tipo de campo `casillas`

El campo declara `tipo => 'casillas'` y un argumento `opciones` (`clave => etiqueta`), que se suma a
`predeterminado`, `ejemplo` y `leyenda` en la lista de argumentos opcionales de
`registrar_ajustes()`. `pintar_campo()` gana la rama:

```html
<fieldset>
  <legend class="screen-reader-text"><span>Paneles</span></legend>
  <input type="hidden" name="escritoriowp_ajustes[paneles_activos][entradas]" value="0">
  <label for="escritoriowp-campo-paneles_activos-entradas">
    <input type="checkbox" id="escritoriowp-campo-paneles_activos-entradas"
           name="escritoriowp_ajustes[paneles_activos][entradas]" value="1" checked> Entradas
  </label><br>
  …
</fieldset>
```

Es el marcado de las pantallas de ajustes de WordPress (Comentarios, Lectura) para grupos de
casillas. Para este tipo, `add_settings_field()` se llama **sin `label_for`**: con él, la cabecera
de la fila sería un `<label for>` apuntando a un id que no existe, y el nombre del grupo lo da la
`legend`.

Las opciones se calculan al registrar el campo con `Capacidades::disponible()`, que ya devuelve
`false` para los tipos de WooCommerce sin WooCommerce: paneles con `Capacidades::etiqueta()`,
indicadores con `Resumen::etiqueta()`. Los dos campos van en la sección «Escritorio», en este orden:
«Sustituir el escritorio nativo», «Ocultar los widgets de otros plugins», **«Indicadores»**,
**«Paneles»** y «Elementos por panel». Es el orden en que aparecen en el escritorio, y «Elementos por
panel» queda junto a los paneles.

### 7. Los de WooCommerce se conservan al guardar sin WooCommerce

Sin WooCommerce, el formulario no pinta sus casillas y el envío no trae esas claves. `sanear()` les
daría `true` y se perdería un «Pedidos» desactivado. `sanear_formulario()`, que ya es la parte del
guardado que conoce WordPress, completa antes de sanear lo enviado con lo guardado:

```php
foreach ( array( 'indicadores_activos', 'paneles_activos' ) as $clave ) {
	if ( is_array( $entrada[ $clave ] ?? null ) ) {
		$entrada[ $clave ] += (array) $this->obtener( $clave );
	}
}
```

La unión de arrays conserva lo enviado y añade solo lo que falta. Si WordPress llama dos veces a
`sanear_formulario()` en el mismo guardado, la segunda vez recibe el mapa ya completo y la unión no
cambia nada. `sanear()` sigue siendo pura.

## Risks / Trade-offs

- **[Un usuario reordena sus paneles mientras uno está desactivado]** → El JS guarda
  `orden_paneles` solo con los paneles que ve, así que al reactivarse ese panel queda el último para
  ese usuario (`Preferencias::ordenar()` deja al final lo que no está en el orden). Se acepta: es un
  caso raro, no pierde datos y el usuario lo recoloca con «Personalizar». Arreglarlo exigiría que el
  JS conociera los paneles que no puede mostrar.
- **[El administrador desmarca todo]** → El escritorio se queda en la cabecera. Es lo que se pidió
  y es reversible. Las descripciones de los dos campos explican que afectan a todos los usuarios.
- **[Ampliar el tipo de `obtener()` a `array`]** → Un llamante que use el valor sin convertirlo
  podría recibir un array. Hoy todos lo convierten, y los mapas solo se leen con
  `panel_activo()` e `indicador_activo()`. La tarea correspondiente lo verifica con `grep`.
- **[Una tarjeta nueva olvidada en `INDICADORES`]** → Con la decisión 4, una tarjeta que no esté en
  la lista nunca se muestra. El fallo es visible al probarla, no silencioso. La receta de
  `CLAUDE.md` §8 lo indica.
- **[Volver a una versión anterior]** → La versión anterior ignora las dos claves, porque su
  `sanear()` solo recorre sus `defectos()`, y las borra en el siguiente guardado. Se vuelven a ver
  todos los paneles e indicadores. No hay nada más que deshacer.

## Migration Plan

Sin migración: un sitio que actualiza no tiene las claves, `sanear()` les da su valor por defecto
(todo activo) y nada cambia hasta que un administrador guarda otra cosa. `uninstall.php` ya borra la
opción entera. Publicar como versión menor con `/nueva-version minor` cuando se decida.
