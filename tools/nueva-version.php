<?php
/**
 * Sube la versión del plugin y cierra la sección de novedades del changelog.
 *
 * Hace la parte mecánica de preparar una versión, la que tiene una única respuesta correcta:
 * calcula el número nuevo, lo escribe en los tres sitios donde vive (la cabecera y la constante
 * VERSION de escritoriowp.php, y el «Stable tag» de readme.txt) y convierte «## [No publicado]» de
 * CHANGELOG.md en «## [X.Y.Z] - AAAA-MM-DD», dejando encima una sección vacía nueva. Redactar las
 * novedades y añadir las entradas «= X.Y.Z =» de readme.txt queda fuera: es prosa, y lo hace la
 * skill /nueva-version o quien prepare la versión a mano.
 *
 * Primero calcula los tres contenidos nuevos y solo después los escribe, así que cualquier error
 * deja los ficheros como estaban. No llama a git: las comprobaciones de rama y tags son de la skill.
 *
 * Con --simular calcula y valida sin escribir nada. No exige contenido en «## [No publicado]»,
 * porque la skill lo usa para conocer el número antes de haber escrito las novedades.
 *
 * Con --comprobar no escribe nada: verifica lo mismo que el workflow antes de publicar, es decir,
 * que las tres versiones coinciden y que esa versión tiene sección en CHANGELOG.md y entrada en el
 * changelog de readme.txt.
 *
 * Uso: php tools/nueva-version.php <patch|minor|major|X.Y.Z> [--fecha=AAAA-MM-DD] [--simular]
 *      php tools/nueva-version.php --comprobar
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

require_once __DIR__ . '/empaquetar.php';
require_once __DIR__ . '/notas-release.php';

/**
 * Forma de una versión X.Y.Z, sin ceros a la izquierda.
 */
const PATRON_VERSION = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/';

/**
 * Cabecera de la sección de CHANGELOG.md donde se acumulan las novedades sin publicar.
 */
const CABECERA_NO_PUBLICADO = '## [No publicado]';

/**
 * Calcula la versión nueva a partir de la actual.
 *
 * Función pura. «patch» sube el tercer número, «minor» el segundo y pone el tercero a cero, «major»
 * el primero y pone los otros dos a cero. Una versión explícita solo se acepta si es mayor que la
 * actual.
 *
 * @param string $actual   Versión actual, X.Y.Z.
 * @param string $peticion «patch», «minor», «major» o una versión X.Y.Z.
 * @return string|null Versión nueva, o null si la petición o la versión actual no son válidas, o si
 *                     la versión explícita no avanza.
 */
function calcular_version( string $actual, string $peticion ): ?string {
	if ( 1 !== preg_match( PATRON_VERSION, $actual, $partes ) ) {
		return null;
	}

	$mayor  = (int) $partes[1];
	$menor  = (int) $partes[2];
	$parche = (int) $partes[3];

	return match ( $peticion ) {
		'patch' => sprintf( '%d.%d.%d', $mayor, $menor, $parche + 1 ),
		'minor' => sprintf( '%d.%d.0', $mayor, $menor + 1 ),
		'major' => sprintf( '%d.0.0', $mayor + 1 ),
		default => 1 === preg_match( PATRON_VERSION, $peticion ) && version_compare( $peticion, $actual, '>' )
			? $peticion
			: null,
	};
}

/**
 * Lee la versión de los tres sitios donde se declara.
 *
 * @param string $php    Contenido de escritoriowp.php.
 * @param string $readme Contenido de readme.txt.
 * @return array{cabecera: string|null, constante: string|null, estable: string|null}
 */
function versiones_declaradas( string $php, string $readme ): array {
	return array(
		'cabecera'  => version_de_cabecera( $php ),
		'constante' => 1 === preg_match( "/^[ \t]*const[ \t]+VERSION[ \t]*=[ \t]*'([^']*)'[ \t]*;/m", $php, $constante ) ? $constante[1] : null,
		'estable'   => 1 === preg_match( '/^Stable tag:[ \t]*(\S+)/mi', $readme, $estable ) ? $estable[1] : null,
	);
}

