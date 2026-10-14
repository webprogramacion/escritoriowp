## Context

Motivación en `proposal.md`; comportamiento exigido en `specs/avisos-administradores/spec.md` y en el
delta de `specs/nucleo-plugin/spec.md`.

Lo que condiciona el enfoque, comprobado en el código actual:

- **La clasificación ya existe.** `Seguridad\Detector::analizar()` lee `usermeta` y `users` con `$wpdb`,
  evalúa a cada candidato con `Permisos::evaluar()`, añade a los superadministradores y reparte en
  administradores y marcados. Pero mezcla eso con lo que solo necesita la pantalla: recorta los
  marcados a `LIMITE` (100), comprueba los ocultos, cuenta los roles alterados y guarda el transitorio
  del contador. La revisión necesita la clasificación completa y nada más. `ocultos()` es privada.
- **El envío ya existe.** `Avisos\AvisosPlugins::mandar()` y `destinatarios()` hacen el envío en texto
  plano (cabecera más filtro temporal de `wp_mail_content_type` con `PHP_INT_MAX`, quitado en un
  `finally`) y resuelven los destinatarios vacíos a `admin_email`. Son privadas y no devuelven el
  resultado de `wp_mail()`.
- **Los ajustes.** Una casilla nueva solo necesita su clave en `Ajustes::defectos()` y su campo en
  `registrar_ajustes()`: `sanear()` la trata como booleano. La descripción del campo se pinta con
  `esc_html()` en un único `<p>` y se calcula en `admin_init`, así que puede llevar texto que cambia
  en cada carga.
- **WP-Cron.** No hay intervalo de 6 horas de serie (`hourly`, `twicedaily`, `daily`, `weekly`): hay que
  añadirlo con el filtro `cron_schedules`, y tiene que estar registrado en toda petición, porque
  `wp_schedule_event()` rechaza un intervalo desconocido y `wp-cron.php` lo usa para reprogramar. El
  núcleo programa sus propias comprobaciones en `init` si no están programadas
  (`wp_schedule_update_checks()`), patrón que se repite aquí. `wp-cron.php` carga WordPress entero, así
  que el plugin arranca en `plugins_loaded` y su callback existe.
- **El ciclo de vida.** `escritoriowp.php` tiene gancho de activación pero no de desactivación, y
  `uninstall.php` borra con nombres literales (no carga el autoloader). Las acciones
  `update_option_{opción}` y `add_option_{opción}` reciben el valor ya saneado por
  `sanear_formulario()`; el valor anterior llega tal como estaba en la base de datos.
- **Las pruebas no cargan WordPress**: solo funciones puras.

## Goals / Non-Goals

**Goals:**

- Que la comparación entre dos situaciones, la construcción de la situación y el formato de cada línea
  sean funciones puras con pruebas.
- Que `Detector::analizar()` y la pantalla Administradores se comporten exactamente igual que ahora.
- Que con la casilla apagada no haya ninguna tarea programada ni ninguna consulta extra en cada
  petición.
- Un solo camino de envío en texto plano para los dos tipos de aviso.

**Non-Goals:**

- Cola, reintentos inmediatos o registro de revisiones: si el envío falla se reintenta en la revisión
  siguiente (D6), nada más.
- Saber quién hizo el cambio: la revisión no ve acciones, solo estados.
- Revisar todos los sitios de una red desde uno: cada sitio revisa sus usuarios con su propio ajuste.

## Decisions

### 1. Tres clases nuevas en `includes/avisos/`

| Clase | Responsabilidad | WordPress |
|-------|-----------------|-----------|
| `Avisos\Correo` | Destinatarios (ajuste o `admin_email`) y envío en texto plano; devuelve si `wp_mail()` lo aceptó | Sí |
| `Avisos\CambiosAdministradores` | Situación a partir de los usuarios evaluados, comparación, línea de cada usuario y recorte de apartados | No: funciones puras, probadas |
| `Avisos\AvisosAdministradores` | Intervalo y tarea de WP-Cron, punto de partida, revisión, textos traducibles y redacción del correo | Sí |

`AvisosPlugins::mandar()` pasa a delegar en `Correo::enviar()` sin cambiar lo que hace. `Correo` se
construye con `Ajustes`, como los módulos.

