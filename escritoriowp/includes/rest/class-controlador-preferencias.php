<?php
/**
 * Controlador REST de las preferencias del escritorio.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Rest;

use EscritorioWP\Escritorio\Preferencias;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Responde a «escritoriowp/v1/preferencias» leyendo y guardando las preferencias del usuario.
 */
final class ControladorPreferencias {

	/**
	 * Devuelve las preferencias del usuario actual.
	 *
	 * @return WP_REST_Response
	 */
	public static function obtener(): WP_REST_Response {
		return new WP_REST_Response( Preferencias::obtener(), 200 );
	}

	/**
	 * Guarda las preferencias del usuario actual.
	 *
	 * @param WP_REST_Request $peticion Petición en curso.
	 * @return WP_REST_Response
	 */
	public static function guardar( WP_REST_Request $peticion ): WP_REST_Response {
		$datos = $peticion->get_json_params();
		$datos = is_array( $datos ) ? $datos : (array) $peticion->get_body_params();

		return new WP_REST_Response( Preferencias::guardar( $datos ), 200 );
	}
}
