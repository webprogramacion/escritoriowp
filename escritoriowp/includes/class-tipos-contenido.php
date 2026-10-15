<?php
/**
 * Tipos de contenido personalizados que el escritorio puede mostrar.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP;

use WP_Post_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Fuente única de qué tipos de contenido personalizados se ofrecen como panel e indicador, y de
 * la clave con la que el plugin los identifica: `cpt-` seguido del identificador de WordPress.
 *
 * Las funciones puras (clave, icono y nombres de las casillas) no llaman a WordPress, para poder
 * probarlas de forma aislada.
 */
final class TiposContenido {

	/**
	 * Prefijo de la clave de un tipo personalizado. Ninguna clave propia lleva guion, así que un
	 * tipo llamado como un panel propio no puede pisarlo.
	 */
	public const PREFIJO = 'cpt-';

	/**
	 * Icono de un tipo sin icono de Dashicons: el mismo que le pone WordPress en el menú.
	 */
	private const ICONO_DEFECTO = 'dashicons-admin-post';

	/**
	 * Tipos que nunca se ofrecen ni se buscan como contenido: adjuntos, estructuras del editor y
	 * datos internos de WooCommerce, que tienen sus propios paneles y fuentes de búsqueda.
	 *
	 * @var string[]
	 */
	public const EXCLUIDOS = array(
		'attachment',
		'revision',
		'nav_menu_item',
		'custom_css',
		'customize_changeset',
		'oembed_cache',
		'user_request',
		'wp_block',
		'wp_template',
		'wp_template_part',
		'wp_global_styles',
		'wp_navigation',
		'wp_font_family',
		'wp_font_face',
		'product',
		'product_variation',
		'shop_order',
		'shop_order_refund',
		'shop_order_placehold',
		'shop_coupon',
		'shop_subscription',
	);

	/**
	 * Tipos detectados, ya cacheados para el resto de la petición.
	 *
	 * @var array<string, WP_Post_Type>|null
	 */
	private static ?array $detectados = null;

	/**
	 * Indica si un texto es la clave de un tipo personalizado.
	 *
	 * Tras el prefijo admite lo mismo que WordPress en un identificador de tipo: minúsculas,
	 * números, guiones y guiones bajos, hasta 20 caracteres.
	 *
	 * @param string $clave Clave que se comprueba.
	 * @return bool
	 */
	public static function es_clave( string $clave ): bool {
		return 1 === preg_match( '/^cpt-[a-z0-9_-]{1,20}\z/', $clave );
	}

	/**
	 * Clave de un tipo personalizado a partir de su identificador de WordPress.
	 *
	 * @param string $tipo Identificador del tipo.
	 * @return string
	 */
	public static function clave( string $tipo ): string {
		return self::PREFIJO . $tipo;
	}

	/**
	 * Identificador de WordPress de una clave de tipo personalizado.
	 *
	 * @param string $clave Clave del tipo.
	 * @return string Identificador, o cadena vacía si no es una clave de tipo personalizado.
	 */
	public static function tipo( string $clave ): string {
		return self::es_clave( $clave ) ? substr( $clave, strlen( self::PREFIJO ) ) : '';
	}

	/**
	 * Icono de Dashicons a partir del `menu_icon` de un tipo.
	 *
	 * La interfaz solo sabe pintar clases de Dashicons: una URL, un SVG en `data:` o `none` se
	 * sustituyen por el icono por defecto.
	 *
	 * @param mixed $icono Valor de `menu_icon`.
	 * @return string
	 */
	public static function icono_menu( mixed $icono ): string {
		if ( is_string( $icono ) && 1 === preg_match( '/^dashicons-[a-z0-9-]+\z/', $icono ) ) {
			return $icono;
		}

		return self::ICONO_DEFECTO;
	}

	/**
	 * Textos de las casillas de los tipos: su nombre, y su identificador entre paréntesis cuando
	 * otro tipo se llama igual, para poder distinguirlos.
	 *
	 * @param array<string, string> $nombres Clave del tipo => nombre, en su orden.
	 * @return array<string, string> Clave del tipo => texto de la casilla, en el mismo orden.
	 */
	public static function desambiguar( array $nombres ): array {
		$normalizar = static fn ( string $nombre ): string => mb_strtolower( trim( $nombre ) );
		$repetidos  = array_count_values( array_map( $normalizar, $nombres ) );
		$textos     = array();

		foreach ( $nombres as $clave => $nombre ) {
			$clave = (string) $clave;

			if ( $repetidos[ $normalizar( $nombre ) ] > 1 ) {
				$tipo   = self::tipo( $clave );
				$nombre = sprintf( '%s (%s)', $nombre, '' !== $tipo ? $tipo : $clave );
			}

			$textos[ $clave ] = $nombre;
		}

		return $textos;
	}

