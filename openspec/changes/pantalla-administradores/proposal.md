## Why

Una técnica habitual tras una intrusión en WordPress es dejar una puerta trasera que no salte a la
vista: en lugar de crear un administrador, el atacante da permisos de administrador a un usuario
normal escribiendo directamente en la base de datos (en el metadato `wp_capabilities` del usuario o
en la definición de un rol como «Suscriptor»). En la pantalla Usuarios ese usuario aparece con su rol
de siempre, así que nadie lo revisa. Hoy no hay forma de detectarlo sin consultar la base de datos a
mano o instalar un plugin de seguridad completo.

## What Changes

- **Pantalla nueva «Administradores»** en el menú «EscritorioWP», entre «Ajustes» y «Acerca de». Muestra:
  - Los usuarios con el rol Administrador (y, en multisitio, los superadministradores de la red), con
    su nombre, usuario, correo, fecha de alta y enlace a su ficha.
  - Marcados con una etiqueta roja, los usuarios **sin el rol Administrador que tienen algún permiso
    crítico**: los que en WordPress solo tiene el administrador y que dan control del sitio
    (`manage_options`, `edit_users`, `promote_users`, `install_plugins`, `edit_plugins`, `edit_files`,
    `update_core`…). De cada uno se dice qué permisos tiene y de dónde le vienen: asignados sueltos al
    usuario o heredados de un rol alterado. Los permisos que WooCommerce da al gestor de tienda
    (`import`, `export`, `list_users`, `edit_theme_options`) no cuentan.
  - Los roles distintos de Administrador que tienen permisos críticos, con cuántos usuarios los usan.
  - Una etiqueta «Oculto en Usuarios» en cualquier usuario de la lista que WordPress no devuelve en su
    pantalla Usuarios, que es como suelen esconderse estas cuentas.
- **La detección lee la base de datos y replica el cálculo de WordPress**: qué claves del metadato son
  roles (aunque su valor sea falso), qué permisos aporta cada rol y qué permisos sueltos los
  sobrescriben. Así ve lo que WordPress concede de verdad, no lo que muestra su interfaz, y no la
  engaña el código que oculta usuarios del listado.
- **Número rojo en el menú.** Cuando hay usuarios marcados, «Administradores» y la entrada de primer
  nivel «EscritorioWP» muestran su número, como el contador de actualizaciones de WordPress.
- **Botón «Retirar permisos»** en cada usuario marcado que tenga permisos críticos sueltos. Tras una
  confirmación que lista exactamente qué se va a quitar, retira esos permisos sin tocar su rol. Si lo
  alterado es el rol, la pantalla lo explica pero no lo modifica. Nadie puede retirarse sus propios
  permisos.
- La pantalla y la acción exigen `manage_options` y `list_users`; retirar permisos exige además poder
  cambiar el rol de ese usuario (`promote_user`) y un nonce propio por usuario.

## Capabilities

### New Capabilities

- `auditoria-administradores`: listado de administradores, detección de usuarios con permisos de
  administrador sin el rol, roles alterados y usuarios ocultos, el aviso en el menú y la retirada de
  permisos sueltos.

### Modified Capabilities

- `menu-administracion`: el requisito «Menú de primer nivel» pasa de dos entradas a tres: «Ajustes»,
  «Administradores» y «Acerca de». Nota: esta capacidad la crea el cambio
  `releases-github-y-menu-propio`, que aún no está archivado (le queda la tarea 7.4). Este delta
  modifica un requisito suyo, así que **ese cambio tiene que archivarse antes que este**.

## Impact

- **Plugin:** carpeta nueva `includes/seguridad/` con el cálculo puro de permisos, la lectura de la
  base de datos con su caché y la pantalla con el contador y la acción de retirar; `class-plugin.php`
  (la compone antes que Acerca de, para respetar el orden del menú), `assets/css/ajustes.css` (tabla y
  confirmación), `readme.txt` (descripción y changelog), `languages/escritoriowp.pot`.
- **Base de datos:** solo lectura, salvo el botón «Retirar permisos», que modifica el metadato de
  permisos de ese usuario con la API de WordPress (`WP_User::remove_cap()`). Un transitorio nuevo,
  `escritoriowp_administradores`, que ya borra la desinstalación por su prefijo.
- **Desarrollo:** `tests/prueba-permisos.php` (nuevo), `docs/qa.md`, `CLAUDE.md` (estructura, menú,
  cachés y una sección sobre la detección), `README.md`, `CHANGELOG.md`.
- **Dependencias:** ninguna nueva. Sin JavaScript: la confirmación es una vista del servidor.
- **Rendimiento:** la pantalla consulta siempre en el momento. El contador del menú usa la caché, que
  se invalida con los enganches de WordPress que cambian permisos y caduca a los 15 minutos para
  recoger también los cambios hechos directamente en la base de datos.
- **Fuera de alcance:** permisos concedidos en tiempo de ejecución por código (filtros
  `user_has_cap` o `map_meta_cap`), que no están en la base de datos; corregir roles alterados;
  marcar un usuario como revisado para que deje de contar.
