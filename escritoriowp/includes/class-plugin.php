<?php
/**
 * Punto de entrada del plugin: registra todos los módulos.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP;

use EscritorioWP\Escritorio\Escritorio;
use EscritorioWP\Lanzador\Lanzador;
use EscritorioWP\Rest\Rest;

defined( 'ABSPATH' ) || exit;

/**
 * Instancia única que compone los módulos del plugin y engancha sus acciones.
 */
final class Plugin {

	/**
	 * Instancia única.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instancia = null;

	/**
	 * Indica si el plugin ya arrancó, para evitar registrar los enganches dos veces.
	 *
	 * @var bool
	 */
	private bool $arrancado = false;

	/**
	 * Constructor privado: la clase se usa como singleton.
	 */
	private function __construct() {}

	/**
	 * Devuelve la instancia única.
	 *
	 * @return Plugin
	 */
	public static function instancia(): Plugin {
		if ( null === self::$instancia ) {
			self::$instancia = new self();
		}

		return self::$instancia;
	}

	/**
	 * Registra los enganches de todos los módulos.
	 *
	 * @return void
	 */
	public function arrancar(): void {
		if ( $this->arrancado ) {
			return;
		}

		$this->arrancado = true;

		add_action( 'init', array( $this, 'cargar_traducciones' ) );

		$ajustes = Ajustes::instancia();
		$ajustes->registrar();

		( new Activos( $ajustes ) )->registrar();
		( new Escritorio( $ajustes ) )->registrar();
		( new Lanzador( $ajustes ) )->registrar();
		( new Rest( $ajustes ) )->registrar();
	}

	/**
	 * Carga el dominio de texto del plugin.
	 *
	 * @return void
	 */
	public function cargar_traducciones(): void {
		load_plugin_textdomain(
			'escritoriowp',
			false,
			dirname( ESCRITORIOWP_BASENAME ) . '/languages'
		);
	}
}