	/**
	 * Tipos personalizados que se ofrecen como panel e indicador, ordenados por nombre.
	 *
	 * Son los registrados con pantalla de administración y entrada en el menú, sin los excluidos.
	 * El resultado se cachea para el resto de la petición solo cuando ya ha terminado `init`, que
	 * es cuando los plugins registran sus tipos: antes se devolvería una lista incompleta.
	 *
	 * @return array<string, WP_Post_Type> Clave del tipo => objeto del tipo.
	 */
	public static function detectados(): array {
		if ( null !== self::$detectados ) {
			return self::$detectados;
		}

		$candidatos  = array();
		$registrados = get_post_types(
			array(
				'_builtin' => false,
				'show_ui'  => true,
			),
			'objects'
		);

		foreach ( $registrados as $objeto ) {
			if ( ! empty( $objeto->show_in_menu ) && ! self::excluido( $objeto->name ) ) {
				$candidatos[] = $objeto->name;
			}
		}

		/**
		 * Filtra los tipos de contenido personalizados que se ofrecen como panel e indicador.
		 *
		 * Sirve para quitar un tipo o para añadir uno que no está en el menú. Del resultado se
		 * descartan los tipos que no están registrados con pantalla de administración (`show_ui`),
		 * las entradas, las páginas y los tipos de `TiposContenido::EXCLUIDOS`. Ofrecer un tipo no
		 * concede nada: cada usuario sigue viendo solo lo que su rol le deja editar.
		 *
		 * @param string[] $tipos Identificadores de los tipos detectados.
		 */
		$tipos      = (array) apply_filters( 'escritoriowp_tipos_contenido', $candidatos );
		$detectados = array();

		foreach ( $tipos as $tipo ) {
			$tipo   = is_string( $tipo ) ? $tipo : '';
			$objeto = '' !== $tipo ? get_post_type_object( $tipo ) : null;

			if ( ! $objeto instanceof WP_Post_Type || ! $objeto->show_ui || self::excluido( $tipo ) || ! self::es_clave( self::clave( $tipo ) ) ) {
				continue;
			}

			$detectados[ self::clave( $tipo ) ] = $objeto;
		}

		uasort(
			$detectados,
			static function ( WP_Post_Type $a, WP_Post_Type $b ): int {
				$orden = strnatcasecmp( remove_accents( (string) $a->labels->name ), remove_accents( (string) $b->labels->name ) );

				return 0 !== $orden ? $orden : strcmp( $a->name, $b->name );
			}
		);

		if ( did_action( 'wp_loaded' ) ) {
			self::$detectados = $detectados;
		}

		return $detectados;
	}

	/**
	 * Objeto de un tipo personalizado que se ofrece.
	 *
	 * @param string $clave Clave del tipo.
	 * @return WP_Post_Type|null Nulo si la clave no es de un tipo que se ofrece.
	 */
	public static function objeto( string $clave ): ?WP_Post_Type {
		return self::es_clave( $clave ) ? ( self::detectados()[ $clave ] ?? null ) : null;
	}

	/**
	 * Nombre en plural de un tipo personalizado, tal como lo registra su plugin.
	 *
	 * @param string $clave Clave del tipo.
	 * @return string
	 */
	public static function nombre( string $clave ): string {
		$objeto = self::objeto( $clave );

		return $objeto ? (string) $objeto->labels->name : '';
	}

	/**
	 * Icono de Dashicons de un tipo personalizado.
	 *
	 * @param string $clave Clave del tipo.
	 * @return string
	 */
	public static function icono( string $clave ): string {
		$objeto = self::objeto( $clave );

		return self::icono_menu( $objeto ? $objeto->menu_icon : null );
	}

	/**
	 * Casillas de los tipos que se ofrecen, en su orden: clave => texto de la casilla.
	 *
	 * @return array<string, string>
	 */
	public static function opciones(): array {
		return self::desambiguar(
			array_map(
				static fn ( WP_Post_Type $objeto ): string => (string) $objeto->labels->name,
				self::detectados()
			)
		);
	}

	/**
	 * Indica si un tipo no se ofrece nunca: las entradas y las páginas, que tienen sus paneles
	 * propios, y los de `EXCLUIDOS`.
	 *
	 * @param string $tipo Identificador del tipo.
	 * @return bool
	 */
	private static function excluido( string $tipo ): bool {
		return in_array( $tipo, array( 'post', 'page' ), true ) || in_array( $tipo, self::EXCLUIDOS, true );
	}
}
