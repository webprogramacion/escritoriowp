<?php
/**
 * Pantalla «Acerca de» del menú del plugin.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP;

use EscritorioWP\Actualizaciones\ActualizadorGithub;
use EscritorioWP\Escritorio\Preferencias;

defined( 'ABSPATH' ) || exit;

/**
 * Muestra la versión instalada, los enlaces del proyecto y el historial de cambios.
 *
 * El historial sale del readme.txt que el propio plugin distribuye, así que no hay que mantener la
 * misma lista en dos sitios.
 */
final class AcercaDe {

	/**
	 * Identificador de la subpágina.
	 */
	public const PAGINA = 'escritoriowp-acerca';

	/**
	 * Identificador de pantalla que devuelve WordPress al registrar la subpágina.
	 *
	 * @var string
	 */
	private string $pantalla = '';

	/**
	 * Constructor.
	 *
	 * @param ActualizadorGithub|null $actualizador Actualizador, o null si el plugin no ofrece
	 *                                              actualizaciones propias.
	 */
	public function __construct( private readonly ?ActualizadorGithub $actualizador = null ) {}

	/**
	 * URL de la pantalla.
	 *
	 * @return string
	 */
	public static function url(): string {
		return admin_url( 'admin.php?page=' . self::PAGINA );
	}

	/**
	 * Registra los enganches del módulo.
	 *
	 * @return void
	 */
	public function registrar(): void {
		// Prioridad 11: el menú del plugin lo crea Ajustes en la prioridad 10.
		add_action( 'admin_menu', array( $this, 'anadir_pagina' ), 11 );
		add_action( 'admin_enqueue_scripts', array( $this, 'encolar' ) );
	}

	/**
	 * Añade la subpágina al menú del plugin.
	 *
	 * @return void
	 */
	public function anadir_pagina(): void {
		$this->pantalla = (string) add_submenu_page(
			Ajustes::PAGINA,
			__( 'Acerca de EscritorioWP', 'escritoriowp' ),
			__( 'Acerca de', 'escritoriowp' ),
			'manage_options',
			self::PAGINA,
			array( $this, 'pintar' )
		);
	}

	/**
	 * Encola la hoja de estilo de la pantalla.
	 *
	 * @param string $pantalla Identificador de la pantalla actual.
	 * @return void
	 */
	public function encolar( string $pantalla ): void {
		if ( '' === $this->pantalla || $this->pantalla !== $pantalla ) {
			return;
		}

		wp_enqueue_style( 'escritoriowp-ajustes' );
	}

