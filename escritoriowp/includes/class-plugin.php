<?php
/**
 * Punto de entrada del plugin: registra todos los módulos.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP;

use EscritorioWP\Actualizaciones\ActualizadorGithub;
use EscritorioWP\Avisos\AvisosAdministradores;
use EscritorioWP\Avisos\AvisosPlugins;
use EscritorioWP\Avisos\Correo;
use EscritorioWP\Escritorio\Escritorio;
use EscritorioWP\Lanzador\Lanzador;
use EscritorioWP\Rest\Rest;
use EscritorioWP\Seguridad\Administradores;
use EscritorioWP\Seguridad\Detector;

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

		if ( is_admin() ) {
			( new Menu( $ajustes ) )->registrar();
		}

		( new Escritorio( $ajustes ) )->registrar();
		( new Lanzador( $ajustes ) )->registrar();
		( new Rest( $ajustes ) )->registrar();

		// Fuera del administrador también: un alta o un cambio de rol desde la tienda o la API REST
		// tiene que invalidar el contador del menú.
		Detector::registrar();
		( new Administradores() )->registrar();

		// También fuera del administrador: la API REST y WP-CLI activan, instalan y borran plugins.
		$correo = new Correo( $ajustes );
		( new AvisosPlugins( $ajustes, $correo ) )->registrar();

		// También fuera del administrador: la revisión corre en WP-Cron.
		( new AvisosAdministradores( $ajustes, $correo ) )->registrar();

		$actualizador = $this->actualizador();
		$actualizador?->registrar();

		( new AcercaDe( $actualizador ) )->registrar();
	}

	/**
	 * Crea el actualizador desde GitHub, si esta copia del plugin lo incluye.
	 *
	 * La variante que se envía al directorio de WordPress.org deja vacía la constante del
	 * repositorio, y entonces el plugin no registra ningún enganche de actualizaciones.
	 *
	 * @return ActualizadorGithub|null
	 */
	private function actualizador(): ?ActualizadorGithub {
		$repositorio = defined( 'ESCRITORIOWP_REPOSITORIO' ) ? (string) ESCRITORIOWP_REPOSITORIO : '';

		return '' === $repositorio ? null : new ActualizadorGithub( $repositorio );
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
