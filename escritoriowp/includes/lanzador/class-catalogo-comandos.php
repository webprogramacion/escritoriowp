<?php
/**
 * Catálogo de comandos de navegación y acciones del lanzador.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Lanzador;

use EscritorioWP\Ajustes;
use EscritorioWP\Capacidades;

defined( 'ABSPATH' ) || exit;

/**
 * Construye la lista de comandos que el lanzador filtra en el navegador.
 *
 * Los comandos de navegación se derivan del menú de administración del usuario, de modo que las
 * pantallas que añaden otros plugins aparecen sin configuración adicional. El resultado se guarda
 * en un transitorio por usuario para poder ofrecerlo también en el sitio público, donde el menú de
 * administración no existe.
 */
final class CatalogoComandos {

	/**
	 * Prefijo del transitorio donde se cachea el catálogo de cada usuario.
	 */
	private const TRANSITORIO = 'escritoriowp_comandos_';

	/**
	 * Duración de la caché del catálogo.
	 */
	private const CADUCIDAD = 12 * HOUR_IN_SECONDS;

	/**
	 * Construye el catálogo a partir del menú de administración y lo cachea.
	 *
	 * Solo funciona dentro del administrador, donde existen los globales del menú.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function construir(): array {
		$comandos = array_merge( self::comandos_menu(), self::comandos_accion() );

		/**
		 * Filtra los comandos disponibles en el lanzador.
		 *
		 * @param array<int, array<string, mixed>> $comandos Comandos del usuario actual.
		 */
		$comandos = (array) apply_filters( 'escritoriowp_comandos', $comandos );

		self::guardar_cache( $comandos );

