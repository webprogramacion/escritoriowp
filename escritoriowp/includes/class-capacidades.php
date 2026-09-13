<?php
/**
 * Mapa de tipos gestionados y comprobaciones de capacidad.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP;

use EscritorioWP\Woo\Woo;

defined( 'ABSPATH' ) || exit;

/**
 * Fuente única de verdad sobre qué puede ver y crear el usuario actual, y a qué pantallas enlaza
 * cada tipo. Toda la interfaz y todas las rutas REST deben pasar por aquí.
 */
final class Capacidades {

	/**
	 * Tipos gestionados por el plugin y las capacidades que exige cada uno.
	 *
	 * Las claves «ver», «crear» y «publicar» contienen la capacidad de WordPress o WooCommerce
	 * equivalente a la de la pantalla nativa. «woo» marca los tipos que requieren WooCommerce.
	 *
	 * @var array<string, array{ver: string[], crear: string, publicar: string, woo: bool}>
	 */
	public const MAPA = array(
		'entradas'        => array(
			'ver'      => array( 'edit_posts' ),
			'crear'    => 'edit_posts',
			'publicar' => 'publish_posts',
			'woo'      => false,
		),
		'paginas'         => array(
			'ver'      => array( 'edit_pages' ),
			'crear'    => 'edit_pages',
			'publicar' => 'publish_pages',
			'woo'      => false,
		),
		'usuarios'        => array(
			'ver'      => array( 'list_users' ),
			'crear'    => 'create_users',
			'publicar' => '',
			'woo'      => false,
		),
		'productos'       => array(
			'ver'      => array( 'edit_products' ),
			'crear'    => 'edit_products',
			'publicar' => 'publish_products',
			'woo'      => true,
		),
		'pedidos'         => array(
			'ver'      => array( 'edit_shop_orders' ),
			'crear'    => 'edit_shop_orders',
			'publicar' => '',
			'woo'      => true,
		),
		'comentarios'     => array(
			'ver'      => array( 'moderate_comments' ),
			'crear'    => '',
			'publicar' => '',
			'woo'      => false,
		),
		'actualizaciones' => array(
			'ver'      => array( 'update_core', 'update_plugins', 'update_themes' ),
			'crear'    => '',
			'publicar' => '',
			'woo'      => false,
		),
	);

	/**
	 * Tipos que se muestran como panel de «últimos elementos» en el escritorio.
	 *
	 * @var string[]
	 */
	public const TIPOS_PANEL = array( 'entradas', 'paginas', 'usuarios', 'productos', 'pedidos' );

	/**
	 * Devuelve todos los tipos gestionados.
	 *
	 * @return string[]
	 */
	public static function tipos(): array {
		return array_keys( self::MAPA );
	}

	/**
	 * Indica si un tipo existe en el mapa.
	 *
	 * @param string $tipo Clave del tipo.
	 * @return bool
	 */
	public static function existe( string $tipo ): bool {
		return array_key_exists( $tipo, self::MAPA );
	}

	/**
	 * Indica si el tipo está disponible en esta instalación (WooCommerce activo cuando procede).
	 *
	 * @param string $tipo Clave del tipo.
	 * @return bool
	 */
	public static function disponible( string $tipo ): bool {
		if ( ! self::existe( $tipo ) ) {
			return false;
		}

		return ! self::MAPA[ $tipo ]['woo'] || Woo::activo();
	}

