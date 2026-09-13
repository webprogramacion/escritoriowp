<?php
/**
 * Preferencias de escritorio de cada usuario.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Escritorio;

use EscritorioWP\Capacidades;

defined( 'ABSPATH' ) || exit;

/**
 * Guarda y recupera las preferencias del escritorio asociadas a un usuario, de forma que se
 * apliquen en cualquier navegador desde el que entre.
 */
final class Preferencias {

	/**
	 * Clave del metadato de usuario donde se guardan las preferencias.
	 */
	public const META = 'escritoriowp_preferencias';

	/**
	 * Temas admitidos.
	 *
	 * @var string[]
	 */
	public const TEMAS = array( 'auto', 'claro', 'oscuro' );

	/**
	 * Preferencias por defecto.
	 *
	 * @return array{paneles_ocultos: string[], orden_paneles: string[], tema: string}
	 */
	public static function defectos(): array {
		return array(
			'paneles_ocultos' => array(),
			'orden_paneles'   => array(),
			'tema'            => 'auto',
		);
	}

	/**
	 * Sanea unas preferencias recibidas del cliente o de la base de datos.
	 *
	 * Función pura: recibe la lista de paneles válidos en lugar de consultarla, para poder probarla
	 * sin WordPress.
	 *
	 * @param mixed    $entrada         Preferencias sin sanear.
	 * @param string[] $paneles_validos Claves de panel admitidas.
	 * @return array{paneles_ocultos: string[], orden_paneles: string[], tema: string}
	 */
	public static function sanear( mixed $entrada, array $paneles_validos ): array {
		$entrada = is_array( $entrada ) ? $entrada : array();
		$salida  = self::defectos();

		$ocultos = is_array( $entrada['paneles_ocultos'] ?? null ) ? $entrada['paneles_ocultos'] : array();
		$ocultos = array_values(
			array_unique(
				array_filter(
					array_map( 'strval', $ocultos ),
					static fn ( string $panel ): bool => in_array( $panel, $paneles_validos, true )
				)
			)
		);

		$orden = is_array( $entrada['orden_paneles'] ?? null ) ? $entrada['orden_paneles'] : array();
		$orden = array_values(
			array_unique(
				array_filter(
					array_map( 'strval', $orden ),
					static fn ( string $panel ): bool => in_array( $panel, $paneles_validos, true )
				)
			)
		);

		$tema = is_string( $entrada['tema'] ?? null ) ? $entrada['tema'] : '';

		$salida['paneles_ocultos'] = $ocultos;
		$salida['orden_paneles']   = $orden;
		$salida['tema']            = in_array( $tema, self::TEMAS, true ) ? $tema : 'auto';

		return $salida;
	}

	/**
	 * Ordena los paneles visibles según la preferencia del usuario.
	 *
	 * Los paneles que no aparecen en la preferencia de orden se mantienen al final, en su orden
	 * original. Función pura.
	 *
	 * @param string[] $paneles Paneles disponibles, en su orden por defecto.
	 * @param string[] $orden   Orden preferido por el usuario.
	 * @return string[]
	 */
	public static function ordenar( array $paneles, array $orden ): array {
		$preferidos = array_values( array_filter( $orden, static fn ( string $p ): bool => in_array( $p, $paneles, true ) ) );
		$restantes  = array_values( array_filter( $paneles, static fn ( string $p ): bool => ! in_array( $p, $preferidos, true ) ) );

		return array_merge( $preferidos, $restantes );
	}

	/**
	 * Devuelve las preferencias de un usuario.
	 *
	 * @param int $usuario_id Identificador del usuario; 0 para el usuario actual.
	 * @return array{paneles_ocultos: string[], orden_paneles: string[], tema: string}
	 */
	public static function obtener( int $usuario_id = 0 ): array {
		$usuario_id = $usuario_id > 0 ? $usuario_id : get_current_user_id();

		if ( $usuario_id <= 0 ) {
			return self::defectos();
		}

		return self::sanear( get_user_meta( $usuario_id, self::META, true ), Capacidades::TIPOS_PANEL );
	}

	/**
	 * Guarda las preferencias de un usuario.
	 *
	 * @param mixed $entrada    Preferencias sin sanear.
	 * @param int   $usuario_id Identificador del usuario; 0 para el usuario actual.
	 * @return array{paneles_ocultos: string[], orden_paneles: string[], tema: string} Preferencias guardadas.
	 */
	public static function guardar( mixed $entrada, int $usuario_id = 0 ): array {
		$usuario_id = $usuario_id > 0 ? $usuario_id : get_current_user_id();
		$limpias    = self::sanear( $entrada, Capacidades::TIPOS_PANEL );

		if ( $usuario_id > 0 ) {
			update_user_meta( $usuario_id, self::META, $limpias );
		}

		return $limpias;
	}
}