Alternativa descartada: meter la revisión en `includes/seguridad/`. Calcula con `Detector`, pero lo que
añade es un aviso por correo, y `includes/avisos/` ya tiene el envío y las convenciones (textos fijos
traducibles, `try`/`catch`, texto plano). `seguridad/` sigue sin enviar nada.

### 2. `Detector` expone la clasificación sin tocar `analizar()`

- `usuarios_evaluados(): array` — lo que hoy son las primeras líneas de `analizar()`: candidatos,
  detalle, `Permisos::evaluar()` y superadministradores. Todos los candidatos evaluados, por
  identificador, sin límite, sin ocultos y sin transitorio.
- `estado( array $usuario ): string` — `ESTADO_ADMINISTRADOR` si es administrador o superadministrador,
  `ESTADO_MARCADO` si no y tiene críticos, `''` si ninguno. Pura. `analizar()` la usa para repartir, así
  que la regla vive en un solo sitio.
- `ocultos()` pasa a pública, sin cambios.
- `existentes( int[] $ids ): array` — consulta directa a `users` por lotes de `LOTE`, con su
  `phpcs:ignore` justificado como el resto: decir «usuario borrado» no puede depender de
  `get_userdata()`, que es reemplazable. Devuelve los datos básicos de cada usuario que sigue
  existiendo, por identificador, porque quien deja de ser administrador ya no está entre los
  candidatos y el correo tiene que dar su nombre visible y su correo.

`analizar()` queda como `usuarios_evaluados()` más lo que ya hacía después, con el mismo resultado:
los roles alterados se siguen contando sobre todos los candidatos evaluados, no solo sobre
administradores y marcados.

### 3. La situación guardada

Opción `escritoriowp_revision_administradores`, sin carga automática (`update_option( …, false )`):

```php
array(
	'fecha'    => 1760443200,                 // Momento de la revisión que la guardó (marca Unix).
	'usuarios' => array(
		12 => array( 'login' => 'ana', 'estado' => 'administrador' ),
		57 => array( 'login' => 'tienda', 'estado' => 'marcado' ),
	),
)
```

Solo identificador, nombre de usuario y estado: lo mínimo para comparar y para nombrar a un usuario
que ya no existe. El nombre visible, el correo, los críticos y la señal de oculto se leen al redactar,
de los usuarios actuales. Con 5.000 marcados por un rol alterado ocupa unos 200 KB, y por eso no se
carga en cada petición.

Una situación guardada que no es un array con `fecha` entera y `usuarios` array se trata como si no
existiera (D5: punto de partida). `CambiosAdministradores::situacion()` construye `usuarios` a partir de
`usuarios_evaluados()` y `estado()`, descartando los de estado `''`.

Alternativa descartada: un transitorio. Puede desaparecer en cualquier momento (caché de objetos
llena, limpieza de transitorios) y la revisión siguiente lo tomaría como punto de partida, perdiendo
los cambios de ese intervalo sin avisar.

### 4. Comparación

`CambiosAdministradores::comparar( array $antes, array $ahora ): array` devuelve cuatro listas de
identificadores, ordenadas por nombre de usuario:

| Apartado | Condición | Nota en la línea |
|----------|-----------|------------------|
| `administradores_nuevos` | ahora `administrador`, antes no | «antes tenía permisos de administrador sin el rol» si antes `marcado` |
| `marcados_nuevos` | ahora `marcado`, antes no | «antes era administrador» si antes `administrador` |
| `ya_no_administradores` | antes `administrador`, ahora ausente | «usuario borrado» o «sigue existiendo, sin permisos de administrador» |
| `ya_no_marcados` | antes `marcado`, ahora ausente | «usuario borrado» o «sigue existiendo, sin permisos de administrador» |

Un usuario cae como mucho en un apartado. Los que siguen en el mismo estado no cuentan, aunque cambien
sus críticos (fuera de alcance según la spec). Si las cuatro listas están vacías, no hay correo.

### 5. Programación y punto de partida

Constantes en `AvisosAdministradores`: `EVENTO = 'escritoriowp_revisar_administradores'`,
`INTERVALO = 'escritoriowp_seis_horas'` (6 × `HOUR_IN_SECONDS`), `OPCION` (la de D3) y
`MAX_LINEAS = 50`.

