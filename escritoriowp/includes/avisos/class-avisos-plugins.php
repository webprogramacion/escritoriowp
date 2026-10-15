<?php
/**
 * Avisos por correo cuando se activa, desactiva, instala o borra un plugin.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Avisos;

use EscritorioWP\Ajustes;

defined( 'ABSPATH' ) || exit;

/**
 * Escucha los cambios en los plugins y avisa por correo a los destinatarios configurados.
 */
final class AvisosPlugins {

	/**
	 * Evento: un plugin se ha activado.
	 */
	public const ACTIVACION = 'activacion';

	/**
	 * Evento: un plugin se ha desactivado.
	 */
	public const DESACTIVACION = 'desactivacion';

	/**
	 * Evento: un plugin se ha instalado.
	 */
	public const INSTALACION = 'instalacion';

	/**
	 * Evento: un plugin se ha borrado.
	 */
	public const BORRADO = 'borrado';

	/**
	 * Casilla de ajustes que enciende el aviso de cada evento.
	 */
	public const CASILLAS = array(
		self::ACTIVACION    => 'aviso_activar',
		self::DESACTIVACION => 'aviso_desactivar',
		self::INSTALACION   => 'aviso_instalar',
		self::BORRADO       => 'aviso_borrar',
	);

	/**
	 * Ajustes del plugin.
	 *
	 * @var Ajustes
	 */
	private Ajustes $ajustes;

	/**
	 * Envío de los correos.
	 *
	 * @var Correo
	 */
	private Correo $correo;

	/**
	 * Plugins afectados en esta petición, por evento y por fichero, a la espera de `shutdown`.
	 *
	 * @var array<string, array<string, array{nombre: string, version: string, red: bool}>>
	 */
	private array $pendientes = array();

	/**
	 * Nombre y versión de los plugins que WordPress va a borrar, leídos mientras sus ficheros existen.
	 *
	 * @var array<string, array{nombre: string, version: string}>
	 */
	private array $por_borrar = array();

	/**
	 * Constructor.
	 *
	 * @param Ajustes $ajustes Ajustes del plugin.
	 * @param Correo  $correo  Envío de los correos.
	 */
	public function __construct( Ajustes $ajustes, Correo $correo ) {
		$this->ajustes = $ajustes;
		$this->correo  = $correo;
	}

	/**
	 * Registra los enganches de los eventos cuya casilla está marcada; con todas apagadas, ninguno.
	 *
	 * Solo se escuchan los enganches que WordPress dispara cuando el cambio no es silencioso: al
	 * actualizar un plugin, WordPress lo desactiva y lo reactiva en silencio, y eso no debe avisar.
	 *
	 * @return void
	 */
	public function registrar(): void {
		if ( $this->vigila( self::ACTIVACION ) ) {
			add_action( 'activated_plugin', array( $this, 'al_activar' ), 10, 2 );
		}

		if ( $this->vigila( self::DESACTIVACION ) ) {
			add_action( 'deactivated_plugin', array( $this, 'al_desactivar' ), 10, 2 );
		}

		if ( $this->vigila( self::INSTALACION ) ) {
			add_action( 'upgrader_process_complete', array( $this, 'al_instalar' ), 10, 2 );
		}

		if ( $this->vigila( self::BORRADO ) ) {
			add_action( 'delete_plugin', array( $this, 'al_ir_a_borrar' ) );
			add_action( 'deleted_plugin', array( $this, 'al_borrar' ), 10, 2 );
		}
	}

	/**
	 * Indica si el aviso de un evento está encendido.
	 *
	 * @param string $evento Uno de los eventos de la clase.
	 * @return bool
	 */
	private function vigila( string $evento ): bool {
		return (bool) $this->ajustes->obtener( self::CASILLAS[ $evento ] );
	}

	/**
	 * Anota la activación de un plugin.
	 *
	 * @param mixed $archivo Fichero principal del plugin, relativo a la carpeta de plugins.
	 * @param mixed $red     Si se ha activado para toda la red.
	 * @return void
	 */
	public function al_activar( mixed $archivo, mixed $red = false ): void {
		if ( is_string( $archivo ) ) {
			$this->anotar( self::ACTIVACION, $archivo, self::datos_plugin( $archivo ), (bool) $red );
		}
	}

