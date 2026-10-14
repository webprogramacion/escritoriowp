## Context

Motivación en `proposal.md`; comportamiento exigido en `specs/auditoria-administradores/spec.md`.

Lo que condiciona el enfoque:

- **Cómo concede WordPress los permisos** (`WP_User::get_role_caps()`, comprobado en el código de
  `trunk`): lee el metadato `{prefijo}capabilities` del usuario; es rol cada **clave** que coincide con
  un rol existente, **sin mirar su valor**; fusiona los permisos de esos roles y encima sobrescribe con
  los del propio usuario; `has_cap()` da por concedido un permiso si su valor **no está vacío**. Con
  varias filas del mismo metadato, WordPress usa la primera (la de `umeta_id` menor).
- **Cómo se esconde a un usuario**: lo habitual es un `pre_user_query` en un plugin o mu-plugin que
  añade `AND ID != X` a toda consulta de usuarios. Afecta a `get_users()`, `WP_User_Query` y a la
  pantalla Usuarios, así que no sirven para detectar.
- **WooCommerce** da al gestor de tienda `import`, `export`, `list_users`, `edit_theme_options` y
  `level_9`, y le quitó `edit_users` en la 3.5.4; el permiso de editar clientes se lo concede en
  tiempo de ejecución. Nada de eso debe marcar a un gestor de tienda.
- El menú lo crea `Ajustes::anadir_pagina()` en `admin_menu` con prioridad 10; `AcercaDe` añade su
  entrada en la 11. El lanzador lee `$submenu` y `CatalogoComandos::limpiar_titulo()` ya quita los
  `<span>` de contadores. `Menu::opciones()` excluye la entrada propia.
- Las pruebas unitarias no cargan WordPress: lo que se quiera probar tiene que vivir en funciones puras.

## Goals / Non-Goals

**Goals:**

- Que el cálculo de «quién tiene qué» sea una función pura, idéntica a la de WordPress y cubierta por
  pruebas, separada de la lectura de la base de datos y de la pantalla.
- Que ni la lectura ni la presentación pasen por API que un código malicioso pueda filtrar para
  esconder a un usuario.
- Que el contador del menú cueste una lectura de caché en casi todas las cargas.

**Non-Goals:**

- Detectar permisos que solo existen en tiempo de ejecución (`user_has_cap`, `map_meta_cap`): no
  están en la base de datos y cualquier plugin legítimo los usa.
- Un filtro para cambiar la lista de permisos críticos: en una herramienta de seguridad sería la
  primera cosa que tocaría un atacante.
- Corregir roles, borrar usuarios o registrar un historial de cambios.
- REST o JavaScript: la pantalla y la confirmación se pintan en el servidor.

## Decisions

### 1. Tres clases en `includes/seguridad/`

| Clase | Responsabilidad | WordPress |
|-------|-----------------|-----------|
| `Seguridad\Permisos` | Lista `CRITICOS`, desserializado seguro, cálculo por usuario, roles alterados, patrones de búsqueda | No: funciones puras, probadas |
| `Seguridad\Detector` | Lee la base de datos, arma el informe, cuenta, cachea e invalida la caché, comprueba ocultos | Sí |
| `Seguridad\Administradores` | Submenú y contador, pantalla, confirmación, acción de retirar | Sí |

Alternativa descartada: una sola clase como `AcercaDe`. La lógica de permisos es lo único que merece
pruebas unitarias y mezclada con `$wpdb` no se podría probar sin WordPress.

No se pasa por `Capacidades`: esa clase modela los tipos de contenido del escritorio y del lanzador.
Esta pantalla, como Acerca de, solo comprueba sus capacidades fijas.

### 2. `Permisos::evaluar()` replica `get_role_caps()`

Entrada: el array de permisos guardados del usuario y el array de roles (`slug => ['name', 'capabilities']`).
Salida: roles del usuario, si es administrador y, por cada permiso crítico concedido, su origen
(`usuario` o el slug del rol). Reglas, en este orden: claves que son rol (sea cual sea su valor),
fusión de los permisos de esos roles, sobrescritura con los del usuario, filtro `! empty()`. El origen
de un permiso es `usuario` si el valor efectivo viene del propio usuario; si no, el último de sus
roles que lo define, que es el que aporta el valor tras la fusión.

`Permisos::desserializar()` imita lo que hace WordPress al cargar el metadato pero con
`unserialize( $valor, [ 'allowed_classes' => false ] )`: un valor manipulado no puede instanciar
objetos. Lo que no sea un array da `[]`, igual que `WP_User::for_site()`.

`Permisos::roles_alterados()` devuelve los roles distintos de `administrator` con algún permiso
crítico no vacío.

### 3. Lectura directa de la base de datos, en dos pasos

