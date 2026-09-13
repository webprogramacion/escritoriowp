<?php
/**
 * Registro de las rutas REST del plugin.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Rest;

use EscritorioWP\Ajustes;
use EscritorioWP\Busqueda\Buscador;
use EscritorioWP\Capacidades;
use EscritorioWP\Creacion\Creador;
use WP_Error;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Declara el espacio de nombres «escritoriowp/v1» y sus rutas.
 *
 * Todas las rutas exigen usuario identificado; además, cada una comprueba la capacidad concreta del
 * tipo al que accede, de modo que el control de acceso nunca depende de lo que muestre la interfaz.
 */
final class Rest {

	/**
	 * Espacio de nombres de la API.
	 */
	public const ESPACIO = 'escritoriowp/v1';

	/**
	 * Ajustes del plugin.
	 *
	 * @var Ajustes
	 */
	private Ajustes $ajustes;

	/**
	 * Constructor.
	 *
	 * @param Ajustes $ajustes Ajustes del plugin.
	 */
	public function __construct( Ajustes $ajustes ) {
		$this->ajustes = $ajustes;
	}

	/**
	 * Registra los enganches del módulo.
	 *
	 * @return void
	 */
	public function registrar(): void {
		add_action( 'rest_api_init', array( $this, 'registrar_rutas' ) );
	}

	/**
	 * Declara todas las rutas.
	 *
	 * @return void
	 */
	public function registrar_rutas(): void {
		register_rest_route(
			self::ESPACIO,
			'/buscar',
			array(
				'methods'             => 'GET',
				'callback'            => array( ControladorBusqueda::class, 'buscar' ),
				'permission_callback' => array( __CLASS__, 'exigir_identificado' ),
				'args'                => array(
					'q'      => array(
						'type'        => 'string',
						'required'    => true,
						'description' => __( 'Texto a buscar.', 'escritoriowp' ),
					),
					'tipos'  => array(
						'type'        => 'string',
						'required'    => false,
						'description' => __( 'Claves de fuente separadas por comas.', 'escritoriowp' ),
					),
					'limite' => array(
						'type'              => 'integer',
						'required'          => false,
						'default'           => Buscador::LIMITE_DEFECTO,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::ESPACIO,
			'/resumen',
			array(
				'methods'             => 'GET',
				'callback'            => array( ControladorResumen::class, 'obtener' ),
				'permission_callback' => array( __CLASS__, 'exigir_identificado' ),
				'args'                => array(
					'refrescar' => array(
						'type'     => 'boolean',
						'required' => false,
						'default'  => false,
					),
				),
			)
		);

		register_rest_route(
			self::ESPACIO,
			'/recientes/(?P<tipo>[a-z]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( ControladorRecientes::class, 'obtener' ),
				'permission_callback' => array( __CLASS__, 'exigir_ver_tipo' ),
				'args'                => array(
					'tipo'   => array(
						'type'     => 'string',
						'required' => true,
					),
					'limite' => array(
						'type'              => 'integer',
						'required'          => false,
						'default'           => (int) $this->ajustes->obtener( 'elementos_por_panel' ),
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::ESPACIO,
			'/crear/(?P<tipo>[a-z]+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( ControladorCreacion::class, 'crear' ),
				'permission_callback' => array( __CLASS__, 'exigir_crear_tipo' ),
				'args'                => array(
					'tipo' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			self::ESPACIO,
			'/preferencias',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( ControladorPreferencias::class, 'obtener' ),
					'permission_callback' => array( __CLASS__, 'exigir_identificado' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( ControladorPreferencias::class, 'guardar' ),
					'permission_callback' => array( __CLASS__, 'exigir_identificado' ),
				),
			)
		);
	}

	/**
	 * Exige que haya un usuario identificado.
	 *
	 * @return true|WP_Error
	 */
	public static function exigir_identificado(): bool|WP_Error {
		if ( is_user_logged_in() ) {
			return true;
		}

		return self::error_acceso();
	}

	/**
	 * Exige capacidad para ver el tipo pedido en la ruta.
	 *
	 * @param WP_REST_Request $peticion Petición en curso.
	 * @return true|WP_Error
	 */
	public static function exigir_ver_tipo( WP_REST_Request $peticion ): bool|WP_Error {
		$identificado = self::exigir_identificado();

		if ( is_wp_error( $identificado ) ) {
			return $identificado;
		}

		$tipo = (string) $peticion['tipo'];

		if ( ! Capacidades::existe( $tipo ) || ! Capacidades::disponible( $tipo ) ) {
			return self::error_no_encontrado();
		}

		return Capacidades::puede_ver( $tipo ) ? true : self::error_acceso();
	}

	/**
	 * Exige capacidad para crear elementos del tipo pedido en la ruta.
	 *
	 * @param WP_REST_Request $peticion Petición en curso.
	 * @return true|WP_Error
	 */
	public static function exigir_crear_tipo( WP_REST_Request $peticion ): bool|WP_Error {
		$identificado = self::exigir_identificado();

		if ( is_wp_error( $identificado ) ) {
			return $identificado;
		}

		$tipo = (string) $peticion['tipo'];

		if ( ! Creador::admite( $tipo ) || ! Capacidades::disponible( $tipo ) ) {
			return self::error_no_encontrado();
		}

		return Capacidades::puede_crear( $tipo ) ? true : self::error_acceso();
	}

	/**
	 * Error de acceso: 401 si no hay sesión, 403 si la hay.
	 *
	 * @return WP_Error
	 */
	private static function error_acceso(): WP_Error {
		return new WP_Error(
			'escritoriowp_sin_permiso',
			__( 'No tienes permisos para acceder a estos datos.', 'escritoriowp' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Error de recurso inexistente.
	 *
	 * @return WP_Error
	 */
	private static function error_no_encontrado(): WP_Error {
		return new WP_Error(
			'escritoriowp_no_encontrado',
			__( 'Ese tipo de contenido no está disponible.', 'escritoriowp' ),
			array( 'status' => 404 )
		);
	}
}
