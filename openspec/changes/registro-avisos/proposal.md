## Why

Los avisos por correo (cambios en los plugins y en los administradores) solo existen en el buzón: si
`wp_mail()` falla, el servidor no tiene correo configurado o el mensaje acaba en spam, el aviso se
pierde y nadie se entera. Para los avisos de plugins, además, el código actual lo dice tal cual: «si
`wp_mail()` falla, el aviso se pierde sin más». El responsable del sitio necesita que lo que el plugin
quiso contarle quede guardado en el propio sitio y poder verlo al entrar en el escritorio.

## What Changes

- **Cada aviso que el plugin intenta enviar se guarda en la base de datos**, en una tabla propia, con
  la fecha, el tipo (Activación, Desactivación, Instalación, Borrado o Administradores), el asunto y el
  mensaje tal como se enviaron, los destinatarios y el estado del envío.
- **Se guarda antes de enviar y se actualiza después**, así que el aviso se conserva aunque el envío
  rompa la petición. Tres estados: «Enviado» (la función de correo de WordPress lo aceptó, que no es lo
  mismo que entregado), «Fallido» (con el motivo que dé WordPress) y «Sin confirmar» (la petición
  terminó antes de saber el resultado).
- **Guardar no interfiere con el envío**: si la base de datos falla, el correo sale igual y no se
  muestran errores.
- **Se conservan para siempre**: ni desactivar el plugin ni el paso del tiempo los borra; solo la
  desinstalación elimina la tabla.
- **Pantalla nueva EscritorioWP › Avisos**, solo para usuarios con `manage_options`, entre
  «Administradores» y «Acerca de»: lista todos los avisos del más reciente al más antiguo, 20 por
  página, con filtros por tipo y por estado, y una vista de detalle con el mensaje completo, los
  destinatarios y el motivo del fallo.
- **Bloque «Avisos por correo» en el escritorio**, visible solo para usuarios con `manage_options`,
  arriba a la derecha: en una columna junto a la cabecera y los indicadores (debajo de los indicadores
  en pantallas estrechas). Muestra los cinco últimos avisos con su tipo, su estado y cuándo se
  enviaron, y enlaza a la pantalla Avisos. Es un bloque propio, fuera del ajuste «Paneles» y del menú
  «Personalizar». No aparece mientras no haya ningún aviso guardado ni ningún aviso activado.
- **Avisos sin leer**, por administrador: una campana con el número en rojo en la cabecera del bloque,
  un punto rojo y el asunto en negrita en cada aviso sin leer del bloque y un contador rojo en
  «EscritorioWP › Avisos» y en «EscritorioWP» del menú lateral. Abrir la pantalla Avisos o pulsar
  «Marcar como leídos» en el bloque los marca todos como leídos para ese usuario; por eso la pantalla
  Avisos los muestra ya leídos.
- **Rutas REST nuevas** `escritoriowp/v1/avisos` (los últimos avisos y cuántos hay sin leer) y
  `escritoriowp/v1/avisos/leidos` (marcarlos como leídos) para el bloque, con la misma comprobación de
  `manage_options` en el servidor.

## Capabilities

### New Capabilities

- `registro-avisos`: registro en la base de datos de los avisos por correo del plugin: qué se guarda,
  cuándo, los estados del envío, la conservación, y la pantalla Avisos con su listado, sus filtros, su
  detalle y su control de acceso.

### Modified Capabilities

- `escritorio-panel`: requisito nuevo «Bloque de últimos avisos» (quién lo ve, cuándo aparece, dónde
  va, qué muestra cada fila, su estado vacío y de error, y la ruta REST que lo alimenta).
- `menu-administracion`: «Menú de primer nivel» pasa a tener cuatro entradas: «Ajustes»,
  «Administradores», «Avisos» y «Acerca de». Este delta parte del texto que deja
  `pantalla-administradores`, sin archivar; hay que archivar ese cambio antes que este.
- `nucleo-plugin`: «Ciclo de vida y desinstalación limpia» incluye la tabla de avisos: la
  desactivación la conserva y la desinstalación la elimina.

## Impact

- **Plugin:** clase nueva `Avisos\Registro` en `includes/avisos/` (tabla, guardar, actualizar el
  estado, consultas) con sus funciones puras; `Avisos\Correo` guarda cada envío y recoge el motivo de
  `wp_mail_failed`, y recibe el tipo del aviso desde `AvisosPlugins` y `AvisosAdministradores`. Pantalla
  nueva `Avisos\PantallaAvisos` en el menú del plugin, controlador REST nuevo en `includes/rest/`,
  bloque nuevo en `Escritorio::pintar_app()` y `assets/js/escritorio.js` (con sus textos en
  `Activos::textos()`), estilos en `escritorio.css` y `ajustes.css`, `Avisos\Lectura` (la marca de
  leídos de cada usuario), el contador de `Menu` que suman Administradores y Avisos, `escritoriowp.php` (crear la tabla
  al activar), `uninstall.php`, `readme.txt` y `languages/escritoriowp.pot`.
- **Base de datos:** tabla nueva `{prefijo}escritoriowp_avisos`, una opción pequeña con la versión de
  su esquema y, por usuario, una opción de usuario con el último aviso visto. En multisitio, una tabla y
  una marca por sitio. Cada aviso guarda IPs, nombres de usuario y correos
  que ya iban en el mensaje, y no se borran solos.
- **Desarrollo:** `tests/prueba-registro.php` (nueva), `tests/ejecutar.php`, `docs/qa.md`, `CLAUDE.md`,
  `README.md`, `CHANGELOG.md` y `BACKLOG.md`.
- **Dependencias:** ninguna nueva.
- **Fuera de alcance:** reenviar un aviso desde el registro, borrar avisos a mano o por antigüedad,
  avisar en el administrador o con un contador propio cuando un envío falla (el contador del menú cuenta
  los avisos sin leer, no los fallidos), marcar como leído un aviso suelto, un widget para el
  escritorio nativo y las herramientas de privacidad de WordPress (exportar y borrar datos personales).
  Reenviar, la caducidad configurable y el contador quedan en `BACKLOG.md`.
