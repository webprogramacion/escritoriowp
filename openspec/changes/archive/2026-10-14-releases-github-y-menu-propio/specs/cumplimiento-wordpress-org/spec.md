## Purpose

Garantizar de forma continua que el código y el empaquetado del plugin cumplen las directrices y las comprobaciones técnicas del directorio oficial de plugins de WordPress.org, para poder enviarlo cuando se decida.

## ADDED Requirements

### Requirement: Plugin Check sin errores

El directorio `escritoriowp/` SHALL pasar Plugin Check, la herramienta oficial de revisión del directorio, sin errores en cada ejecución de integración continua. Cada aviso que se decida tolerar SHALL estar excluido de forma explícita en la configuración del workflow con un comentario que lo justifique.

#### Scenario: Ejecución en CI

- **WHEN** se ejecuta el workflow sobre `main`
- **THEN** el paso de Plugin Check termina sin errores y los únicos avisos presentes son los excluidos explícitamente

#### Scenario: Error nuevo

- **WHEN** un commit introduce código que Plugin Check clasifica como error (por ejemplo, salida sin escapar o una llamada remota sin documentar)
- **THEN** el workflow falla y no se publica release

### Requirement: Declaración de servicios externos

El `readme.txt` SHALL documentar todo servicio externo al que el plugin se conecte, indicando qué servicio es, para qué se usa, en qué circunstancias se envía cada petición y enlaces a sus condiciones de uso y política de privacidad.

#### Scenario: Consulta a GitHub

- **WHEN** se lee el `readme.txt` distribuido
- **THEN** existe una sección que declara la consulta a la API de GitHub para comprobar actualizaciones, cuándo se produce, que no envía datos del sitio ni del usuario, y enlaza a las condiciones y a la privacidad de GitHub

### Requirement: Variante para WordPress.org

El plugin SHALL poder empaquetarse sin el actualizador desde GitHub ni la cabecera `Update URI` con un único comando documentado, sin cambios manuales en el resto del código, para cumplir la directriz que prohíbe servir actualizaciones desde fuera de WordPress.org.

#### Scenario: Empaquetado para el directorio

- **WHEN** se ejecuta el comando de empaquetado con la opción de WordPress.org
- **THEN** el zip resultante no contiene el módulo de actualizaciones, su cabecera no tiene `Update URI`, la constante del repositorio está vacía y el plugin arranca y pasa Plugin Check igualmente

### Requirement: Guía de publicación

El repositorio SHALL incluir en `docs/` una lista de comprobación para enviar el plugin al directorio oficial: requisitos del `readme.txt`, capturas y recursos gráficos, cabeceras, licencia, `Tested up to`, variante sin actualizador y proceso de envío y de subida a SVN.

#### Scenario: Consulta de la guía

- **WHEN** alguien se propone enviar el plugin al directorio
- **THEN** encuentra en `docs/` todos los pasos y puede completar el envío sin consultar otra documentación interna
