<?php
/**
 * Lanzador propio y desactivación de la paleta de comandos nativa.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Lanzador;

use EscritorioWP\Activos;
use EscritorioWP\Ajustes;
use EscritorioWP\Busqueda\Buscador;
use EscritorioWP\Capacidades;
use WP_Admin_Bar;

defined( 'ABSPATH' ) || exit;

/**
 * Sustituye la paleta de comandos de wp-admin por el lanzador del plugin.
 *
 * WordPress carga su paleta desde wp_enqueue_command_palette_assets(), enganchada a
 * «admin_enqueue_scripts» con prioridad 10, y no ofrece ningún filtro para desactivarla. Este
 * módulo quita ese enganche con prioridad 1, siempre fuera del editor de bloques, que es el único
 * sitio donde la paleta nativa sigue teniendo sentido.
 */
final class Lanzador {

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
		add_action( 'admin_enqueue_scripts', array( $this, 'preparar_admin' ), 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'preparar_sitio' ), 1 );
		add_action( 'admin_bar_menu', array( $this, 'boton_barra' ), 56 );
	}

	/**
	 * Indica si la pantalla actual es un editor de bloques, donde no intervenimos.
	 *
	 * @return bool
	 */
	public function en_editor_bloques(): bool {
		if ( 'site-editor.php' === ( $GLOBALS['pagenow'] ?? '' ) ) {
			return true;
		}

		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$pantalla = get_current_screen();

		return $pantalla && method_exists( $pantalla, 'is_block_editor' ) && $pantalla->is_block_editor();
	}

	/**
	 * Prepara el administrador: quita la paleta nativa y encola el lanzador propio.
	 *
	 * @return void
	 */
	public function preparar_admin(): void {
		if ( $this->en_editor_bloques() ) {
			return;
		}

		if ( $this->ajustes->obtener( 'desactivar_paleta_nativa' ) ) {
			remove_action( 'admin_enqueue_scripts', 'wp_enqueue_command_palette_assets' );
		}

		if ( ! $this->ajustes->obtener( 'lanzador_activo' ) ) {
			return;
		}

		$this->encolar( CatalogoComandos::construir() );
	}

	/**
	 * Prepara el sitio público para usuarios identificados con barra de administración.
	 *
	 * @return void
	 */
	public function preparar_sitio(): void {
		if ( ! $this->activo_en_sitio() ) {
			return;
		}

		$this->encolar( CatalogoComandos::cacheado() );
	}

	/**
	 * Indica si el lanzador debe cargarse en el sitio público.
	 *
	 * @return bool
	 */
	private function activo_en_sitio(): bool {
		return $this->ajustes->obtener( 'lanzador_activo' )
			&& $this->ajustes->obtener( 'lanzador_frontend' )
			&& is_user_logged_in()
			&& is_admin_bar_showing();
	}

	/**
	 * Encola los activos del lanzador y publica su configuración.
	 *
	 * @param array<int, array<string, mixed>> $comandos Catálogo de comandos.
	 * @return void
	 */
	private function encolar( array $comandos ): void {
		wp_enqueue_style( 'escritoriowp-lanzador' );
		wp_enqueue_script( 'escritoriowp-lanzador' );

		wp_add_inline_script(
			'escritoriowp-lanzador',
			'window.escritoriowpLanzador = ' . wp_json_encode( $this->configuracion( $comandos ) ) . ';',
			'before'
		);
	}

	/**
	 * Configuración que necesita el script del lanzador.
	 *
	 * @param array<int, array<string, mixed>> $comandos Catálogo de comandos.
	 * @return array<string, mixed>
	 */
	private function configuracion( array $comandos ): array {
		return array(
			'comandos'      => $comandos,
			'filtros'       => $this->filtros(),
			'minCaracteres' => Buscador::MIN_CARACTERES,
			'limite'        => Buscador::LIMITE_DEFECTO,
			'espera'        => 200,
			'maxRecientes'  => 8,
		);
	}

	/**
	 * Filtros por tipo disponibles para el usuario actual.
	 *
	 * @return array<int, array{clave: string, etiqueta: string}>
	 */
	private function filtros(): array {
		$filtros = array(
			array(
				'clave'    => '',
				'etiqueta' => __( 'Todo', 'escritoriowp' ),
			),
			array(
				'clave'    => 'comandos',
				'etiqueta' => __( 'Menú', 'escritoriowp' ),
			),
		);

		$fuentes = array( 'entradas', 'paginas', 'usuarios', 'productos', 'pedidos' );

		foreach ( $fuentes as $tipo ) {
			if ( ! Capacidades::puede_ver( $tipo ) ) {
				continue;
			}

			$filtros[] = array(
				'clave'    => $tipo,
				'etiqueta' => Capacidades::etiqueta( $tipo ),
			);
		}

		return $filtros;
	}

	/**
	 * Añade el botón de búsqueda a la barra de administración.
	 *
	 * @param WP_Admin_Bar $barra Barra de administración.
	 * @return void
	 */
	public function boton_barra( WP_Admin_Bar $barra ): void {
		if ( ! $this->ajustes->obtener( 'lanzador_activo' ) || ! is_user_logged_in() ) {
			return;
		}

		if ( is_admin() ) {
			if ( $this->en_editor_bloques() ) {
				return;
			}
		} elseif ( ! $this->ajustes->obtener( 'lanzador_frontend' ) ) {
			return;
		}

		$atajo = Activos::es_mac() ? '⌘K' : 'Ctrl+K';

		$barra->add_node(
			array(
				'id'    => 'escritoriowp-lanzador',
				'title' => sprintf(
					'<span class="ab-icon dashicons dashicons-search" aria-hidden="true"></span><span class="ab-label">%1$s</span><span class="escritoriowp-barra-atajo" data-escritoriowp-atajo>%2$s</span>',
					esc_html__( 'Buscar', 'escritoriowp' ),
					esc_html( $atajo )
				),
				'href'  => '#escritoriowp-lanzador',
				'meta'  => array(
					'class' => 'escritoriowp-barra-boton',
					'title' => __( 'Abrir el buscador de EscritorioWP', 'escritoriowp' ),
				),
			)
		);
	}
}
