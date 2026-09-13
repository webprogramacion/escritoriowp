<?php
/**
 * Pruebas de la extracción de notas de release desde el changelog.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

grupo( 'Notas de release' );

$changelog = <<<'MD'
# Registro de cambios

Texto de introducción que no pertenece a ninguna versión.

## [No publicado]

## [0.2.0] - 2026-09-20

### Añadido

- Release automática al empujar a main.
- Menú propio del plugin.

### Corregido

- Un detalle.

## [0.1.10] - 2026-09-15

- Una versión con dos cifras en el parche.

## [0.1.0] - 2026-09-13

### Añadido

- Primera versión.
MD;

comprobar(
	"### Añadido\n\n- Release automática al empujar a main.\n- Menú propio del plugin.\n\n### Corregido\n\n- Un detalle.",
	extraer_seccion_changelog( $changelog, '0.2.0' ),
	'devuelve el cuerpo completo hasta la siguiente versión, sin la cabecera'
);

comprobar(
	"### Añadido\n\n- Primera versión.",
	extraer_seccion_changelog( $changelog, '0.1.0' ),
	'la última sección del fichero llega hasta el final'
);

comprobar(
	'- Una versión con dos cifras en el parche.',
	extraer_seccion_changelog( $changelog, '0.1.10' ),
	'0.1.10 no se confunde con 0.1.1'
);

comprobar( null, extraer_seccion_changelog( $changelog, '0.1.1' ), 'una versión que no existe devuelve null' );
comprobar( null, extraer_seccion_changelog( $changelog, '9.9.9' ), 'una versión inexistente devuelve null' );
comprobar( null, extraer_seccion_changelog( $changelog, '0.2' ), 'una versión incompleta no coincide' );

comprobar(
	null,
	extraer_seccion_changelog( "## [0.3.0] - 2026-10-01\n\n\n## [0.2.0] - 2026-09-20\n\n- Algo.\n", '0.3.0' ),
	'una sección sin contenido se considera inexistente'
);

comprobar(
	'- Con saltos de Windows.',
	extraer_seccion_changelog( "## [1.0.0]\r\n\r\n- Con saltos de Windows.\r\n\r\n## [0.9.0]\r\n", '1.0.0' ),
	'los saltos de línea de Windows se tratan igual'
);
