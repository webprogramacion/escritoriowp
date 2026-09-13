<?php
/**
 * Empaqueta el plugin en un zip instalable desde WordPress.
 *
 * Genera dist/escritoriowp-X.Y.Z.zip con el directorio «escritoriowp/» como única entrada raíz,
 * que es lo que WordPress espera tanto al subir el zip a mano como al actualizar automáticamente.
 * El workflow de publicación usa esta misma herramienta, así que el zip que se prueba en local y
 * el que se publica en GitHub son idénticos.
 *
 * Con la opción --wordpress-org genera además la variante que se puede enviar al directorio
 * oficial: sin el módulo de actualizaciones desde GitHub, sin la cabecera «Update URI» y con la
 * constante del repositorio vacía, porque las directrices del directorio prohíben servir
 * actualizaciones desde fuera de WordPress.org. El árbol de trabajo no se modifica.
 *
 * Uso: php tools/empaquetar.php [--wordpress-org]
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

/**
 * Patrones de ficheros que nunca entran en el zip.
 *
 * @var array<int, string>
 */
const EXCLUSIONES = array( '.DS_Store', 'Thumbs.db', '*.map' );

/**
 * Directorio del plugin que solo existe en la variante distribuida por GitHub.
 */
const DIRECTORIO_ACTUALIZACIONES = 'includes/actualizaciones/';

/**
 * Indica si un fichero del plugin debe quedar fuera del zip.
 *
 * Función pura: recibe la ruta relativa al directorio del plugin y decide, sin tocar el disco.
 *
 * @param string $relativa Ruta relativa dentro del plugin, con barras normales.
 * @return bool
 */