	/**
	 * Anota la desactivación de un plugin, también la del propio EscritorioWP.
	 *
	 * @param mixed $archivo Fichero principal del plugin, relativo a la carpeta de plugins.
	 * @param mixed $red     Si se ha desactivado para toda la red.
	 * @return void
	 */
	public function al_desactivar( mixed $archivo, mixed $red = false ): void {
		if ( is_string( $archivo ) ) {
			$this->anotar( self::DESACTIVACION, $archivo, self::datos_plugin( $archivo ), (bool) $red );
		}
	}

	/**
	 * Anota la instalación de un plugin: desde el directorio, subiendo un zip (también si sustituye a
	 * uno instalado), por la API REST o con WP-CLI.
	 *
	 * WordPress dispara este enganche también cuando la instalación falla, y para actualizaciones,
	 * temas y traducciones. Solo cuenta una instalación de plugin que ha dejado su carpeta en su sitio:
	 * `plugin_info()` devuelve falso si el actualizador no llegó a copiar nada.
	 *
	 * @param mixed $actualizador Actualizador que ha terminado.
	 * @param mixed $extra        Datos del proceso: tipo, acción y elementos.
	 * @return void
	 */
	public function al_instalar( mixed $actualizador, mixed $extra = array() ): void {
		if (
			! $actualizador instanceof \Plugin_Upgrader
			|| ! is_array( $extra )
			|| 'plugin' !== ( $extra['type'] ?? '' )
			|| 'install' !== ( $extra['action'] ?? '' )
			|| ! is_array( $actualizador->result )
		) {
			return;
		}

		$archivo = $actualizador->plugin_info();

		if ( is_string( $archivo ) && '' !== $archivo ) {
			$this->anotar( self::INSTALACION, $archivo, self::datos_plugin( $archivo ), false );
		}
	}

	/**
	 * Lee el nombre y la versión de un plugin justo antes de que WordPress borre sus ficheros.
	 *
	 * @param mixed $archivo Fichero principal del plugin, relativo a la carpeta de plugins.
	 * @return void
	 */
	public function al_ir_a_borrar( mixed $archivo ): void {
		if ( is_string( $archivo ) ) {
			$this->por_borrar[ $archivo ] = self::datos_plugin( $archivo );
		}
	}

	/**
	 * Anota el borrado de un plugin si WordPress ha conseguido eliminar sus ficheros.
	 *
	 * @param mixed $archivo Fichero principal del plugin, relativo a la carpeta de plugins.
	 * @param mixed $borrado Si el borrado ha tenido éxito.
	 * @return void
	 */
	public function al_borrar( mixed $archivo, mixed $borrado = false ): void {
		if ( ! is_string( $archivo ) || ! isset( $this->por_borrar[ $archivo ] ) ) {
			return;
		}

		$datos = $this->por_borrar[ $archivo ];
		unset( $this->por_borrar[ $archivo ] );

		if ( $borrado ) {
			$this->anotar( self::BORRADO, $archivo, $datos, false );
		}
	}

	/**
	 * Guarda un plugin afectado hasta el final de la petición.
	 *
	 * Los correos se envían en `shutdown`, uno por evento: así una acción en lote manda un correo con
	 * todos los plugins y no uno por plugin. `shutdown` corre también tras las redirecciones y tras
	 * `wp_die()` en AJAX. No se usa cron: si lo desactivado es EscritorioWP, cuando corriera ya no
	 * existiría quien enviara el aviso.
	 *
	 * @param string                                 $evento  Uno de los eventos de la clase.
	 * @param string                                 $archivo Fichero principal del plugin.
	 * @param array{nombre: string, version: string} $datos   Nombre y versión del plugin.
	 * @param bool                                   $red     Si el cambio afecta a toda la red.
	 * @return void
	 */
	private function anotar( string $evento, string $archivo, array $datos, bool $red ): void {
		if ( array() === $this->pendientes ) {
			add_action( 'shutdown', array( $this, 'enviar' ) );
		}

		$this->pendientes[ $evento ][ $archivo ] = array(
			'nombre'  => $datos['nombre'],
			'version' => $datos['version'],
			'red'     => $red,
		);
	}

