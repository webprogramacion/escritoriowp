<?php
/**
 * Controlador REST del bloque de últimos avisos del escritorio.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Rest;

use EscritorioWP\Avisos\PantallaAvisos;
use EscritorioWP\Avisos\Registro;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Responde a «escritoriowp/v1/avisos» con los avisos guardados más recientes, con la forma de las
 * filas de los paneles.
 */
final class ControladorAvisos {

	/**
	 * Devuelve los avisos más recientes, como mucho `Registro::LIMITE_MAXIMO`.
	 *
	 * @param WP_REST_Request $peticion Petición en curso.
	 * @return WP_REST_Response
	 */
	public static function obtener( WP_REST_Request $peticion ): WP_REST_Response {
		$limite = Registro::limite( $peticion->get_param( 'limite' ) );

		return new WP_REST_Response(
			array(
				'elementos' => array_map( array( PantallaAvisos::class, 'fila' ), Registro::recientes( $limite ) ),
			),
			200
		);
	}
}
