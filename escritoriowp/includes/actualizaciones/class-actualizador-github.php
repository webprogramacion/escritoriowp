<?php
/**
 * Actualizaciones del plugin desde las releases públicas de GitHub.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Actualizaciones;

use EscritorioWP\AcercaDe;

defined( 'ABSPATH' ) || exit;

/**
 * Ofrece a WordPress la última versión publicada en GitHub.
 *
 * El plugin declara en su cabecera «Update URI: https://github.com/…», así que WordPress deja de
 * preguntar por él a WordPress.org y pasa la decisión al filtro «update_plugins_github.com». Este
 * módulo responde a ese filtro con los datos de la release, y WordPress se encarga del resto:
 * comparar versiones, avisar en la pantalla Plugins y descargar e instalar el zip.
 *
 * Todo el módulo está aislado aquí a propósito: la variante que se envíe al directorio oficial de
 * WordPress.org no lo incluye, porque sus directrices prohíben servir actualizaciones desde fuera.
 */
final class ActualizadorGithub {

	/**
	 * Transitorio donde se guarda la última release conocida.
	 */
	public const TRANSITORIO = 'escritoriowp_actualizacion';

	/**
	 * Acción de administración que fuerza una comprobación.
	 */
	public const ACCION = 'escritoriowp_buscar_actualizaciones';

	/**
	 * Identificador del plugin para WordPress.
	 */
	public const SLUG = 'escritoriowp';

	/**
	 * Cuánto se guarda una respuesta correcta.
	 */
	private const DURACION = 12 * HOUR_IN_SECONDS;

	/**
	 * Cuánto se guarda un fallo, para no insistir contra un servicio caído.
	 */
	private const DURACION_ERROR = HOUR_IN_SECONDS;

	/**
	 * Constructor.
	 *
	 * @param string $repositorio URL del repositorio público, como «https://github.com/usuario/repo».
	 */
	public function __construct( private readonly string $repositorio ) {}

	/**
	 * Registra los enganches del módulo.
	 *
	 * @return void
	 */
	public function registrar(): void {
		if ( '' === $this->repositorio ) {
			return;
		}

		add_filter( 'update_plugins_' . $this->anfitrion(), array( $this, 'ofrecer_actualizacion' ), 10, 4 );
		add_filter( 'plugins_api', array( $this, 'detalles' ), 10, 3 );
		add_action( 'admin_post_' . self::ACCION, array( $this, 'comprobar_ahora' ) );
	}

	/**
	 * Comprueba si hay versión nueva a petición del usuario y vuelve a la pantalla Acerca de.
	 *
	 * @return void
	 */
	public function comprobar_ahora(): void {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die(
				esc_html__( 'No tienes permisos para buscar actualizaciones.', 'escritoriowp' ),
				'',
				array( 'response' => 403 )
			);
		}

		check_admin_referer( self::ACCION );

		$this->olvidar();
		wp_clean_plugins_cache( true );
		wp_update_plugins();

