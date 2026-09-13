<?php
/**
 * Base común de la creación rápida de entradas y páginas.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Creacion;

use EscritorioWP\Capacidades;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Crea contenidos con el juego mínimo de campos del modal.
 */
abstract class CreadorContenido {

	/**
	 * Tipo de contenido de WordPress que crea la clase.
	 *
	 * @return string
	 */
	abstract protected static function tipo_contenido(): string;

	/**
	 * Clave del tipo dentro del plugin.
	 *
	 * @return string
	 */
	abstract protected static function tipo(): string;

	/**
	 * Crea el contenido.
	 *
	 * @param array<string, mixed> $datos Datos del formulario.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function crear( array $datos ): array|WP_Error {
		$titulo = sanitize_text_field( (string) ( $datos['titulo'] ?? '' ) );
		$titulo = trim( $titulo );

		if ( '' === $titulo ) {
			return Errores::validacion(
				array( 'titulo' => __( 'El título es obligatorio.', 'escritoriowp' ) )
			);
		}

		$estado = 'publish' === ( $datos['estado'] ?? 'draft' ) ? 'publish' : 'draft';

		if ( 'publish' === $estado && ! Capacidades::puede_publicar( static::tipo() ) ) {
			return Errores::sin_permiso( __( 'Tu rol solo permite guardar borradores.', 'escritoriowp' ) );
		}

		$args = array(
			'post_type'    => static::tipo_contenido(),
			'post_title'   => $titulo,
			'post_content' => wp_kses_post( (string) ( $datos['contenido'] ?? '' ) ),
			'post_status'  => $estado,
			'post_author'  => get_current_user_id(),
		);

		$categoria = (int) ( $datos['categoria'] ?? 0 );

		if ( 'post' === static::tipo_contenido() && $categoria > 0 && term_exists( $categoria, 'category' ) ) {
			$args['post_category'] = array( $categoria );
		}

		$id = wp_insert_post( $args, true );

		if ( is_wp_error( $id ) ) {
			return Errores::guardado( $id->get_error_message() );
		}

		$entrada = get_post( (int) $id );

		return array(
			'id'        => (int) $id,
			'tipo'      => static::tipo(),
			'titulo'    => $titulo,
			'estado'    => $estado,
			'urlEditar' => (string) get_edit_post_link( (int) $id, 'raw' ),
			'urlVer'    => ( $entrada && 'publish' === $entrada->post_status ) ? (string) get_permalink( $entrada ) : '',
		);
	}
}
