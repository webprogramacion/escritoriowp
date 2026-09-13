<?php
/**
 * Controlador REST de la búsqueda unificada.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Rest;

use EscritorioWP\Busqueda\Buscador;
use EscritorioWP\Busqueda\Grupo;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Responde a «escritoriowp/v1/buscar» con los resultados agrupados por tipo.
 */
final class ControladorBusqueda {

	/**
	 * Ejecuta la búsqueda.
	 *
	 * @param WP_REST_Request $peticion Petición en curso.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function buscar( WP_REST_Request $peticion ): WP_REST_Response|WP_Error {
		$termino = Buscador::normalizar_termino( (string) $peticion->get_param( 'q' ) );

		if ( '' === $termino ) {
			return new WP_Error(
				'escritoriowp_termino_invalido',
				sprintf(
					/* translators: 1: número mínimo de caracteres, 2: número máximo. */
					__( 'La búsqueda debe tener entre %1$d y %2$d caracteres.', 'escritoriowp' ),
					Buscador::MIN_CARACTERES,
					Buscador::MAX_CARACTERES
				),
				array( 'status' => 400 )
			);
		}

		$tipos = array_values(
			array_filter(
				array_map( 'sanitize_key', explode( ',', (string) $peticion->get_param( 'tipos' ) ) )
			)
		);

		$limite   = (int) $peticion->get_param( 'limite' );
		$buscador = Buscador::crear();
		$grupos   = $buscador->buscar( $termino, $tipos, $limite > 0 ? $limite : Buscador::LIMITE_DEFECTO );

		$total = 0;

		foreach ( $grupos as $grupo ) {
			$total += count( $grupo->resultados );
		}

		return new WP_REST_Response(
			array(
				'termino' => $termino,
				'total'   => $total,
				'grupos'  => array_map(
					static fn ( Grupo $grupo ): array => $grupo->a_array(),
					$grupos
				),
			),
			200
		);
	}
}
