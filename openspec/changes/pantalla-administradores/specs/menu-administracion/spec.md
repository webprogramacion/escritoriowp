## MODIFIED Requirements

### Requirement: Menú de primer nivel

El plugin SHALL registrar un menú de primer nivel «EscritorioWP» en la barra lateral del administrador, visible solo para usuarios con `manage_options`, con tres entradas en este orden: «Ajustes», «Administradores» y «Acerca de». Las pantallas del menú SHALL aparecer en el lanzador como cualquier otra pantalla del administrador.

#### Scenario: Administrador

- **WHEN** un administrador carga cualquier pantalla del administrador
- **THEN** ve el menú «EscritorioWP» con las entradas «Ajustes», «Administradores» y «Acerca de», en ese orden, y al escribir «acerca» en el lanzador aparece la pantalla Acerca de

#### Scenario: Usuario sin permisos

- **WHEN** un editor carga el administrador o accede directamente a `admin.php?page=escritoriowp-acerca`
- **THEN** no ve el menú y WordPress deniega el acceso a la URL
