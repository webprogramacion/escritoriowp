## MODIFIED Requirements

### Requirement: Página de ajustes

El plugin SHALL ofrecer la pantalla de ajustes como primera entrada de su menú propio «EscritorioWP» (`admin.php?page=escritoriowp`), visible solo para usuarios con capacidad `manage_options`, desde la que modificar todos los ajustes globales con protección contra CSRF y saneamiento de cada valor. La subpágina antigua en Ajustes › EscritorioWP deja de existir.

#### Scenario: Guardado correcto

- **WHEN** un administrador cambia "Sustituir el escritorio nativo" a "No" y guarda
- **THEN** la opción se actualiza, se muestra un aviso de éxito y el escritorio nativo vuelve a mostrarse en la siguiente carga

#### Scenario: Usuario sin permisos

- **WHEN** un usuario sin `manage_options` intenta acceder a la URL de la página de ajustes
- **THEN** WordPress deniega el acceso y no se muestra ningún ajuste

#### Scenario: Valor inválido

- **WHEN** se envía un número de elementos por panel fuera del rango permitido (menos de 3 o más de 20)
- **THEN** el valor se ajusta al límite más cercano y se guarda sin error

#### Scenario: URL antigua

- **WHEN** se accede a `options-general.php?page=escritoriowp`
- **THEN** WordPress no encuentra la página y no se muestra ningún formulario del plugin

### Requirement: Cabecera y requisitos mínimos

El plugin SHALL declarar en su cabecera `Requires at least: 6.7`, `Requires PHP: 8.1`, `Text Domain: escritoriowp`, `Update URI: https://github.com/webprogramacion/escritoriowp` y una versión SemVer; y SHALL comprobar en el arranque que la instalación cumple esos requisitos. La cabecera `Update URI` SHALL impedir que WordPress.org ofrezca actualizaciones para este slug.

#### Scenario: Entorno compatible

- **WHEN** WordPress ≥ 6.7 y PHP ≥ 8.1
- **THEN** el plugin carga todos sus módulos

#### Scenario: Entorno incompatible

- **WHEN** la versión de PHP o de WordPress es inferior a la mínima
- **THEN** el plugin no carga ningún módulo funcional, muestra un aviso en el administrador indicando el requisito incumplido y no provoca ningún error fatal

#### Scenario: Slug coincidente en WordPress.org

- **WHEN** existe en WordPress.org un plugin distinto con el slug `escritoriowp`
- **THEN** WordPress no ofrece ese plugin como actualización de esta instalación
