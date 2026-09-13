<?php
/**
 * Genera languages/escritoriowp.pot recorriendo el código del plugin.
 *
 * Alternativa a «wp i18n make-pot» para entornos sin wp-cli.
 *
 * Uso: php tools/generar-pot.php
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

const DOMINIO = 'escritoriowp';

/**
 * Funciones de traducción reconocidas y la posición de cada argumento.
 *
 * Cada entrada indica en qué posición (base 0) están el texto, el plural, el contexto y el dominio.
 *
 * @var array<string, array{texto: int, plural: int|null, contexto: int|null, dominio: int}>
 */
const FUNCIONES = array(
	'__'          => array( 'texto' => 0, 'plural' => null, 'contexto' => null, 'dominio' => 1 ),
	'_e'          => array( 'texto' => 0, 'plural' => null, 'contexto' => null, 'dominio' => 1 ),
	'esc_html__'  => array( 'texto' => 0, 'plural' => null, 'contexto' => null, 'dominio' => 1 ),
	'esc_html_e'  => array( 'texto' => 0, 'plural' => null, 'contexto' => null, 'dominio' => 1 ),
	'esc_attr__'  => array( 'texto' => 0, 'plural' => null, 'contexto' => null, 'dominio' => 1 ),
	'esc_attr_e'  => array( 'texto' => 0, 'plural' => null, 'contexto' => null, 'dominio' => 1 ),
	'_x'          => array( 'texto' => 0, 'plural' => null, 'contexto' => 1, 'dominio' => 2 ),
	'esc_html_x'  => array( 'texto' => 0, 'plural' => null, 'contexto' => 1, 'dominio' => 2 ),
	'esc_attr_x'  => array( 'texto' => 0, 'plural' => null, 'contexto' => 1, 'dominio' => 2 ),
	'_n'          => array( 'texto' => 0, 'plural' => 1, 'contexto' => null, 'dominio' => 3 ),
	'_nx'         => array( 'texto' => 0, 'plural' => 1, 'contexto' => 3, 'dominio' => 4 ),
);

/**
 * Recorre un fichero PHP y extrae sus cadenas traducibles.
 *
 * @param string $ruta     Ruta del fichero.
 * @param string $relativa Ruta relativa para las referencias.
 * @return array<string, array<string, mixed>> Cadenas indexadas por clave única.
 */
function extraer( string $ruta, string $relativa ): array {
	$tokens  = token_get_all( (string) file_get_contents( $ruta ) );
	$cadenas = array();
	$total   = count( $tokens );

	for ( $i = 0; $i < $total; $i++ ) {
		$token = $tokens[ $i ];

		if ( ! is_array( $token ) || T_STRING !== $token[0] || ! isset( FUNCIONES[ $token[1] ] ) ) {
			continue;
		}

		// Descarta métodos y propiedades con el mismo nombre.
		$anterior = anterior_significativo( $tokens, $i );

		if ( is_array( $anterior ) && in_array( $anterior[0], array( T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION ), true ) ) {
			continue;
		}

		$argumentos = argumentos( $tokens, $i );

		if ( null === $argumentos ) {
			continue;
		}

		$mapa    = FUNCIONES[ $token[1] ];
		$dominio = $argumentos[ $mapa['dominio'] ] ?? null;

		if ( DOMINIO !== $dominio ) {
			continue;
		}

		$texto = $argumentos[ $mapa['texto'] ] ?? null;

		if ( null === $texto ) {
			continue;
		}

		$contexto  = null === $mapa['contexto'] ? null : ( $argumentos[ $mapa['contexto'] ] ?? null );
		$plural    = null === $mapa['plural'] ? null : ( $argumentos[ $mapa['plural'] ] ?? null );
		$clave     = ( $contexto ?? '' ) . "\x04" . $texto;
		$comentario = comentario_traductores( $tokens, $i );

		if ( ! isset( $cadenas[ $clave ] ) ) {
			$cadenas[ $clave ] = array(
				'texto'       => $texto,
				'plural'      => $plural,
				'contexto'    => $contexto,
				'comentarios' => array(),
				'referencias' => array(),
			);
		}

		$cadenas[ $clave ]['referencias'][] = $relativa . ':' . $token[2];

		if ( null !== $comentario ) {
			$cadenas[ $clave ]['comentarios'][ $comentario ] = true;
		}
	}

	return $cadenas;
}

/**
 * Devuelve el token significativo anterior a una posición.
 *
 * @param array<int, mixed> $tokens Lista de tokens.
 * @param int               $indice Posición de referencia.
 * @return mixed
 */
