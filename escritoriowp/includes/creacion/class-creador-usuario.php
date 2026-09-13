<?php
/**
 * Creación rápida de usuarios.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Creacion;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Crea usuarios desde el modal del escritorio, con contraseña generada y aviso opcional por email.
 */
final class CreadorUsuario {

	/**
	 * Crea el usuario.
	 *
	 * @param array<string, mixed> $datos Datos del formulario.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function crear( array $datos ): array|WP_Error {
		$login     = sanitize_user( (string) ( $datos['login'] ?? '' ), true );
		$email     = sanitize_email( (string) ( $datos['email'] ?? '' ) );
		$nombre    = sanitize_text_field( (string) ( $datos['nombre'] ?? '' ) );
		$apellidos = sanitize_text_field( (string) ( $datos['apellidos'] ?? '' ) );
		$rol       = sanitize_key( (string) ( $datos['rol'] ?? '' ) );
		$avisar    = ! isset( $datos['avisar'] ) || (bool) $datos['avisar'];

		$campos = array();

		if ( '' === trim( $login ) ) {
			$campos['login'] = __( 'El nombre de usuario es obligatorio.', 'escritoriowp' );
		} elseif ( ! validate_username( $login ) ) {
			$campos['login'] = __( 'Ese nombre de usuario no es válido.', 'escritoriowp' );
		} elseif ( username_exists( $login ) ) {
			$campos['login'] = __( 'Ya existe un usuario con ese nombre.', 'escritoriowp' );
		}

		if ( '' === trim( $email ) ) {
			$campos['email'] = __( 'El email es obligatorio.', 'escritoriowp' );
		} elseif ( ! is_email( $email ) ) {
			$campos['email'] = __( 'Ese email no es válido.', 'escritoriowp' );
		} elseif ( email_exists( $email ) ) {
			$campos['email'] = __( 'Ya existe un usuario con este email.', 'escritoriowp' );
		}

		if ( array() !== $campos ) {
			return Errores::validacion( $campos );
		}

		$asignables = array_keys( get_editable_roles() );

		if ( '' === $rol ) {
			$rol = (string) get_option( 'default_role', 'subscriber' );
		}

		if ( ! in_array( $rol, $asignables, true ) ) {
			return Errores::sin_permiso( __( 'No puedes asignar ese rol.', 'escritoriowp' ) );
		}

		$id = wp_insert_user(
			array(
				'user_login' => $login,
				'user_email' => $email,
				'user_pass'  => wp_generate_password( 24, true, false ),
				'first_name' => $nombre,
				'last_name'  => $apellidos,
				'role'       => $rol,
			)
		);

		if ( is_wp_error( $id ) ) {
			return Errores::guardado( $id->get_error_message() );
		}

		$id = (int) $id;

		if ( $avisar ) {
			wp_new_user_notification( $id, null, 'user' );
		}

		$usuario = get_userdata( $id );

		return array(
			'id'        => $id,
			'tipo'      => 'usuarios',
			'titulo'    => $usuario ? $usuario->display_name : $login,
			'estado'    => $rol,
			'urlEditar' => (string) get_edit_user_link( $id ),
			'urlVer'    => '',
		);
	}
}
