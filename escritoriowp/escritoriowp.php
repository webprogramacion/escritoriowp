<?php
/**
 * Plugin Name:       EscritorioWP
 * Plugin URI:        https://webprogramacion.es/escritoriowp
 * Description:       Sustituye el escritorio de WordPress por una interfaz propia y reemplaza la paleta de comandos por un lanzador que busca entradas, páginas, usuarios, productos y pedidos.
 * Version:           0.2.1
 * Requires at least: 6.7
 * Requires PHP:      8.1
 * Author:            webprogramacion.es
 * Author URI:        https://webprogramacion.es
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       escritoriowp
 * Domain Path:       /languages
 * Update URI:        https://github.com/webprogramacion/escritoriowp
 * WC requires at least: 8.0
 * WC tested up to:   11.1
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP;

defined( 'ABSPATH' ) || exit;

const VERSION    = '0.2.1';
const WP_MINIMO  = '6.7';
const PHP_MINIMO = '8.1';
const WOO_MINIMO = '8.0';

/*
 * Repositorio del que se descargan las actualizaciones. Vaciarla desactiva por completo el módulo
 * de actualizaciones: es lo que hace «php tools/empaquetar.php --wordpress-org», porque el
 * directorio oficial no permite servir actualizaciones desde otros servidores.
 */
const REPOSITORIO = 'https://github.com/webprogramacion/escritoriowp';

define( 'ESCRITORIOWP_VERSION', VERSION );
define( 'ESCRITORIOWP_FILE', __FILE__ );
define( 'ESCRITORIOWP_DIR', plugin_dir_path( __FILE__ ) );
define( 'ESCRITORIOWP_URL', plugin_dir_url( __FILE__ ) );
define( 'ESCRITORIOWP_BASENAME', plugin_basename( __FILE__ ) );
define( 'ESCRITORIOWP_REPOSITORIO', REPOSITORIO );

/**
 * Comprueba que el entorno cumple los requisitos mínimos del plugin.
 *
 * Devuelve un código en lugar de un texto traducido porque esta comprobación se ejecuta antes
 * de «init», cuando WordPress todavía no admite la carga de traducciones.
 *
 * @return string Cadena vacía si el entorno es compatible; «php» o «wp» si no lo es.
 */
function escritoriowp_requisito_incumplido(): string {
	if ( version_compare( PHP_VERSION, PHP_MINIMO, '<' ) ) {
		return 'php';
	}

	if ( version_compare( get_bloginfo( 'version' ), WP_MINIMO, '<' ) ) {
		return 'wp';
	}

	return '';
}

/**
 * Arranca el plugin si el entorno es compatible; si no, muestra un aviso en el administrador.
 *
 * @return void
 */
function escritoriowp_arrancar(): void {
	$motivo = escritoriowp_requisito_incumplido();

	if ( '' !== $motivo ) {
		add_action(
			'admin_notices',
			static function () use ( $motivo ): void {
				$texto = ( 'php' === $motivo )
					? sprintf(
						/* translators: 1: versión de PHP requerida, 2: versión de PHP instalada. */
						__( 'EscritorioWP necesita PHP %1$s o superior. Esta instalación usa PHP %2$s.', 'escritoriowp' ),
						PHP_MINIMO,
						PHP_VERSION
					)
					: sprintf(
						/* translators: 1: versión de WordPress requerida, 2: versión de WordPress instalada. */
						__( 'EscritorioWP necesita WordPress %1$s o superior. Esta instalación usa WordPress %2$s.', 'escritoriowp' ),
						WP_MINIMO,
						get_bloginfo( 'version' )
					);

				printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $texto ) );
			}
		);
		return;
	}

	require_once ESCRITORIOWP_DIR . 'includes/class-autoloader.php';
	Autoloader::registrar( ESCRITORIOWP_DIR . 'includes/' );

	Plugin::instancia()->arrancar();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\escritoriowp_arrancar' );

/*
 * La compatibilidad con el almacenamiento de pedidos de alto rendimiento (HPOS) debe declararse
 * antes de que WooCommerce se inicialice, por lo que el enganche se registra al incluir el fichero
 * y no dentro de "plugins_loaded".
 */
add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( '' !== escritoriowp_requisito_incumplido() ) {
			return;
		}
		if ( ! class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			return;
		}
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', ESCRITORIOWP_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', ESCRITORIOWP_FILE, true );
	}
);

register_activation_hook(
	__FILE__,
	static function (): void {
		if ( '' !== escritoriowp_requisito_incumplido() ) {
			return;
		}
		require_once ESCRITORIOWP_DIR . 'includes/class-autoloader.php';
		Autoloader::registrar( ESCRITORIOWP_DIR . 'includes/' );
		Ajustes::instancia()->asegurar_defectos();
	}
);
