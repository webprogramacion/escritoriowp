<?php
/**
 * Lectura del historial de cambios que el plugin distribuye en su readme.txt.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP;

defined( 'ABSPATH' ) || exit;

/**
 * Convierte la sección «Changelog» del readme.txt en una lista de versiones con sus cambios.
 *
 * El readme.txt ya viaja dentro del plugin y ya es obligatorio para el directorio de WordPress.org,
 * así que la pantalla Acerca de lo usa como única fuente del historial en lugar de duplicarlo.
 */
final class Changelog {

	/**
	 * Lee el historial de cambios de un readme.txt.
	 *
	 * Función pura: recibe el texto y devuelve la lista, sin tocar el sistema de ficheros.
	 *
	 * @param string $contenido Contenido completo del readme.txt.
	 * @return array<int, array{version: string, cambios: array<int, string>}> Versiones en el orden
	 *                                                                        en que aparecen.
	 */
	public static function desde_readme( string $contenido ): array {
		$seccion = self::seccion_changelog( $contenido );

		if ( '' === $seccion ) {
			return array();
		}

		$versiones = array();
		$actual    = null;
		$lineas    = preg_split( '/\R/', $seccion );

		if ( false === $lineas ) {
			return array();
		}

		foreach ( $lineas as $linea ) {
			$linea = trim( $linea );

			if ( 1 === preg_match( '/^=\s*(.+?)\s*=$/', $linea, $coincidencia ) ) {
				if ( null !== $actual ) {
					$versiones[] = $actual;
				}

				$actual = array(
					'version' => $coincidencia[1],
					'cambios' => array(),
				);

				continue;
			}

			// Solo cuentan las viñetas: el resto de líneas de la sección se ignora.
			if ( null !== $actual && 1 === preg_match( '/^\*\s+(.+)$/', $linea, $coincidencia ) ) {
				$actual['cambios'][] = trim( $coincidencia[1] );
			}
		}

		if ( null !== $actual ) {
			$versiones[] = $actual;
		}

		return $versiones;
	}

	/**
	 * Devuelve el texto de la sección «Changelog», sin su propia cabecera.
	 *
	 * @param string $contenido Contenido completo del readme.txt.
	 * @return string Cadena vacía si la sección no existe.
	 */
	private static function seccion_changelog( string $contenido ): string {
		$lineas  = preg_split( '/\R/', $contenido );
		$dentro  = false;
		$seccion = array();

		if ( false === $lineas ) {
			return '';
		}

		foreach ( $lineas as $linea ) {
			if ( 1 === preg_match( '/^==\s*(.+?)\s*==$/', trim( $linea ), $coincidencia ) ) {
				if ( $dentro ) {
					break;
				}

				$dentro = 0 === strcasecmp( 'changelog', $coincidencia[1] );
				continue;
			}

			if ( $dentro ) {
				$seccion[] = $linea;
			}
		}

		return $dentro ? implode( "\n", $seccion ) : '';
	}
}