`registrar()`, compuesto en `Plugin::arrancar()` fuera de `is_admin()`:

- **Siempre**: el filtro `cron_schedules` con el intervalo; `update_option_escritoriowp_ajustes` y
  `add_option_escritoriowp_ajustes`, para ver el cambio de la casilla; y la acción `EVENTO`, para que
  una tarea que quedara programada con la casilla apagada se quite sola al correr.
- **Solo con la casilla marcada**: `init` → `asegurar_programacion()`, que programa la tarea para dentro
  de 6 horas si `wp_next_scheduled()` no la encuentra. Es lo que la recupera tras reactivar el plugin o
  si alguien la borra. `wp_next_scheduled()` lee la opción `cron`, que ya está cargada.

Al guardar los ajustes (comparando el valor anterior pasado por `Ajustes::sanear()` con el nuevo):

- **Apagada → marcada**: guarda la situación actual con la fecha de ahora (punto de partida, sin
  correo), quita cualquier tarea anterior y programa la primera para dentro de 6 horas.
- **Marcada → apagada**: `wp_clear_scheduled_hook( EVENTO )` y `delete_option( OPCION )`.
- **Sin cambio en la casilla**: nada; guardar otro ajuste no reinicia el ciclo.

`revisar()`, el callback de la tarea:

1. Con la casilla apagada: quita la tarea y termina.
2. Calcula la situación actual. Sin situación guardada válida: la guarda y termina, sin correo.
3. Compara. Sin cambios: guarda la actual (avanza la fecha) y termina.
4. Con cambios: redacta y envía (D6).

Alternativa descartada: programar solo al guardar los ajustes. No se recuperaría tras desactivar y
reactivar el plugin, ni si otro plugin limpia las tareas de WP-Cron.

### 6. Envío y fallo

Asunto fijo y traducible, `[{sitio}] Cambios en los administradores`, pasado por
`Plantilla::una_linea()`. Mensaje con este esquema (texto traducible por partes):

```
La revisión periódica de EscritorioWP ha encontrado cambios en los administradores de {sitio}
({url}) desde la revisión anterior, del {fecha anterior}.

Administradores nuevos (1):
- Ana Pérez (ana, ana@ejemplo.es)

Usuarios nuevos con permisos de administrador sin el rol (2):
- Tienda (tienda, tienda@ejemplo.es): manage_options (asignado al usuario). Oculto en la pantalla Usuarios.
- …
… y 250 más. Míralos en la pantalla Administradores.

Ya no son administradores (1):
- luis: usuario borrado.

Revisa los administradores en {Administradores::url()}

Este aviso compara la situación de ahora con la de la revisión anterior: no dice quién hizo los
cambios ni cuándo exactamente.
```

- Los apartados vacíos no se pintan. El recuento entre paréntesis es el total; se listan `MAX_LINEAS`.
- `CambiosAdministradores::linea( string $login, string $nombre, string $correo, string[] $detalles )`
  da `- Nombre (login, correo): detalle. Detalle.`; sin nombre o igual al login, `- login (correo)`;
  sin datos (usuario borrado), `- login: usuario borrado.`. Los detalles llegan ya traducidos.
- Los críticos de un marcado se agrupan por origen: «manage_options, install_plugins (asignados al
  usuario)», «edit_plugins (del rol «Tienda»)», con `Detector::nombres_roles()`.
- La señal de oculto sale de `Detector::ocultos()` solo con los usuarios que se van a listar en los dos
  primeros apartados (como mucho 100 usuarios, por nombre de usuario). La de borrado, de
  `Detector::existentes()` con los de los dos últimos.
- Fechas con `wp_date()` y los formatos de fecha y hora del sitio.

`Correo::enviar()` devuelve el booleano de `wp_mail()`. `revisar()` guarda la situación nueva **solo si
devuelve `true`**; con `false` o con una excepción (atrapada con `catch ( \Throwable )`) deja la
anterior, y la revisión siguiente vuelve a encontrar esos cambios y la misma fecha de comparación.

Alternativa descartada: guardar siempre, como los avisos de plugins. Allí el aviso perdido es un evento
ya ocurrido; aquí la situación sigue ahí y se puede volver a contar sin coste, y perder el aviso de un
administrador nuevo es justo lo que el usuario quiere evitar.