function debe_excluirse( string $relativa ): bool {
	$nombre = basename( $relativa );

	foreach ( EXCLUSIONES as $patron ) {
		if ( fnmatch( $patron, $nombre ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Devuelve el fichero principal del plugin preparado para el directorio de WordPress.org.
 *
 * Función pura: quita la cabecera «Update URI» y vacía la constante del repositorio, que es lo que
 * impide que el módulo de actualizaciones se registre. No toca nada más del fichero.
 *
 * @param string $php Contenido del fichero principal del plugin.
 * @return string Contenido transformado.
 */
function preparar_para_wordpress_org( string $php ): string {
	$php = (string) preg_replace( '/^[ \t]*\*[ \t]*Update URI:.*\R/mi', '', $php );

	return (string) preg_replace( "/(const\s+REPOSITORIO\s*=\s*)'[^']*'/", "$1''", $php );
}

/**
 * Indica si el fichero principal ya no ofrece actualizaciones propias.
 *
 * @param string $php Contenido del fichero principal del plugin.
 * @return bool
 */
function sin_actualizador( string $php ): bool {
	return 1 !== preg_match( '/Update URI:/i', $php )
		&& 1 === preg_match( "/const\s+REPOSITORIO\s*=\s*''\s*;/", $php );
}

/**
 * Lee la versión declarada en la cabecera del plugin.
 *
 * @param string $php Contenido del fichero principal del plugin.
 * @return string|null Versión, o null si la cabecera no la declara.
 */
function version_de_cabecera( string $php ): ?string {
	if ( 1 === preg_match( '/^\s*\*\s*Version:\s*(\S+)/mi', $php, $coincidencia ) ) {
		return $coincidencia[1];
	}

	return null;
}

/**
 * Recorre un directorio y devuelve las rutas relativas de sus ficheros, ordenadas.
 *
 * @param string $directorio Directorio a recorrer.
 * @return array<int, string>
 */
function ficheros_del_plugin( string $directorio ): array {
	$iterador = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $directorio, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::SELF_FIRST
	);

	$rutas = array();

	foreach ( $iterador as $fichero ) {
		if ( ! $fichero->isFile() ) {
			continue;
		}

		$rutas[] = str_replace( '\\', '/', substr( $fichero->getPathname(), strlen( $directorio ) + 1 ) );
	}

	sort( $rutas );

	return $rutas;
}

/*
 * Punto de entrada de línea de órdenes. La comparación con $argv[0] permite que las pruebas
 * incluyan este fichero para probar sus funciones sin que se ejecute nada más.
 */
if ( 'cli' === PHP_SAPI && isset( $argv[0] ) && realpath( $argv[0] ) === __FILE__ ) {
	$raiz      = dirname( __DIR__ );
	$origen    = $raiz . '/escritoriowp';
	$destino   = $raiz . '/dist';
	$para_org  = in_array( '--wordpress-org', array_slice( $argv, 1 ), true );
	$sobrantes = array_diff( array_slice( $argv, 1 ), array( '--wordpress-org' ) );

	if ( array() !== $sobrantes ) {
		fwrite( STDERR, "Uso: php tools/empaquetar.php [--wordpress-org]\n" );
		exit( 1 );
	}

	if ( ! is_dir( $origen ) ) {
		fwrite( STDERR, "No se encuentra el directorio del plugin en $origen\n" );
		exit( 1 );
	}

	$principal = $origen . '/escritoriowp.php';
	$cabecera  = (string) file_get_contents( $principal );
	$version   = version_de_cabecera( $cabecera );

	if ( null === $version ) {
		fwrite( STDERR, "La cabecera de $principal no declara ninguna versión.\n" );
		exit( 1 );
	}

	if ( ! is_dir( $destino ) && ! mkdir( $destino, 0755, true ) && ! is_dir( $destino ) ) {
		fwrite( STDERR, "No se puede crear $destino\n" );
		exit( 1 );
	}

	$zip    = $destino . '/escritoriowp-' . $version . ( $para_org ? '-wordpress-org' : '' ) . '.zip';
	$activo = new ZipArchive();

	if ( file_exists( $zip ) ) {
		unlink( $zip );
	}

	if ( true !== $activo->open( $zip, ZipArchive::CREATE ) ) {
		fwrite( STDERR, "No se puede escribir $zip\n" );
		exit( 1 );
	}

	$incluidos = 0;

	foreach ( ficheros_del_plugin( $origen ) as $relativa ) {
		if ( debe_excluirse( $relativa ) ) {
			continue;
		}

		if ( $para_org && str_starts_with( $relativa, DIRECTORIO_ACTUALIZACIONES ) ) {
			continue;
		}

		if ( $para_org && 'escritoriowp.php' === $relativa ) {
			$preparado = preparar_para_wordpress_org( $cabecera );

			if ( ! sin_actualizador( $preparado ) ) {
				fwrite( STDERR, "No se ha podido quitar la cabecera «Update URI» o vaciar la constante del repositorio.\n" );
				$activo->close();
				unlink( $zip );
				exit( 1 );
			}

			$activo->addFromString( 'escritoriowp/' . $relativa, $preparado );
			++$incluidos;
			continue;
		}

		$activo->addFile( $origen . '/' . $relativa, 'escritoriowp/' . $relativa );
		++$incluidos;
	}

	$activo->close();

	// El zip solo sirve si WordPress lo instala en wp-content/plugins/escritoriowp/.
	$comprobacion = new ZipArchive();
	$comprobacion->open( $zip );
	$raices = array();

	for ( $i = 0; $i < $comprobacion->numFiles; $i++ ) {
		$raices[ strtok( (string) $comprobacion->getNameIndex( $i ), '/' ) ] = true;
	}

	$comprobacion->close();

	if ( array( 'escritoriowp' ) !== array_keys( $raices ) ) {
		fwrite( STDERR, 'El zip tiene entradas raíz inesperadas: ' . implode( ', ', array_keys( $raices ) ) . "\n" );
		unlink( $zip );
		exit( 1 );
	}

	printf( "Generado %s con %d ficheros.\n", str_replace( $raiz . '/', '', $zip ), $incluidos );
	exit( 0 );
}