	/**
	 * Pinta la pantalla.
	 *
	 * @return void
	 */
	public function pintar(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'escritoriowp' ) );
		}

		$datos        = $this->datos();
		$preferencias = Preferencias::obtener();
		$repositorio  = (string) ESCRITORIOWP_REPOSITORIO;

		?>
		<div class="wrap escritoriowp-acerca">
			<h1><?php echo esc_html__( 'Acerca de EscritorioWP', 'escritoriowp' ); ?></h1>

			<div class="escritoriowp" data-tema="<?php echo esc_attr( $preferencias['tema'] ); ?>">
				<div class="escritoriowp-acerca__cabecera">
					<div>
						<h2 class="escritoriowp-acerca__nombre"><?php echo esc_html( $datos['Name'] ); ?></h2>
						<p class="escritoriowp-acerca__descripcion"><?php echo esc_html( $datos['Description'] ); ?></p>
					</div>
					<span class="escritoriowp-etiqueta" data-tono="azul">
						<?php
						printf(
							/* translators: %s: número de versión instalada. */
							esc_html__( 'Versión %s', 'escritoriowp' ),
							esc_html( ESCRITORIOWP_VERSION )
						);
						?>
					</span>
				</div>

				<p class="escritoriowp-acerca__enlaces">
					<?php if ( '' !== $repositorio ) : ?>
						<a class="escritoriowp-boton escritoriowp-boton--claro" href="<?php echo esc_url( $repositorio ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html__( 'Repositorio en GitHub', 'escritoriowp' ); ?>
						</a>
						<a class="escritoriowp-boton escritoriowp-boton--claro" href="<?php echo esc_url( $repositorio . '/releases' ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html__( 'Todas las versiones', 'escritoriowp' ); ?>
						</a>
					<?php endif; ?>
					<?php if ( '' !== $datos['AuthorURI'] ) : ?>
						<a class="escritoriowp-boton escritoriowp-boton--claro" href="<?php echo esc_url( $datos['AuthorURI'] ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $datos['Author'] ); ?>
						</a>
					<?php endif; ?>
					<a class="escritoriowp-boton escritoriowp-boton--claro" href="<?php echo esc_url( Ajustes::url() ); ?>">
						<?php echo esc_html__( 'Ajustes del plugin', 'escritoriowp' ); ?>
					</a>
				</p>

				<?php $this->pintar_actualizacion(); ?>

				<h2 class="escritoriowp-acerca__titulo"><?php echo esc_html__( 'Novedades', 'escritoriowp' ); ?></h2>
				<?php $this->pintar_novedades(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Pinta el estado de la actualización y el botón para comprobarla.
	 *
	 * Solo se lee lo que ya está guardado: pintar esta pantalla nunca provoca una petición a GitHub.
	 * Quien pregunta es la comprobación periódica de WordPress, o el usuario con el botón.
	 *
	 * @return void
	 */
	private function pintar_actualizacion(): void {
		if ( null === $this->actualizador || ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		$release = $this->actualizador->guardada();
		$hay_una = null !== $release && version_compare( $release['version'], ESCRITORIOWP_VERSION, '>' );

		/*
		 * Solo se mira si venimos de pulsar el botón, para elegir el mensaje. No hay ninguna acción
		 * asociada a este parámetro, así que no procede comprobar ningún nonce aquí.
		 */
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$comprobado = isset( $_GET['comprobado'] );

		?>
		<div class="escritoriowp-acerca__actualizacion" data-estado="<?php echo $hay_una ? 'nueva' : 'al-dia'; ?>">
			<p class="escritoriowp-acerca__estado">
				<?php if ( $hay_una ) : ?>
					<strong>
						<?php
						printf(
							/* translators: %s: número de la versión disponible. */
							esc_html__( 'Hay una versión nueva disponible: %s', 'escritoriowp' ),
							esc_html( $release['version'] )
						);
						?>
					</strong>
				<?php elseif ( null !== $release ) : ?>
					<?php echo esc_html__( 'Estás en la última versión publicada.', 'escritoriowp' ); ?>
				<?php elseif ( $comprobado ) : ?>
					<?php echo esc_html__( 'No se ha podido consultar GitHub. Vuelve a intentarlo más tarde.', 'escritoriowp' ); ?>
				<?php else : ?>
					<?php echo esc_html__( 'Todavía no se ha comprobado si hay versiones nuevas.', 'escritoriowp' ); ?>
				<?php endif; ?>
			</p>

			<p class="escritoriowp-acerca__acciones">
				<?php if ( $hay_una ) : ?>
					<a class="escritoriowp-boton" href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>">
						<?php echo esc_html__( 'Actualizar en la pantalla Plugins', 'escritoriowp' ); ?>
					</a>
				<?php endif; ?>

				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<input type="hidden" name="action" value="<?php echo esc_attr( ActualizadorGithub::ACCION ); ?>">
					<?php wp_nonce_field( ActualizadorGithub::ACCION ); ?>
					<button type="submit" class="escritoriowp-boton escritoriowp-boton--claro">
						<?php echo esc_html__( 'Buscar actualizaciones ahora', 'escritoriowp' ); ?>
					</button>
				</form>
			</p>
		</div>
		<?php
	}

	/**
	 * Pinta el historial de cambios leído del readme.txt del plugin.
	 *
	 * @return void
	 */
	private function pintar_novedades(): void {
		$versiones = Changelog::desde_readme( $this->leer_readme() );

		if ( array() === $versiones ) {
			printf(
				'<p class="escritoriowp-acerca__vacio">%s</p>',
				esc_html__( 'No hay historial de cambios disponible.', 'escritoriowp' )
			);

			return;
		}

		foreach ( $versiones as $version ) {
			$instalada = ESCRITORIOWP_VERSION === $version['version'];

			?>
			<section class="escritoriowp-acerca__version">
				<h3>
					<?php echo esc_html( $version['version'] ); ?>
					<?php if ( $instalada ) : ?>
						<span class="escritoriowp-etiqueta" data-tono="verde"><?php echo esc_html__( 'Instalada', 'escritoriowp' ); ?></span>
					<?php endif; ?>
				</h3>
				<?php if ( array() !== $version['cambios'] ) : ?>
					<ul>
						<?php foreach ( $version['cambios'] as $cambio ) : ?>
							<li><?php echo esc_html( $cambio ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</section>
			<?php
		}
	}

	/**
	 * Lee el readme.txt que viaja dentro del plugin.
	 *
	 * @return string Cadena vacía si el fichero no se puede leer.
	 */
	private function leer_readme(): string {
		$ruta = ESCRITORIOWP_DIR . 'readme.txt';

		if ( ! is_readable( $ruta ) ) {
			return '';
		}

		/*
		 * Se lee un fichero propio del plugin, dentro de su directorio y de solo lectura. No procede
		 * WP_Filesystem, que está pensado para escribir y para sistemas de ficheros remotos: pedir
		 * credenciales de FTP para pintar el historial dejaría la pantalla vacía en muchos sitios.
		 */
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$contenido = file_get_contents( $ruta );

		return false === $contenido ? '' : $contenido;
	}

	/**
	 * Cabecera del propio plugin.
	 *
	 * @return array<string, string>
	 */
	private function datos(): array {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return get_plugin_data( ESCRITORIOWP_FILE, false, true );
	}
}