function anterior_significativo( array $tokens, int $indice ): mixed {
	for ( $i = $indice - 1; $i >= 0; $i-- ) {
		if ( is_array( $tokens[ $i ] ) && in_array( $tokens[ $i ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
			continue;
		}

		return $tokens[ $i ];
	}

	return null;
}

/**
 * Extrae los argumentos literales de una llamada.
 *
 * Devuelve null si la llamada no empieza por paréntesis. Los argumentos que no son cadenas
 * literales quedan como null.
 *
 * @param array<int, mixed> $tokens Lista de tokens.
 * @param int               $indice Posición del nombre de la función.
 * @return array<int, string|null>|null
 */
function argumentos( array $tokens, int $indice ): ?array {
	$i = $indice + 1;

	while ( isset( $tokens[ $i ] ) && is_array( $tokens[ $i ] ) && T_WHITESPACE === $tokens[ $i ][0] ) {
		$i++;
	}

	if ( ! isset( $tokens[ $i ] ) || '(' !== $tokens[ $i ] ) {
		return null;
	}

	$profundidad  = 0;
	$argumentos   = array();
	$actual       = null;
	$solo_literal = true;

	for ( ; isset( $tokens[ $i ] ); $i++ ) {
		$token = $tokens[ $i ];

		if ( '(' === $token ) {
			$profundidad++;

			if ( 1 === $profundidad ) {
				continue;
			}
		}

		if ( ')' === $token ) {
			$profundidad--;

			if ( 0 === $profundidad ) {
				$argumentos[] = $solo_literal ? $actual : null;
				break;
			}
		}

		if ( 1 === $profundidad && ',' === $token ) {
			$argumentos[]  = $solo_literal ? $actual : null;
			$actual        = null;
			$solo_literal  = true;
			continue;
		}

		if ( is_array( $token ) && T_WHITESPACE === $token[0] ) {
			continue;
		}

		if ( is_array( $token ) && T_CONSTANT_ENCAPSED_STRING === $token[0] ) {
			if ( null === $actual ) {
				$actual = literal( $token[1] );
			} else {
				$actual .= literal( $token[1] );
			}
			continue;
		}

		if ( '.' === $token ) {
			continue;
		}

		$solo_literal = false;
	}

	return $argumentos;
}

/**
 * Convierte un literal PHP en su valor.
 *
 * @param string $literal Literal tal y como aparece en el código.
 * @return string
 */
function literal( string $literal ): string {
	$comilla = $literal[0];
	$interno = substr( $literal, 1, -1 );

	if ( "'" === $comilla ) {
		return str_replace( array( "\\'", '\\\\' ), array( "'", '\\' ), $interno );
	}

	return stripcslashes( $interno );
}

/**
 * Busca el comentario «translators:» que precede a una llamada.
 *
 * @param array<int, mixed> $tokens Lista de tokens.
 * @param int               $indice Posición del nombre de la función.
 * @return string|null
 */
function comentario_traductores( array $tokens, int $indice ): ?string {
	for ( $i = $indice - 1; $i >= 0 && $i > $indice - 12; $i-- ) {
		$token = $tokens[ $i ];

		if ( ! is_array( $token ) ) {
			continue;
		}

		if ( T_WHITESPACE === $token[0] ) {
			continue;
		}

		if ( T_COMMENT === $token[0] ) {
			$texto = trim( preg_replace( '#^/\*+|\*+/$|^//#', '', $token[1] ) ?? '' );

			if ( stripos( $texto, 'translators:' ) === 0 ) {
				return trim( $texto );
			}
		}

		if ( ! in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
			break;
		}
	}

	return null;
}

/**
 * Escapa una cadena para el formato PO.
 *
 * @param string $texto Texto a escapar.
 * @return string
 */
function escapar( string $texto ): string {
	return str_replace(
		array( '\\', '"', "\n", "\t", "\r" ),
		array( '\\\\', '\\"', '\\n', '\\t', '\\r' ),
		$texto
	);
}

$raiz    = dirname( __DIR__ );
$origen  = $raiz . '/escritoriowp';
$destino = $origen . '/languages/escritoriowp.pot';

$iterador = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $origen ) );
$cadenas  = array();

foreach ( $iterador as $archivo ) {
	if ( ! $archivo->isFile() || 'php' !== $archivo->getExtension() ) {
		continue;
	}

	$relativa = str_replace( $raiz . '/', '', $archivo->getPathname() );

	foreach ( extraer( $archivo->getPathname(), $relativa ) as $clave => $cadena ) {
		if ( isset( $cadenas[ $clave ] ) ) {
			$cadenas[ $clave ]['referencias'] = array_merge( $cadenas[ $clave ]['referencias'], $cadena['referencias'] );
			$cadenas[ $clave ]['comentarios'] = $cadenas[ $clave ]['comentarios'] + $cadena['comentarios'];
			continue;
		}

		$cadenas[ $clave ] = $cadena;
	}
}

ksort( $cadenas );

$fecha    = gmdate( 'Y-m-d H:iO' );
$cabecera = <<<POT
# Copyright (C) webprogramacion.es
# This file is distributed under the GPL-2.0-or-later license.
msgid ""
msgstr ""
"Project-Id-Version: EscritorioWP 0.1.0\\n"
"Report-Msgid-Bugs-To: https://webprogramacion.es\\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\\n"
"Language-Team: LANGUAGE <LL@li.org>\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"POT-Creation-Date: {$fecha}\\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\\n"
"X-Generator: tools/generar-pot.php\\n"
"X-Domain: escritoriowp\\n"

POT;

$salida = array( $cabecera );

foreach ( $cadenas as $cadena ) {
	$bloque = '';

	foreach ( array_keys( $cadena['comentarios'] ) as $comentario ) {
		$bloque .= '#. ' . $comentario . "\n";
	}

	foreach ( array_unique( $cadena['referencias'] ) as $referencia ) {
		$bloque .= '#: ' . $referencia . "\n";
	}

	if ( null !== $cadena['contexto'] ) {
		$bloque .= 'msgctxt "' . escapar( $cadena['contexto'] ) . "\"\n";
	}

	$bloque .= 'msgid "' . escapar( $cadena['texto'] ) . "\"\n";

	if ( null !== $cadena['plural'] ) {
		$bloque .= 'msgid_plural "' . escapar( $cadena['plural'] ) . "\"\n";
		$bloque .= "msgstr[0] \"\"\nmsgstr[1] \"\"\n";
	} else {
		$bloque .= "msgstr \"\"\n";
	}

	$salida[] = $bloque;
}

file_put_contents( $destino, implode( "\n", $salida ) );

printf( "Generado %s con %d cadenas.\n", str_replace( $raiz . '/', '', $destino ), count( $cadenas ) );
