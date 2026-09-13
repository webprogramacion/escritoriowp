<?php
/**
 * Imprime las notas de una versión leyéndolas de CHANGELOG.md.
 *
 * El workflow de publicación usa esta herramienta dos veces: para comprobar que la versión que se
 * va a publicar tiene entrada en el changelog y para generar el cuerpo de la release de GitHub.
 *
 * Uso: php tools/notas-release.php 0.2.0 [ruta/al/CHANGELOG.md]
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

/**
 * Extrae de un changelog en Markdown el cuerpo de la sección de una versión.
 *
 * Función pura: recibe el texto y devuelve el texto, sin tocar el sistema de ficheros. La sección
 * empieza en la cabecera «## [X.Y.Z]» y termina en la siguiente cabecera de nivel dos o al final
 * del fichero. La propia cabecera no se incluye: el título de la release ya es la versión.
 *
 * @param string $markdown Contenido completo del changelog.
 * @param string $version  Versión buscada, sin la «v» inicial.
 * @return string|null Cuerpo de la sección sin espacios sobrantes, o null si no existe o está vacía.
 */
function extraer_seccion_changelog( string $markdown, string $version ): ?string {
	$destino = '## [' . $version . ']';
	$lineas  = preg_split( '/\R/', $markdown );

	if ( false === $lineas ) {
		return null;
	}

	$dentro = false;
	$cuerpo = array();

	foreach ( $lineas as $linea ) {
		if ( str_starts_with( $linea, '## ' ) ) {
			if ( $dentro ) {
				break;
			}

			$dentro = str_starts_with( $linea, $destino );
			continue;
		}

		if ( $dentro ) {
			$cuerpo[] = $linea;
		}
	}

	if ( ! $dentro ) {
		return null;
	}

	$texto = trim( implode( "\n", $cuerpo ) );

	return '' === $texto ? null : $texto;
}

/*
 * Punto de entrada de línea de órdenes. La comparación con $argv[0] permite que las pruebas
 * incluyan este fichero para probar la función sin que se ejecute nada más.
 */
if ( 'cli' === PHP_SAPI && isset( $argv[0] ) && realpath( $argv[0] ) === __FILE__ ) {
	$version = $argv[1] ?? '';

	if ( 1 !== preg_match( '/^\d+\.\d+\.\d+$/', $version ) ) {
		fwrite( STDERR, "Uso: php tools/notas-release.php X.Y.Z [ruta/al/CHANGELOG.md]\n" );
		exit( 1 );
	}

	$ruta = $argv[2] ?? dirname( __DIR__ ) . '/CHANGELOG.md';

	if ( ! is_readable( $ruta ) ) {
		fwrite( STDERR, "No se puede leer $ruta\n" );
		exit( 1 );
	}

	$notas = extraer_seccion_changelog( (string) file_get_contents( $ruta ), $version );

	if ( null === $notas ) {
		fwrite( STDERR, "CHANGELOG.md no tiene ninguna entrada con contenido para la versión $version.\n" );
		exit( 1 );
	}

	echo $notas . "\n";
	exit( 0 );
}
