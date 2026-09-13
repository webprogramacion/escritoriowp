<?php
/**
 * Controlador REST de la creación rápida.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Rest;

use EscritorioWP\Creacion\Creador;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Responde a «escritoriowp/v1/crear/{tipo}» creando el elemento y devolviendo sus enlaces.
 */
final class ControladorCreacion {

	/**
	 * Crea el elemento.
	 *
	 * @param WP_REST_Request $peticion Petición en curso.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function crear( WP_REST_Request $peticion ): WP_REST_Response|WP_Error {
		$tipo  = (string) $peticion['tipo'];
		$datos = $peticion->get_json_params();
		$datos = is_array( $datos ) ? $datos : (array) $peticion->get_body_params();

		$creado = Creador::crear( $tipo, $datos );

		if ( is_wp_error( $creado ) ) {
			return $creado;
		}

		return new WP_REST_Response( $creado, 201 );
	}
}