/**
 * Escribe la versión en la cabecera y en la constante VERSION del fichero principal.
 *
 * Función pura: sustituye solo la primera cabecera «Version:» y la primera «const VERSION = '…';»,
 * conservando el relleno de espacios. No toca nada más del fichero.
 *
 * @param string $php     Contenido de escritoriowp.php.
 * @param string $version Versión nueva.
 * @return string
 */
function fijar_version_plugin( string $php, string $version ): string {
	$php = (string) preg_replace( '/^([ \t]*\*[ \t]*Version:[ \t]*)\S+/mi', '${1}' . $version, $php, 1 );

	return (string) preg_replace( "/^([ \t]*const[ \t]+VERSION[ \t]*=[ \t]*')[^']*(')/m", '${1}' . $version . '${2}', $php, 1 );
}

/**
 * Escribe la versión en el «Stable tag» de readme.txt.
 *
 * @param string $readme  Contenido de readme.txt.
 * @param string $version Versión nueva.
 * @return string
 */
function fijar_stable_tag( string $readme, string $version ): string {
	return (string) preg_replace( '/^(Stable tag:[ \t]*)\S+/mi', '${1}' . $version, $readme, 1 );
}

/**
 * Convierte «## [No publicado]» en la sección de una versión y deja otra vacía encima.
 *
 * Función pura. El cuerpo de la sección pasa tal cual a «## [X.Y.Z] - AAAA-MM-DD» y el resto del
 * fichero no cambia.
 *
 * @param string $changelog Contenido de CHANGELOG.md.
 * @param string $version   Versión nueva.
 * @param string $fecha     Fecha de la versión, AAAA-MM-DD.
 * @return string|null Contenido nuevo, o null si no hay «## [No publicado]» o está vacía.
 */
function promover_no_publicado( string $changelog, string $version, string $fecha ): ?string {
	$lineas = preg_split( '/\R/', $changelog );

	if ( false === $lineas ) {
		return null;
	}

	$inicio = indice_no_publicado( $lineas );

	if ( null === $inicio ) {
		return null;
	}

	$total = count( $lineas );
	$fin   = $total;

	for ( $i = $inicio + 1; $i < $total; $i++ ) {
		if ( str_starts_with( $lineas[ $i ], '## ' ) ) {
			$fin = $i;
			break;
		}
	}

	$cuerpo = array_slice( $lineas, $inicio + 1, $fin - $inicio - 1 );

	if ( '' === trim( implode( '', $cuerpo ) ) ) {
		return null;
	}

	$nuevas = array_merge(
		array_slice( $lineas, 0, $inicio ),
		array( CABECERA_NO_PUBLICADO, '', '## [' . $version . '] - ' . $fecha ),
		$cuerpo,
		array_slice( $lineas, $fin )
	);

	return implode( str_contains( $changelog, "\r\n" ) ? "\r\n" : "\n", $nuevas );
}

/**
 * Devuelve la posición de la cabecera «## [No publicado]».
 *
 * @param array<int, string> $lineas Líneas de CHANGELOG.md.
 * @return int|null Índice de la línea, o null si no existe.
 */
function indice_no_publicado( array $lineas ): ?int {
	foreach ( $lineas as $indice => $linea ) {
		if ( CABECERA_NO_PUBLICADO === rtrim( $linea ) ) {
			return $indice;
		}
	}

	return null;
}

/**
 * Indica si el changelog de readme.txt tiene la entrada «= X.Y.Z =».
 *
 * Solo cuenta la sección «== Changelog ==»: las preguntas frecuentes y el aviso de actualización
 * también usan cabeceras «= … =».
 *
 * @param string $readme  Contenido de readme.txt.
 * @param string $version Versión buscada.
 * @return bool
 */
