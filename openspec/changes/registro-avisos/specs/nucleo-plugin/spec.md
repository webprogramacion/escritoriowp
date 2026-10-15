## MODIFIED Requirements

### Requirement: Ciclo de vida y desinstalación limpia

La desactivación del plugin SHALL conservar ajustes, preferencias, avisos guardados y cualquier otro dato guardado, y SHALL quitar las tareas programadas del plugin; la desinstalación (borrado desde Plugins) SHALL eliminar la opción de ajustes, cualquier otra opción propia, los metadatos de usuario creados por el plugin, cualquier transitorio propio, sus tareas programadas y la tabla de avisos guardados.

#### Scenario: Desactivar y reactivar

- **WHEN** el plugin se desactiva y se vuelve a activar
- **THEN** los ajustes, las preferencias de usuario y los avisos guardados previos se conservan

#### Scenario: Desactivar con tareas programadas

- **WHEN** con el aviso de cambios en los administradores activado, el plugin se desactiva
- **THEN** WP-Cron no conserva ninguna tarea del plugin, y la última situación revisada sigue guardada

#### Scenario: Desinstalar

- **WHEN** el plugin se borra desde la pantalla de Plugins
- **THEN** no queda en la base de datos ninguna opción, metadato de usuario, transitorio, tarea de WP-Cron ni tabla con el prefijo del plugin
