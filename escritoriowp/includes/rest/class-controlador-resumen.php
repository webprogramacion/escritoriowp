<?php
/**
 * Controlador REST de los indicadores del escritorio.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Rest;

use EscritorioWP\Escritorio\Resumen;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Responde a «escritoriowp/v1/resumen» con las tarjetas de indicadores visibles.
 */
final class ControladorResumen {

	/**
	 * Devuelve los indicadores.
	 *
	 * @param WP_REST_Request $peticion Petición en curso.
	 * @return WP_REST_Response
	 */
	public static function obtener( WP_REST_Request $peticion ): WP_REST_Response {
		$refrescar = (bool) $peticion->get_param( 'refrescar' );

		return new WP_REST_Response(
			array( 'tarjetas' => Resumen::obtener( $refrescar ) ),
			200
		);
	}
}