function readme_tiene_entrada( string $readme, string $version ): bool {
	$lineas = preg_split( '/\R/', $readme );
	$dentro = false;

	if ( false === $lineas ) {
		return false;
	}

	foreach ( $lineas as $linea ) {
		$linea = trim( $linea );

		if ( 1 === preg_match( '/^==\s*(.+?)\s*==$/', $linea, $seccion ) ) {
			if ( $dentro ) {
				return false;
			}

			$dentro = 0 === strcasecmp( 'changelog', $seccion[1] );
			continue;
		}

		if ( $dentro && 1 === preg_match( '/^=\s*' . preg_quote( $version, '/' ) . '\s*=$/', $linea ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Comprueba lo mismo que el workflow antes de publicar la versión declarada en la cabecera.
 *
 * @param string $php       Contenido de escritoriowp.php.
 * @param string $readme    Contenido de readme.txt.
 * @param string $changelog Contenido de CHANGELOG.md.
 * @return array<int, string> Un mensaje por problema; vacía si todo cuadra.
 */
function problemas_de_coherencia( string $php, string $readme, string $changelog ): array {
	$versiones = versiones_declaradas( $php, $readme );
	$version   = $versiones['cabecera'];

	if ( null === $version ) {
		return array( 'La cabecera de escritoriowp/escritoriowp.php no declara ninguna versión.' );
	}

	$problemas = array();

	if ( $version !== $versiones['constante'] || $version !== $versiones['estable'] ) {
		$problemas[] = describir_versiones( $versiones );
	}

	if ( null === extraer_seccion_changelog( $changelog, $version ) ) {
		$problemas[] = "CHANGELOG.md no tiene la sección «## [$version]» con contenido.";
	}

	if ( ! readme_tiene_entrada( $readme, $version ) ) {
		$problemas[] = "readme.txt no tiene la entrada «= $version =» en su changelog.";
	}

	return $problemas;
}

/**
 * Describe las tres versiones leídas, para los mensajes de error.
 *
 * @param array{cabecera: string|null, constante: string|null, estable: string|null} $versiones Versiones.
 * @return string
 */
function describir_versiones( array $versiones ): string {
	return sprintf(
		'Las versiones no coinciden: cabecera=%s, constante VERSION=%s, Stable tag=%s.',
		$versiones['cabecera'] ?? '(ninguna)',
		$versiones['constante'] ?? '(ninguna)',
		$versiones['estable'] ?? '(ninguna)'
	);
}

/**
 * Lee un fichero o termina con error.
 *
 * @param string $ruta Ruta del fichero.
 * @return string
 */
function leer_o_salir( string $ruta ): string {
	if ( ! is_readable( $ruta ) ) {
		salir_con_error( "No se puede leer $ruta" );
	}

	return (string) file_get_contents( $ruta );
}

/**
 * Escribe un mensaje en la salida de error y termina con código 1.
 *
 * @param string $mensaje Mensaje.
 * @return never
 */
function salir_con_error( string $mensaje ): never {
	fwrite( STDERR, $mensaje . "\n" );
	exit( 1 );
}

/*
 * Punto de entrada de línea de órdenes. La comparación con $argv[0] permite que las pruebas
 * incluyan este fichero para probar sus funciones sin que se ejecute nada más.
 */
if ( 'cli' === PHP_SAPI && isset( $argv[0] ) && realpath( $argv[0] ) === __FILE__ ) {
	$uso = "Uso: php tools/nueva-version.php <patch|minor|major|X.Y.Z> [--fecha=AAAA-MM-DD] [--simular]\n"
		. '     php tools/nueva-version.php --comprobar';

	$raiz      = dirname( __DIR__ );
	$rutas     = array(
		'php'       => $raiz . '/escritoriowp/escritoriowp.php',
		'readme'    => $raiz . '/escritoriowp/readme.txt',
		'changelog' => $raiz . '/CHANGELOG.md',
	);
	$comprobar = false;
	$simular   = false;
	$fecha     = date( 'Y-m-d' );
	$peticion  = array();

	foreach ( array_slice( $argv, 1 ) as $argumento ) {
		if ( '--comprobar' === $argumento ) {
			$comprobar = true;
		} elseif ( '--simular' === $argumento ) {
			$simular = true;
		} elseif ( str_starts_with( $argumento, '--fecha=' ) ) {
			$fecha = substr( $argumento, strlen( '--fecha=' ) );
		} elseif ( str_starts_with( $argumento, '--' ) ) {
			salir_con_error( "Opción desconocida: $argumento\n$uso" );
		} else {
			$peticion[] = $argumento;
		}
	}

	$php       = leer_o_salir( $rutas['php'] );
	$readme    = leer_o_salir( $rutas['readme'] );
	$changelog = leer_o_salir( $rutas['changelog'] );

	if ( $comprobar ) {
		if ( array() !== $peticion || $simular || count( $argv ) > 2 ) {
			salir_con_error( "--comprobar no admite más argumentos.\n$uso" );
		}

		$problemas = problemas_de_coherencia( $php, $readme, $changelog );

		if ( array() !== $problemas ) {
			salir_con_error( implode( "\n", $problemas ) );
		}

		printf( "Versión %s coherente: cabecera, constante, Stable tag, CHANGELOG.md y readme.txt.\n", version_de_cabecera( $php ) );
		exit( 0 );
	}

	if ( 1 !== count( $peticion ) ) {
		salir_con_error( $uso );
	}

	$peticion = $peticion[0];

	if ( 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}$/', $fecha ) || ! checkdate( (int) substr( $fecha, 5, 2 ), (int) substr( $fecha, 8, 2 ), (int) substr( $fecha, 0, 4 ) ) ) {
		salir_con_error( "Fecha no válida: «{$fecha}». Usa AAAA-MM-DD." );
	}

	$versiones = versiones_declaradas( $php, $readme );
	$actual    = $versiones['cabecera'];

	if ( null === $actual || $actual !== $versiones['constante'] || $actual !== $versiones['estable'] ) {
		salir_con_error( describir_versiones( $versiones ) . ' Corrígelas antes de preparar una versión nueva.' );
	}

	$nueva = calcular_version( $actual, $peticion );

	if ( null === $nueva ) {
		salir_con_error(
			1 === preg_match( PATRON_VERSION, $peticion )
				? "La versión nueva debe ser mayor que $actual."
				: "Petición no válida: «{$peticion}». Usa patch, minor, major o una versión X.Y.Z.\n$uso"
		);
	}

	if ( 1 === preg_match( '/^## \[' . preg_quote( $nueva, '/' ) . '\]/m', $changelog ) ) {
		salir_con_error( "CHANGELOG.md ya tiene una sección «## [$nueva]»." );
	}

	$lineas_changelog = preg_split( '/\R/', $changelog );

	if ( false === $lineas_changelog || null === indice_no_publicado( $lineas_changelog ) ) {
		salir_con_error( 'CHANGELOG.md no tiene la sección «## [No publicado]».' );
	}

	if ( $simular ) {
		echo $nueva . "\n";
		exit( 0 );
	}

	$changelog_nuevo = promover_no_publicado( $changelog, $nueva, $fecha );

	if ( null === $changelog_nuevo ) {
		salir_con_error( 'La sección «## [No publicado]» de CHANGELOG.md está vacía. Anota ahí las novedades antes de preparar la versión.' );
	}

	$php_nuevo    = fijar_version_plugin( $php, $nueva );
	$readme_nuevo = fijar_stable_tag( $readme, $nueva );
	$resultado    = versiones_declaradas( $php_nuevo, $readme_nuevo );

	if ( array( $nueva ) !== array_values( array_unique( $resultado ) ) ) {
		salir_con_error( 'No se ha podido escribir la versión en los tres sitios. ' . describir_versiones( $resultado ) );
	}

	file_put_contents( $rutas['php'], $php_nuevo );
	file_put_contents( $rutas['readme'], $readme_nuevo );
	file_put_contents( $rutas['changelog'], $changelog_nuevo );

	echo $nueva . "\n";
	exit( 0 );
}