	/**
	 * Nombre y versión de un plugin instalado, leídos de su cabecera sin traducir ni formatear.
	 *
	 * @param string $archivo Fichero principal, relativo a la carpeta de plugins.
	 * @return array{nombre: string, version: string} Si no hay nombre, el nombre es el fichero.
	 */
	private static function datos_plugin( string $archivo ): array {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$ruta   = WP_PLUGIN_DIR . '/' . $archivo;
		$datos  = is_readable( $ruta ) ? get_plugin_data( $ruta, false, false ) : array();
		$nombre = trim( (string) ( $datos['Name'] ?? '' ) );

		return array(
			'nombre'  => '' === $nombre ? $archivo : $nombre,
			'version' => trim( (string) ( $datos['Version'] ?? '' ) ),
		);
	}

	/**
	 * Envía los avisos anotados en esta petición: un correo por evento, en el orden en que aparecieron.
	 *
	 * Corre en `shutdown`: un error aquí no puede deshacer la acción, pero sí ensuciar la respuesta, así
	 * que cada envío va protegido. Si `wp_mail()` falla, el aviso queda en el registro como fallido.
	 *
	 * @return void
	 */
	public function enviar(): void {
		$pendientes       = $this->pendientes;
		$this->pendientes = array();

		foreach ( $pendientes as $evento => $plugins ) {
			try {
				$this->enviar_evento( (string) $evento, $plugins );
			} catch ( \Throwable ) {
				// Sin reintentos: `Correo` ya ha dejado el aviso en el registro como fallido, con su motivo.
				continue;
			}
		}
	}

	/**
	 * Rellena la plantilla de un evento y envía su correo.
	 *
	 * @param string                                                           $evento  Uno de los eventos de la clase.
	 * @param array<string, array{nombre: string, version: string, red: bool}> $plugins Plugins afectados, por fichero.
	 * @return void
	 */
	private function enviar_evento( string $evento, array $plugins ): void {
		$nombres = array();
		$lineas  = array();

		foreach ( $plugins as $archivo => $plugin ) {
			$nombres[] = Plantilla::una_linea( $plugin['nombre'] . ' ' . $plugin['version'] );
			$lineas[]  = Plantilla::linea(
				$plugin['nombre'],
				$plugin['version'],
				(string) $archivo,
				$plugin['red'] ? __( 'en toda la red', 'escritoriowp' ) : ''
			);
		}

		$valores = array(
			'evento'      => self::etiqueta_evento( $evento ),
			'plugins'     => Plantilla::resumir(
				$nombres,
				/* translators: 1: nombres separados por comas, 2: el último nombre. */
				__( '%1$s y %2$s', 'escritoriowp' ),
				/* translators: 1: los primeros nombres, separados por comas, 2: cuántos más hay. */
				__( '%1$s y %2$d más', 'escritoriowp' )
			),
			'lista'       => implode( "\n", $lineas ),
			'cantidad'    => count( $plugins ),
			'usuario'     => self::usuario(),
			'fecha'       => (string) wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
			'sitio'       => wp_specialchars_decode( (string) get_option( 'blogname' ), ENT_QUOTES ),
			'url'         => home_url( '/' ),
			'url_plugins' => self_admin_url( 'plugins.php' ),
			'ip'          => self::ip(),
		);

		$asunto  = (string) $this->ajustes->obtener( 'aviso_asunto' );
		$mensaje = (string) $this->ajustes->obtener( 'aviso_mensaje' );

		$this->correo->enviar(
			$evento,
			Plantilla::una_linea( Plantilla::rellenar( '' === $asunto ? self::asunto_por_defecto() : $asunto, $valores ) ),
			Plantilla::rellenar( '' === $mensaje ? self::mensaje_por_defecto() : $mensaje, $valores )
		);
	}

	/**
	 * Quién ha hecho el cambio: «Nombre visible (login)», solo el login si coinciden, o «WP-CLI».
	 *
	 * @return string
	 */
	private static function usuario(): string {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return 'WP-CLI';
		}

