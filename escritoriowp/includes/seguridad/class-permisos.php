<?php
/**
 * Cálculo de roles y permisos de un usuario a partir de lo que hay guardado.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Seguridad;

defined( 'ABSPATH' ) || exit;

/**
 * Funciones puras que deciden qué permisos críticos tiene un usuario y de dónde le vienen.
 *
 * Replican las reglas de `WP_User::get_role_caps()` y `WP_User::has_cap()` para que la detección vea
 * exactamente lo que WordPress concede, no lo que muestra su interfaz. No llaman a WordPress: así se
 * pueden probar sin cargarlo.
 */
final class Permisos {

	/**
	 * Slug del rol de administrador.
	 */
	public const ADMINISTRADOR = 'administrator';

	/**
	 * Origen de un permiso guardado en el propio usuario, y no en uno de sus roles.
	 */
	public const ORIGEN_USUARIO = 'usuario';

	/**
	 * Permisos que en una instalación estándar solo tiene el administrador y que dan control del sitio
	 * o de la red: cambiar ajustes, gestionar usuarios y sus roles, instalar o editar código y
	 * actualizar.
	 *
	 * Quedan fuera a propósito los que WooCommerce da al gestor de tienda (`import`, `export`,
	 * `list_users`, `edit_theme_options`, `level_9`) y `unfiltered_html`, que tiene el editor. La lista
	 * es cerrada y no tiene filtro: en una herramienta de seguridad, un filtro para reducirla sería lo
	 * primero que tocaría un atacante.
	 *
	 * @var string[]
	 */
	public const CRITICOS = array(
		'manage_options',
		'edit_users',
		'create_users',
		'delete_users',
		'remove_users',
		'promote_users',
		'install_plugins',
		'activate_plugins',
		'edit_plugins',
		'update_plugins',
		'delete_plugins',
		'install_themes',
		'switch_themes',
		'edit_themes',
		'update_themes',
		'delete_themes',
		'edit_files',
		'update_core',
		'unfiltered_upload',
		'level_10',
		'manage_network',
		'manage_sites',
		'manage_network_users',
		'manage_network_plugins',
		'manage_network_themes',
		'manage_network_options',
		'upgrade_network',
		'setup_network',
		'create_sites',
		'delete_sites',
	);

	/**
	 * Convierte el valor guardado del metadato de permisos en un array, como hace WordPress al cargar
	 * al usuario, pero sin instanciar objetos.
	 *
	 * Lo que no sea un array serializado da un array vacío, igual que `WP_User::for_site()` cuando el
	 * metadato no es un array.
	 *
	 * @param mixed $valor Valor crudo de la columna `meta_value`.
	 * @return array<int|string, mixed>
	 */
	public static function desserializar( mixed $valor ): array {
		if ( is_array( $valor ) ) {
			return $valor;
		}

		if ( ! is_string( $valor ) ) {
			return array();
		}

		$valor = trim( $valor );

		if ( ! str_starts_with( $valor, 'a:' ) ) {
			return array();
		}

		/*
		 * El valor sale de la base de datos y puede estar manipulado. `allowed_classes => false` impide
		 * que un objeto serializado se instancie, algo que el `maybe_unserialize()` de WordPress no
		 * evita. El error se silencia igual que en `maybe_unserialize()`: un valor corrupto no debe
		 * llenar el registro de avisos.
		 */
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize, WordPress.PHP.NoSilencedErrors.Discouraged
		$datos = @unserialize( $valor, array( 'allowed_classes' => false ) );

		return is_array( $datos ) ? $datos : array();
	}

	/**
	 * Calcula los roles y los permisos críticos de un usuario con las reglas de WordPress.
	 *
	 * Es rol cada clave de los permisos guardados que coincide con un rol existente, sea cual sea su
	 * valor. Los permisos de esos roles se combinan en ese orden y encima se ponen los guardados en el
	 * propio usuario. Un permiso cuenta si su valor no está vacío.
	 *
	 * El origen de cada permiso crítico es `usuario` si su valor sale de los permisos guardados en el
	 * usuario; si no, el último de sus roles que lo define, que es el que aporta el valor.
	 *
	 * @param array<int|string, mixed>                                      $guardados Permisos guardados del usuario.
	 * @param array<int|string, array{name?: string, capabilities?: mixed}> $roles     Roles del sitio, como `wp_roles()->roles`.
	 * @return array{roles: array<int, int|string>, administrador: bool, criticos: array<string, int|string>}
	 */
	public static function evaluar( array $guardados, array $roles ): array {
		$roles_usuario = array();

		foreach ( array_keys( $guardados ) as $clave ) {
			if ( isset( $roles[ $clave ] ) ) {
				$roles_usuario[] = $clave;
			}
		}

		$efectivos = array();

		foreach ( $roles_usuario as $rol ) {
			$efectivos = array_merge( $efectivos, self::capacidades_rol( $roles[ $rol ] ) );
		}

		$efectivos = array_merge( $efectivos, $guardados );
		$criticos  = array();

		foreach ( self::CRITICOS as $permiso ) {
			if ( empty( $efectivos[ $permiso ] ) ) {
				continue;
			}

			$criticos[ $permiso ] = array_key_exists( $permiso, $guardados )
				? self::ORIGEN_USUARIO
				: self::origen_rol( $permiso, $roles_usuario, $roles );
		}

		return array(
			'roles'         => $roles_usuario,
			'administrador' => in_array( self::ADMINISTRADOR, $roles_usuario, true ),
			'criticos'      => $criticos,
		);
	}