1. **Candidatos.** Una consulta sobre `$wpdb->usermeta` unida a `$wpdb->users` (descarta metadatos
   huérfanos), con `meta_key = {prefijo del sitio}capabilities` y un `OR` de
   `meta_value LIKE '%"x"%'` por cada permiso crítico, por `administrator` y por cada rol alterado.
   Devuelve solo `user_id`. Los patrones salen de `Permisos::patrones()` y pasan por `esc_like()` y
   `prepare()`.
2. **Detalle.** Para esos `user_id`, todas sus filas de permisos ordenadas por `umeta_id`, más
   `user_login`, `user_email`, `display_name` y `user_registered` de la misma unión. Se queda la
   primera fila de cada usuario, como WordPress, y se evalúa en PHP.

El `LIKE` es solo un filtro grueso (puede traer falsos candidatos, nunca perderlos); la decisión la
toma `evaluar()`. El paso 2 existe porque el filtro podría casar con una segunda fila del metadato que
WordPress ignora.

Alternativas descartadas: `get_users()`/`WP_User_Query` (las filtra `pre_user_query`, que es justo lo
que hay que esquivar) y `get_user_meta()`/`get_userdata()` (filtro `get_user_metadata`; `get_userdata`
es reemplazable). Las consultas llevan `phpcs:ignore` con el comentario de por qué son la única vía,
como pide `CLAUDE.md`.

Los roles salen de `wp_roles()->roles`, no de la opción cruda: es lo que WordPress usa para conceder,
y así se ve también un rol alterado en tiempo de ejecución.

Los superadministradores (multisitio) salen de `get_super_admins()`; sus datos, de una consulta
directa a `$wpdb->users` por `user_login`.

### 4. Usuarios ocultos

Para los usuarios que se van a pintar (administradores y como mucho 100 marcados) se lanza
`get_users( [ 'login__in' => $logins, 'fields' => 'ID' ] )`: los que faltan son los ocultos. Por
nombre de usuario y no con `include`, porque con `include` WordPress ignora `exclude`, que es otra
forma de esconder a alguien desde `pre_get_users` (verificado con un mu-plugin de prueba). Para los
superadministradores que no son miembros del sitio, la misma consulta con `'blog_id' => 0`, que es lo
que usa la lista de usuarios de la red. Así un superadministrador ajeno al sitio no sale como oculto
por error.

### 5. Informe y límite

`Detector::analizar()` devuelve un informe con `administradores`, `marcados` (ya ordenados: primero
con permisos sueltos, después por fecha de alta descendente), `total_marcados`, `roles_alterados` (con
su número de usuarios, contado sobre los candidatos, que incluyen a todos los de esos roles) y los IDs
ocultos. Solo los 100 primeros marcados llevan datos para pintar; el resto solo cuenta.

`total_marcados` = usuarios con permisos críticos sin ser administradores ni superadministradores ∪
usuarios mostrados que están ocultos. Es el número del resumen y del contador.

### 6. Contador del menú con caché

- `Detector::contar()` lee el transitorio `escritoriowp_administradores` (entero); si no existe,
  ejecuta `analizar()` y lo guarda **15 minutos**. `analizar()` siempre guarda el resultado, así que
  abrir la pantalla lo refresca.
- Se invalida con `updated_user_meta` y `deleted_user_meta` cuando la clave es la de permisos del
  sitio (cubre `set_role`, `add_cap`, `remove_cap` y bajas, porque todas acaban ahí), con
  `add_option_` / `update_option_{prefijo}user_roles` y con `granted_super_admin` /
  `revoked_super_admin`. `added_user_meta` (cada alta de usuario) solo invalida si el usuario nace
  administrador o con permisos críticos (`Permisos::sospechoso()`): en una tienda con muchos
  registros, invalidar en cada alta obligaría a recalcular casi en cada pantalla.
- Los enganches los registra `Plugin::arrancar()` también fuera del administrador, porque las altas y
  los cambios de rol pueden llegar desde la tienda o la API REST.
- Lo que no pasa por WordPress (una escritura directa, activar un plugin que oculta usuarios) lo
  recoge la caducidad.

Alternativa descartada: calcular en cada carga. En un sitio con muchos clientes la consulta recorre
todas las filas de permisos y no merece pagarla en cada pantalla. 15 minutos acota el retraso de un
aviso de seguridad sin coste apreciable.

### 7. Registro del submenú y contador

En `admin_menu` con prioridad 11 y solo si el usuario tiene `manage_options` y `list_users`:
`add_submenu_page( Ajustes::PAGINA, …, 'manage_options', 'escritoriowp-administradores', …, 1 )`. El
último argumento (posición, WordPress 5.3+) la coloca justo detrás de «Ajustes» sin depender del
orden en que se registran los módulos. Sin `list_users` no se registra la página, así que la URL
directa da la denegación de WordPress.

