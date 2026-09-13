<?php
/**
 * Últimos elementos de cada tipo para los paneles del escritorio.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Escritorio;

use EscritorioWP\Capacidades;
use EscritorioWP\Woo\Woo;
use WC_Order;
use WC_Product;
use WP_Post;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Obtiene los elementos más recientes de cada tipo, ya formateados para pintarlos en un panel.
 *
 * Cada fila lleva el título, la URL de edición, la URL pública cuando procede y una lista de metas
 * con el texto ya traducido y el tono de color que le corresponde.
 */
final class Recientes {

	/**
	 * Devuelve los últimos elementos de un tipo.
	 *
	 * @param string $tipo   Clave del tipo (entradas, paginas, usuarios, productos, pedidos).
	 * @param int    $limite Número máximo de elementos.
	 * @return array<int, array<string, mixed>>
	 */
	public static function obtener( string $tipo, int $limite ): array {
		if ( ! Capacidades::puede_ver( $tipo ) ) {
			return array();
		}

		return match ( $tipo ) {
			'entradas', 'paginas' => self::contenidos( 'entradas' === $tipo ? 'post' : 'page', $limite ),
			'usuarios'            => self::usuarios( $limite ),
			'productos'           => self::productos( $limite ),
			'pedidos'             => self::pedidos( $limite ),
			default               => array(),
		};
	}

	/**
	 * Últimas entradas o páginas modificadas.
	 *
	 * @param string $tipo_contenido Tipo de contenido de WordPress.
	 * @param int    $limite         Número máximo de elementos.
	 * @return array<int, array<string, mixed>>
	 */
	private static function contenidos( string $tipo_contenido, int $limite ): array {
		$objeto = get_post_type_object( $tipo_contenido );

		$args = array(
			'post_type'           => $tipo_contenido,
			'post_status'         => self::estados_visibles(),
			'orderby'             => 'modified',
			'order'               => 'DESC',
			'posts_per_page'      => $limite,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'suppress_filters'    => false,
		);

		// Igual que el listado nativo: quien no puede editar lo ajeno solo ve lo suyo.
		if ( $objeto && ! current_user_can( $objeto->cap->edit_others_posts ) ) {
			$args['author'] = get_current_user_id();
		}

		$filas = array();

		foreach ( get_posts( $args ) as $entrada ) {
			$filas[] = self::fila_contenido( $entrada );
		}

		return $filas;
	}

	/**
	 * Convierte una entrada en una fila de panel.
	 *
	 * @param WP_Post $entrada Entrada o página.
	 * @return array<string, mixed>
	 */
	public static function fila_contenido( WP_Post $entrada ): array {
		$autor = get_the_author_meta( 'display_name', (int) $entrada->post_author );

		$metas = array( self::meta_estado( $entrada->post_status ) );

		if ( '' !== (string) $autor ) {
			$metas[] = array(
				'texto' => sprintf(
					/* translators: %s: nombre del autor. */
					__( 'por %s', 'escritoriowp' ),
					$autor
				),
				'tono'  => '',
			);
		}

		$metas[] = array(
			'texto' => self::fecha_relativa( (int) get_post_modified_time( 'U', true, $entrada ) ),
			'tono'  => '',
		);

		return array(
			'id'        => (int) $entrada->ID,
			'titulo'    => self::titulo( $entrada ),
			'urlEditar' => (string) get_edit_post_link( $entrada->ID, 'raw' ),
			'urlVer'    => 'publish' === $entrada->post_status ? (string) get_permalink( $entrada ) : '',
			'imagen'    => '',
			'inicial'   => self::inicial( self::titulo( $entrada ) ),
			'metas'     => $metas,
		);
	}

	/**
	 * Últimos usuarios registrados.
	 *
	 * @param int $limite Número máximo de elementos.
	 * @return array<int, array<string, mixed>>
	 */
	private static function usuarios( int $limite ): array {
		$usuarios = get_users(
			array(
				'number'  => $limite,
				'orderby' => 'registered',
				'order'   => 'DESC',
			)
		);

		$filas = array();

		foreach ( $usuarios as $usuario ) {
			$filas[] = self::fila_usuario( $usuario );
		}

		return $filas;
	}

