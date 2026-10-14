<?php
/**
 * Comparación entre dos revisiones de los administradores del sitio.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Avisos;

use EscritorioWP\Seguridad\Detector;

defined( 'ABSPATH' ) || exit;

/**
 * Funciones puras que resumen la situación de los administradores, comparan dos situaciones y dan
 * formato a las líneas del aviso. No llaman a WordPress: así se pueden probar sin cargarlo.
 */
final class CambiosAdministradores {

	/**
	 * Apartado: usuarios que ahora son administradores y antes no.
	 */
	public const ADMINISTRADORES_NUEVOS = 'administradores_nuevos';

	/**
	 * Apartado: usuarios que ahora están marcados y antes no.
	 */
	public const MARCADOS_NUEVOS = 'marcados_nuevos';

	/**
	 * Apartado: administradores que ya no son administradores ni están marcados.
	 */
	public const YA_NO_ADMINISTRADORES = 'ya_no_administradores';

	/**
	 * Apartado: usuarios marcados que ya no son administradores ni están marcados.
	 */
	public const YA_NO_MARCADOS = 'ya_no_marcados';

	/**
	 * Apartados del aviso, en el orden en que se pintan.
	 */
	public const APARTADOS = array(
		self::ADMINISTRADORES_NUEVOS,
		self::MARCADOS_NUEVOS,
		self::YA_NO_ADMINISTRADORES,
		self::YA_NO_MARCADOS,
	);

	/**
	 * Situación que se guarda de cada revisión: el nombre de usuario y el estado de cada administrador
	 * y de cada usuario marcado. Los demás no se guardan.
	 *
	 * @param array<int, array<string, mixed>> $usuarios Usuarios evaluados por `Detector::usuarios_evaluados()`, por identificador.
	 * @return array<int, array{login: string, estado: string}>
	 */
	public static function situacion( array $usuarios ): array {
		$situacion = array();

		foreach ( $usuarios as $id => $usuario ) {
			$estado = Detector::estado( $usuario );

			if ( '' !== $estado ) {
				$situacion[ (int) $id ] = array(
					'login'  => (string) ( $usuario['login'] ?? '' ),
					'estado' => $estado,
				);
			}
		}

		return $situacion;
	}

	/**
	 * Indica si un valor leído de la base de datos es una situación guardada utilizable.
	 *
	 * @param mixed $guardada Valor de la opción.
	 * @return bool
	 */
	public static function valida( mixed $guardada ): bool {
		return is_array( $guardada )
			&& is_int( $guardada['fecha'] ?? null )
			&& is_array( $guardada['usuarios'] ?? null );
	}

	/**
	 * Compara dos situaciones y reparte a los usuarios que han cambiado entre los cuatro apartados.
	 *
	 * Cada usuario cae como mucho en uno: quien pasa de marcado a administrador solo es administrador
	 * nuevo, y quien pierde el rol pero conserva permisos críticos solo es marcado nuevo. Quien sigue en
	 * el mismo estado no cuenta, aunque hayan cambiado sus permisos.
	 *
	 * @param array<int, array{login?: mixed, estado?: mixed}> $antes Situación de la revisión anterior.
	 * @param array<int, array{login?: mixed, estado?: mixed}> $ahora Situación actual.
	 * @return array<string, int[]> Por apartado, los identificadores ordenados por nombre de usuario.
	 */
	public static function comparar( array $antes, array $ahora ): array {
		$cambios = array_fill_keys( self::APARTADOS, array() );

		foreach ( $ahora as $id => $usuario ) {
			$estado   = self::estado_guardado( $usuario );
			$anterior = isset( $antes[ $id ] ) ? self::estado_guardado( $antes[ $id ] ) : '';

			if ( $estado === $anterior ) {
				continue;
			}

			if ( Detector::ESTADO_ADMINISTRADOR === $estado ) {
				$cambios[ self::ADMINISTRADORES_NUEVOS ][] = (int) $id;
			} elseif ( Detector::ESTADO_MARCADO === $estado ) {
				$cambios[ self::MARCADOS_NUEVOS ][] = (int) $id;
			}
		}

		foreach ( $antes as $id => $usuario ) {
			if ( isset( $ahora[ $id ] ) ) {
				continue;
			}

			$anterior = self::estado_guardado( $usuario );

			if ( Detector::ESTADO_ADMINISTRADOR === $anterior ) {
				$cambios[ self::YA_NO_ADMINISTRADORES ][] = (int) $id;
			} elseif ( Detector::ESTADO_MARCADO === $anterior ) {
				$cambios[ self::YA_NO_MARCADOS ][] = (int) $id;
			}
		}

		$cambios[ self::ADMINISTRADORES_NUEVOS ] = self::ordenar( $cambios[ self::ADMINISTRADORES_NUEVOS ], $ahora );
		$cambios[ self::MARCADOS_NUEVOS ]        = self::ordenar( $cambios[ self::MARCADOS_NUEVOS ], $ahora );
		$cambios[ self::YA_NO_ADMINISTRADORES ]  = self::ordenar( $cambios[ self::YA_NO_ADMINISTRADORES ], $antes );
		$cambios[ self::YA_NO_MARCADOS ]         = self::ordenar( $cambios[ self::YA_NO_MARCADOS ], $antes );

		return $cambios;
	}

