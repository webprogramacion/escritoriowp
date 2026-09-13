<?php
/**
 * Sustitución de la pantalla de escritorio de WordPress.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Escritorio;

use EscritorioWP\Activos;
use EscritorioWP\Ajustes;
use EscritorioWP\Capacidades;

defined( 'ABSPATH' ) || exit;

/**
 * Vacía el escritorio nativo y pinta en su lugar la interfaz de EscritorioWP.
 *
 * La interfaz se registra como una caja del propio escritorio para no alterar la estructura de la
 * pantalla: así los avisos de administración, el título y el resto de enganches de WordPress siguen
 * funcionando exactamente igual.
 */
final class Escritorio {

	/**
	 * Identificador de la caja que contiene la interfaz.
	 */
	private const ID_CAJA = 'escritoriowp-app';

	/**
	 * Widgets de WordPress y de WooCommerce que sustituye la interfaz.
	 *
	 * @var string[]
	 */
	private const WIDGETS_SUSTITUIDOS = array(
		'dashboard_right_now',
		'dashboard_activity',
		'dashboard_quick_press',
		'dashboard_primary',
		'dashboard_secondary',
		'dashboard_site_health',
		'dashboard_php_nag',
		'dashboard_browser_nag',
		'dashboard_incoming_links',
		'dashboard_plugins',
		'dashboard_recent_comments',
		'dashboard_recent_drafts',
		'woocommerce_dashboard_status',
		'woocommerce_dashboard_recent_reviews',
		'woocommerce_network_orders',
		'wc_admin_dashboard_setup',
	);

	/**
	 * Ajustes del plugin.
	 *
	 * @var Ajustes
	 */
	private Ajustes $ajustes;

