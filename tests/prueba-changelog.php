<?php
/**
 * Pruebas de la lectura del historial de cambios del readme.txt.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

use EscritorioWP\Changelog;

grupo( 'Changelog · lectura del readme' );

$readme = <<<'TXT'
=== EscritorioWP ===
Stable tag: 0.2.0

Descripción corta del plugin.

== Description ==

Un párrafo que no es un cambio.

* Una viñeta que tampoco es un cambio, porque está fuera del changelog.

== Changelog ==

= 0.2.0 =
* Release automática al empujar a main.
* Menú propio con Ajustes y Acerca de.

Una línea suelta que no es una viñeta.

= 0.1.0 =
* Primera versión.

== Upgrade Notice ==

= 0.2.0 =
Actualiza para tener el menú propio.
TXT;

$versiones = Changelog::desde_readme( $readme );

comprobar( 2, count( $versiones ), 'encuentra las dos versiones del changelog' );
comprobar( '0.2.0', $versiones[0]['version'], 'la primera versión es la más reciente' );
comprobar( '0.1.0', $versiones[1]['version'], 'la segunda es la anterior' );
comprobar(
	array( 'Release automática al empujar a main.', 'Menú propio con Ajustes y Acerca de.' ),
	$versiones[0]['cambios'],
	'recoge las viñetas de la versión'
);
comprobar( array( 'Primera versión.' ), $versiones[1]['cambios'], 'recoge las viñetas de la versión anterior' );

comprobar( array(), Changelog::desde_readme( "=== Plugin ===\n\n== Description ==\n\nSin changelog.\n" ), 'sin sección de changelog devuelve una lista vacía' );
comprobar( array(), Changelog::desde_readme( '' ), 'un readme vacío devuelve una lista vacía' );

$al_final = Changelog::desde_readme( "== Changelog ==\n\n= 1.0.0 =\n* Última sección del fichero.\n" );

comprobar( 1, count( $al_final ), 'la sección final del fichero también se lee' );
comprobar( array( 'Última sección del fichero.' ), $al_final[0]['cambios'], 'con sus viñetas' );

$sin_viñetas = Changelog::desde_readme( "== Changelog ==\n\n= 1.0.0 =\nUn texto sin viñetas.\n" );

comprobar( 1, count( $sin_viñetas ), 'una versión sin viñetas sigue apareciendo' );
comprobar( array(), $sin_viñetas[0]['cambios'], 'pero sin cambios' );

comprobar(
	1,
	count( Changelog::desde_readme( "== changelog ==\n\n= 1.0.0 =\n* Cabecera en minúsculas.\n" ) ),
	'la cabecera de la sección no distingue mayúsculas'
);
