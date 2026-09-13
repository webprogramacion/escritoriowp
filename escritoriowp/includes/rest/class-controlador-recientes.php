<?php
/**
 * Controlador REST de los paneles de últimos elementos.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Rest;

use EscritorioWP\Ajustes;
use EscritorioWP\Capacidades;
use EscritorioWP\Escritorio\Recientes;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Responde a «escritoriowp/v1/recientes/{tipo}» con las filas de un panel.
 */
final class ControladorRecientes {

	/**
	 * Devuelve los últimos elementos de un tipo.
	 *
	 * @param WP_REST_Request $peticion Petición en curso.
	 * @return WP_REST_Response
	 */
	public static function obtener( WP_REST_Request $peticion ): WP_REST_Response {
		$tipo   = (string) $peticion['tipo'];
		$limite = (int) $peticion->get_param( 'limite' );
		$limite = max( Ajustes::MIN_ELEMENTOS, min( Ajustes::MAX_ELEMENTOS, $limite ) );

		return new WP_REST_Response(
			array(
				'tipo'       => $tipo,
				'urlListado' => Capacidades::url_listado( $tipo ),
				'elementos'  => Recientes::obtener( $tipo, $limite ),
			),
			200
		);
	}
}
