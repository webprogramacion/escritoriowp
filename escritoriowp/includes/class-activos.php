<?php
/**
 * Registro de hojas de estilo, scripts y datos compartidos con el navegador.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP;

use EscritorioWP\Escritorio\Preferencias;
use EscritorioWP\Woo\Woo;

defined( 'ABSPATH' ) || exit;

/**
 * Registra los activos del plugin y publica la configuración común que necesitan los scripts.
 *
 * Todos los textos visibles viajan ya traducidos desde PHP: el JavaScript nunca contiene cadenas
 * literales en español.
 */
final class Activos {

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
		add_action( 'admin_enqueue_scripts', array( $this, 'registrar_activos' ), 0 );
		add_action( 'wp_enqueue_scripts', array( $this, 'registrar_activos' ), 0 );
	}

	/**
	 * Registra (sin encolar) todas las hojas de estilo y scripts del plugin.
	 *
	 * @return void
	 */
	public function registrar_activos(): void {
		if ( wp_style_is( 'escritoriowp-comun', 'registered' ) ) {
			return;
		}

		$version = ESCRITORIOWP_VERSION;
		$css     = ESCRITORIOWP_URL . 'assets/css/';
		$js      = ESCRITORIOWP_URL . 'assets/js/';

		wp_register_style( 'escritoriowp-comun', $css . 'comun.css', array( 'dashicons' ), $version );
		wp_register_style( 'escritoriowp-escritorio', $css . 'escritorio.css', array( 'escritoriowp-comun' ), $version );
		wp_register_style( 'escritoriowp-lanzador', $css . 'lanzador.css', array( 'escritoriowp-comun' ), $version );
		wp_register_style( 'escritoriowp-ajustes', $css . 'ajustes.css', array(), $version );

		wp_register_script( 'escritoriowp-comun', $js . 'comun.js', array(), $version, true );
		wp_register_script( 'escritoriowp-escritorio', $js . 'escritorio.js', array( 'escritoriowp-comun' ), $version, true );
		wp_register_script( 'escritoriowp-lanzador', $js . 'lanzador.js', array( 'escritoriowp-comun' ), $version, true );

		wp_add_inline_script(
			'escritoriowp-comun',
			'window.escritoriowpConfig = ' . wp_json_encode( $this->configuracion() ) . ';',
			'before'
		);
	}

	/**
	 * Configuración común disponible para todos los scripts del plugin.
	 *
	 * @return array<string, mixed>
	 */
	private function configuracion(): array {
		$preferencias = Preferencias::obtener();

		return array(
			'version'   => ESCRITORIOWP_VERSION,
			'rest'      => esc_url_raw( rest_url( 'escritoriowp/v1/' ) ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'urlAdmin'  => esc_url_raw( admin_url() ),
			'urlSitio'  => esc_url_raw( home_url( '/' ) ),
			'usuarioId' => get_current_user_id(),
			'esMac'     => self::es_mac(),
			'woo'       => Woo::activo(),
			'tema'      => $preferencias['tema'],
			'textos'    => self::textos(),
		);
	}

	/**
	 * Adivina si el usuario navega desde macOS, para mostrar ⌘ en lugar de Ctrl.
	 *
	 * El navegador corrige este valor al cargar; esta comprobación solo evita que el atajo aparezca
	 * mal durante el primer pintado.
	 *
	 * @return bool
	 */
	public static function es_mac(): bool {
		$agente = isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: '';

		return (bool) preg_match( '/Mac|iPhone|iPad|iPod/i', $agente );
	}

	/**
	 * Catálogo completo de textos traducibles que usan los scripts.
	 *
	 * @return array<string, string>
	 */
	public static function textos(): array {
		return array(
			// Generales.
			'cargando'              => __( 'Cargando…', 'escritoriowp' ),
			'reintentar'            => __( 'Reintentar', 'escritoriowp' ),
			'errorCarga'            => __( 'No se han podido cargar los datos.', 'escritoriowp' ),
			'errorGuardar'          => __( 'No se ha podido guardar.', 'escritoriowp' ),
			'cerrar'                => __( 'Cerrar', 'escritoriowp' ),
			'cancelar'              => __( 'Cancelar', 'escritoriowp' ),
			'ver'                   => __( 'Ver', 'escritoriowp' ),
			'editar'                => __( 'Editar', 'escritoriowp' ),
			'verTodos'              => __( 'Ver todos', 'escritoriowp' ),
			'obligatorio'           => __( 'Este campo es obligatorio.', 'escritoriowp' ),

			// Paneles.
			'panelVacioEntradas'    => __( 'Todavía no hay entradas.', 'escritoriowp' ),
			'panelVacioPaginas'     => __( 'Todavía no hay páginas.', 'escritoriowp' ),
			'panelVacioUsuarios'    => __( 'Todavía no hay usuarios.', 'escritoriowp' ),
			'panelVacioProductos'   => __( 'Todavía no hay productos.', 'escritoriowp' ),
			'panelVacioPedidos'     => __( 'Todavía no hay pedidos.', 'escritoriowp' ),
			'crearPrimeroEntradas'  => __( 'Crear la primera entrada', 'escritoriowp' ),
			'crearPrimeroPaginas'   => __( 'Crear la primera página', 'escritoriowp' ),
			'crearPrimeroUsuarios'  => __( 'Crear el primer usuario', 'escritoriowp' ),
			'crearPrimeroProductos' => __( 'Crear el primer producto', 'escritoriowp' ),
			'crearPrimeroPedidos'   => __( 'Crear el primer pedido', 'escritoriowp' ),

			// Personalización.
			'personalizar'          => __( 'Personalizar', 'escritoriowp' ),
			'paneles'               => __( 'Paneles', 'escritoriowp' ),
			'tema'                  => __( 'Tema', 'escritoriowp' ),
			'temaAuto'              => __( 'Automático', 'escritoriowp' ),
			'temaClaro'             => __( 'Claro', 'escritoriowp' ),
			'temaOscuro'            => __( 'Oscuro', 'escritoriowp' ),
			'restablecer'           => __( 'Restablecer', 'escritoriowp' ),
			/* translators: %s: nombre del panel. */
			'subirPanel'            => __( 'Subir el panel %s', 'escritoriowp' ),
			/* translators: %s: nombre del panel. */
			'bajarPanel'            => __( 'Bajar el panel %s', 'escritoriowp' ),

			// Creación rápida.
			'campoTitulo'           => __( 'Título', 'escritoriowp' ),
			'campoContenido'        => __( 'Contenido', 'escritoriowp' ),
			'campoCategoria'        => __( 'Categoría', 'escritoriowp' ),
			'campoEstado'           => __( 'Estado', 'escritoriowp' ),
			'campoLogin'            => __( 'Nombre de usuario', 'escritoriowp' ),
			'campoEmail'            => __( 'Email', 'escritoriowp' ),
			'campoNombre'           => __( 'Nombre', 'escritoriowp' ),
			'campoApellidos'        => __( 'Apellidos', 'escritoriowp' ),
			'campoRol'              => __( 'Rol', 'escritoriowp' ),
			'campoAvisoEmail'       => __( 'Enviarle un email de bienvenida', 'escritoriowp' ),
			'campoPrecio'           => __( 'Precio normal', 'escritoriowp' ),
			'campoSku'              => __( 'SKU', 'escritoriowp' ),
			'estadoBorrador'        => __( 'Borrador', 'escritoriowp' ),
			'estadoPublicado'       => __( 'Publicada', 'escritoriowp' ),
			'guardarBorrador'       => __( 'Guardar borrador', 'escritoriowp' ),
			'publicar'              => __( 'Publicar', 'escritoriowp' ),
			'crear'                 => __( 'Crear', 'escritoriowp' ),
			/* translators: %s: título del elemento creado. */
			'creadoOk'              => __( '¡Listo! Se ha creado «%s».', 'escritoriowp' ),
			'crearOtro'             => __( 'Crear otro', 'escritoriowp' ),
			'soloBorradores'        => __( 'Tu rol solo permite guardar borradores.', 'escritoriowp' ),
			'opcional'              => __( 'opcional', 'escritoriowp' ),

			// Lanzador.
			'lanzadorTitulo'        => __( 'Buscador de EscritorioWP', 'escritoriowp' ),
			'lanzadorPlaceholder'   => __( 'Busca pantallas, entradas, páginas, usuarios, productos o pedidos…', 'escritoriowp' ),
			'lanzadorSugerencia'    => __( 'Escribe para buscar por título, email, SKU o número de pedido.', 'escritoriowp' ),
			'lanzadorRecientes'     => __( 'Recientes', 'escritoriowp' ),
			'lanzadorAcciones'      => __( 'Acciones rápidas', 'escritoriowp' ),
			/* translators: %s: texto buscado. */
			'lanzadorSinResultados' => __( 'Sin resultados para «%s»', 'escritoriowp' ),
			'lanzadorError'         => __( 'La búsqueda ha fallado. Los comandos siguen disponibles.', 'escritoriowp' ),
			'lanzadorVerTodos'      => __( 'Ver todos en el listado', 'escritoriowp' ),
			'lanzadorFiltroMenu'    => __( 'Menú', 'escritoriowp' ),
			/* translators: %s: número de resultados. */
			'lanzadorResultados'    => __( '%s resultados', 'escritoriowp' ),
			'lanzadorUnResultado'   => __( '1 resultado', 'escritoriowp' ),
			'lanzadorAbrir'         => __( 'abrir', 'escritoriowp' ),
			'lanzadorNuevaPestana'  => __( 'pestaña nueva', 'escritoriowp' ),
			'lanzadorVerSitio'      => __( 'ver en el sitio', 'escritoriowp' ),
			'lanzadorNavegar'       => __( 'navegar', 'escritoriowp' ),
			'lanzadorFiltrar'       => __( 'filtrar', 'escritoriowp' ),
		);
	}
}
