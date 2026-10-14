<?php
/**
 * Lectura de administradores y usuarios con permisos de administrador.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Seguridad;

defined( 'ABSPATH' ) || exit;

/**
 * Lee de la base de datos quién es administrador y quién tiene permisos críticos sin serlo.
 *
 * No usa `get_users()`, `WP_User_Query` ni `get_user_meta()` para leer: un código malicioso que
 * quiera esconder a un usuario filtra justo esas funciones. Las consultas van directas a las tablas y
 * la decisión la toma `Permisos::evaluar()`.
 */
final class Detector {

	/**
	 * Transitorio con el número de usuarios marcados, para el contador del menú.
	 */
	public const TRANSITORIO = 'escritoriowp_administradores';

	/**
	 * Duración de la caché del contador. Acota lo que tarda en verse un cambio hecho directamente en
	 * la base de datos, que no dispara ningún enganche.
	 */
	public const CADUCIDAD = 15 * MINUTE_IN_SECONDS;

	/**
	 * Usuarios marcados que se pintan como mucho.
	 */
	public const LIMITE = 100;

	/**
	 * Identificadores por consulta en la lectura del detalle.
	 */
	private const LOTE = 500;

	/**
	 * Registra los enganches que invalidan la caché del contador.
	 *
	 * Cubren todo lo que cambia permisos pasando por WordPress: `set_role()`, `add_cap()`,
	 * `remove_cap()`, altas y bajas de usuarios (todo acaba en el metadato de permisos), los cambios
	 * en la definición de los roles y los superadministradores. Lo que no pasa por WordPress, como
	 * una escritura directa en la base de datos, lo recoge la caducidad.
	 *
	 * @return void
	 */
	public static function registrar(): void {
		global $wpdb;

		$opcion_roles = $wpdb->get_blog_prefix() . 'user_roles';

		add_action( 'added_user_meta', array( self::class, 'al_anadir_metadato' ), 10, 4 );
		add_action( 'updated_user_meta', array( self::class, 'al_cambiar_metadato' ), 10, 3 );
		add_action( 'deleted_user_meta', array( self::class, 'al_cambiar_metadato' ), 10, 3 );
		add_action( "add_option_{$opcion_roles}", array( self::class, 'olvidar' ), 10, 0 );
		add_action( "update_option_{$opcion_roles}", array( self::class, 'olvidar' ), 10, 0 );
		add_action( 'granted_super_admin', array( self::class, 'olvidar' ), 10, 0 );
		add_action( 'revoked_super_admin', array( self::class, 'olvidar' ), 10, 0 );
	}

	/**
	 * Número de usuarios marcados, desde la caché si la hay.
	 *
	 * @return int
	 */
	public static function contar(): int {
		$guardado = get_transient( self::TRANSITORIO );

		if ( false !== $guardado ) {
			return (int) $guardado;
		}

		return self::analizar()['total_marcados'];
	}

	/**
	 * Borra la caché del contador.
	 *
	 * @return void
	 */
	public static function olvidar(): void {
		delete_transient( self::TRANSITORIO );
	}

	/**
	 * Invalida la caché cuando se crea el metadato de permisos de un usuario con algo sospechoso.
	 *
	 * Cada alta de usuario crea ese metadato. En una tienda con muchos registros, invalidar en todas
	 * obligaría a recalcular casi en cada pantalla del administrador, así que solo se hace si el
	 * usuario nace con el rol de administrador o con permisos críticos.
	 *
	 * @param mixed  $id_metadato Identificador del metadato.
	 * @param int    $usuario     Usuario.
	 * @param string $clave       Clave del metadato.
	 * @param mixed  $valor       Valor guardado.
	 * @return void
	 */
	public static function al_anadir_metadato( mixed $id_metadato, int $usuario, string $clave, mixed $valor ): void {
		if ( self::clave_permisos() === $clave && Permisos::sospechoso( Permisos::desserializar( $valor ), self::roles() ) ) {
			self::olvidar();
		}
	}

