<?php
/**
 * Ajustes globales del plugin y su página de administración.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP;

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
	 * Instancia única.
	 *
	 * @var Ajustes|null
	 */
	private static ?Ajustes $instancia = null;

	/**
	 * Ajustes ya resueltos durante esta petición.
	 *
	 * @var array<string, bool|int>|null
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
	 * @return array<string, bool|int>
	 */
	public static function defectos(): array {
		return array(
			'sustituir_escritorio'     => true,
			'ocultar_widgets_terceros' => true,
			'desactivar_paleta_nativa' => true,
			'lanzador_activo'          => true,
			'lanzador_frontend'        => true,
			'elementos_por_panel'      => 5,
		);
	}

	/**
	 * Sanea un conjunto de ajustes y lo completa con los valores por defecto.
	 *
	 * Función pura: no usa funciones de WordPress, para poder probarla de forma aislada.
	 *
	 * @param mixed $entrada Valores sin sanear.
	 * @return array<string, bool|int> Ajustes saneados y completos.
	 */
	public static function sanear( mixed $entrada ): array {
		$entrada  = is_array( $entrada ) ? $entrada : array();
		$defectos = self::defectos();
		$salida   = array();

		foreach ( $defectos as $clave => $defecto ) {
			if ( ! array_key_exists( $clave, $entrada ) ) {
				$salida[ $clave ] = $defecto;
				continue;
			}

			if ( is_bool( $defecto ) ) {
				$salida[ $clave ] = self::a_booleano( $entrada[ $clave ] );
				continue;
			}

			$numero           = is_numeric( $entrada[ $clave ] ) ? (int) $entrada[ $clave ] : $defecto;
			$salida[ $clave ] = max( self::MIN_ELEMENTOS, min( self::MAX_ELEMENTOS, $numero ) );
		}

		return $salida;
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
	 * @return array<string, bool|int>
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
	 * @param string $clave Nombre del ajuste.
	 * @return bool|int
	 */
	public function obtener( string $clave ): bool|int {
		$ajustes = $this->todos();

		return $ajustes[ $clave ] ?? false;
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
	 * Encola la hoja de estilo de la pantalla de ajustes.
	 *
	 * @param string $pantalla Identificador de la pantalla actual.
	 * @return void
	 */
	public function encolar( string $pantalla ): void {
		if ( ! in_array( $pantalla, $this->pantallas, true ) ) {
			return;
		}

		wp_enqueue_style( 'escritoriowp-ajustes' );
	}

	/**
	 * Crea el menú propio del plugin con los ajustes como primera entrada.
	 *
	 * Sin posición explícita: el menú queda al final, junto al de los demás plugins, sin desplazar
	 * los menús nativos de WordPress.
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
				'sanitize_callback' => array( __CLASS__, 'sanear' ),
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

		$campos = array(
			'sustituir_escritorio'     => array(
				'seccion'     => 'escritoriowp_seccion_escritorio',
				'etiqueta'    => __( 'Sustituir el escritorio nativo', 'escritoriowp' ),
				'descripcion' => __( 'Muestra la interfaz de EscritorioWP en lugar de los widgets de WordPress.', 'escritoriowp' ),
			),
			'ocultar_widgets_terceros' => array(
				'seccion'     => 'escritoriowp_seccion_escritorio',
				'etiqueta'    => __( 'Ocultar los widgets de otros plugins', 'escritoriowp' ),
				'descripcion' => __( 'Si lo desactivas, los widgets de otros plugins se muestran debajo, bajo el título «Otros widgets».', 'escritoriowp' ),
			),
			'elementos_por_panel'      => array(
				'seccion'     => 'escritoriowp_seccion_escritorio',
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
				'etiqueta'    => __( 'Desactivar la paleta de comandos nativa', 'escritoriowp' ),
				'descripcion' => __( 'Evita que WordPress cargue su paleta en el administrador. La paleta del editor de bloques no se toca.', 'escritoriowp' ),
			),
			'lanzador_activo'          => array(
				'seccion'     => 'escritoriowp_seccion_lanzador',
				'etiqueta'    => __( 'Activar el lanzador de EscritorioWP', 'escritoriowp' ),
				'descripcion' => __( 'Abre el buscador con Comando+K o Control+K y desde el botón de la barra de administración.', 'escritoriowp' ),
			),
			'lanzador_frontend'        => array(
				'seccion'     => 'escritoriowp_seccion_lanzador',
				'etiqueta'    => __( 'Disponible también en el sitio público', 'escritoriowp' ),
				'descripcion' => __( 'Para usuarios identificados que ven la barra de administración.', 'escritoriowp' ),
			),
		);

		foreach ( $campos as $clave => $campo ) {
			add_settings_field(
				'escritoriowp_campo_' . $clave,
				$campo['etiqueta'],
				array( $this, 'pintar_campo' ),
				self::PAGINA,
				$campo['seccion'],
				array(
					'clave'       => $clave,
					'etiqueta'    => $campo['etiqueta'],
					'descripcion' => $campo['descripcion'],
					'label_for'   => 'escritoriowp-campo-' . $clave,
				)
			);
		}
	}

	/**
	 * Pinta un campo del formulario de ajustes.
	 *
	 * @param array<string, string> $args Argumentos del campo.
	 * @return void
	 */
	public function pintar_campo( array $args ): void {
		$clave  = $args['clave'];
		$valor  = $this->obtener( $clave );
		$id     = 'escritoriowp-campo-' . $clave;
		$nombre = self::OPCION . '[' . $clave . ']';

		if ( is_bool( self::defectos()[ $clave ] ) ) {
			/*
			 * El campo oculto garantiza que la clave siempre viaja en el envío: sin él, desmarcar
			 * la casilla dejaría el ajuste ausente y volvería a tomar su valor por defecto.
			 */
			printf(
				'<input type="hidden" name="%1$s" value="0"><label for="%2$s"><input type="checkbox" id="%2$s" name="%1$s" value="1" %3$s> %4$s</label>',
				esc_attr( $nombre ),
				esc_attr( $id ),
				checked( (bool) $valor, true, false ),
				esc_html( $args['etiqueta'] )
			);
		} else {
			printf(
				'<input type="number" id="%1$s" name="%2$s" value="%3$s" min="%4$s" max="%5$s" class="small-text">',
				esc_attr( $id ),
				esc_attr( $nombre ),
				esc_attr( (string) (int) $valor ),
				esc_attr( (string) self::MIN_ELEMENTOS ),
				esc_attr( (string) self::MAX_ELEMENTOS )
			);
		}

		printf( '<p class="description">%s</p>', esc_html( $args['descripcion'] ) );
	}

	/**
	 * Pinta la pantalla de ajustes.
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
