## Why

La pantalla Administradores detecta puertas traseras, pero solo sirve a quien la abre: un
administrador creado por un atacante, o un suscriptor al que se le ha colado `manage_options`, puede
pasar semanas sin que nadie mire. Lo que el responsable del sitio necesita es enterarse sin tener que
entrar, y con el mismo criterio que ya usa esa pantalla, que lee la base de datos y no se deja
engañar por un código que esconde usuarios.

## What Changes

- **Casilla nueva «Avisar de cambios en los administradores»** en la sección «Avisos por correo» de
  EscritorioWP › Ajustes. Desmarcada por defecto: nadie empieza a recibir correos al actualizar.
- **Revisión cada 6 horas con WP-Cron** mientras la casilla está marcada. Cada revisión clasifica a
  los usuarios con las reglas de la pantalla Administradores (administradores, superadministradores en
  multisitio, y usuarios con permisos críticos sin el rol) y compara el resultado con el de la
  revisión anterior.
- **Qué avisa**, cada usuario en un solo apartado:
  - **Administradores nuevos**: quien no lo era en la revisión anterior, también si antes tenía
    permisos de administrador sin el rol.
  - **Permisos de administrador sin el rol, nuevos**: quien no estaba marcado, también si antes era
    administrador y le han quitado el rol pero no los permisos.
  - **Ya no son administradores**: se le ha quitado el rol o se ha borrado el usuario, y el correo
    dice cuál de las dos.
  - **Ya no tienen permisos de administrador**: el usuario marcado que deja de estarlo o se borra.
- **Un solo correo por revisión** con todos los cambios, y ninguno si no hay cambios. Dice desde
  cuándo se compara, nombra a cada usuario con su nombre de usuario, nombre visible y correo, añade sus
  permisos críticos a los marcados, señala a los que algún código esconde de la pantalla Usuarios y
  enlaza a la pantalla Administradores. Cada apartado lista como mucho 50 usuarios y dice cuántos
  quedan.
- **Punto de partida al activar la casilla**: se guarda la situación de ese momento sin enviar nada,
  así que lo que ya existía no avisa (ya lo enseña la pantalla Administradores). Desmarcarla quita la
  revisión programada y borra el punto de partida.
- **Comparte los destinatarios** con los avisos de plugins. El texto del correo es fijo y traducible:
  el asunto y el mensaje configurables siguen siendo solo de los avisos de plugins, porque sus
  marcadores hablan de plugins.
- **Si el envío falla, no se pierde el cambio**: la situación guardada solo avanza cuando hay correo
  aceptado o no había nada que contar, así que la siguiente revisión lo vuelve a avisar.
- **La casilla muestra cuándo fue la última revisión y cuándo toca la próxima**, para comprobar que
  WP-Cron la está ejecutando.
- **Desactivar el plugin quita la revisión programada** y conserva la última situación guardada: al
  reactivarlo, la primera revisión avisa también de lo que cambió mientras estaba desactivado.

## Capabilities

### New Capabilities

- `avisos-administradores`: avisos por correo de los cambios en los administradores y en los usuarios
  con permisos de administrador sin el rol: el interruptor, la revisión periódica, el punto de
  partida, qué cambios avisan, el contenido del correo, los destinatarios y qué queda fuera.

### Modified Capabilities

- `nucleo-plugin`:
  - «Ajustes globales con valores por defecto» incorpora el aviso de cambios en los administradores,
    desmarcado. Este delta parte del texto que deja `elegir-paneles-e-indicadores`, completo pero sin
    archivar; el orden de archivado es `releases-github-y-menu-propio`, `menu-posicion-y-color`,
    `avisos-correo-plugins`, `elegir-paneles-e-indicadores` y después este.
  - «Ciclo de vida y desinstalación limpia»: la desactivación quita las tareas programadas del plugin,
    y la desinstalación borra también las opciones propias distintas de los ajustes.

## Impact

- **Plugin:** clases nuevas en `includes/avisos/`: el envío común en texto plano (que pasa a usar
  también `AvisosPlugins`), la comparación de situaciones (funciones puras) y el módulo que programa la
  revisión y envía el aviso. `includes/seguridad/class-detector.php` expone la clasificación completa de
  usuarios, sin el límite de 100 de la pantalla, y la comprobación de usuarios ocultos y existentes.
  `includes/class-ajustes.php` (la casilla, su descripción con las fechas y los textos de la sección),
  `includes/class-plugin.php` (lo compone fuera del administrador, porque la revisión corre en
  WP-Cron), `escritoriowp.php` (gancho de desactivación), `uninstall.php`, `readme.txt` y
  `languages/escritoriowp.pot`.
- **Base de datos:** una clave nueva en `escritoriowp_ajustes`, una opción nueva sin carga automática
  con la última situación revisada (identificador, nombre de usuario y estado de cada administrador y
  usuario marcado) y una tarea de WP-Cron con un intervalo propio de 6 horas.
- **Desarrollo:** `tests/prueba-cambios-administradores.php` (nueva) y `tests/prueba-ajustes.php`,
  `docs/qa.md`, `CLAUDE.md`, `README.md`, `CHANGELOG.md` y `BACKLOG.md`.
- **Dependencias:** ninguna nueva. Depende de que WP-Cron funcione: en un sitio sin visitas o con
  `DISABLE_WP_CRON` y sin tarea en el servidor, la revisión se retrasa.
- **Fuera de alcance:** avisar al instante con los enganches de WordPress, saber quién hizo el cambio y
  desde qué IP (la revisión compara listas, no ve la acción), avisar de un cambio que se deshace entre
  dos revisiones, de los cambios en los permisos críticos de un usuario que sigue marcado y de los
  cambios en la definición de los roles, un intervalo configurable y un botón «Revisar ahora». Los dos
  últimos y el aviso al instante quedan en `BACKLOG.md`.