	/**
	 * Invalida la caché cuando cambia o se borra el metadato de permisos de un usuario.
	 *
	 * Aquí no basta con mirar el valor nuevo: quitarle el rol a un administrador también cambia el
	 * resultado.
	 *
	 * @param mixed  $id_metadato Identificador o identificadores del metadato.
	 * @param int    $usuario     Usuario.
	 * @param string $clave       Clave del metadato.
	 * @return void
	 */
	public static function al_cambiar_metadato( mixed $id_metadato, int $usuario, string $clave ): void {
		if ( self::clave_permisos() === $clave ) {
			self::olvidar();
		}
	}

	/**
	 * Analiza el sitio actual y guarda el número de marcados para el contador del menú.
	 *
	 * @return array{administradores: array<int, array<string, mixed>>, marcados: array<int, array<string, mixed>>, total_marcados: int, restantes: int, roles_alterados: array<int|string, array{nombre: string, criticos: string[], usuarios: int}>, nombres_roles: array<int|string, string>}
	 */
	public static function analizar(): array {
		$roles      = self::roles();
		$alterados  = Permisos::roles_alterados( $roles );
		$candidatos = self::candidatos( Permisos::patrones( $roles ) );
		$usuarios   = self::detalle( $candidatos );

		foreach ( $usuarios as $id => $usuario ) {
			$evaluado = Permisos::evaluar( $usuario['permisos'], $roles );

			unset( $usuarios[ $id ]['permisos'] );

			$usuarios[ $id ]['roles']         = $evaluado['roles'];
			$usuarios[ $id ]['criticos']      = $evaluado['criticos'];
			$usuarios[ $id ]['administrador'] = $evaluado['administrador'];
		}

		$usuarios = self::anadir_superadministradores( $usuarios );

		$administradores = array();
		$marcados        = array();

		foreach ( $usuarios as $usuario ) {
			if ( $usuario['administrador'] || $usuario['superadmin'] ) {
				$administradores[] = $usuario;
			} elseif ( array() !== $usuario['criticos'] ) {
				$marcados[] = $usuario;
			}

			foreach ( $usuario['roles'] as $rol ) {
				if ( isset( $alterados[ $rol ] ) ) {
					$alterados[ $rol ]['usuarios'] = ( $alterados[ $rol ]['usuarios'] ?? 0 ) + 1;
				}
			}
		}

		$administradores = self::ordenar_por_alta( $administradores );
		$marcados        = self::ordenar_marcados( $marcados );
		$total           = count( $marcados );
		$mostrados       = array_slice( $marcados, 0, self::LIMITE );

		$ocultos = self::ocultos( array_merge( $administradores, $mostrados ) );

		foreach ( $administradores as $i => $usuario ) {
			$administradores[ $i ]['oculto'] = in_array( $usuario['id'], $ocultos, true );

			if ( $administradores[ $i ]['oculto'] ) {
				++$total;
			}
		}

		foreach ( $mostrados as $i => $usuario ) {
			$mostrados[ $i ]['oculto'] = in_array( $usuario['id'], $ocultos, true );
		}

		$nombres = self::nombres_roles( $roles );

		foreach ( $alterados as $slug => $rol ) {
			$alterados[ $slug ]['nombre']   = $nombres[ $slug ] ?? $rol['nombre'];
			$alterados[ $slug ]['usuarios'] = $rol['usuarios'] ?? 0;
		}

		set_transient( self::TRANSITORIO, $total, self::CADUCIDAD );

		return array(
			'administradores' => $administradores,
			'marcados'        => $mostrados,
			'total_marcados'  => $total,
			'restantes'       => max( 0, count( $marcados ) - count( $mostrados ) ),
			'roles_alterados' => $alterados,
			'nombres_roles'   => $nombres,
		);
	}

