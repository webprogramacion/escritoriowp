<?php
/**
 * Errores normalizados de la creación rápida.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Creacion;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Construye los errores que devuelven los creadores, con el código HTTP y los campos afectados.
 */
final class Errores {

	/**
	 * Error de validación con el detalle por campo.
	 *
	 * @param array<string, string> $campos Mensajes indexados por nombre de campo.
	 * @return WP_Error
	 */
	public static function validacion( array $campos ): WP_Error {
		return new WP_Error(
			'escritoriowp_validacion',
			__( 'Revisa los datos del formulario.', 'escritoriowp' ),
			array(
				'status' => 400,
				'campos' => $campos,
			)
		);
	}

	/**
	 * Error de permisos.
	 *
	 * @param string $mensaje Mensaje opcional.
	 * @return WP_Error
	 */
	public static function sin_permiso( string $mensaje = '' ): WP_Error {
		return new WP_Error(
			'escritoriowp_sin_permiso',
			'' !== $mensaje ? $mensaje : __( 'No tienes permisos para hacer esto.', 'escritoriowp' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Error de recurso inexistente o no disponible.
	 *
	 * @return WP_Error
	 */
	public static function no_encontrado(): WP_Error {
		return new WP_Error(
			'escritoriowp_no_encontrado',
			__( 'Ese tipo de contenido no está disponible.', 'escritoriowp' ),
			array( 'status' => 404 )
		);
	}

	/**
	 * Error inesperado al guardar.
	 *
	 * @param string $mensaje Mensaje del origen del fallo.
	 * @return WP_Error
	 */
	public static function guardado( string $mensaje ): WP_Error {
		return new WP_Error(
			'escritoriowp_error_guardado',
			'' !== $mensaje ? $mensaje : __( 'No se ha podido guardar.', 'escritoriowp' ),
			array( 'status' => 500 )
		);
	}
}
