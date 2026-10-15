<?php
/**
 * Ajustes globales del plugin y su página de administración.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP;

use EscritorioWP\Avisos\AvisosAdministradores;
use EscritorioWP\Avisos\AvisosPlugins;
use EscritorioWP\Avisos\PantallaAvisos;
use EscritorioWP\Avisos\Plantilla;
use EscritorioWP\Escritorio\Resumen;

defined( 'ABSPATH' ) || exit;

/**
 * Gestiona la opción de ajustes, sus valores por defecto y la pantalla EscritorioWP › Ajustes.
 */
final class Ajustes {

	/**
	 * Nombre de la opción donde se guardan todos los ajustes.
	 */
	public const OPCION = 'escritoriowp_ajustes';

	/**
	 * Grupo de ajustes de la Settings API.
	 */
	private const GRUPO = 'escritoriowp_grupo_ajustes';

	/**
	 * Identificador de la subpágina de ajustes.
	 */
	public const PAGINA = 'escritoriowp';

	/**
	 * Número mínimo de elementos por panel.
	 */
	public const MIN_ELEMENTOS = 3;

	/**
	 * Número máximo de elementos por panel.
	 */
	public const MAX_ELEMENTOS = 20;

	/**
	 * Menú bajo el que aparece el del plugin si no se elige otro: el Escritorio.
	 */
	public const MENU_POR_DEFECTO = 'index.php';

	/**
	 * Longitud máxima del identificador del menú de referencia.
	 */
	private const MAX_LONGITUD_MENU = 200;

	/**
	 * Número máximo de destinatarios de los avisos por correo.
	 */
	public const MAX_DESTINATARIOS = 10;

	/**
	 * Longitud máxima, en caracteres, del asunto de los avisos.
	 */
	private const MAX_ASUNTO = 200;

	/**
	 * Longitud máxima, en caracteres, del mensaje de los avisos.
	 */
	private const MAX_MENSAJE = 5000;

	/**
	 * Número máximo de tipos de contenido personalizados activos en cada lista de casillas.
	 */
	public const MAX_TIPOS_CONTENIDO = 100;

	/**
	 * Instancia única.
	 *
	 * @var Ajustes|null
	 */
	private static ?Ajustes $instancia = null;

	/**
	 * Ajustes ya resueltos durante esta petición.
	 *
	 * @var array<string, bool|int|string|array<string, bool>>|null
	 */
	private ?array $cache = null;

	/**
	 * Identificadores de pantalla que WordPress devuelve al registrar el menú.
	 *
	 * @var array<int, string>
	 */
	private array $pantallas = array();

	/**
	 * Constructor privado: la clase se usa como singleton.
	 */
	private function __construct() {}

	/**
	 * Devuelve la instancia única.
	 *
	 * @return Ajustes
	 */
	public static function instancia(): Ajustes {
		if ( null === self::$instancia ) {
			self::$instancia = new self();
		}

		return self::$instancia;
	}

	/**
	 * Valores por defecto de todos los ajustes.
	 *
	 * `indicadores_activos` y `paneles_activos` dicen, para todo el sitio, qué indicadores y paneles
	 * muestra el escritorio: al principio, todos los propios y ningún tipo de contenido
	 * personalizado, que solo aparece si su clave `cpt-…` está guardada. `menu_debajo_de` vacío
	 * significa «Al final»;
	 * `menu_color_fondo` vacío, sin color propio. Los avisos por correo empiezan apagados;
	 * `aviso_destinatarios` vacío significa el correo de administración del sitio, y `aviso_asunto` y
	 * `aviso_mensaje` vacíos, el texto predeterminado, que se traduce al enviar en lugar de guardarse
	 * en el idioma de la activación.
	 *
	 * @return array<string, bool|int|string|array<string, bool>>
	 */
	public static function defectos(): array {
		return array(
			'sustituir_escritorio'     => true,
			'ocultar_widgets_terceros' => true,
			'desactivar_paleta_nativa' => true,
			'lanzador_activo'          => true,
			'lanzador_frontend'        => true,
			'elementos_por_panel'      => 5,
			'indicadores_activos'      => array_fill_keys( array_keys( Resumen::INDICADORES ), true ),
			'paneles_activos'          => array_fill_keys( Capacidades::TIPOS_PANEL, true ),
			'menu_debajo_de'           => self::MENU_POR_DEFECTO,
			'menu_color_fondo'         => '',
			'aviso_activar'            => false,
			'aviso_desactivar'         => false,
			'aviso_instalar'           => false,
			'aviso_borrar'             => false,
			'aviso_administradores'    => false,
			'aviso_destinatarios'      => '',
			'aviso_asunto'             => '',
			'aviso_mensaje'            => '',
		);
	}

	/**
	 * Sanea un conjunto de ajustes y lo completa con los valores por defecto.
	 *
	 * Cada ajuste se sanea según su tipo. Las casillas no necesitan rama propia; un ajuste de otro
	 * tipo sin rama conserva su valor por defecto, para que nunca se guarde mal interpretado.
	 *
	 * Función pura: no usa funciones de WordPress, para poder probarla de forma aislada.
	 *
	 * @param mixed $entrada Valores sin sanear.
	 * @return array<string, bool|int|string|array<string, bool>> Ajustes saneados y completos.
	 */
	public static function sanear( mixed $entrada ): array {
		$entrada = is_array( $entrada ) ? $entrada : array();
		$salida  = array();

		foreach ( self::defectos() as $clave => $defecto ) {
			if ( ! array_key_exists( $clave, $entrada ) ) {
				$salida[ $clave ] = $defecto;
				continue;
			}

			$valor = $entrada[ $clave ];

			$salida[ $clave ] = match ( $clave ) {
				'elementos_por_panel' => self::sanear_elementos( $valor ),
				'indicadores_activos' => self::sanear_activos( $valor, array_keys( Resumen::INDICADORES ) ),
				'paneles_activos'     => self::sanear_activos( $valor, Capacidades::TIPOS_PANEL ),
				'menu_debajo_de'      => self::sanear_menu_debajo_de( $valor ),
				'menu_color_fondo'    => self::sanear_color( $valor ),
				'aviso_destinatarios' => self::sanear_correos( $valor ),
				'aviso_asunto'        => self::sanear_asunto( $valor ),
				'aviso_mensaje'       => self::sanear_mensaje( $valor ),
				default               => is_bool( $defecto ) ? self::a_booleano( $valor ) : $defecto,
			};
		}

		return $salida;
	}