Con `contar() > 0`, el título del submenú lleva el contador y se añade el mismo a la entrada de
primer nivel en `$menu`. Marcado igual que el de WordPress:
`<span class="awaiting-mod count-N"><span class="pending-count" aria-hidden="true">N</span><span class="screen-reader-text">…</span></span>`,
con el texto de `_n()`. Tocar `$menu` lleva su `phpcs:ignore`: WordPress no ofrece API para cambiar el
título de un menú ya registrado y el propio núcleo lo hace así con «Plugins» y «Comentarios».

### 8. Retirar permisos: confirmación en el servidor y `remove_cap()`

- El botón es un enlace a `…&retirar={id}`. Esa vista **no cambia nada**: vuelve a analizar al
  usuario, comprueba las mismas condiciones que la acción y lista los permisos sueltos que se
  quitarían, con un formulario POST a `admin-post.php` (`action=escritoriowp_retirar_permisos`,
  `usuario={id}`, nonce `escritoriowp_retirar_permisos_{id}`) y un enlace «Cancelar».
- La acción: `check_admin_referer()` con la acción ligada al ID, `manage_options` + `list_users` +
  `current_user_can( 'promote_user', $id )`, que no sea el usuario actual, que no sea administrador ni
  superadministrador. Vuelve a calcular con el `Detector` los permisos críticos **sueltos** y llama a
  `WP_User::remove_cap()` por cada uno. Compara antes y después y redirige con `wp_safe_redirect()` a
  la pantalla con el número retirado (o el motivo del rechazo) en la URL.
- `admin-post.php` dispara `admin_post_{acción}` también por GET (lee `$_REQUEST`), así que la
  acción comprueba ella misma que el método es POST y, si no, redirige sin tocar nada.
  `check_admin_referer()` termina la petición si el nonce no vale.

Por qué `remove_cap()` y no SQL: dispara los enganches (la caché se invalida sola, los plugins de
registro de actividad lo anotan), limpia la caché de objetos y recalcula `user_level`, que es el otro
rastro que suele dejar una escalada a mano. Solo actúa sobre claves que `WP_User` ve en su metadato;
si alguna no se pudo retirar, el mensaje lo dice.

El mensaje de éxito nombra los permisos retirados, para poder devolverlos con un editor de roles si
eran de una integración legítima.

### 9. Presentación

Mismo patrón que Acerca de: `.wrap` con `<h1>`, contenedor `.escritoriowp` con `data-tema` de las
preferencias y `escritoriowp-etiqueta` con `data-tono="rojo"` / `"verde"` / `"azul"`. Tablas con clase
propia en `ajustes.css` y tokens de `comun.css` (las de WordPress no siguen el tema oscuro), que en
pantallas estrechas se apilan. Fechas con `wp_date()` a partir de `user_registered`, que está en UTC.
Enlace a la ficha con `get_edit_user_link()`, que ya devuelve vacío si no se puede editar.

## Risks / Trade-offs

- [Rol personalizado legítimo con permisos críticos (un editor de roles, un plugin de socios)] →
  aparece marcado y el contador no se apaga. Es correcto desde el punto de vista de la seguridad y la
  sección de roles alterados explica de dónde viene. Si resulta frecuente, «marcar como revisado» es un
  cambio posterior.
- [Código que oculta usuarios solo cuando la página es `users.php`] → esta pantalla no lo ve como
  oculto, aunque el usuario sí aparece marcado por sus permisos si los tiene. Se documenta como
  limitación en `docs/qa.md` y en la propia pantalla no se promete más.
- [Un atacante con permisos de administrador puede desactivar el plugin] → es detección, no
  prevención. Inevitable.
- [Retirar permisos a un usuario de servicio legítimo rompe una integración] → la confirmación lista
  cada permiso y el mensaje final nombra los retirados.
- [Coste de la consulta al caducar la caché en sitios muy grandes] → filtro por `meta_key` (indexado)
  más `LIKE` sobre valores cortos, una vez cada 15 minutos como mucho; la pantalla limita el pintado a
  100 marcados.
- [`unserialize` en el código] → con `allowed_classes => false` es más seguro que el
  `maybe_unserialize()` que usa WordPress; lleva su `phpcs:ignore` justificado.
- [Archivo de OpenSpec] → el delta de `menu-administracion` modifica un requisito que crea
  `releases-github-y-menu-propio`, sin archivar; hay que archivar ese cambio antes que este.

## Migration Plan

Nada que migrar: la pantalla es nueva y el transitorio se crea solo la primera vez que alguien con
permisos carga el administrador. `uninstall.php` ya borra los transitorios por su prefijo
`escritoriowp_`. Desactivar el plugin quita la pantalla y el contador; el transitorio caduca solo.