	/**
	 * Convierte un usuario en una fila de panel.
	 *
	 * @param WP_User $usuario Usuario.
	 * @return array<string, mixed>
	 */
	public static function fila_usuario( WP_User $usuario ): array {
		$metas = array(
			array(
				'texto' => $usuario->user_email,
				'tono'  => '',
			),
			array(
				'texto' => self::nombre_rol( $usuario ),
				'tono'  => 'neutro',
			),
			array(
				'texto' => self::fecha_relativa( (int) strtotime( $usuario->user_registered . ' GMT' ) ),
				'tono'  => '',
			),
		);

		return array(
			'id'        => (int) $usuario->ID,
			'titulo'    => $usuario->display_name,
			'urlEditar' => (string) get_edit_user_link( $usuario->ID ),
			'urlVer'    => '',
			'imagen'    => (string) get_avatar_url( $usuario->ID, array( 'size' => 64 ) ),
			'inicial'   => self::inicial( $usuario->display_name ),
			'metas'     => $metas,
		);
	}

	/**
	 * Últimos productos creados.
	 *
	 * @param int $limite Número máximo de elementos.
	 * @return array<int, array<string, mixed>>
	 */
	private static function productos( int $limite ): array {
		if ( ! Woo::activo() ) {
			return array();
		}

		$productos = wc_get_products(
			array(
				'limit'   => $limite,
				'orderby' => 'date',
				'order'   => 'DESC',
				'status'  => array( 'publish', 'draft', 'pending', 'private' ),
			)
		);

		$filas = array();

		foreach ( $productos as $producto ) {
			$filas[] = self::fila_producto( $producto );
		}

		return $filas;
	}

	/**
	 * Convierte un producto en una fila de panel.
	 *
	 * @param WC_Product $producto Producto de WooCommerce.
	 * @return array<string, mixed>
	 */
	public static function fila_producto( WC_Product $producto ): array {
		$imagen_id = $producto->get_image_id();
		$precio    = $producto->get_price();

		$metas = array();

		if ( '' !== (string) $precio ) {
			$metas[] = array(
				'texto' => Woo::precio( (float) $precio ),
				'tono'  => '',
			);
		}

		$metas[] = $producto->is_in_stock()
			? array(
				'texto' => __( 'En stock', 'escritoriowp' ),
				'tono'  => 'verde',
			)
			: array(
				'texto' => __( 'Agotado', 'escritoriowp' ),
				'tono'  => 'rojo',
			);

		$metas[] = self::meta_estado( $producto->get_status() );

		return array(
			'id'        => $producto->get_id(),
			'titulo'    => $producto->get_name(),
			'urlEditar' => (string) get_edit_post_link( $producto->get_id(), 'raw' ),
			'urlVer'    => 'publish' === $producto->get_status() ? (string) $producto->get_permalink() : '',
			'imagen'    => $imagen_id ? (string) wp_get_attachment_image_url( (int) $imagen_id, 'thumbnail' ) : '',
			'inicial'   => self::inicial( $producto->get_name() ),
			'metas'     => $metas,
		);
	}

	/**
	 * Últimos pedidos recibidos.
	 *
	 * @param int $limite Número máximo de elementos.
	 * @return array<int, array<string, mixed>>
	 */
	private static function pedidos( int $limite ): array {
		if ( ! Woo::activo() ) {
			return array();
		}

		$pedidos = wc_get_orders(
			array(
				'limit'   => $limite,
				'type'    => 'shop_order',
				'orderby' => 'date',
				'order'   => 'DESC',
				'status'  => Woo::estados_pedido(),
			)
		);

		$filas = array();

		foreach ( $pedidos as $pedido ) {
			if ( $pedido instanceof WC_Order ) {
				$filas[] = self::fila_pedido( $pedido );
			}
		}

		return $filas;
	}

	/**
	 * Convierte un pedido en una fila de panel.
	 *
	 * @param WC_Order $pedido Pedido de WooCommerce.
	 * @return array<string, mixed>
	 */
	public static function fila_pedido( WC_Order $pedido ): array {
		$cliente = trim( $pedido->get_formatted_billing_full_name() );
		$cliente = '' !== $cliente ? $cliente : __( 'Invitado', 'escritoriowp' );

		$metas = array(
			array(
				'texto' => $cliente,
				'tono'  => '',
			),
			array(
				'texto' => Woo::precio( (float) $pedido->get_total() ),
				'tono'  => '',
			),
			self::meta_estado_pedido( $pedido->get_status() ),
			array(
				'texto' => self::fecha_relativa( (int) ( $pedido->get_date_created() ? $pedido->get_date_created()->getTimestamp() : 0 ) ),
				'tono'  => '',
			),
		);

		return array(
			'id'        => $pedido->get_id(),
			'titulo'    => '#' . $pedido->get_order_number(),
			'urlEditar' => Woo::url_editar_pedido( $pedido->get_id() ),
			'urlVer'    => '',
			'imagen'    => '',
			'inicial'   => '#',
			'metas'     => $metas,
		);
	}