	/**
	 * Acota el número de elementos por panel entre el mínimo y el máximo.
	 *
	 * @param mixed $valor Valor recibido.
	 * @return int
	 */
	private static function sanear_elementos( mixed $valor ): int {
		$numero = is_numeric( $valor ) ? (int) $valor : (int) self::defectos()['elementos_por_panel'];

		return max( self::MIN_ELEMENTOS, min( self::MAX_ELEMENTOS, $numero ) );
	}

	/**
	 * Sanea una lista de casillas, como la de los paneles o los indicadores activos.
	 *
	 * Devuelve un booleano por cada clave conocida, en su orden. Una clave que no llega vale
	 * verdadero, para que lo que añada una versión futura salga activo; las desconocidas se
	 * descartan. El formulario envía `0` o `1` por cada casilla, igual que el tipo `casilla`.
	 *
	 * Detrás van las claves de tipos de contenido personalizados (`cpt-…`) que llegan marcadas,
	 * hasta `MAX_TIPOS_CONTENIDO`. Un tipo personalizado que falta está desactivado, así que las
	 * desmarcadas no se guardan. No se comprueba si el tipo está registrado: puede desaparecer y
	 * volver, como el de un plugin desactivado y reactivado.
	 *
	 * @param mixed    $valor  Valor recibido: clave => valor de la casilla.
	 * @param string[] $claves Claves conocidas, en el orden en que se guardan.
	 * @return array<string, bool>
	 */
	public static function sanear_activos( mixed $valor, array $claves ): array {
		$valor  = is_array( $valor ) ? $valor : array();
		$salida = array();

		foreach ( $claves as $clave ) {
			$salida[ $clave ] = ! array_key_exists( $clave, $valor ) || self::a_booleano( $valor[ $clave ] );
		}

		$tipos = 0;

		foreach ( $valor as $clave => $marcada ) {
			if ( $tipos >= self::MAX_TIPOS_CONTENIDO ) {
				break;
			}

			if ( TiposContenido::es_clave( (string) $clave ) && self::a_booleano( $marcada ) ) {
				$salida[ (string) $clave ] = true;
				++$tipos;
			}
		}

		return $salida;
	}

	/**
	 * Sanea el identificador del menú bajo el que aparece el del plugin.
	 *
	 * Solo admite los caracteres que aparecen en los identificadores de menú de WordPress y de los
	 * plugins (nombres de fichero con su consulta, como `edit.php?post_type=page`). No comprueba que
	 * el menú exista: puede desaparecer y volver, como el de un plugin desactivado y reactivado.
	 *
	 * @param mixed $valor Valor recibido.
	 * @return string Identificador del menú, o cadena vacía para «Al final».
	 */
	public static function sanear_menu_debajo_de( mixed $valor ): string {
		if ( ! is_string( $valor ) ) {
			return self::MENU_POR_DEFECTO;
		}

		$valor = trim( $valor );

		if ( '' === $valor ) {
			return '';
		}

		if (
			strlen( $valor ) > self::MAX_LONGITUD_MENU
			|| self::PAGINA === $valor
			|| 1 !== preg_match( '#^[A-Za-z0-9._\-/?=&%:]+$#', $valor )
		) {
			return self::MENU_POR_DEFECTO;
		}

		return $valor;
	}

	/**
	 * Sanea un color hexadecimal.
	 *
	 * @param mixed $valor Valor recibido.
	 * @return string Color en formato `#rrggbb` y minúsculas, o cadena vacía si no es válido.
	 */
	public static function sanear_color( mixed $valor ): string {
		if ( ! is_string( $valor ) || 1 !== preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', trim( $valor ), $partes ) ) {
			return '';
		}

		$hex = strtolower( $partes[1] );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		return '#' . $hex;
	}

	/**
	 * Sanea la lista de destinatarios de los avisos.
	 *
	 * Admite direcciones separadas por comas, punto y coma o espacios. Descarta las no válidas y las
	 * repetidas, y conserva como mucho `MAX_DESTINATARIOS`. Valida con `filter_var()` y no con
	 * `is_email()` para no depender de WordPress; `wp_mail()` vuelve a validar al enviar.
	 *
	 * @param mixed $valor Valor recibido.
	 * @return string Direcciones en minúsculas separadas por «, », o cadena vacía.
	 */
	public static function sanear_correos( mixed $valor ): string {
		return implode( ', ', self::partir_correos( $valor )['validos'] );
	}

	/**
	 * Direcciones que `sanear_correos()` descarta por no ser válidas, tal como se escribieron.
	 *
	 * @param mixed $valor Valor recibido.
	 * @return array<int, string>
	 */
	public static function correos_descartados( mixed $valor ): array {
		return self::partir_correos( $valor )['descartados'];
	}

	/**
	 * Separa una lista de destinatarios en direcciones válidas y descartadas.
	 *
	 * @param mixed $valor Valor recibido.
	 * @return array{validos: array<int, string>, descartados: array<int, string>}
	 */
	private static function partir_correos( mixed $valor ): array {
		$validos     = array();
		$descartados = array();
		$trozos      = is_string( $valor ) ? preg_split( '/[\s,;]+/', $valor, -1, PREG_SPLIT_NO_EMPTY ) : array();

		foreach ( (array) $trozos as $trozo ) {
			$correo = strtolower( $trozo );

			if ( false === filter_var( $correo, FILTER_VALIDATE_EMAIL ) ) {
				$descartados[] = $trozo;
				continue;
			}

			$validos[] = $correo;
		}

		return array(
			'validos'     => array_slice( array_values( array_unique( $validos ) ), 0, self::MAX_DESTINATARIOS ),
			'descartados' => array_values( array_unique( $descartados ) ),
		);
	}

