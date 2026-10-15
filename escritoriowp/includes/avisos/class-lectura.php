<?php
/**
 * Avisos por correo sin leer de cada usuario.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Avisos;

defined( 'ABSPATH' ) || exit;

/**
 * Guarda, por usuario y sitio, el último aviso que ha visto. Sin leer son los guardados después.
 *
 * Una sola marca por usuario, y no una lista de leídos, que crecería sin límite: abrir la pantalla
 * Avisos o pulsar «Marcar como leídos» los marca todos a la vez. Es una opción de usuario
 * (`{prefijo del sitio}escritoriowp_avisos_vistos`) porque cada sitio de una red tiene su registro.
 */
final class Lectura {

	/**
	 * Opción de usuario con el identificador del último aviso visto.
	 */
	public const OPCION = 'escritoriowp_avisos_vistos';

	/**
	 * Marca guardada lista para usar: un entero no negativo, o 0 si no hay o no es válida.
	 *
	 * @param mixed $valor Valor guardado.
	 * @return int
	 */
	public static function marca( mixed $valor ): int {
		$numero = is_int( $valor ) || is_string( $valor ) ? filter_var( $valor, FILTER_VALIDATE_INT ) : false;

		return false === $numero || $numero < 0 ? 0 : $numero;
	}

	/**
	 * Último aviso que ha visto un usuario en este sitio.
	 *
	 * @param int $usuario Usuario.
	 * @return int
	 */
	public static function vistos( int $usuario ): int {
		return $usuario > 0 ? self::marca( get_user_option( self::OPCION, $usuario ) ) : 0;
	}

	/**
	 * Avisos que un usuario tiene sin leer en este sitio.
	 *
	 * @param int $usuario Usuario.
	 * @return int
	 */
	public static function sin_leer( int $usuario ): int {
		return $usuario > 0 ? Registro::contar_desde( self::vistos( $usuario ) ) : 0;
	}

	/**
	 * Marca como leídos todos los avisos guardados hasta ahora. Solo escribe si la marca sube.
	 *
	 * @param int $usuario Usuario.
	 * @return void
	 */
	public static function marcar( int $usuario ): void {
		if ( $usuario <= 0 ) {
			return;
		}

		$ultimo = Registro::ultimo_id();

		if ( $ultimo > self::vistos( $usuario ) ) {
			update_user_option( $usuario, self::OPCION, $ultimo );
		}
	}
}