	/**
	 * Estados de contenido que aparecen en los listados del administrador.
	 *
	 * @return string[]
	 */
	public static function estados_visibles(): array {
		$estados = get_post_stati( array( 'show_in_admin_all_list' => true ) );

		return array_values( array_diff( array_keys( $estados ), array( 'trash', 'auto-draft' ) ) );
	}

	/**
	 * Meta con la etiqueta y el tono del estado de un contenido.
	 *
	 * @param string $estado Estado del contenido.
	 * @return array{texto: string, tono: string}
	 */
	public static function meta_estado( string $estado ): array {
		$objeto = get_post_status_object( $estado );

		return array(
			'texto' => $objeto ? $objeto->label : $estado,
			'tono'  => self::tono_estado( $estado ),
		);
	}

	/**
	 * Tono de color asociado al estado de un contenido.
	 *
	 * @param string $estado Estado del contenido.
	 * @return string
	 */
	public static function tono_estado( string $estado ): string {
		return match ( $estado ) {
			'publish' => 'verde',
			'draft'   => 'ambar',
			'pending' => 'azul',
			'future'  => 'azul',
			'private' => 'neutro',
			default   => 'neutro',
		};
	}

	/**
	 * Meta con la etiqueta y el tono del estado de un pedido.
	 *
	 * @param string $estado Estado del pedido, sin prefijo.
	 * @return array{texto: string, tono: string}
	 */
	public static function meta_estado_pedido( string $estado ): array {
		return array(
			'texto' => Woo::etiqueta_estado_pedido( $estado ),
			'tono'  => self::tono_estado_pedido( $estado ),
		);
	}

	/**
	 * Tono de color asociado al estado de un pedido.
	 *
	 * @param string $estado Estado del pedido, sin prefijo.
	 * @return string
	 */
	public static function tono_estado_pedido( string $estado ): string {
		return match ( Resumen::estado_sin_prefijo( $estado ) ) {
			'completed'  => 'verde',
			'processing' => 'azul',
			'on-hold'    => 'ambar',
			'pending'    => 'neutro',
			'cancelled'  => 'rojo',
			'failed'     => 'rojo',
			'refunded'   => 'morado',
			default      => 'neutro',
		};
	}

	/**
	 * Título de un contenido, con un texto de relleno si está vacío.
	 *
	 * @param WP_Post $entrada Entrada o página.
	 * @return string
	 */
	private static function titulo( WP_Post $entrada ): string {
		$titulo = trim( wp_strip_all_tags( get_the_title( $entrada ) ) );

		return '' !== $titulo ? $titulo : __( '(sin título)', 'escritoriowp' );
	}

	/**
	 * Nombre traducido del rol principal de un usuario.
	 *
	 * @param WP_User $usuario Usuario.
	 * @return string
	 */
	public static function nombre_rol( WP_User $usuario ): string {
		$roles   = wp_roles()->role_names;
		$primero = $usuario->roles[0] ?? '';

		if ( '' === $primero ) {
			return __( 'Sin rol', 'escritoriowp' );
		}

		return isset( $roles[ $primero ] ) ? translate_user_role( $roles[ $primero ] ) : $primero;
	}

	/**
	 * Devuelve una fecha relativa («hace 2 horas») o absoluta si es antigua.
	 *
	 * @param int $marca_gmt Marca de tiempo en GMT.
	 * @return string
	 */
	public static function fecha_relativa( int $marca_gmt ): string {
		if ( $marca_gmt <= 0 ) {
			return '';
		}

		$ahora = time();

		if ( $ahora - $marca_gmt < WEEK_IN_SECONDS && $marca_gmt <= $ahora ) {
			return sprintf(
				/* translators: %s: intervalo de tiempo, por ejemplo «2 horas». */
				__( 'hace %s', 'escritoriowp' ),
				human_time_diff( $marca_gmt, $ahora )
			);
		}

		return wp_date( (string) get_option( 'date_format' ), $marca_gmt );
	}

	/**
	 * Primera letra de un texto, para el avatar de relleno.
	 *
	 * @param string $texto Texto de origen.
	 * @return string
	 */
	private static function inicial( string $texto ): string {
		$texto = trim( $texto );

		return '' !== $texto ? mb_strtoupper( mb_substr( $texto, 0, 1 ) ) : '·';
	}
}