	/**
	 * Indica si han quedado widgets de otros plugins por pintar.
	 *
	 * @var bool
	 */
	private bool $hay_widgets_ajenos = false;

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
		add_action( 'wp_dashboard_setup', array( $this, 'preparar_escritorio' ), PHP_INT_MAX );
		add_action( 'admin_enqueue_scripts', array( $this, 'encolar' ) );
		add_filter( 'get_user_option_screen_layout_dashboard', array( $this, 'forzar_una_columna' ) );
		add_filter( 'screen_options_show_screen', array( $this, 'ocultar_opciones_pantalla' ), 10, 2 );
		add_filter( 'admin_body_class', array( $this, 'clase_body' ) );
	}

	/**
	 * Indica si el plugin debe hacerse cargo de la pantalla actual.
	 *
	 * @return bool
	 */
	public function activo_aqui(): bool {
		if ( ! $this->ajustes->obtener( 'sustituir_escritorio' ) ) {
			return false;
		}

		if ( is_network_admin() || is_user_admin() ) {
			return false;
		}

		return 'index.php' === ( $GLOBALS['pagenow'] ?? '' );
	}

	/**
	 * Vacía el escritorio nativo y registra la caja con la interfaz propia.
	 *
	 * @return void
	 */
	public function preparar_escritorio(): void {
		if ( ! $this->activo_aqui() ) {
			return;
		}

		remove_action( 'welcome_panel', 'wp_welcome_panel' );

		$this->limpiar_widgets();

		add_meta_box(
			self::ID_CAJA,
			__( 'EscritorioWP', 'escritoriowp' ),
			array( $this, 'pintar_app' ),
			'dashboard',
			'normal',
			'high'
		);
	}

	/**
	 * Quita los widgets sustituidos y, según los ajustes, también los de otros plugins.
	 *
	 * Los widgets ajenos que se conservan se mueven al contexto principal para que queden todos
	 * juntos debajo de la interfaz, bajo el título «Otros widgets».
	 *
	 * @return void
	 */
	private function limpiar_widgets(): void {
		global $wp_meta_boxes;

		if ( empty( $wp_meta_boxes['dashboard'] ) || ! is_array( $wp_meta_boxes['dashboard'] ) ) {
			return;
		}

		$ocultar_ajenos = (bool) $this->ajustes->obtener( 'ocultar_widgets_terceros' );

		/**
		 * Filtra la lista de widgets del escritorio que EscritorioWP sustituye.
		 *
		 * @param string[] $widgets Identificadores de widget.
		 */
		$sustituidos = (array) apply_filters( 'escritoriowp_widgets_sustituidos', self::WIDGETS_SUSTITUIDOS );

		$conservados = array();

		foreach ( $wp_meta_boxes['dashboard'] as $contexto => $prioridades ) {
			if ( ! is_array( $prioridades ) ) {
				continue;
			}

			foreach ( $prioridades as $prioridad => $cajas ) {
				if ( ! is_array( $cajas ) ) {
					continue;
				}

				foreach ( $cajas as $id => $caja ) {
					if ( self::ID_CAJA === $id || false === $caja || empty( $caja ) ) {
						continue;
					}

					if ( ! $ocultar_ajenos && ! in_array( $id, $sustituidos, true ) ) {
						$conservados[ $id ] = $caja;
					}
				}
			}
		}

		/*
		 * Reescribir el registro de cajas del escritorio es justamente lo que hace este módulo:
		 * deja solo la interfaz propia y, si procede, los widgets ajenos que se conservan, movidos
		 * al contexto principal para que queden juntos bajo «Otros widgets».
		 */
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_meta_boxes['dashboard'] = array(
			'normal' => array(
				'high' => array(),
				'core' => array(),
				'low'  => $conservados,
			),
			'side'   => array(),
		);

		$this->hay_widgets_ajenos = array() !== $conservados;
	}

	/**
	 * Fuerza el escritorio a una sola columna mientras la interfaz está activa.
	 *
	 * @param mixed $valor Número de columnas guardado por el usuario.
	 * @return mixed
	 */
	public function forzar_una_columna( mixed $valor ): mixed {
		return $this->activo_aqui() ? 1 : $valor;
	}

	/**
	 * Oculta la pestaña «Opciones de pantalla» en el escritorio sustituido.
	 *
	 * @param bool             $mostrar  Si debe mostrarse.
	 * @param \WP_Screen|mixed $pantalla Pantalla actual.
	 * @return bool
	 */
	public function ocultar_opciones_pantalla( bool $mostrar, mixed $pantalla ): bool {
		if ( is_object( $pantalla ) && isset( $pantalla->id ) && 'dashboard' === $pantalla->id && $this->activo_aqui() ) {
			return false;
		}

		return $mostrar;
	}

	/**
	 * Añade la clase de la interfaz al body del administrador.
	 *
	 * @param string $clases Clases actuales.
	 * @return string
	 */
	public function clase_body( string $clases ): string {
		return $this->activo_aqui() ? trim( $clases . ' escritoriowp-activo' ) : $clases;
	}

	/**
	 * Encola los activos de la interfaz y publica su configuración.
	 *
	 * @return void
	 */
	public function encolar(): void {
		if ( ! $this->activo_aqui() ) {
			return;
		}

		wp_enqueue_style( 'escritoriowp-escritorio' );
		wp_enqueue_script( 'escritoriowp-escritorio' );

		wp_add_inline_script(
			'escritoriowp-escritorio',
			'window.escritoriowpEscritorio = ' . wp_json_encode( $this->configuracion() ) . ';',
			'before'
		);
	}

	/**
	 * Configuración que necesita el script del escritorio.
	 *
	 * @return array<string, mixed>
	 */
	private function configuracion(): array {
		$preferencias = Preferencias::obtener();
		$visibles     = Capacidades::paneles_visibles();
		$ordenados    = Preferencias::ordenar( $visibles, $preferencias['orden_paneles'] );

		$paneles = array();

		foreach ( $ordenados as $tipo ) {
			$paneles[] = array(
				'clave'         => $tipo,
				'etiqueta'      => Capacidades::etiqueta( $tipo ),
				'etiquetaNuevo' => Capacidades::etiqueta_nuevo( $tipo ),
				'icono'         => Capacidades::icono( $tipo ),
				'urlListado'    => Capacidades::url_listado( $tipo ),
				'urlNuevo'      => Capacidades::url_nuevo( $tipo ),
				'puedeCrear'    => Capacidades::puede_crear( $tipo ),
				'enModal'       => 'pedidos' !== $tipo && Capacidades::puede_crear( $tipo ),
				'puedePublicar' => Capacidades::puede_publicar( $tipo ),
			);
		}

		return array(
			'paneles'           => $paneles,
			'preferencias'      => $preferencias,
			'elementosPorPanel' => (int) $this->ajustes->obtener( 'elementos_por_panel' ),
			'roles'             => self::roles_asignables(),
			'categorias'        => self::categorias(),
		);
	}

	/**
	 * Roles que el usuario actual puede asignar al crear un usuario.
	 *
	 * @return array<int, array{valor: string, etiqueta: string}>
	 */
	public static function roles_asignables(): array {
		if ( ! Capacidades::puede_crear( 'usuarios' ) ) {
			return array();
		}

		$roles  = array();
		$actual = get_option( 'default_role', 'subscriber' );

		foreach ( get_editable_roles() as $clave => $rol ) {
			$roles[] = array(
				'valor'    => (string) $clave,
				'etiqueta' => translate_user_role( $rol['name'] ),
				'defecto'  => $clave === $actual,
			);
		}

		return $roles;
	}

	/**
	 * Categorías disponibles para la creación rápida de entradas.
	 *
	 * @return array<int, array{valor: int, etiqueta: string}>
	 */
	public static function categorias(): array {
		if ( ! Capacidades::puede_crear( 'entradas' ) ) {
			return array();
		}

		$terminos = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => false,
				'number'     => 100,
				'orderby'    => 'name',
			)
		);

		if ( is_wp_error( $terminos ) ) {
			return array();
		}

		$categorias = array();

		foreach ( $terminos as $termino ) {
			$categorias[] = array(
				'valor'    => (int) $termino->term_id,
				'etiqueta' => $termino->name,
			);
		}

		return $categorias;
	}

	/**
	 * Pinta el esqueleto de la interfaz: cabecera completa y contenedores en estado de carga.
	 *
	 * @return void
	 */
	public function pintar_app(): void {
		$usuario      = wp_get_current_user();
		$preferencias = Preferencias::obtener();
		$lanzador     = (bool) $this->ajustes->obtener( 'lanzador_activo' );
		$atajo        = Activos::es_mac() ? '⌘K' : 'Ctrl+K';

		?>
		<div class="escritoriowp" id="escritoriowp-raiz" data-tema="<?php echo esc_attr( $preferencias['tema'] ); ?>">
			<header class="escritoriowp__cabecera">
				<div class="escritoriowp__saludo">
					<h2 class="escritoriowp__titulo"><?php echo esc_html( self::saludo( $usuario->display_name ) ); ?></h2>
					<p class="escritoriowp__fecha"><?php echo esc_html( self::fecha_larga() ); ?></p>
				</div>

				<div class="escritoriowp__acciones">
					<?php $this->pintar_botones_creacion(); ?>

					<?php if ( $lanzador ) : ?>
						<button type="button" class="escritoriowp-boton escritoriowp-boton--fantasma escritoriowp-abrir-lanzador">
							<span class="dashicons dashicons-search" aria-hidden="true"></span>
							<?php echo esc_html__( 'Buscar', 'escritoriowp' ); ?>
							<kbd class="escritoriowp-atajo" data-escritoriowp-atajo><?php echo esc_html( $atajo ); ?></kbd>
						</button>
					<?php endif; ?>

					<button type="button" class="escritoriowp-boton escritoriowp-boton--fantasma" data-escritoriowp-accion="refrescar">
						<span class="dashicons dashicons-update" aria-hidden="true"></span>
						<?php echo esc_html__( 'Actualizar', 'escritoriowp' ); ?>
					</button>

					<div class="escritoriowp-personalizar" data-escritoriowp-personalizar></div>
				</div>
			</header>

			<section class="escritoriowp__resumen" data-escritoriowp-resumen aria-label="<?php echo esc_attr__( 'Resumen del sitio', 'escritoriowp' ); ?>"></section>

			<section class="escritoriowp__paneles" data-escritoriowp-paneles aria-label="<?php echo esc_attr__( 'Últimos elementos', 'escritoriowp' ); ?>"></section>

			<noscript>
				<p class="escritoriowp__aviso"><?php echo esc_html__( 'EscritorioWP necesita JavaScript para mostrar el escritorio.', 'escritoriowp' ); ?></p>
			</noscript>
		</div>

		<?php if ( $this->hay_widgets_ajenos ) : ?>
			<h2 class="escritoriowp__otros-widgets"><?php echo esc_html__( 'Otros widgets', 'escritoriowp' ); ?></h2>
		<?php endif; ?>
		<?php
	}

	/**
	 * Pinta los botones de creación rápida que el usuario puede usar.
	 *
	 * @return void
	 */
	private function pintar_botones_creacion(): void {
		foreach ( Capacidades::TIPOS_PANEL as $tipo ) {
			if ( ! Capacidades::puede_crear( $tipo ) ) {
				continue;
			}

			$etiqueta = Capacidades::etiqueta_nuevo( $tipo );

			if ( 'pedidos' === $tipo ) {
				printf(
					'<a class="escritoriowp-boton escritoriowp-boton--claro" href="%1$s"><span class="dashicons %2$s" aria-hidden="true"></span>%3$s</a>',
					esc_url( Capacidades::url_nuevo( $tipo ) ),
					esc_attr( Capacidades::icono( $tipo ) ),
					esc_html( $etiqueta )
				);
				continue;
			}

			printf(
				'<button type="button" class="escritoriowp-boton escritoriowp-boton--claro" data-escritoriowp-crear="%1$s"><span class="dashicons %2$s" aria-hidden="true"></span>%3$s</button>',
				esc_attr( $tipo ),
				esc_attr( Capacidades::icono( $tipo ) ),
				esc_html( $etiqueta )
			);
		}
	}

	/**
	 * Saludo según la hora del sitio.
	 *
	 * @param string $nombre Nombre visible del usuario.
	 * @return string
	 */
	public static function saludo( string $nombre ): string {
		$hora = (int) wp_date( 'G' );

		if ( $hora < 6 || $hora >= 21 ) {
			/* translators: %s: nombre del usuario. */
			return sprintf( __( 'Buenas noches, %s', 'escritoriowp' ), $nombre );
		}

		if ( $hora < 14 ) {
			/* translators: %s: nombre del usuario. */
			return sprintf( __( 'Buenos días, %s', 'escritoriowp' ), $nombre );
		}

		/* translators: %s: nombre del usuario. */
		return sprintf( __( 'Buenas tardes, %s', 'escritoriowp' ), $nombre );
	}

	/**
	 * Fecha de hoy en el idioma y la zona horaria del sitio.
	 *
	 * @return string
	 */
	public static function fecha_larga(): string {
		$formato = _x( 'l, j \d\e F \d\e Y', 'formato de fecha de la cabecera del escritorio', 'escritoriowp' );

		return ucfirst( (string) wp_date( $formato ) );
	}
}