		$usuario = wp_get_current_user();

		if ( ! $usuario->exists() ) {
			return __( 'Sin usuario identificado', 'escritoriowp' );
		}

		$login  = (string) $usuario->user_login;
		$nombre = trim( (string) $usuario->display_name );

		return ( '' === $nombre || $nombre === $login ) ? $login : $nombre . ' (' . $login . ')';
	}

	/**
	 * IP desde la que llega la petición, o «—» si no hay.
	 *
	 * Solo `REMOTE_ADDR`: las cabeceras como `X-Forwarded-For` las escribe quien hace la petición, y en
	 * un aviso de seguridad una IP inventada es peor que la del proxy. Con WP-CLI no hay petición,
	 * aunque WP-CLI rellena `REMOTE_ADDR` con `127.0.0.1` para imitarla.
	 *
	 * @return string
	 */
	private static function ip(): string {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return '—';
		}

		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		return false === filter_var( $ip, FILTER_VALIDATE_IP ) ? '—' : $ip;
	}

	/**
	 * Asunto predeterminado de los avisos.
	 *
	 * @return string
	 */
	public static function asunto_por_defecto(): string {
		/* translators: Asunto predeterminado del aviso por correo. Los marcadores entre llaves no se traducen. */
		return __( '[{sitio}] {evento}: {plugins}', 'escritoriowp' );
	}

	/**
	 * Mensaje predeterminado de los avisos.
	 *
	 * @return string
	 */
	public static function mensaje_por_defecto(): string {
		/* translators: Mensaje predeterminado del aviso por correo. Los marcadores entre llaves no se traducen. */
		return __( "Ha habido un cambio en los plugins de {sitio} ({url}).\n\nEvento: {evento}\nPlugins ({cantidad}):\n{lista}\n\nUsuario: {usuario}\nIP: {ip}\nFecha: {fecha}\n\nRevisa los plugins en {url_plugins}", 'escritoriowp' );
	}

	/**
	 * Nombre de un evento, como sustantivo para que concuerde igual con uno o con varios plugins.
	 *
	 * @param string $evento Uno de los eventos de la clase.
	 * @return string
	 */
	public static function etiqueta_evento( string $evento ): string {
		return match ( $evento ) {
			self::ACTIVACION    => __( 'Activación', 'escritoriowp' ),
			self::DESACTIVACION => __( 'Desactivación', 'escritoriowp' ),
			self::INSTALACION   => __( 'Instalación', 'escritoriowp' ),
			self::BORRADO       => __( 'Borrado', 'escritoriowp' ),
			default             => $evento,
		};
	}

	/**
	 * Significado de cada marcador, en el orden de `Plantilla::MARCADORES`, para la leyenda de ajustes.
	 *
	 * @return array<string, string>
	 */
	public static function marcadores(): array {
		return array(
			'evento'      => __( 'Qué ha pasado: Activación, Desactivación, Instalación o Borrado.', 'escritoriowp' ),
			'plugins'     => __( 'Nombre y versión de los plugins, resumidos para el asunto: los tres primeros y cuántos más hay.', 'escritoriowp' ),
			'lista'       => __( 'Un plugin por línea, con su versión y su fichero.', 'escritoriowp' ),
			'cantidad'    => __( 'Número de plugins del aviso.', 'escritoriowp' ),
			'usuario'     => __( 'Quién ha hecho el cambio: su nombre visible y su nombre de usuario, o WP-CLI.', 'escritoriowp' ),
			'fecha'       => __( 'Fecha y hora, en el formato y la zona horaria del sitio.', 'escritoriowp' ),
			'sitio'       => __( 'Nombre del sitio.', 'escritoriowp' ),
			'url'         => __( 'Dirección del sitio.', 'escritoriowp' ),
			'url_plugins' => __( 'Enlace a la pantalla Plugins.', 'escritoriowp' ),
			'ip'          => __( 'IP desde la que se hizo el cambio. Si el sitio está detrás de un proxy o una CDN, es la del proxy.', 'escritoriowp' ),
		);
	}
}