	/**
	 * Ordena los usuarios marcados: primero los que tienen permisos críticos asignados al propio
	 * usuario y, dentro de cada grupo, los dados de alta más recientemente.
	 *
	 * @param array<int, array<string, mixed>> $marcados Usuarios con `criticos`, `alta` e `id`.
	 * @return array<int, array<string, mixed>>
	 */
	public static function ordenar_marcados( array $marcados ): array {
		usort(
			$marcados,
			static function ( array $a, array $b ): int {
				$sueltos = (int) self::tiene_sueltos( $b ) <=> (int) self::tiene_sueltos( $a );

				return 0 !== $sueltos ? $sueltos : self::comparar_alta( $a, $b );
			}
		);

		return $marcados;
	}

	/**
	 * Indica si el usuario tiene algún permiso crítico asignado a él y no a uno de sus roles.
	 *
	 * @param array<string, mixed> $usuario Usuario con `criticos`.
	 * @return bool
	 */
	public static function tiene_sueltos( array $usuario ): bool {
		return array() !== self::sueltos( $usuario );
	}

	/**
	 * Permisos críticos asignados al propio usuario, que son los únicos que se pueden retirar sin
	 * tocar su rol.
	 *
	 * @param array<string, mixed> $usuario Usuario con `criticos`.
	 * @return string[]
	 */
	public static function sueltos( array $usuario ): array {
		$sueltos = array();

		foreach ( (array) ( $usuario['criticos'] ?? array() ) as $permiso => $origen ) {
			if ( Permisos::ORIGEN_USUARIO === $origen ) {
				$sueltos[] = (string) $permiso;
			}
		}

		return $sueltos;
	}

