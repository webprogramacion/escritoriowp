<?php
/**
 * Despachador de la creación rápida.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Creacion;

use EscritorioWP\Capacidades;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Recibe una petición de creación rápida, elige el creador del tipo y normaliza sus respuestas.
 */
final class Creador {

	/**
	 * Tipos que se pueden crear desde el modal del escritorio.
	 *
	 * @var array<string, class-string>
	 */
	private const CREADORES = array(
		'entradas'  => CreadorEntrada::class,
		'paginas'   => CreadorPagina::class,
		'usuarios'  => CreadorUsuario::class,
		'productos' => CreadorProducto::class,
	);

	/**
	 * Indica si un tipo admite creación rápida.
	 *
	 * @param string $tipo Clave del tipo.
	 * @return bool
	 */
	public static function admite( string $tipo ): bool {
		return array_key_exists( $tipo, self::CREADORES );
	}

	/**
	 * Crea un elemento del tipo indicado.
	 *
	 * @param string               $tipo  Clave del tipo.
	 * @param array<string, mixed> $datos Datos del formulario.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function crear( string $tipo, array $datos ): array|WP_Error {
		if ( ! self::admite( $tipo ) ) {
			return Errores::no_encontrado();
		}

		if ( ! Capacidades::disponible( $tipo ) ) {
			return Errores::no_encontrado();
		}

		if ( ! Capacidades::puede_crear( $tipo ) ) {
			return Errores::sin_permiso();
		}

		$clase = self::CREADORES[ $tipo ];

		return $clase::crear( $datos );
	}
}
