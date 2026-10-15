<?php
/**
 * Controlador REST del bloque de últimos avisos del escritorio.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Rest;

use EscritorioWP\Avisos\Lectura;
use EscritorioWP\Avisos\PantallaAvisos;
use EscritorioWP\Avisos\Registro;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Responde a «escritoriowp/v1/avisos» con los avisos guardados más recientes, con la forma de las
 * filas de los paneles y cuántos tiene sin leer quien pregunta, y a «escritoriowp/v1/avisos/leidos»
 * marcándolos como leídos.
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
		$vistos = Lectura::vistos( get_current_user_id() );

		return new WP_REST_Response(
			array(
				'elementos' => array_map(
					static fn( array $aviso ): array => PantallaAvisos::fila( $aviso, $vistos ),
					Registro::recientes( $limite )
				),
				'sinLeer'   => Registro::contar_desde( $vistos ),
			),
			200
		);
	}

	/**
	 * Marca como leídos todos los avisos guardados hasta ahora para el usuario que lo pide.
	 *
	 * @return WP_REST_Response
	 */
	public static function marcar_leidos(): WP_REST_Response {
		$usuario = get_current_user_id();

		Lectura::marcar( $usuario );

		return new WP_REST_Response( array( 'sinLeer' => Lectura::sin_leer( $usuario ) ), 200 );
	}
}