	/**
	 * Analiza a un solo usuario con las mismas reglas que `analizar()`, leyendo la base de datos en el
	 * momento.
	 *
	 * @param int $id Usuario.
	 * @return array<string, mixed>|null Null si el usuario no existe o no tiene permisos en este sitio.
	 */
	public static function analizar_usuario( int $id ): ?array {
		global $wpdb;

		/*
		 * Consulta directa por el mismo motivo que el resto: se decide qué retirar con lo que hay en la
		 * base de datos, no con lo que devuelvan funciones que otro código puede filtrar.
		 */
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$fila = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT um.user_id, um.meta_value, u.user_login, u.user_email, u.display_name, u.user_registered FROM {$wpdb->usermeta} um INNER JOIN {$wpdb->users} u ON u.ID = um.user_id WHERE um.meta_key = %s AND um.user_id = %d ORDER BY um.umeta_id ASC LIMIT 1",
				self::clave_permisos(),
				$id
			),
			ARRAY_A
		);
		// phpcs:enable

		if ( ! is_array( $fila ) ) {
			return null;
		}

		$evaluado = Permisos::evaluar( Permisos::desserializar( $fila['meta_value'] ), self::roles() );

		return array_merge(
			self::usuario( $fila ),
			array(
				'roles'         => $evaluado['roles'],
				'criticos'      => $evaluado['criticos'],
				'administrador' => $evaluado['administrador'],
				'superadmin'    => is_multisite() && in_array( (string) $fila['user_login'], array_map( 'strval', (array) get_super_admins() ), true ),
			)
		);
	}

	/**
	 * Ordena por fecha de alta, de la más reciente a la más antigua.
	 *
	 * @param array<int, array<string, mixed>> $usuarios Usuarios con `alta` e `id`.
	 * @return array<int, array<string, mixed>>
	 */
	private static function ordenar_por_alta( array $usuarios ): array {
		usort( $usuarios, array( self::class, 'comparar_alta' ) );

		return $usuarios;
	}

	/**
	 * Compara dos usuarios por fecha de alta descendente y, a igual fecha, por identificador.
	 *
	 * @param array<string, mixed> $a Usuario.
	 * @param array<string, mixed> $b Usuario.
	 * @return int
	 */
	private static function comparar_alta( array $a, array $b ): int {
		return array( (string) $b['alta'], (int) $b['id'] ) <=> array( (string) $a['alta'], (int) $a['id'] );
	}

	/**
	 * Roles del sitio tal como los usa WordPress para conceder permisos.
	 *
	 * @return array<int|string, array{name?: string, capabilities?: mixed}>
	 */
	private static function roles(): array {
		return wp_roles()->roles;
	}

	/**
	 * Nombre visible y traducido de cada rol.
	 *
	 * @param array<int|string, array{name?: string, capabilities?: mixed}>|null $roles Roles del sitio; por defecto, los actuales.
	 * @return array<int|string, string>
	 */
	public static function nombres_roles( ?array $roles = null ): array {
		$nombres = array();

		foreach ( $roles ?? self::roles() as $slug => $rol ) {
			$nombres[ $slug ] = translate_user_role( (string) ( $rol['name'] ?? $slug ) );
		}

		return $nombres;
	}

	/**
	 * Clave del metadato de permisos del sitio actual.
	 *
	 * @return string
	 */
	public static function clave_permisos(): string {
		global $wpdb;

		return $wpdb->get_blog_prefix() . 'capabilities';
	}

	/**
	 * Usuarios existentes cuyo metadato de permisos contiene alguno de los patrones.
	 *
	 * @param string[] $patrones Fragmentos sin comodines.
	 * @return int[]
	 */
	private static function candidatos( array $patrones ): array {
		global $wpdb;

		$condiciones = implode( ' OR ', array_fill( 0, count( $patrones ), 'um.meta_value LIKE %s' ) );
		$valores     = array_map( static fn( string $patron ): string => '%' . $wpdb->esc_like( $patron ) . '%', $patrones );

		/*
		 * Consulta directa a propósito: `WP_User_Query` y `get_users()` pasan por `pre_user_query`,
		 * que es justo lo que usa el código malicioso para esconder usuarios. Los marcadores se
		 * generan según el número de patrones y todos los valores pasan por `prepare()`. No se cachea:
		 * el resultado tiene que reflejar la base de datos de ahora.
		 */
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT um.user_id FROM {$wpdb->usermeta} um INNER JOIN {$wpdb->users} u ON u.ID = um.user_id WHERE um.meta_key = %s AND ( {$condiciones} )",
				array_merge( array( self::clave_permisos() ), $valores )
			)
		);
		// phpcs:enable

		return array_map( 'intval', $ids );
	}

	/**
	 * Datos de los usuarios y su metadato de permisos, quedándose con la primera fila de cada uno.
	 *
	 * WordPress usa la primera fila (la de `umeta_id` menor) cuando el metadato está repetido, así
	 * que se hace igual: una segunda fila que solo existe para despistar no cuenta.
	 *
	 * @param int[] $ids Usuarios candidatos.
	 * @return array<int, array<string, mixed>> Por identificador.
	 */
	private static function detalle( array $ids ): array {
		global $wpdb;

		$usuarios = array();

		foreach ( array_chunk( $ids, self::LOTE ) as $lote ) {
			$marcadores = implode( ',', array_fill( 0, count( $lote ), '%d' ) );

			/*
			 * Consulta directa por el mismo motivo que la de candidatos: ni `get_userdata()` (que se
			 * puede reemplazar) ni `get_user_meta()` (filtro `get_user_metadata`) son de fiar para esto.
			 */
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$filas = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT um.umeta_id, um.user_id, um.meta_value, u.user_login, u.user_email, u.display_name, u.user_registered FROM {$wpdb->usermeta} um INNER JOIN {$wpdb->users} u ON u.ID = um.user_id WHERE um.meta_key = %s AND um.user_id IN ({$marcadores}) ORDER BY um.umeta_id ASC",
					array_merge( array( self::clave_permisos() ), $lote )
				),
				ARRAY_A
			);
			// phpcs:enable

			foreach ( (array) $filas as $fila ) {
				$id = (int) $fila['user_id'];

				if ( isset( $usuarios[ $id ] ) ) {
					continue;
				}

				$usuarios[ $id ] = self::usuario( $fila ) + array( 'permisos' => Permisos::desserializar( $fila['meta_value'] ) );
			}
		}

		return $usuarios;
	}

	/**
	 * Añade a los superadministradores de la red, que no necesitan rol en el sitio.
	 *
	 * @param array<int, array<string, mixed>> $usuarios Usuarios ya evaluados, por identificador.
	 * @return array<int, array<string, mixed>>
	 */
	private static function anadir_superadministradores( array $usuarios ): array {
		global $wpdb;

		if ( ! is_multisite() ) {
			return $usuarios;
		}

		$logins = array_values( array_filter( array_map( 'strval', (array) get_super_admins() ) ) );

		if ( array() === $logins ) {
			return $usuarios;
		}

		$marcadores = implode( ',', array_fill( 0, count( $logins ), '%s' ) );

		/*
		 * Consulta directa por el mismo motivo que el resto: los datos del usuario no pueden pasar por
		 * funciones que otro código pueda filtrar.
		 */
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$filas = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID AS user_id, user_login, user_email, display_name, user_registered FROM {$wpdb->users} WHERE user_login IN ({$marcadores})",
				$logins
			),
			ARRAY_A
		);
		// phpcs:enable

		foreach ( (array) $filas as $fila ) {
			$id = (int) $fila['user_id'];

			if ( isset( $usuarios[ $id ] ) ) {
				$usuarios[ $id ]['superadmin'] = true;
				continue;
			}

			$usuarios[ $id ] = array_merge(
				self::usuario( $fila ),
				array(
					'roles'         => array(),
					'criticos'      => array(),
					'administrador' => false,
					'superadmin'    => true,
					'miembro'       => false,
				)
			);
		}

		return $usuarios;
	}

	/**
	 * Datos básicos de un usuario a partir de una fila de la tabla de usuarios.
	 *
	 * @param array<string, mixed> $fila Fila con `user_id`, `user_login`, `user_email`, `display_name` y `user_registered`.
	 * @return array<string, mixed>
	 */
	private static function usuario( array $fila ): array {
		return array(
			'id'         => (int) $fila['user_id'],
			'login'      => (string) $fila['user_login'],
			'email'      => (string) $fila['user_email'],
			'nombre'     => (string) $fila['display_name'],
			'alta'       => (string) $fila['user_registered'],
			'superadmin' => false,
			'miembro'    => true,
			'oculto'     => false,
		);
	}

	/**
	 * Identificadores, de entre los usuarios dados, que la consulta de usuarios de WordPress no
	 * devuelve porque algún código los oculta.
	 *
	 * Se busca por nombre de usuario (`login__in`) y no por identificador (`include`): con `include`,
	 * WordPress ignora `exclude`, que es otra forma de esconder a alguien desde `pre_get_users`. Los
	 * superadministradores que no son miembros del sitio se buscan en toda la red, como hace su lista
	 * de usuarios, para no darlos por ocultos solo porque no tienen rol aquí.
	 *
	 * @param array<int, array<string, mixed>> $usuarios Usuarios a comprobar.
	 * @return int[]
	 */
	private static function ocultos( array $usuarios ): array {
		$ocultos = array();

		foreach ( array( true, false ) as $miembros ) {
			$grupo = array_filter( $usuarios, static fn( array $usuario ): bool => $miembros === $usuario['miembro'] );

			if ( array() === $grupo ) {
				continue;
			}

			$argumentos = array(
				'login__in'   => array_column( $grupo, 'login' ),
				'fields'      => 'ID',
				'number'      => -1,
				'count_total' => false,
			);

			if ( ! $miembros ) {
				$argumentos['blog_id'] = 0;
			}

			$vistos  = array_map( 'intval', get_users( $argumentos ) );
			$ocultos = array_merge( $ocultos, array_diff( array_column( $grupo, 'id' ), $vistos ) );
		}

		return array_values( $ocultos );
	}
}
