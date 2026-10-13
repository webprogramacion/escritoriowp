<?php
/**
 * Posición del menú del plugin en la barra lateral del administrador.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP;

use EscritorioWP\Lanzador\CatalogoComandos;

defined( 'ABSPATH' ) || exit;

/**
 * Coloca el menú «EscritorioWP» debajo del menú que elija el administrador.
 *
 * La posición se resuelve con el filtro `menu_order`, cuando el menú ya está completo, y no con el
 * parámetro de posición de `add_menu_page()`: así funciona también con los menús de plugins que se
 * registran después que este.
 */
final class Menu {

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
	 * WordPress solo aplica `menu_order` si `custom_menu_order` devuelve verdadero. Se engancha con
	 * la prioridad más alta para recibir la lista ya modificada por otros plugins, como WooCommerce,
	 * y que la posición elegida aquí sea la que se ve.
	 *
	 * @return void
	 */
	public function registrar(): void {
		add_filter( 'custom_menu_order', '__return_true' );
		add_filter( 'menu_order', array( $this, 'ordenar' ), PHP_INT_MAX );
		add_action( 'admin_enqueue_scripts', array( $this, 'encolar_color' ) );
	}

	/**
	 * Añade el color de fondo de la entrada a la hoja del menú lateral, que carga toda pantalla del
	 * administrador. Sin color elegido no añade nada.
	 *
	 * @return void
	 */
	public function encolar_color(): void {
		$css = self::css( (string) $this->ajustes->obtener( 'menu_color_fondo' ) );

		if ( '' !== $css ) {
			wp_add_inline_style( 'admin-menu', $css );
		}
	}

	/**
	 * Aplica la posición elegida en los ajustes al orden de la barra lateral.
	 *
	 * @param mixed $orden Identificadores de los menús en el orden actual.
	 * @return mixed
	 */
	public function ordenar( mixed $orden ): mixed {
		if ( ! is_array( $orden ) ) {
			return $orden;
		}

		return self::recolocar( $orden, Ajustes::PAGINA, (string) $this->ajustes->obtener( 'menu_debajo_de' ) );
	}

	/**
	 * Menús de primer nivel que se pueden elegir como referencia, en el orden de la barra lateral.
	 *
	 * Se salta los separadores, los elementos sin título o sin identificador y el propio menú del
	 * plugin. Los títulos pierden los contadores, como el de comentarios pendientes.
	 *
	 * @param array<int|string, mixed> $menu Menú de administración de WordPress (`$menu`).
	 * @return array<string, string> Nombre de cada menú por identificador.
	 */
	public static function opciones( array $menu ): array {
		$opciones = array();

		foreach ( $menu as $elemento ) {
			if ( ! is_array( $elemento ) || empty( $elemento[0] ) || ! is_string( $elemento[0] ) ) {
				continue;
			}

			if ( str_contains( (string) ( $elemento[4] ?? '' ), 'wp-menu-separator' ) ) {
				continue;
			}

			$slug   = (string) ( $elemento[2] ?? '' );
			$titulo = CatalogoComandos::limpiar_titulo( $elemento[0] );

			if ( '' === $slug || '' === $titulo || Ajustes::PAGINA === $slug ) {
				continue;
			}

			$opciones[ $slug ] = $titulo;
		}

		return $opciones;
	}