### 7. Ajustes

- `aviso_administradores` (`false`) en `defectos()` detrás de `aviso_borrar`; campo `casilla` en la
  sección «Avisos por correo» detrás de «Avisar al borrar un plugin», con la etiqueta «Avisar de
  cambios en los administradores».
- Descripción: qué revisa y cada cuánto, que el texto del correo es fijo, y, con la casilla marcada,
  «Última revisión: {fecha}. Próxima: {fecha}.» desde `AvisosAdministradores::estado_revision()`, que
  lee `fecha` de la opción y `wp_next_scheduled()`. Si falta alguna de las dos, se omite esa parte.
- La introducción de la sección pasa a nombrar los dos tipos de aviso, y las descripciones de «Asunto»
  y «Mensaje» dicen que solo se aplican a los avisos de plugins. Las etiquetas no cambian.

### 8. Ciclo de vida

- `escritoriowp.php`: `register_deactivation_hook()` con `wp_clear_scheduled_hook(
  'escritoriowp_revisar_administradores' )`, con el nombre literal y un comentario: así no carga ninguna
  clase del plugin si no se cumplen los requisitos de PHP. Conserva la situación guardada (spec
  «Cambios mientras el plugin estaba desactivado»).
- `uninstall.php`: `delete_option( 'escritoriowp_revision_administradores' )` y
  `wp_clear_scheduled_hook( 'escritoriowp_revisar_administradores' )`, con literales como el resto del
  fichero.

## Risks / Trade-offs

- **[WP-Cron depende de las visitas]** En un sitio sin tráfico o con `DISABLE_WP_CRON` sin tarea en el
  servidor, la revisión se retrasa o no corre → la casilla muestra la próxima revisión, y una fecha
  pasada delata el problema; se explica en `readme.txt` y `docs/qa.md`.
- **[Un atacante deshace el cambio entre dos revisiones]** Un administrador creado y borrado en menos de
  6 horas no avisa → límite declarado en la spec; avisar al instante con enganches queda en
  `BACKLOG.md`.
- **[Un atacante con acceso a la base de datos edita también la situación guardada]** Puede añadirse a
  la opción para no aparecer como nuevo → con ese acceso puede hacer cualquier cosa; la pantalla
  Administradores lo sigue marcando y el contador del menú lo cuenta.
- **[Desactivar el plugin apaga la vigilancia]** → el aviso de desactivación de plugins (si está
  marcado) lo cuenta al momento, y al reactivar se avisa de lo que cambió entre medias.
- **[Un rol alterado marca a miles de usuarios]** → un correo con 50 líneas por apartado y el total; la
  línea de cada uno dice que el permiso viene del rol.
- **[Superadministradores en una red]** Cada sitio con la casilla marcada avisa de los cambios de
  superadministradores, que son de toda la red → varios correos iguales; aceptable, porque cada sitio
  se configura por separado.
- **[Desactivar en toda la red]** El gancho de desactivación corre solo en el sitio actual, así que las
  tareas de los demás sitios siguen programadas sin callback → WordPress las reprograma sin hacer nada;
  inofensivo, y se quitan al reactivar y apagar la casilla. Se documenta.
- **[Dos ejecuciones simultáneas de WP-Cron]** El bloqueo de `doing_cron` lo hace raro, pero podrían
  salir dos correos iguales → aceptable.
- **[Correo con datos personales]** Lleva nombres de usuario y correos → va solo a los destinatarios
  configurados; se menciona en `readme.txt`.

## Migration Plan

- Sin migración: `aviso_administradores` toma `false` al leer la opción, así que no se programa nada
  hasta que alguien marca la casilla.
- Volver a una versión anterior deja una tarea de WP-Cron sin callback y una opción que nadie lee:
  inofensivas. Para limpiarlas, desmarcar la casilla antes de volver.
- Orden de archivado: `releases-github-y-menu-propio`, `menu-posicion-y-color`, `avisos-correo-plugins`,
  `elegir-paneles-e-indicadores` y después este, porque el delta de «Ajustes globales con valores por
  defecto» parte del texto que deja `elegir-paneles-e-indicadores`.