	/**
	 * Sanea el asunto de los avisos: sin etiquetas, en una sola línea y con longitud máxima.
	 *
	 * @param mixed $valor Valor recibido.
	 * @return string Asunto saneado, o cadena vacía para el predeterminado.
	 */
	public static function sanear_asunto( mixed $valor ): string {
		if ( ! is_string( $valor ) ) {
			return '';
		}

		return mb_substr( Plantilla::una_linea( self::sin_etiquetas( $valor ) ), 0, self::MAX_ASUNTO );
	}

	/**
	 * Sanea el mensaje de los avisos: sin etiquetas, con saltos de línea `\n`, sin otros caracteres de
	 * control que el tabulador y con longitud máxima.
	 *
	 * @param mixed $valor Valor recibido.
	 * @return string Mensaje saneado, o cadena vacía para el predeterminado.
	 */
	public static function sanear_mensaje( mixed $valor ): string {
		if ( ! is_string( $valor ) ) {
			return '';
		}

		$texto = str_replace( array( "\r\n", "\r" ), "\n", self::sin_etiquetas( $valor ) );
		$texto = (string) preg_replace( '/[\x00-\x08\x0B-\x1F\x7F]+/', '', $texto );

		return trim( mb_substr( trim( $texto ), 0, self::MAX_MENSAJE ) );
	}