	/**
	 * Color del texto que se lee sobre un fondo: blanco si llega a 4,5:1 de contraste, negro si no.
	 *
	 * Usa la luminancia relativa de WCAG 2. Si el blanco no llega a 4,5:1 es que la luminancia del
	 * fondo pasa de 0,1833, y entonces el negro da más de 4,67:1: nunca queda por debajo de 4,5:1.
	 *
	 * Función pura: no usa funciones de WordPress, para poder probarla de forma aislada.
	 *
	 * @param string $fondo Color de fondo en formato `#rrggbb`.
	 * @return string `#ffffff` o `#000000`.
	 */
	public static function color_texto( string $fondo ): string {
		$hex        = ltrim( $fondo, '#' );
		$luminancia = 0.0;

		foreach ( array( 0.2126, 0.7152, 0.0722 ) as $indice => $peso ) {
			$canal       = hexdec( substr( $hex, $indice * 2, 2 ) ) / 255;
			$lineal      = $canal <= 0.04045 ? $canal / 12.92 : ( ( $canal + 0.055 ) / 1.055 ) ** 2.4;
			$luminancia += $peso * $lineal;
		}

		return 1.05 / ( $luminancia + 0.05 ) >= 4.5 ? '#ffffff' : '#000000';
	}

	/**
	 * Reglas CSS que pintan la entrada del plugin con el color de fondo elegido.
	 *
	 * Se aplican en reposo y con la entrada abierta. En reposo dejan paso a WordPress al pasar el
	 * ratón o con el foco; abierta solo con el foco, porque WordPress tampoco cambia una entrada
	 * abierta al pasar el ratón y, si no, pasar por su submenú la haría saltar de color.
	 *
	 * Función pura: no usa funciones de WordPress, para poder probarla de forma aislada.
	 *
	 * @param string $fondo Color de fondo; se vuelve a sanear, así que nunca llega al CSS sin validar.
	 * @return string Reglas CSS, o cadena vacía si no hay color.
	 */
	public static function css( string $fondo ): string {
		$fondo = Ajustes::sanear_color( $fondo );

		if ( '' === $fondo ) {
			return '';
		}

		$texto   = self::color_texto( $fondo );
		$entrada = '#adminmenu #toplevel_page_' . Ajustes::PAGINA;
		$enlaces = array(
			$entrada . ':not(:hover):not(.opensub) > a.menu-top:not(:focus)',
			$entrada . '.wp-has-current-submenu > a.menu-top:not(:focus)',
		);

		$interiores = array();

		foreach ( $enlaces as $enlace ) {
			$interiores[] = $enlace . ' .wp-menu-name';
			$interiores[] = $enlace . ' .wp-menu-image::before';
		}

		return sprintf(
			"%1\$s{background-color:%2\$s;color:%3\$s}\n%4\$s{color:%3\$s}",
			implode( ',', $enlaces ),
			$fondo,
			$texto,
			implode( ',', $interiores )
		);
	}

	/**
	 * Mueve el menú propio justo debajo del menú de referencia.
	 *
	 * Con referencia vacía lo deja el último. Si la referencia no está en la lista, lo pone debajo
	 * del Escritorio; si tampoco está, o si el menú propio no está (el usuario no puede verlo),
	 * devuelve la lista tal cual. El resto de menús conserva su orden.
	 *
	 * Función pura: no usa funciones de WordPress, para poder probarla de forma aislada.
	 *
	 * @param array<int, mixed> $orden     Identificadores de los menús en el orden actual.
	 * @param string            $propio    Identificador del menú del plugin.
	 * @param string            $debajo_de Identificador del menú de referencia, o vacío para el final.
	 * @return array<int, mixed> Identificadores en el orden nuevo, con índices consecutivos.
	 */
	public static function recolocar( array $orden, string $propio, string $debajo_de ): array {
		$orden    = array_values( $orden );
		$posicion = array_search( $propio, $orden, true );

		if ( false === $posicion ) {
			return $orden;
		}

		$resto = $orden;
		array_splice( $resto, $posicion, 1 );

		if ( '' === $debajo_de ) {
			$resto[] = $propio;

			return $resto;
		}

		$referencia = array_search( $debajo_de, $resto, true );

		if ( false === $referencia ) {
			$referencia = array_search( Ajustes::MENU_POR_DEFECTO, $resto, true );
		}

		if ( false === $referencia ) {
			return $orden;
		}

		array_splice( $resto, $referencia + 1, 0, array( $propio ) );

		return $resto;
	}
}