		wp_safe_redirect( add_query_arg( 'comprobado', '1', AcercaDe::url() ) );
		exit;
	}

	/**
	 * Anfitrión del repositorio, que es el que da nombre al filtro de actualizaciones.
	 *
	 * @return string
	 */
	public function anfitrion(): string {
		return (string) wp_parse_url( $this->repositorio, PHP_URL_HOST );
	}

	/**
	 * URL de la API de GitHub con la última release del repositorio.
	 *
	 * @return string
	 */
	private function url_api(): string {
		$ruta = trim( (string) wp_parse_url( $this->repositorio, PHP_URL_PATH ), '/' );

		return 'https://api.github.com/repos/' . $ruta . '/releases/latest';
	}

	/**
	 * Devuelve la última release conocida, consultando GitHub solo si hace falta.
	 *
	 * @param bool $forzar Si es cierto, descarta lo guardado y vuelve a preguntar.
	 * @return array<string, string>|null Datos de la release, o null si no se pudo obtener.
	 */
	public function release( bool $forzar = false ): ?array {
		if ( $forzar ) {
			$this->olvidar();
		}

		$guardado = get_transient( self::TRANSITORIO );

		if ( is_array( $guardado ) ) {
			// Un fallo también se guarda, para no insistir en cada carga contra un servicio caído.
			return isset( $guardado['error'] ) ? null : $guardado;
		}

		$release = $this->consultar();

		if ( null === $release ) {
			set_transient( self::TRANSITORIO, array( 'error' => true ), self::DURACION_ERROR );

			return null;
		}

		set_transient( self::TRANSITORIO, $release, self::DURACION );

		return $release;
	}

	/**
	 * Devuelve la última release ya guardada, sin preguntar a GitHub.
	 *
	 * Lo usa la pantalla Acerca de: pintarla no debe provocar ninguna petición de red. Quien decide
	 * cuándo se pregunta es WordPress con su comprobación periódica, o el usuario con el botón.
	 *
	 * @return array<string, string>|null
	 */
	public function guardada(): ?array {
		$guardado = get_transient( self::TRANSITORIO );

		return ( is_array( $guardado ) && ! isset( $guardado['error'] ) ) ? $guardado : null;
	}

	/**
	 * Borra lo que haya guardado sobre la última release.
	 *
	 * @return void
	 */
	public function olvidar(): void {
		delete_transient( self::TRANSITORIO );
	}

	/**
	 * Pregunta a GitHub por la última release publicada.
	 *
	 * La petición no lleva ningún dato del sitio ni del usuario: solo el nombre y la versión del
	 * plugin como agente de usuario, que GitHub exige para identificar al cliente.
	 *
	 * @return array<string, string>|null
	 */
	private function consultar(): ?array {
		$respuesta = wp_remote_get(
			$this->url_api(),
			array(
				'timeout'    => 10,
				'user-agent' => 'EscritorioWP/' . ESCRITORIOWP_VERSION,
				'headers'    => array( 'Accept' => 'application/vnd.github+json' ),
			)
		);

		if ( is_wp_error( $respuesta ) || 200 !== wp_remote_retrieve_response_code( $respuesta ) ) {
			return null;
		}

		$json = json_decode( wp_remote_retrieve_body( $respuesta ), true );

		return is_array( $json ) ? self::interpretar_release( $json ) : null;
	}

	/**
	 * Ofrece a WordPress la versión publicada en GitHub.
	 *
	 * WordPress llama a este filtro por cada plugin cuya cabecera «Update URI» apunte a github.com.
	 * Aquí solo se devuelven los datos: comparar la versión con la instalada y decidir si hay que
	 * avisar es cosa suya.
	 *
	 * @param array<string, mixed>|false $actualizacion Lo que hayan devuelto otros filtros.
	 * @param array<string, string>      $datos         Cabecera del plugin consultado.
	 * @param string                     $fichero       Ruta del plugin respecto al directorio de plugins.
	 * @param array<int, string>         $locales       Idiomas activos en la instalación.
	 * @return array<string, mixed>|false
	 */
	public function ofrecer_actualizacion( mixed $actualizacion, array $datos, string $fichero, array $locales ): mixed {
		unset( $locales );

		if ( ESCRITORIOWP_BASENAME !== $fichero ) {
			return $actualizacion;
		}

		$release = $this->release();

		if ( null === $release ) {
			return $actualizacion;
		}

		return array(
			'id'           => $this->repositorio,
			'slug'         => self::SLUG,
			'plugin'       => $fichero,
			'version'      => $release['version'],
			'url'          => '' !== $release['url'] ? $release['url'] : $this->repositorio,
			'package'      => $release['paquete'],
			'requires'     => (string) ( $datos['RequiresWP'] ?? '' ),
			'requires_php' => (string) ( $datos['RequiresPHP'] ?? '' ),
			'tested'       => $this->probado_hasta(),
			'icons'        => array(),
			'banners'      => array(),
			'banners_rtl'  => array(),
		);
	}

	/**
	 * Responde a la ventana «Ver detalles de la versión» con los datos de la release.
	 *
	 * Sin este filtro WordPress preguntaría por el plugin a WordPress.org, donde no está, y la
	 * ventana mostraría un error.
	 *
	 * @param mixed  $resultado Lo que hayan devuelto otros filtros.
	 * @param string $accion    Acción solicitada a la API de plugins.
	 * @param mixed  $args      Argumentos de la consulta, normalmente un objeto con «slug».
	 * @return mixed
	 */
	public function detalles( mixed $resultado, string $accion, mixed $args ): mixed {
		if ( 'plugin_information' !== $accion ) {
			return $resultado;
		}

		$slug = '';

		if ( is_object( $args ) && isset( $args->slug ) ) {
			$slug = (string) $args->slug;
		} elseif ( is_array( $args ) && isset( $args['slug'] ) ) {
			$slug = (string) $args['slug'];
		}

		if ( self::SLUG !== $slug ) {
			return $resultado;
		}

		$release = $this->release();

		if ( null === $release ) {
			return $resultado;
		}

		$datos     = $this->datos_plugin();
		$publicada = '' !== $release['publicada'] ? (int) strtotime( $release['publicada'] ) : 0;

		$informacion                = new \stdClass();
		$informacion->name          = $datos['Name'];
		$informacion->slug          = self::SLUG;
		$informacion->version       = $release['version'];
		$informacion->author        = $datos['Author'];
		$informacion->homepage      = $this->repositorio;
		$informacion->requires      = $datos['RequiresWP'];
		$informacion->requires_php  = $datos['RequiresPHP'];
		$informacion->tested        = $this->probado_hasta();
		$informacion->last_updated  = $publicada > 0 ? gmdate( 'Y-m-d H:i:s', $publicada ) : '';
		$informacion->download_link = $release['paquete'];
		$informacion->banners       = array();
		$informacion->icons         = array();
		$informacion->sections      = array(
			'description' => wp_kses_post( wpautop( $datos['Description'] ) ),
			'changelog'   => wp_kses_post( wpautop( $release['notas'] ) ),
		);

		return $informacion;
	}

	/**
	 * Cabecera del propio plugin.
	 *
	 * @return array<string, string>
	 */
	private function datos_plugin(): array {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return get_plugin_data( ESCRITORIOWP_FILE, false, true );
	}

	/**
	 * Última versión de WordPress con la que se ha probado el plugin, según su readme.txt.
	 *
	 * @return string
	 */
	private function probado_hasta(): string {
		$readme = ESCRITORIOWP_DIR . 'readme.txt';

		if ( ! is_readable( $readme ) ) {
			return '';
		}

		$datos = get_file_data( $readme, array( 'probado' => 'Tested up to' ) );

		return trim( (string) $datos['probado'] );
	}

	/**
	 * Interpreta la respuesta de la API de GitHub y devuelve solo lo que hace falta.
	 *
	 * Función pura: recibe el JSON ya decodificado y no toca ni la red ni la base de datos.
	 *
	 * @param array<string, mixed> $json Release tal como la devuelve la API de GitHub.
	 * @return array<string, string>|null Datos de la release, o null si no sirve como actualización.
	 */
	public static function interpretar_release( array $json ): ?array {
		if ( ! empty( $json['draft'] ) || ! empty( $json['prerelease'] ) ) {
			return null;
		}

		$version = ltrim( (string) ( $json['tag_name'] ?? '' ), 'vV' );

		// Un tag que no sea una versión (por ejemplo «ultima») no puede compararse con la instalada.
		if ( 1 !== preg_match( '/^\d+(\.\d+){1,3}$/', $version ) ) {
			return null;
		}

		$paquete = '';

		foreach ( (array) ( $json['assets'] ?? array() ) as $activo ) {
			$nombre = is_array( $activo ) ? (string) ( $activo['name'] ?? '' ) : '';

			if ( str_ends_with( strtolower( $nombre ), '.zip' ) ) {
				$paquete = (string) ( $activo['browser_download_url'] ?? '' );
				break;
			}
		}

		// Sin zip no hay nada que instalar: una release así se ignora en lugar de ofrecerse.
		if ( '' === $paquete ) {
			return null;
		}

		return array(
			'version'   => $version,
			'paquete'   => $paquete,
			'url'       => (string) ( $json['html_url'] ?? '' ),
			'notas'     => (string) ( $json['body'] ?? '' ),
			'publicada' => (string) ( $json['published_at'] ?? '' ),
		);
	}
}