	/**
	 * Quita las etiquetas HTML de un texto, y el contenido de las de `script` y `style`.
	 *
	 * Es lo que hace `wp_strip_all_tags()`, sin depender de WordPress para poder probarlo; el correo
	 * es de texto plano, así que una etiqueta solo serviría para despistar. También la usa el registro
	 * de avisos con el motivo de un fallo.
	 *
	 * @param string $texto Texto recibido.
	 * @return string
	 */
	public static function sin_etiquetas( string $texto ): string {
		$texto = (string) preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $texto );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- La línea anterior hace la parte que añade wp_strip_all_tags(), que no está disponible en las pruebas.
		return strip_tags( $texto );
	}

	/**
	 * Interpreta un valor arbitrario como booleano.
	 *
	 * @param mixed $valor Valor recibido del formulario o de la base de datos.
	 * @return bool
	 */
	private static function a_booleano( mixed $valor ): bool {
		if ( is_string( $valor ) ) {
			return in_array( strtolower( $valor ), array( '1', 'true', 'on', 'si', 'sí', 'yes' ), true );
		}

		return (bool) $valor;
	}

	/**
	 * Devuelve todos los ajustes, completados con los valores por defecto.
	 *
	 * @return array<string, bool|int|string|array<string, bool>>
	 */
	public function todos(): array {
		if ( null === $this->cache ) {
			$this->cache = self::sanear( get_option( self::OPCION, array() ) );
		}

		return $this->cache;
	}

	/**
	 * Devuelve el valor de un ajuste concreto.
	 *
	 * Las listas de casillas se leen con `panel_activo()` e `indicador_activo()`, no con este método.
	 *
	 * @param string $clave Nombre del ajuste.
	 * @return bool|int|string|array<string, bool>
	 */
	public function obtener( string $clave ): bool|int|string|array {
		$ajustes = $this->todos();

		return $ajustes[ $clave ] ?? false;
	}

	/**
	 * Indica si un panel del escritorio está activo en el sitio. Un panel propio que no está en la
	 * lista está activo; uno de un tipo de contenido personalizado, no.
	 *
	 * No dice si el usuario actual puede verlo: eso lo decide `Capacidades`.
	 *
	 * @param string $tipo Clave del panel.
	 * @return bool
	 */
	public function panel_activo( string $tipo ): bool {
		return $this->activo( 'paneles_activos', $tipo );
	}

	/**
	 * Indica si un indicador del escritorio está activo en el sitio. Un indicador propio que no está
	 * en la lista está activo; uno de un tipo de contenido personalizado, no.
	 *
	 * No dice si el usuario actual puede verlo: eso lo decide `Resumen::claves_visibles()`.
	 *
	 * @param string $clave Clave del indicador.
	 * @return bool
	 */
	public function indicador_activo( string $clave ): bool {
		return $this->activo( 'indicadores_activos', $clave );
	}

	/**
	 * Lee una casilla de una lista de casillas. Una clave que no está en la lista vale verdadero si
	 * es propia y falso si es de un tipo de contenido personalizado, como en `sanear_activos()`.
	 *
	 * @param string $ajuste Nombre del ajuste que guarda la lista.
	 * @param string $clave  Clave de la casilla.
	 * @return bool
	 */
	private function activo( string $ajuste, string $clave ): bool {
		$lista = $this->obtener( $ajuste );

		if ( ! is_array( $lista ) || ! array_key_exists( $clave, $lista ) ) {
			return self::activo_por_defecto( $clave );
		}

		return (bool) $lista[ $clave ];
	}

	/**
	 * Valor de una casilla de una lista de casillas sin valor guardado: marcada si es propia,
	 * desmarcada si es de un tipo de contenido personalizado.
	 *
	 * @param string $clave Clave de la casilla.
	 * @return bool
	 */
	private static function activo_por_defecto( string $clave ): bool {
		return ! TiposContenido::es_clave( $clave );
	}

	/**
	 * Crea la opción con los valores por defecto si todavía no existe.
	 *
	 * @return void
	 */
	public function asegurar_defectos(): void {
		if ( false === get_option( self::OPCION, false ) ) {
			add_option( self::OPCION, self::defectos() );
		}

		$this->cache = null;
	}

	/**
	 * Registra los enganches de la pantalla de ajustes.
	 *
	 * @return void
	 */
	public function registrar(): void {
		add_action( 'admin_menu', array( $this, 'anadir_pagina' ) );
		add_action( 'admin_init', array( $this, 'registrar_ajustes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'encolar' ) );
	}

	/**
	 * URL de la pantalla de ajustes.
	 *
	 * @return string
	 */
	public static function url(): string {
		return admin_url( 'admin.php?page=' . self::PAGINA );
	}

	/**
	 * Encola la hoja de estilo de la pantalla de ajustes y el selector de color de WordPress.
	 *
	 * @param string $pantalla Identificador de la pantalla actual.
	 * @return void
	 */
	public function encolar( string $pantalla ): void {
		if ( ! in_array( $pantalla, $this->pantallas, true ) ) {
			return;
		}

		wp_enqueue_style( 'escritoriowp-ajustes' );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'escritoriowp-ajustes' );
	}

	/**
	 * Crea el menú propio del plugin con los ajustes como primera entrada.
	 *
	 * Sin posición explícita: dónde aparece lo decide `Menu`, que lo recoloca cuando el menú ya está
	 * completo según el ajuste «Posición en el menú».
	 *
	 * @return void
	 */
	public function anadir_pagina(): void {
		$this->pantallas[] = (string) add_menu_page(
			__( 'Ajustes de EscritorioWP', 'escritoriowp' ),
			__( 'EscritorioWP', 'escritoriowp' ),
			'manage_options',
			self::PAGINA,
			array( $this, 'pintar_pagina' ),
			'dashicons-dashboard'
		);

		/*
		 * Sin esta segunda llamada, la primera entrada del submenú repetiría el nombre del menú.
		 * Al compartir el identificador con el menú, WordPress la sustituye en lugar de añadirla.
		 */
		$this->pantallas[] = (string) add_submenu_page(
			self::PAGINA,
			__( 'Ajustes de EscritorioWP', 'escritoriowp' ),
			__( 'Ajustes', 'escritoriowp' ),
			'manage_options',
			self::PAGINA,
			array( $this, 'pintar_pagina' )
		);
	}

	/**
	 * Registra la opción y sus campos en la Settings API.
	 *
	 * @return void
	 */
	public function registrar_ajustes(): void {
		register_setting(
			self::GRUPO,
			self::OPCION,
			array(
				'type'              => 'object',
				'sanitize_callback' => array( $this, 'sanear_formulario' ),
				'default'           => self::defectos(),
			)
		);

		add_settings_section(
			'escritoriowp_seccion_escritorio',
			__( 'Escritorio', 'escritoriowp' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Controla qué se muestra al entrar en el escritorio de WordPress.', 'escritoriowp' ) . '</p>';
			},
			self::PAGINA
		);

		add_settings_section(
			'escritoriowp_seccion_lanzador',
			__( 'Lanzador', 'escritoriowp' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Controla el buscador que se abre con Comando+K o Control+K.', 'escritoriowp' ) . '</p>';
			},
			self::PAGINA
		);

		add_settings_section(
			'escritoriowp_seccion_menu',
			__( 'Menú', 'escritoriowp' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Controla dónde aparece EscritorioWP en la barra lateral del administrador y cómo se ve.', 'escritoriowp' ) . '</p>';
			},
			self::PAGINA
		);

		add_settings_section(
			'escritoriowp_seccion_avisos',
			__( 'Avisos por correo', 'escritoriowp' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Recibe un correo cada vez que alguien activa, desactiva, instala o borra un plugin, y cuando cambian los administradores del sitio. Si se cambian varios plugins a la vez, llega un solo correo con todos.', 'escritoriowp' ) . ' ';
				printf(
					/* translators: %s: enlace a la pantalla Avisos. */
					esc_html__( 'Cada aviso queda guardado en %s, también si el correo falla.', 'escritoriowp' ),
					'<a href="' . esc_url( PantallaAvisos::url() ) . '">' . esc_html__( 'EscritorioWP › Avisos', 'escritoriowp' ) . '</a>'
				);
				echo '</p>';
			},
			self::PAGINA
		);

		$campos = array(
			'sustituir_escritorio'     => array(
				'seccion'     => 'escritoriowp_seccion_escritorio',
				'tipo'        => 'casilla',
				'etiqueta'    => __( 'Sustituir el escritorio nativo', 'escritoriowp' ),
				'descripcion' => __( 'Muestra la interfaz de EscritorioWP en lugar de los widgets de WordPress.', 'escritoriowp' ),
			),
			'ocultar_widgets_terceros' => array(
				'seccion'     => 'escritoriowp_seccion_escritorio',
				'tipo'        => 'casilla',
				'etiqueta'    => __( 'Ocultar los widgets de otros plugins', 'escritoriowp' ),
				'descripcion' => __( 'Si lo desactivas, los widgets de otros plugins se muestran debajo, bajo el título «Otros widgets».', 'escritoriowp' ),
			),
			'indicadores_activos'      => array(
				'seccion'     => 'escritoriowp_seccion_escritorio',
				'tipo'        => 'casillas',
				'etiqueta'    => __( 'Indicadores', 'escritoriowp' ),
				'descripcion' => __( 'Las tarjetas de resumen de la parte de arriba del escritorio. Las que desmarques no las ve nadie; las marcadas, cada usuario solo si su rol le deja ver esa pantalla.', 'escritoriowp' ) . ' ' . self::descripcion_tipos_contenido(),
				'opciones'    => self::opciones_indicadores(),
			),
			'paneles_activos'          => array(
				'seccion'     => 'escritoriowp_seccion_escritorio',
				'tipo'        => 'casillas',
				'etiqueta'    => __( 'Paneles', 'escritoriowp' ),
				'descripcion' => __( 'Los paneles con los últimos elementos. Los que desmarques no los ve nadie; los marcados, cada usuario solo si su rol se lo permite, y además puede ocultarlos para sí desde «Personalizar», en el propio escritorio.', 'escritoriowp' ) . ' ' . self::descripcion_tipos_contenido(),
				'opciones'    => self::opciones_paneles(),
			),
			'elementos_por_panel'      => array(
				'seccion'     => 'escritoriowp_seccion_escritorio',
				'tipo'        => 'numero',
				'etiqueta'    => __( 'Elementos por panel', 'escritoriowp' ),
				'descripcion' => sprintf(
					/* translators: 1: número mínimo, 2: número máximo. */
					__( 'Cuántos elementos recientes muestra cada panel (entre %1$d y %2$d).', 'escritoriowp' ),
					self::MIN_ELEMENTOS,
					self::MAX_ELEMENTOS
				),
			),
			'desactivar_paleta_nativa' => array(
				'seccion'     => 'escritoriowp_seccion_lanzador',
				'tipo'        => 'casilla',
				'etiqueta'    => __( 'Desactivar la paleta de comandos nativa', 'escritoriowp' ),
				'descripcion' => __( 'Evita que WordPress cargue su paleta en el administrador. La paleta del editor de bloques no se toca.', 'escritoriowp' ),
			),
			'lanzador_activo'          => array(
				'seccion'     => 'escritoriowp_seccion_lanzador',
				'tipo'        => 'casilla',
				'etiqueta'    => __( 'Activar el lanzador de EscritorioWP', 'escritoriowp' ),
				'descripcion' => __( 'Abre el buscador con Comando+K o Control+K y desde el botón de la barra de administración.', 'escritoriowp' ),
			),
			'lanzador_frontend'        => array(
				'seccion'     => 'escritoriowp_seccion_lanzador',
				'tipo'        => 'casilla',
				'etiqueta'    => __( 'Disponible también en el sitio público', 'escritoriowp' ),
				'descripcion' => __( 'Para usuarios identificados que ven la barra de administración.', 'escritoriowp' ),
			),
			'menu_debajo_de'           => array(
				'seccion'     => 'escritoriowp_seccion_menu',
				'tipo'        => 'desplegable',
				'etiqueta'    => __( 'Posición en el menú', 'escritoriowp' ),
				'descripcion' => __( 'EscritorioWP aparece justo debajo del menú que elijas. Si ese menú deja de existir, por ejemplo al desactivar su plugin, vuelve a aparecer debajo del Escritorio.', 'escritoriowp' ),
			),
			'menu_color_fondo'         => array(
				'seccion'     => 'escritoriowp_seccion_menu',
				'tipo'        => 'color',
				'etiqueta'    => __( 'Color de fondo en el menú', 'escritoriowp' ),
				'descripcion' => __( 'Pinta el fondo de la entrada EscritorioWP para que destaque. El texto se pone en blanco o en negro según lo que se lea mejor. Déjalo vacío para que se vea como las demás.', 'escritoriowp' ),
			),
			'aviso_activar'            => array(
				'seccion'     => 'escritoriowp_seccion_avisos',
				'tipo'        => 'casilla',
				'etiqueta'    => __( 'Avisar al activar un plugin', 'escritoriowp' ),
				'descripcion' => __( 'Desde la pantalla Plugins, en lote, por la API REST o con WP-CLI.', 'escritoriowp' ),
			),
			'aviso_desactivar'         => array(
				'seccion'     => 'escritoriowp_seccion_avisos',
				'tipo'        => 'casilla',
				'etiqueta'    => __( 'Avisar al desactivar un plugin', 'escritoriowp' ),
				'descripcion' => __( 'También si alguien desactiva EscritorioWP. Las actualizaciones no avisan, aunque WordPress desactive y reactive el plugin para actualizarlo.', 'escritoriowp' ),
			),
			'aviso_instalar'           => array(
				'seccion'     => 'escritoriowp_seccion_avisos',
				'tipo'        => 'casilla',
				'etiqueta'    => __( 'Avisar al instalar un plugin', 'escritoriowp' ),
				'descripcion' => __( 'Desde el directorio de plugins, subiendo un zip (también si sustituye a uno instalado), por la API REST o con WP-CLI.', 'escritoriowp' ),
			),
			'aviso_borrar'             => array(
				'seccion'     => 'escritoriowp_seccion_avisos',
				'tipo'        => 'casilla',
				'etiqueta'    => __( 'Avisar al borrar un plugin', 'escritoriowp' ),
				'descripcion' => __( 'Con el nombre y la versión que tenía. La pantalla Plugins borra los plugins de uno en uno, así que un borrado en lote manda un correo por plugin.', 'escritoriowp' ),
			),
			'aviso_administradores'    => array(
				'seccion'     => 'escritoriowp_seccion_avisos',
				'tipo'        => 'casilla',
				'etiqueta'    => __( 'Avisar de cambios en los administradores', 'escritoriowp' ),
				'descripcion' => $this->descripcion_aviso_administradores(),
			),
			'aviso_destinatarios'      => array(
				'seccion'     => 'escritoriowp_seccion_avisos',
				'tipo'        => 'correo',
				'etiqueta'    => __( 'Destinatarios', 'escritoriowp' ),
				'descripcion' => sprintf(
					/* translators: %d: número máximo de direcciones. */
					__( 'Una o varias direcciones separadas por comas, hasta %d. Si lo dejas vacío, el aviso va al correo de administración del sitio.', 'escritoriowp' ),
					self::MAX_DESTINATARIOS
				),
				'ejemplo'     => (string) get_option( 'admin_email' ),
			),
			'aviso_asunto'             => array(
				'seccion'        => 'escritoriowp_seccion_avisos',
				'tipo'           => 'texto',
				'etiqueta'       => __( 'Asunto', 'escritoriowp' ),
				'descripcion'    => __( 'Solo para los avisos de plugins. Una sola línea. Vacíalo y guarda para volver al asunto predeterminado.', 'escritoriowp' ),
				'predeterminado' => AvisosPlugins::asunto_por_defecto(),
			),
			'aviso_mensaje'            => array(
				'seccion'        => 'escritoriowp_seccion_avisos',
				'tipo'           => 'area',
				'etiqueta'       => __( 'Mensaje', 'escritoriowp' ),
				'descripcion'    => __( 'Solo para los avisos de plugins. Texto plano, sin HTML. Vacíalo y guarda para volver al mensaje predeterminado. Puedes usar estos marcadores en el asunto y en el mensaje:', 'escritoriowp' ),
				'predeterminado' => AvisosPlugins::mensaje_por_defecto(),
				'leyenda'        => AvisosPlugins::marcadores(),
			),
		);

		foreach ( $campos as $clave => $campo ) {
			$args = array(
				'clave'       => $clave,
				'tipo'        => $campo['tipo'],
				'etiqueta'    => $campo['etiqueta'],
				'descripcion' => $campo['descripcion'],
			) + array_intersect_key( $campo, array_flip( array( 'predeterminado', 'ejemplo', 'leyenda', 'opciones' ) ) );

			// Un grupo de casillas se nombra con su `legend`: con `label_for`, la cabecera de la fila
			// sería la etiqueta de un campo que no existe.
			if ( 'casillas' !== $campo['tipo'] ) {
				$args['label_for'] = 'escritoriowp-campo-' . $clave;
			}

			add_settings_field(
				'escritoriowp_campo_' . $clave,
				$campo['etiqueta'],
				array( $this, 'pintar_campo' ),
				self::PAGINA,
				$campo['seccion'],
				$args
			);
		}
	}

	/**
	 * Descripción de la casilla del aviso de administradores: qué hace y, si está marcada, cuándo fue
	 * la última revisión y cuándo toca la próxima, para ver que WP-Cron la está ejecutando.
	 *
	 * @return string
	 */
	private function descripcion_aviso_administradores(): string {
		$descripcion = __( 'Revisa cada 6 horas quién es administrador y quién tiene permisos de administrador sin el rol, y avisa de lo que haya cambiado desde la revisión anterior. Lo que ya existe al marcar la casilla no avisa. Este correo tiene un texto fijo.', 'escritoriowp' );

		if ( ! $this->obtener( 'aviso_administradores' ) ) {
			return $descripcion;
		}

		return trim( $descripcion . ' ' . AvisosAdministradores::estado_revision() );
	}

	/**
	 * Frase final de las descripciones de «Indicadores» y «Paneles»: dónde están los tipos de
	 * contenido personalizados detectados o, si no hay ninguno, que aparecerían ahí.
	 *
	 * @return string
	 */
	private static function descripcion_tipos_contenido(): string {
		if ( array() === TiposContenido::detectados() ) {
			return __( 'Este sitio no tiene tipos de contenido personalizados; si un plugin añade alguno, aparecerá aquí, desmarcado.', 'escritoriowp' );
		}

		return __( 'Al final están los tipos de contenido personalizados de este sitio; salen desmarcados.', 'escritoriowp' );
	}

	/**
	 * Casillas del ajuste «Indicadores»: una por indicador disponible en esta instalación, con el
	 * nombre de su tarjeta, y detrás una por tipo de contenido personalizado detectado. Los de
	 * WooCommerce solo aparecen con WooCommerce activo.
	 *
	 * @return array<string, string>
	 */
	private static function opciones_indicadores(): array {
		$opciones = array();

		foreach ( Resumen::INDICADORES as $clave => $tipo ) {
			if ( Capacidades::disponible( $tipo ) ) {
				$opciones[ $clave ] = Resumen::etiqueta( $clave );
			}
		}

		return $opciones + TiposContenido::opciones();
	}

	/**
	 * Casillas del ajuste «Paneles»: una por panel disponible en esta instalación, con el nombre de
	 * su cabecera, y detrás una por tipo de contenido personalizado detectado. Los de WooCommerce
	 * solo aparecen con WooCommerce activo.
	 *
	 * @return array<string, string>
	 */
	private static function opciones_paneles(): array {
		$opciones = array();

		foreach ( Capacidades::TIPOS_PANEL as $tipo ) {
			if ( Capacidades::disponible( $tipo ) ) {
				$opciones[ $tipo ] = Capacidades::etiqueta( $tipo );
			}
		}

		return $opciones + TiposContenido::opciones();
	}

	/**
	 * Sanea lo que llega del formulario de ajustes.
	 *
	 * Además de `sanear()`, guarda vacíos el asunto y el mensaje que coinciden con su texto
	 * predeterminado, para que quien guarda otro ajuste sin tocarlos siga recibiendo el predeterminado
	 * de cada versión y en el idioma del momento. Y avisa de las direcciones descartadas: WordPress
	 * puede llamar dos veces a esta función en un mismo guardado, así que el aviso se añade una vez.
	 *
	 * Antes de sanear, completa las listas de casillas con lo guardado: sin WooCommerce no se pintan
	 * sus casillas, y lo que no llega volvería a activarse. La segunda llamada recibe las listas ya
	 * completas, así que la unión no cambia nada.
	 *
	 * @param mixed $entrada Valores enviados por el formulario.
	 * @return array<string, bool|int|string|array<string, bool>>
	 */
	public function sanear_formulario( mixed $entrada ): array {
		if ( is_array( $entrada ) ) {
			foreach ( array( 'indicadores_activos', 'paneles_activos' ) as $clave ) {
				if ( is_array( $entrada[ $clave ] ?? null ) ) {
					$entrada[ $clave ] += (array) $this->obtener( $clave );
				}
			}
		}

		$ajustes = self::sanear( $entrada );

		if ( self::sanear_asunto( AvisosPlugins::asunto_por_defecto() ) === $ajustes['aviso_asunto'] ) {
			$ajustes['aviso_asunto'] = '';
		}

		if ( self::sanear_mensaje( AvisosPlugins::mensaje_por_defecto() ) === $ajustes['aviso_mensaje'] ) {
			$ajustes['aviso_mensaje'] = '';
		}

		$descartados = is_array( $entrada ) ? self::correos_descartados( $entrada['aviso_destinatarios'] ?? '' ) : array();
		$codigo      = 'escritoriowp_correos_descartados';

		if ( array() !== $descartados && ! in_array( $codigo, array_column( get_settings_errors( self::OPCION ), 'code' ), true ) ) {
			add_settings_error(
				self::OPCION,
				$codigo,
				esc_html(
					sprintf(
						/* translators: %s: direcciones de correo descartadas, separadas por comas. */
						_n(
							'Ajustes guardados, pero se ha quitado de los destinatarios esta dirección, que no es válida: %s',
							'Ajustes guardados, pero se han quitado de los destinatarios estas direcciones, que no son válidas: %s',
							count( $descartados ),
							'escritoriowp'
						),
						implode( ', ', $descartados )
					)
				),
				'warning'
			);
		}

		return $ajustes;
	}

	/**
	 * Pinta un campo del formulario de ajustes según el tipo que declara.
	 *
	 * Argumentos opcionales: `predeterminado`, el texto que se muestra mientras el valor guardado está
	 * vacío; `ejemplo`, el texto de ayuda dentro del campo vacío; `leyenda`, una lista de términos con
	 * su significado que se pinta bajo la descripción; y `opciones`, las casillas del tipo `casillas`.
	 *
	 * @param array<string, mixed> $args Argumentos del campo.
	 * @return void
	 */
	public function pintar_campo( array $args ): void {
		$clave  = $args['clave'];
		$valor  = $this->obtener( $clave );
		$id     = 'escritoriowp-campo-' . $clave;
		$nombre = self::OPCION . '[' . $clave . ']';

		if ( '' === $valor && isset( $args['predeterminado'] ) ) {
			$valor = (string) $args['predeterminado'];
		}

		match ( $args['tipo'] ) {
			'casilla'     => $this->pintar_casilla( $id, $nombre, (bool) $valor, $args['etiqueta'] ),
			'casillas'    => $this->pintar_casillas( $id, $nombre, is_array( $valor ) ? $valor : array(), (array) ( $args['opciones'] ?? array() ), $args['etiqueta'] ),
			'numero'      => $this->pintar_numero( $id, $nombre, (int) $valor ),
			'desplegable' => $this->pintar_desplegable( $id, $nombre, (string) $valor ),
			'color'       => $this->pintar_color( $id, $nombre, (string) $valor ),
			'correo'      => $this->pintar_texto( $id, $nombre, (string) $valor, 'regular-text', (string) ( $args['ejemplo'] ?? '' ) ),
			'texto'       => $this->pintar_texto( $id, $nombre, (string) $valor, 'large-text', (string) ( $args['ejemplo'] ?? '' ) ),
			'area'        => $this->pintar_area( $id, $nombre, (string) $valor ),
		};

		printf( '<p class="description">%s</p>', esc_html( $args['descripcion'] ) );

		if ( ! empty( $args['leyenda'] ) && is_array( $args['leyenda'] ) ) {
			$this->pintar_leyenda( $args['leyenda'] );
		}
	}

	/**
	 * Pinta una casilla.
	 *
	 * @param string $id       Identificador del campo.
	 * @param string $nombre   Nombre del campo en el formulario.
	 * @param bool   $valor    Si está marcada.
	 * @param string $etiqueta Texto junto a la casilla.
	 * @return void
	 */
	private function pintar_casilla( string $id, string $nombre, bool $valor, string $etiqueta ): void {
		/*
		 * El campo oculto garantiza que la clave siempre viaja en el envío: sin él, desmarcar la
		 * casilla dejaría el ajuste ausente y volvería a tomar su valor por defecto.
		 */
		printf(
			'<input type="hidden" name="%1$s" value="0"><label for="%2$s"><input type="checkbox" id="%2$s" name="%1$s" value="1" %3$s> %4$s</label>',
			esc_attr( $nombre ),
			esc_attr( $id ),
			checked( $valor, true, false ),
			esc_html( $etiqueta )
		);
	}

	/**
	 * Pinta un grupo de casillas, una por opción, con el marcado de las pantallas de ajustes de
	 * WordPress: un `fieldset` cuya `legend` da nombre al grupo para los lectores de pantalla.
	 *
	 * Cada casilla lleva delante su campo oculto con valor `0`, como `pintar_casilla()`, para que
	 * desmarcarla viaje en el envío. Una opción sin valor guardado se pinta como la interpreta
	 * `sanear_activos()`: marcada si es propia y desmarcada si es de un tipo de contenido
	 * personalizado.
	 *
	 * @param string                $id       Prefijo del identificador de cada casilla.
	 * @param string                $nombre   Nombre del campo en el formulario.
	 * @param array<string, bool>   $valores  Valor guardado de cada opción.
	 * @param array<string, string> $opciones Texto de cada opción.
	 * @param string                $etiqueta Nombre del grupo.
	 * @return void
	 */
	private function pintar_casillas( string $id, string $nombre, array $valores, array $opciones, string $etiqueta ): void {
		printf( '<fieldset><legend class="screen-reader-text"><span>%s</span></legend>', esc_html( $etiqueta ) );

		foreach ( $opciones as $clave => $texto ) {
			printf(
				'<input type="hidden" name="%1$s" value="0"><label for="%2$s"><input type="checkbox" id="%2$s" name="%1$s" value="1" %3$s> %4$s</label><br>',
				esc_attr( $nombre . '[' . $clave . ']' ),
				esc_attr( $id . '-' . $clave ),
				checked( $valores[ $clave ] ?? self::activo_por_defecto( (string) $clave ), true, false ),
				esc_html( $texto )
			);
		}

		echo '</fieldset>';
	}

	/**
	 * Pinta el campo numérico de elementos por panel.
	 *
	 * @param string $id     Identificador del campo.
	 * @param string $nombre Nombre del campo en el formulario.
	 * @param int    $valor  Valor actual.
	 * @return void
	 */
	private function pintar_numero( string $id, string $nombre, int $valor ): void {
		printf(
			'<input type="number" id="%1$s" name="%2$s" value="%3$s" min="%4$s" max="%5$s" class="small-text">',
			esc_attr( $id ),
			esc_attr( $nombre ),
			esc_attr( (string) $valor ),
			esc_attr( (string) self::MIN_ELEMENTOS ),
			esc_attr( (string) self::MAX_ELEMENTOS )
		);
	}

	/**
	 * Pinta el desplegable de la posición en el menú.
	 *
	 * Los menús de primer nivel van agrupados bajo «Debajo de…» y «Al final» queda aparte. Si el menú
	 * guardado no existe hoy, se añade marcado como no disponible: sin él, el navegador
	 * seleccionaría la primera opción y guardar cualquier otro ajuste perdería la referencia.
	 *
	 * @param string $id     Identificador del campo.
	 * @param string $nombre Nombre del campo en el formulario.
	 * @param string $valor  Identificador del menú guardado, o vacío para «Al final».
	 * @return void
	 */
	private function pintar_desplegable( string $id, string $nombre, string $valor ): void {
		global $menu;

		$opciones = Menu::opciones( is_array( $menu ) ? $menu : array() );

		if ( '' !== $valor && ! array_key_exists( $valor, $opciones ) ) {
			$opciones[ $valor ] = sprintf(
				/* translators: %s: identificador del menú guardado, que ya no existe. */
				__( '%s (no disponible)', 'escritoriowp' ),
				$valor
			);
		}

		printf(
			'<select id="%1$s" name="%2$s"><optgroup label="%3$s">',
			esc_attr( $id ),
			esc_attr( $nombre ),
			esc_attr__( 'Debajo de…', 'escritoriowp' )
		);

		foreach ( $opciones as $slug => $titulo ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( (string) $slug ),
				selected( $valor, (string) $slug, false ),
				esc_html( $titulo )
			);
		}

		printf(
			'</optgroup><option value="" %1$s>%2$s</option></select>',
			selected( $valor, '', false ),
			esc_html__( 'Al final', 'escritoriowp' )
		);
	}

	/**
	 * Pinta un campo de color, que `assets/js/ajustes.js` convierte en el selector de WordPress.
	 *
	 * El valor por defecto vacío hace que el botón del selector vacíe el campo, que es como se vuelve
	 * al aspecto del esquema de colores de cada usuario.
	 *
	 * @param string $id     Identificador del campo.
	 * @param string $nombre Nombre del campo en el formulario.
	 * @param string $valor  Color guardado, o vacío.
	 * @return void
	 */
	private function pintar_color( string $id, string $nombre, string $valor ): void {
		printf(
			'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="escritoriowp-color" data-default-color="" maxlength="7" spellcheck="false">',
			esc_attr( $id ),
			esc_attr( $nombre ),
			esc_attr( $valor )
		);
	}

	/**
	 * Pinta un campo de texto de una línea.
	 *
	 * @param string $id      Identificador del campo.
	 * @param string $nombre  Nombre del campo en el formulario.
	 * @param string $valor   Valor que se muestra.
	 * @param string $clase   Clase de ancho de WordPress (`regular-text` o `large-text`).
	 * @param string $ejemplo Texto de ayuda del campo vacío, o vacío para no ponerlo.
	 * @return void
	 */
	private function pintar_texto( string $id, string $nombre, string $valor, string $clase, string $ejemplo ): void {
		printf(
			'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="%4$s"%5$s>',
			esc_attr( $id ),
			esc_attr( $nombre ),
			esc_attr( $valor ),
			esc_attr( $clase ),
			'' === $ejemplo ? '' : ' placeholder="' . esc_attr( $ejemplo ) . '"'
		);
	}

	/**
	 * Pinta un área de texto de varias líneas.
	 *
	 * @param string $id     Identificador del campo.
	 * @param string $nombre Nombre del campo en el formulario.
	 * @param string $valor  Valor que se muestra.
	 * @return void
	 */
	private function pintar_area( string $id, string $nombre, string $valor ): void {
		printf(
			'<textarea id="%1$s" name="%2$s" rows="12" class="large-text escritoriowp-ajustes__area">%3$s</textarea>',
			esc_attr( $id ),
			esc_attr( $nombre ),
			esc_textarea( $valor )
		);
	}

	/**
	 * Pinta una lista de términos con su significado, como la de los marcadores de los avisos.
	 *
	 * @param array<string, string> $leyenda Significado de cada término, sin llaves.
	 * @return void
	 */
	private function pintar_leyenda( array $leyenda ): void {
		echo '<dl class="escritoriowp-ajustes__marcadores">';

		foreach ( $leyenda as $termino => $significado ) {
			printf(
				'<dt><code>{%1$s}</code></dt><dd>%2$s</dd>',
				esc_html( (string) $termino ),
				esc_html( (string) $significado )
			);
		}

		echo '</dl>';
	}

	/**
	 * Pinta la pantalla de ajustes.
	 *
	 * Los avisos de la Settings API («Ajustes guardados» o las direcciones descartadas) se pintan
	 * aquí: WordPress solo los pinta por su cuenta en las pantallas que cuelgan de Ajustes.
	 *
	 * @return void
	 */
	public function pintar_pagina(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos para acceder a esta página.', 'escritoriowp' ) );
		}

		?>
		<div class="wrap escritoriowp-ajustes">
			<h1><?php echo esc_html__( 'Ajustes de EscritorioWP', 'escritoriowp' ); ?></h1>
			<?php settings_errors(); ?>
			<p class="escritoriowp-ajustes__intro">
				<?php echo esc_html__( 'EscritorioWP sustituye el escritorio de WordPress y su paleta de comandos. Puedes desactivar cada parte por separado.', 'escritoriowp' ); ?>
			</p>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::GRUPO );
				do_settings_sections( self::PAGINA );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
