<?php
/**
 * Pruebas de la plantilla de los avisos por correo.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

use EscritorioWP\Avisos\Plantilla;

grupo( 'Avisos · plantilla' );

$valores = array(
	'evento'      => 'Activación',
	'plugins'     => 'Akismet 5.3',
	'lista'       => '- Akismet 5.3 (akismet/akismet.php)',
	'cantidad'    => 1,
	'usuario'     => 'Ana (ana)',
	'fecha'       => '14 de octubre de 2026 10:30',
	'sitio'       => 'Mi web',
	'url'         => 'https://ejemplo.es/',
	'url_plugins' => 'https://ejemplo.es/wp-admin/plugins.php',
	'ip'          => '203.0.113.7',
);

foreach ( Plantilla::MARCADORES as $marcador ) {
	comprobar( (string) $valores[ $marcador ], Plantilla::rellenar( '{' . $marcador . '}', $valores ), "{{$marcador}} se sustituye" );
}

comprobar( 10, count( Plantilla::MARCADORES ), 'hay diez marcadores' );
comprobar(
	'[Mi web] Activación: Akismet 5.3',
	Plantilla::rellenar( '[{sitio}] {evento}: {plugins}', $valores ),
	'el asunto predeterminado se rellena entero'
);
comprobar( 'Hola {plugn}', Plantilla::rellenar( 'Hola {plugn}', $valores ), 'un marcador desconocido se queda igual' );
comprobar(
	'Prueba {sitio} en Mi web',
	Plantilla::rellenar( '{plugins} en {sitio}', array( 'plugins' => 'Prueba {sitio}', 'sitio' => 'Mi web' ) ),
	'el valor de un marcador no se vuelve a sustituir'
);
comprobar(
	'{otro} y Mi web',
	Plantilla::rellenar( '{otro} y {sitio}', array( 'otro' => 'NO', 'sitio' => 'Mi web' ) ),
	'una clave que no es un marcador no se sustituye'
);
comprobar( '{fecha}', Plantilla::rellenar( '{fecha}', array() ), 'un marcador sin valor se queda igual' );

comprobar( 'a b c d', Plantilla::una_linea( "a\r\nb\nc\td" ), 'una_linea() convierte saltos y tabuladores en un espacio' );
comprobar( 'a b', Plantilla::una_linea( "  a   \n\n  b  " ), 'una_linea() colapsa espacios y recorta los extremos' );
comprobar( 'Activación ñandú', Plantilla::una_linea( "Activación\nñandú" ), 'una_linea() conserva las tildes' );

$dos   = '%1$s y %2$s';
$resto = '%1$s y %2$d más';

comprobar( '', Plantilla::resumir( array(), $dos, $resto ), 'resumir() sin nombres devuelve vacío' );
comprobar( 'A', Plantilla::resumir( array( 'A' ), $dos, $resto ), 'resumir() con un nombre' );
comprobar( 'A y B', Plantilla::resumir( array( 'A', 'B' ), $dos, $resto ), 'resumir() con dos nombres' );
comprobar( 'A, B y C', Plantilla::resumir( array( 'A', 'B', 'C' ), $dos, $resto ), 'resumir() con tres nombres' );
comprobar( 'A, B, C y 2 más', Plantilla::resumir( array( 'A', 'B', 'C', 'D', 'E' ), $dos, $resto ), 'resumir() con cinco nombres' );
comprobar( '100% y B', Plantilla::resumir( array( '100%', 'B' ), $dos, $resto ), 'resumir() no interpreta un % del nombre' );

comprobar(
	'- Akismet 5.3 (akismet/akismet.php)',
	Plantilla::linea( 'Akismet', '5.3', 'akismet/akismet.php', '' ),
	'linea() con nombre, versión y fichero'
);
comprobar(
	'- Hola (hola.php)',
	Plantilla::linea( 'Hola', '', 'hola.php', '' ),
	'linea() sin versión no deja doble espacio'
);
comprobar(
	'- Akismet 5.3 (akismet/akismet.php) — en toda la red',
	Plantilla::linea( 'Akismet', '5.3', 'akismet/akismet.php', 'en toda la red' ),
	'linea() añade la marca de red'
);
