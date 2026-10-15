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
 *
 * Además de los tipos de `MAPA`, entiende las claves `cpt-…` de los tipos de contenido
 * personalizados que ofrece `TiposContenido`: sus capacidades, pantallas y textos salen del objeto
 * del tipo, con las mismas capacidades que exigen su listado y su pantalla de creación nativos.
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
		if ( TiposContenido::es_clave( $tipo ) ) {
			return null !== TiposContenido::objeto( $tipo );
		}

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

		if ( TiposContenido::es_clave( $tipo ) ) {
			return true;
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
		if ( TiposContenido::es_clave( $tipo ) ) {
			return self::puede_en_tipo( $tipo, 'edit_posts' );
		}

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
		if ( TiposContenido::es_clave( $tipo ) ) {
			return self::puede_en_tipo( $tipo, 'create_posts' );
		}

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
		if ( TiposContenido::es_clave( $tipo ) ) {
			return self::puede_en_tipo( $tipo, 'publish_posts' );
		}

		if ( ! self::disponible( $tipo ) ) {
			return false;
		}

		$capacidad = self::MAPA[ $tipo ]['publicar'];

		return '' !== $capacidad && current_user_can( $capacidad );
	}

	/**
	 * Indica si el usuario actual tiene una de las capacidades de un tipo personalizado.
	 *
	 * @param string $clave Clave del tipo personalizado.
	 * @param string $cual  Capacidad genérica del tipo: `edit_posts`, `create_posts` o `publish_posts`.
	 * @return bool
	 */
	private static function puede_en_tipo( string $clave, string $cual ): bool {
		$objeto = TiposContenido::objeto( $clave );

		return null !== $objeto && current_user_can( (string) $objeto->cap->$cual );
	}

	/**
	 * Tipos que pueden ser panel: los propios y, detrás, los tipos personalizados que se ofrecen.
	 *
	 * @return string[]
	 */
	public static function tipos_panel(): array {
		return array_merge( self::TIPOS_PANEL, array_keys( TiposContenido::detectados() ) );
	}

	/**
	 * Tipos de panel que el usuario actual puede ver, estén o no activos en el sitio.
	 *
	 * @return string[]
	 */
	public static function paneles_visibles(): array {
		return array_values( array_filter( self::tipos_panel(), array( __CLASS__, 'puede_ver' ) ) );
	}

	/**
	 * URL del listado nativo del tipo, opcionalmente con una búsqueda aplicada.
	 *
	 * @param string $tipo     Clave del tipo.
	 * @param string $busqueda Texto de búsqueda.
	 * @return string
	 */
	public static function url_listado( string $tipo, string $busqueda = '' ): string {
		if ( TiposContenido::es_clave( $tipo ) ) {
			$url = admin_url( 'edit.php?post_type=' . TiposContenido::tipo( $tipo ) );

			return '' === $busqueda ? $url : add_query_arg( 's', rawurlencode( $busqueda ), $url );
		}

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
		if ( TiposContenido::es_clave( $tipo ) ) {
			return admin_url( 'post-new.php?post_type=' . TiposContenido::tipo( $tipo ) );
		}

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
		if ( TiposContenido::es_clave( $tipo ) ) {
			return TiposContenido::nombre( $tipo );
		}

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
		if ( TiposContenido::es_clave( $tipo ) ) {
			return self::etiqueta_del_tipo( $tipo, 'add_new_item' );
		}

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
		if ( TiposContenido::es_clave( $tipo ) ) {
			return TiposContenido::icono( $tipo );
		}

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

	/**
	 * Texto del estado vacío de un panel. Para un tipo personalizado, el que usa su propio listado
	 * cuando no hay elementos.
	 *
	 * @param string $tipo Clave del tipo.
	 * @return string
	 */
	public static function texto_vacio( string $tipo ): string {
		if ( TiposContenido::es_clave( $tipo ) ) {
			return self::etiqueta_del_tipo( $tipo, 'not_found' );
		}

		return match ( $tipo ) {
			'entradas'  => __( 'Todavía no hay entradas.', 'escritoriowp' ),
			'paginas'   => __( 'Todavía no hay páginas.', 'escritoriowp' ),
			'usuarios'  => __( 'Todavía no hay usuarios.', 'escritoriowp' ),
			'productos' => __( 'Todavía no hay productos.', 'escritoriowp' ),
			'pedidos'   => __( 'Todavía no hay pedidos.', 'escritoriowp' ),
			default     => '',
		};
	}

	/**
	 * Texto del botón de crear del estado vacío de un panel. Para un tipo personalizado, el de su
	 * pantalla de creación.
	 *
	 * @param string $tipo Clave del tipo.
	 * @return string
	 */
	public static function texto_crear_primero( string $tipo ): string {
		if ( TiposContenido::es_clave( $tipo ) ) {
			return self::etiqueta_del_tipo( $tipo, 'add_new_item' );
		}

		return match ( $tipo ) {
			'entradas'  => __( 'Crear la primera entrada', 'escritoriowp' ),
			'paginas'   => __( 'Crear la primera página', 'escritoriowp' ),
			'usuarios'  => __( 'Crear el primer usuario', 'escritoriowp' ),
			'productos' => __( 'Crear el primer producto', 'escritoriowp' ),
			'pedidos'   => __( 'Crear el primer pedido', 'escritoriowp' ),
			default     => '',
		};
	}

	/**
	 * Una de las etiquetas de un tipo personalizado, tal como la registra su plugin.
	 *
	 * @param string $clave    Clave del tipo personalizado.
	 * @param string $etiqueta Nombre de la etiqueta, como `add_new_item` o `not_found`.
	 * @return string
	 */
	private static function etiqueta_del_tipo( string $clave, string $etiqueta ): string {
		$objeto = TiposContenido::objeto( $clave );

		return null !== $objeto ? (string) ( $objeto->labels->$etiqueta ?? '' ) : '';
	}
}