	/**
	 * Indica si unos permisos guardados hacen al usuario administrador o le dan algún permiso crítico.
	 *
	 * @param array<int|string, mixed>                                      $guardados Permisos guardados del usuario.
	 * @param array<int|string, array{name?: string, capabilities?: mixed}> $roles     Roles del sitio.
	 * @return bool
	 */
	public static function sospechoso( array $guardados, array $roles ): bool {
		$evaluado = self::evaluar( $guardados, $roles );

		return $evaluado['administrador'] || array() !== $evaluado['criticos'];
	}

	/**
	 * Roles distintos de administrador que incluyen algún permiso crítico.
	 *
	 * @param array<int|string, array{name?: string, capabilities?: mixed}> $roles Roles del sitio, como `wp_roles()->roles`.
	 * @return array<int|string, array{nombre: string, criticos: string[]}> Por slug, su nombre visible y sus permisos críticos.
	 */
	public static function roles_alterados( array $roles ): array {
		$alterados = array();

		foreach ( $roles as $slug => $rol ) {
			if ( self::ADMINISTRADOR === $slug ) {
				continue;
			}

			$capacidades = self::capacidades_rol( $rol );
			$criticos    = array_values(
				array_filter(
					self::CRITICOS,
					static fn( string $permiso ): bool => ! empty( $capacidades[ $permiso ] )
				)
			);

			if ( array() !== $criticos ) {
				$alterados[ $slug ] = array(
					'nombre'   => is_array( $rol ) ? (string) ( $rol['name'] ?? $slug ) : (string) $slug,
					'criticos' => $criticos,
				);
			}
		}

		return $alterados;
	}

	/**
	 * Fragmentos que delatan a un candidato dentro del metadato de permisos serializado.
	 *
	 * La consulta busca cada uno con `LIKE '%…%'`. Es un filtro grueso: puede traer usuarios que luego
	 * no cumplen, pero nunca deja fuera a uno que sí. Una clave de texto se serializa entre comillas
	 * (`s:14:"manage_options";`); un rol con identificador numérico se serializa como entero (`i:7;`),
	 * así que se busca de las dos formas.
	 *
	 * @param array<int|string, array{name?: string, capabilities?: mixed}> $roles Roles del sitio.
	 * @return string[] Sin comodines ni escapar: quien consulta aplica `esc_like()`.
	 */
	public static function patrones( array $roles ): array {
		$claves = array_merge(
			self::CRITICOS,
			array( self::ADMINISTRADOR ),
			array_map( 'strval', array_keys( self::roles_alterados( $roles ) ) )
		);

		$patrones = array();

		foreach ( $claves as $clave ) {
			$patrones[] = '"' . $clave . '"';

			if ( ctype_digit( $clave ) ) {
				$patrones[] = 'i:' . $clave . ';';
			}
		}

		return array_values( array_unique( $patrones ) );
	}

	/**
	 * Último rol del usuario que define el permiso.
	 *
	 * @param string                                                        $permiso       Permiso.
	 * @param array<int, int|string>                                        $roles_usuario Roles del usuario, en orden.
	 * @param array<int|string, array{name?: string, capabilities?: mixed}> $roles         Roles del sitio.
	 * @return int|string
	 */
	private static function origen_rol( string $permiso, array $roles_usuario, array $roles ): int|string {
		foreach ( array_reverse( $roles_usuario ) as $rol ) {
			if ( array_key_exists( $permiso, self::capacidades_rol( $roles[ $rol ] ) ) ) {
				return $rol;
			}
		}

		return '';
	}

	/**
	 * Permisos que declara la definición de un rol.
	 *
	 * @param mixed $rol Definición del rol.
	 * @return array<int|string, mixed>
	 */
	private static function capacidades_rol( mixed $rol ): array {
		return is_array( $rol ) && is_array( $rol['capabilities'] ?? null ) ? $rol['capabilities'] : array();
	}
}