	/**
	 * Indica si el usuario actual puede ver el tipo.
	 *
	 * @param string $tipo Clave del tipo.
	 * @return bool
	 */
	public static function puede_ver( string $tipo ): bool {
		if ( ! self::disponible( $tipo ) ) {
			return false;
		}

		foreach ( self::MAPA[ $tipo ]['ver'] as $capacidad ) {
			if ( current_user_can( $capacidad ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Indica si el usuario actual puede crear elementos de este tipo.
	 *
	 * @param string $tipo Clave del tipo.
	 * @return bool
	 */
	public static function puede_crear( string $tipo ): bool {
		if ( ! self::disponible( $tipo ) ) {
			return false;
		}

		$capacidad = self::MAPA[ $tipo ]['crear'];

		return '' !== $capacidad && current_user_can( $capacidad );
	}

	/**
	 * Indica si el usuario actual puede publicar elementos de este tipo.
	 *
	 * @param string $tipo Clave del tipo.
	 * @return bool
	 */
	public static function puede_publicar( string $tipo ): bool {
		if ( ! self::disponible( $tipo ) ) {
			return false;
		}

		$capacidad = self::MAPA[ $tipo ]['publicar'];

		return '' !== $capacidad && current_user_can( $capacidad );
	}

	/**
	 * Tipos de panel que el usuario actual puede ver.
	 *
	 * @return string[]
	 */
	public static function paneles_visibles(): array {
		return array_values( array_filter( self::TIPOS_PANEL, array( __CLASS__, 'puede_ver' ) ) );
	}

	/**
	 * URL del listado nativo del tipo, opcionalmente con una búsqueda aplicada.
	 *
	 * @param string $tipo     Clave del tipo.
	 * @param string $busqueda Texto de búsqueda.
	 * @return string
	 */
	public static function url_listado( string $tipo, string $busqueda = '' ): string {
		$url = match ( $tipo ) {
			'entradas'        => admin_url( 'edit.php' ),
			'paginas'         => admin_url( 'edit.php?post_type=page' ),
			'usuarios'        => admin_url( 'users.php' ),
			'productos'       => admin_url( 'edit.php?post_type=product' ),
			'pedidos'         => Woo::url_listado_pedidos( $busqueda ),
			'comentarios'     => admin_url( 'edit-comments.php?comment_status=moderated' ),
			'actualizaciones' => admin_url( 'update-core.php' ),
			default           => admin_url(),
		};

		if ( '' === $busqueda || 'pedidos' === $tipo ) {
			return $url;
		}

		return add_query_arg( 's', rawurlencode( $busqueda ), $url );
	}

	/**
	 * URL de la pantalla de creación del tipo.
	 *
	 * @param string $tipo Clave del tipo.
	 * @return string
	 */
	public static function url_nuevo( string $tipo ): string {
		return match ( $tipo ) {
			'entradas'  => admin_url( 'post-new.php' ),
			'paginas'   => admin_url( 'post-new.php?post_type=page' ),
			'usuarios'  => admin_url( 'user-new.php' ),
			'productos' => admin_url( 'post-new.php?post_type=product' ),
			'pedidos'   => Woo::url_nuevo_pedido(),
			default     => admin_url(),
		};
	}

	/**
	 * Etiqueta en plural del tipo, para cabeceras de panel y grupos de resultados.
	 *
	 * @param string $tipo Clave del tipo.
	 * @return string
	 */
	public static function etiqueta( string $tipo ): string {
		return match ( $tipo ) {
			'entradas'        => __( 'Entradas', 'escritoriowp' ),
			'paginas'         => __( 'Páginas', 'escritoriowp' ),
			'usuarios'        => __( 'Usuarios', 'escritoriowp' ),
			'productos'       => __( 'Productos', 'escritoriowp' ),
			'pedidos'         => __( 'Pedidos', 'escritoriowp' ),
			'comentarios'     => __( 'Comentarios', 'escritoriowp' ),
			'actualizaciones' => __( 'Actualizaciones', 'escritoriowp' ),
			'contenidos'      => __( 'Otros contenidos', 'escritoriowp' ),
			'comandos'        => __( 'Comandos', 'escritoriowp' ),
			default           => $tipo,
		};
	}

	/**
	 * Etiqueta en singular del tipo, para los botones de creación.
	 *
	 * @param string $tipo Clave del tipo.
	 * @return string
	 */
	public static function etiqueta_nuevo( string $tipo ): string {
		return match ( $tipo ) {
			'entradas'  => __( 'Nueva entrada', 'escritoriowp' ),
			'paginas'   => __( 'Nueva página', 'escritoriowp' ),
			'usuarios'  => __( 'Nuevo usuario', 'escritoriowp' ),
			'productos' => __( 'Nuevo producto', 'escritoriowp' ),
			'pedidos'   => __( 'Nuevo pedido', 'escritoriowp' ),
			default     => __( 'Nuevo', 'escritoriowp' ),
		};
	}

	/**
	 * Icono Dashicon asociado al tipo.
	 *
	 * @param string $tipo Clave del tipo.
	 * @return string
	 */
	public static function icono( string $tipo ): string {
		return match ( $tipo ) {
			'entradas'        => 'dashicons-admin-post',
			'paginas'         => 'dashicons-admin-page',
			'usuarios'        => 'dashicons-admin-users',
			'productos'       => 'dashicons-products',
			'pedidos'         => 'dashicons-cart',
			'comentarios'     => 'dashicons-admin-comments',
			'actualizaciones' => 'dashicons-update',
			'contenidos'      => 'dashicons-media-default',
			'comandos'        => 'dashicons-arrow-right-alt2',
			default           => 'dashicons-marker',
		};
	}
}