		return $comandos;
	}

	/**
	 * Devuelve el catálogo cacheado del usuario actual, o una lista vacía si no hay caché.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function cacheado(): array {
		$datos = get_transient( self::TRANSITORIO . get_current_user_id() );

		if ( ! is_array( $datos ) || ( $datos['version'] ?? '' ) !== ESCRITORIOWP_VERSION ) {
			return self::comandos_accion();
		}

		return is_array( $datos['comandos'] ?? null ) ? $datos['comandos'] : self::comandos_accion();
	}

	/**
	 * Guarda el catálogo en la caché del usuario actual.
	 *
	 * @param array<int, array<string, mixed>> $comandos Comandos a guardar.
	 * @return void
	 */
	private static function guardar_cache( array $comandos ): void {
		$usuario = get_current_user_id();

		if ( $usuario <= 0 ) {
			return;
		}

		set_transient(
			self::TRANSITORIO . $usuario,
			array(
				'version'  => ESCRITORIOWP_VERSION,
				'comandos' => $comandos,
			),
			self::CADUCIDAD
		);
	}

	/**
	 * Borra la caché de catálogos de todos los usuarios.
	 *
	 * @return void
	 */
	public static function limpiar_cache(): void {
		global $wpdb;

		/*
		 * Los transitorios se borran en bloque porque son uno por usuario y WordPress no ofrece una
		 * API para eliminarlos por prefijo. La consulta no necesita caché: borra la caché.
		 */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . self::TRANSITORIO ) . '%',
				$wpdb->esc_like( '_transient_timeout_' . self::TRANSITORIO ) . '%'
			)
		);
	}

	/**
	 * Comandos derivados del menú de administración del usuario.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function comandos_menu(): array {
		global $menu, $submenu;

		if ( ! is_array( $menu ) ) {
			return array();
		}

		$submenus = is_array( $submenu ) ? $submenu : array();
		$comandos = array();

		foreach ( $menu as $elemento ) {
			if ( ! is_array( $elemento ) || empty( $elemento[0] ) || ! is_string( $elemento[0] ) ) {
				continue;
			}

			if ( str_contains( (string) ( $elemento[4] ?? '' ), 'wp-menu-separator' ) ) {
				continue;
			}

			if ( ! empty( $elemento[1] ) && ! current_user_can( (string) $elemento[1] ) ) {
				continue;
			}

			$titulo = self::limpiar_titulo( (string) $elemento[0] );
			$slug   = (string) ( $elemento[2] ?? '' );

			if ( '' === $titulo || '' === $slug ) {
				continue;
			}

			$icono = self::icono( (string) ( $elemento[6] ?? '' ) );
			$url   = self::url( $slug );

			if ( '' !== $url ) {
				$comandos[] = self::comando( 'menu-' . $slug, $titulo, '', $url, $icono, 'menu' );
			}

			foreach ( (array) ( $submenus[ $slug ] ?? array() ) as $sub ) {
				if ( ! is_array( $sub ) || empty( $sub[0] ) ) {
					continue;
				}

				if ( ! empty( $sub[1] ) && ! current_user_can( (string) $sub[1] ) ) {
					continue;
				}

				$titulo_sub = self::limpiar_titulo( (string) $sub[0] );
				$slug_sub   = (string) ( $sub[2] ?? '' );

				if ( '' === $titulo_sub || '' === $slug_sub ) {
					continue;
				}

				$url_sub = self::url( $slug_sub, $slug );

				if ( '' === $url_sub ) {
					continue;
				}

				$comandos[] = self::comando(
					'menu-' . $slug . '-' . $slug_sub,
					$titulo_sub,
					$titulo,
					$url_sub,
					$icono,
					'menu'
				);
			}
		}

		return $comandos;
	}

	/**
	 * Acciones fijas del lanzador, condicionadas a las capacidades del usuario.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function comandos_accion(): array {
		$acciones = array();

		foreach ( Capacidades::TIPOS_PANEL as $tipo ) {
			if ( ! Capacidades::puede_crear( $tipo ) ) {
				continue;
			}

			$acciones[] = self::comando(
				'accion-nuevo-' . $tipo,
				Capacidades::etiqueta_nuevo( $tipo ),
				'',
				Capacidades::url_nuevo( $tipo ),
				Capacidades::icono( $tipo ),
				'accion'
			);
		}

		$acciones[] = self::comando(
			'accion-escritorio',
			__( 'Ir al escritorio', 'escritoriowp' ),
			'',
			admin_url(),
			'dashicons-dashboard',
			'accion'
		);

		$acciones[] = self::comando(
			'accion-ver-sitio',
			__( 'Ver el sitio', 'escritoriowp' ),
			'',
			home_url( '/' ),
			'dashicons-admin-site-alt3',
			'accion'
		);

		$acciones[] = self::comando(
			'accion-perfil',
			__( 'Mi perfil', 'escritoriowp' ),
			'',
			admin_url( 'profile.php' ),
			'dashicons-admin-users',
			'accion'
		);

		if ( current_user_can( 'manage_options' ) ) {
			$acciones[] = self::comando(
				'accion-ajustes-escritoriowp',
				__( 'Ajustes de EscritorioWP', 'escritoriowp' ),
				'',
				Ajustes::url(),
				'dashicons-admin-settings',
				'accion'
			);
		}

		$acciones[] = self::comando(
			'accion-cerrar-sesion',
			__( 'Cerrar sesión', 'escritoriowp' ),
			'',
			wp_logout_url(),
			'dashicons-exit',
			'accion'
		);

		return $acciones;
	}

	/**
	 * Construye un comando.
	 *
	 * @param string $id       Identificador único.
	 * @param string $titulo   Texto principal.
	 * @param string $contexto Texto del menú padre, si lo hay.
	 * @param string $url      Destino.
	 * @param string $icono    Clase Dashicon.
	 * @param string $grupo    «menu» o «accion».
	 * @return array<string, mixed>
	 */
	private static function comando( string $id, string $titulo, string $contexto, string $url, string $icono, string $grupo ): array {
		/*
		 * Algunos plugins registran slugs de menú con entidades HTML («&amp;»). El destino viaja
		 * como dato hasta el navegador, que lo asigna sin decodificar, así que se decodifica aquí.
		 */
		$url = html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		return array(
			'id'       => $id,
			'titulo'   => $titulo,
			'contexto' => $contexto,
			'url'      => esc_url_raw( $url ),
			'icono'    => $icono,
			'grupo'    => $grupo,
		);
	}

	/**
	 * Limpia el título de un elemento de menú.
	 *
	 * Quita los contadores que WordPress y otros plugins añaden dentro de etiquetas «span», como el
	 * número de plugins por actualizar o de comentarios pendientes.
	 *
	 * @param string $titulo Título con posible marcado.
	 * @return string
	 */
	public static function limpiar_titulo( string $titulo ): string {
		$limpio = (string) preg_replace( '#<span[^>]*>.*?</span>#is', '', $titulo );
		$limpio = wp_strip_all_tags( $limpio );

		return trim( html_entity_decode( $limpio, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}

	/**
	 * Normaliza el icono declarado por un elemento de menú.
	 *
	 * @param string $icono Valor declarado en el menú.
	 * @return string
	 */
	public static function icono( string $icono ): string {
		return str_starts_with( $icono, 'dashicons-' ) ? $icono : 'dashicons-admin-generic';
	}

	/**
	 * Resuelve la URL absoluta de un elemento de menú.
	 *
	 * @param string      $slug  Slug del elemento.
	 * @param string|null $padre Slug del elemento padre, si es un submenú.
	 * @return string
	 */
	private static function url( string $slug, ?string $padre = null ): string {
		global $_parent_pages;

		$registrado = is_array( $_parent_pages ) && array_key_exists( $slug, $_parent_pages )
			? $_parent_pages[ $slug ]
			: null;

		$ruta = self::resolver_ruta( $slug, $padre, is_string( $registrado ) ? $registrado : null );

		if ( '' === $ruta ) {
			return '';
		}

		return self::es_absoluta( $ruta ) ? $ruta : admin_url( $ruta );
	}

	/**
	 * Calcula la ruta de un elemento de menú relativa a wp-admin.
	 *
	 * Función pura: replica las reglas de menu_page_url() sin depender de WordPress, para poder
	 * probarla de forma aislada.
	 *
	 * @param string      $slug            Slug del elemento.
	 * @param string|null $padre           Slug del padre según el menú.
	 * @param string|null $padre_registrado Padre registrado por add_submenu_page(), si lo hay.
	 * @return string Ruta relativa o URL absoluta; cadena vacía si no se puede resolver.
	 */
	public static function resolver_ruta( string $slug, ?string $padre = null, ?string $padre_registrado = null ): string {
		$slug = trim( $slug );

		if ( '' === $slug ) {
			return '';
		}

		if ( self::es_absoluta( $slug ) ) {
			return $slug;
		}

		// Pantallas del núcleo: el propio slug ya es el fichero de destino.
		if ( preg_match( '/\.php($|\?)/', $slug ) ) {
			return $slug;
		}

		/*
		 * Páginas de plugin: cuelgan del fichero padre cuando este es una pantalla del núcleo. El
		 * slug se concatena tal cual, igual que hace menu_page_url(), porque algunos plugins
		 * registran slugs que ya incluyen parámetros (por ejemplo «wc-admin&path=/customers»).
		 */
		$base = $padre_registrado ?? $padre;

		if ( is_string( $base ) && '' !== $base && preg_match( '/\.php($|\?)/', $base ) ) {
			$separador = str_contains( $base, '?' ) ? '&' : '?';

			return $base . $separador . 'page=' . $slug;
		}

		return 'admin.php?page=' . $slug;
	}

	/**
	 * Indica si una cadena es una URL absoluta.
	 *
	 * @param string $valor Cadena a comprobar.
	 * @return bool
	 */
	public static function es_absoluta( string $valor ): bool {
		return (bool) preg_match( '#^(https?:)?//#i', $valor );
	}
}
