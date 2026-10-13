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
	 * Menú bajo el que aparece el del plugin si no se elige otro: el Escritorio.
	 */
	public const MENU_POR_DEFECTO = 'index.php';

	/**
	 * Longitud máxima del identificador del menú de referencia.
	 */
	private const MAX_LONGITUD_MENU = 200;

	/**
	 * Instancia única.
	 *
	 * @var Ajustes|null
	 */
	private static ?Ajustes $instancia = null;

	/**
	 * Ajustes ya resueltos durante esta petición.
	 *
	 * @var array<string, bool|int|string>|null
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
	 * `menu_debajo_de` vacío significa «Al final»; `menu_color_fondo` vacío, sin color propio.
	 *
	 * @return array<string, bool|int|string>
	 */
	public static function defectos(): array {
		return array(
			'sustituir_escritorio'     => true,
			'ocultar_widgets_terceros' => true,
			'desactivar_paleta_nativa' => true,
			'lanzador_activo'          => true,
			'lanzador_frontend'        => true,
			'elementos_por_panel'      => 5,
			'menu_debajo_de'           => self::MENU_POR_DEFECTO,
			'menu_color_fondo'         => '',
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
	 * @return array<string, bool|int|string> Ajustes saneados y completos.
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
				'menu_debajo_de'      => self::sanear_menu_debajo_de( $valor ),
				'menu_color_fondo'    => self::sanear_color( $valor ),
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
	 * @return array<string, bool|int|string>
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
	 * @return bool|int|string
	 */
	public function obtener( string $clave ): bool|int|string {
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

		add_settings_section(
			'escritoriowp_seccion_menu',
			__( 'Menú', 'escritoriowp' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Controla dónde aparece EscritorioWP en la barra lateral del administrador y cómo se ve.', 'escritoriowp' ) . '</p>';
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
					'tipo'        => $campo['tipo'],
					'etiqueta'    => $campo['etiqueta'],
					'descripcion' => $campo['descripcion'],
					'label_for'   => 'escritoriowp-campo-' . $clave,
				)
			);
		}
	}

	/**
	 * Pinta un campo del formulario de ajustes según el tipo que declara.
	 *
	 * @param array<string, string> $args Argumentos del campo.
	 * @return void
	 */
	public function pintar_campo( array $args ): void {
		$clave  = $args['clave'];
		$valor  = $this->obtener( $clave );
		$id     = 'escritoriowp-campo-' . $clave;
		$nombre = self::OPCION . '[' . $clave . ']';

		match ( $args['tipo'] ) {
			'casilla'     => $this->pintar_casilla( $id, $nombre, (bool) $valor, $args['etiqueta'] ),
			'numero'      => $this->pintar_numero( $id, $nombre, (int) $valor ),
			'desplegable' => $this->pintar_desplegable( $id, $nombre, (string) $valor ),
			'color'       => $this->pintar_color( $id, $nombre, (string) $valor ),
		};

		printf( '<p class="description">%s</p>', esc_html( $args['descripcion'] ) );
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