	/**
	 * Indica si una comparación no ha encontrado ningún cambio.
	 *
	 * @param array<string, int[]> $cambios Resultado de `comparar()`.
	 * @return bool
	 */
	public static function sin_cambios( array $cambios ): bool {
		foreach ( $cambios as $ids ) {
			if ( array() !== $ids ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Línea de un usuario en el aviso: `- Nombre (login, correo): detalle. Detalle.`
	 *
	 * Sin nombre visible, o si coincide con el nombre de usuario, solo sale el nombre de usuario; sin
	 * correo, se omite. Cada detalle termina en punto.
	 *
	 * @param string   $login    Nombre de usuario.
	 * @param string   $nombre   Nombre visible, o vacío si el usuario ya no existe.
	 * @param string   $correo   Correo, o vacío si el usuario ya no existe.
	 * @param string[] $detalles Detalles ya traducidos.
	 * @return string
	 */
	public static function linea( string $login, string $nombre, string $correo, array $detalles ): string {
		$login  = Plantilla::una_linea( $login );
		$nombre = Plantilla::una_linea( $nombre );
		$correo = Plantilla::una_linea( $correo );

		$datos = array_values( array_filter( array( $login, $correo ), static fn( string $dato ): bool => '' !== $dato ) );

		if ( '' !== $nombre && $nombre !== $login ) {
			$linea = '- ' . $nombre . ' (' . implode( ', ', $datos ) . ')';
		} elseif ( '' !== $correo ) {
			$linea = '- ' . $login . ' (' . $correo . ')';
		} else {
			$linea = '- ' . $login;
		}

		$detalles = array_values(
			array_filter(
				array_map( static fn( string $detalle ): string => Plantilla::una_linea( $detalle ), $detalles ),
				static fn( string $detalle ): bool => '' !== $detalle
			)
		);

		if ( array() === $detalles ) {
			return $linea;
		}

		$detalles = array_map(
			static fn( string $detalle ): string => str_ends_with( $detalle, '.' ) ? $detalle : $detalle . '.',
			$detalles
		);

		return $linea . ': ' . implode( ' ', $detalles );
	}

	/**
	 * Se queda con las primeras líneas de un apartado y dice cuántas quedan fuera.
	 *
	 * @param string[] $lineas Líneas del apartado.
	 * @param int      $maximo Líneas que se listan como mucho.
	 * @return array{lineas: string[], restantes: int}
	 */
	public static function recortar( array $lineas, int $maximo ): array {
		$lineas = array_values( $lineas );
		$maximo = max( 0, $maximo );

		return array(
			'lineas'    => array_slice( $lineas, 0, $maximo ),
			'restantes' => max( 0, count( $lineas ) - $maximo ),
		);
	}

	/**
	 * Estado de un usuario en una situación guardada, tolerando datos mal formados.
	 *
	 * @param mixed $usuario Entrada de la situación.
	 * @return string
	 */
	private static function estado_guardado( mixed $usuario ): string {
		return is_array( $usuario ) ? (string) ( $usuario['estado'] ?? '' ) : '';
	}

	/**
	 * Ordena identificadores por el nombre de usuario que tienen en una situación y, a igual nombre,
	 * por identificador.
	 *
	 * @param int[]                                            $ids       Identificadores.
	 * @param array<int, array{login?: mixed, estado?: mixed}> $situacion Situación de donde sale el nombre.
	 * @return int[]
	 */
	private static function ordenar( array $ids, array $situacion ): array {
		usort(
			$ids,
			static function ( int $a, int $b ) use ( $situacion ): int {
				$login_a = is_array( $situacion[ $a ] ?? null ) ? (string) ( $situacion[ $a ]['login'] ?? '' ) : '';
				$login_b = is_array( $situacion[ $b ] ?? null ) ? (string) ( $situacion[ $b ]['login'] ?? '' ) : '';

				return array( $login_a, $a ) <=> array( $login_b, $b );
			}
		);

		return $ids;
	}
}
