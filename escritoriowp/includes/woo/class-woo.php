<?php
/**
 * Integración opcional con WooCommerce.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Woo;

use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Detecta WooCommerce y encapsula todo lo que depende de él.
 *
 * Ninguna otra clase del plugin debe llamar a funciones «wc_*» sin comprobar antes Woo::activo().
 */
final class Woo {

	/**
	 * Indica si WooCommerce está activo en esta instalación.
	 *
	 * @return bool
	 */
	public static function activo(): bool {
		return class_exists( 'WooCommerce' ) && function_exists( 'wc_get_orders' );
	}

	/**
	 * Indica si WooCommerce guarda los pedidos en tablas propias (HPOS).
	 *
	 * @return bool
	 */
	public static function hpos_activo(): bool {
		if ( ! self::activo() || ! class_exists( OrderUtil::class ) ) {
			return false;
		}

		return OrderUtil::custom_orders_table_usage_is_enabled();
	}

	/**
	 * URL de la pantalla de listado de pedidos, opcionalmente con una búsqueda aplicada.
	 *
	 * @param string $busqueda Texto de búsqueda a aplicar en el listado.
	 * @return string
	 */
	public static function url_listado_pedidos( string $busqueda = '' ): string {
		$base = self::hpos_activo()
			? admin_url( 'admin.php?page=wc-orders' )
			: admin_url( 'edit.php?post_type=shop_order' );

		if ( '' === $busqueda ) {
			return $base;
		}

		return add_query_arg(
			array(
				's'             => rawurlencode( $busqueda ),
				'search-filter' => 'all',
			),
			$base
		);
	}

	/**
	 * URL de la pantalla de creación de un pedido.
	 *
	 * @return string
	 */
	public static function url_nuevo_pedido(): string {
		return self::hpos_activo()
			? admin_url( 'admin.php?page=wc-orders&action=new' )
			: admin_url( 'post-new.php?post_type=shop_order' );
	}

	/**
	 * URL de edición de un pedido concreto.
	 *
	 * @param int $id Identificador del pedido.
	 * @return string
	 */
	public static function url_editar_pedido( int $id ): string {
		return self::hpos_activo()
			? admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $id )
			: admin_url( 'post.php?post=' . $id . '&action=edit' );
	}

	/**
	 * Formatea un importe como texto plano en la moneda de la tienda.
	 *
	 * @param float|string $importe Importe a formatear.
	 * @return string
	 */
	public static function precio( float|string $importe ): string {
		if ( ! self::activo() ) {
			return (string) $importe;
		}

		$html = wc_price( (float) $importe );

		return trim( html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}

	/**
	 * Estados de pedido reales de la tienda (excluye borradores de pago y papelera).
	 *
	 * @return string[]
	 */
	public static function estados_pedido(): array {
		if ( ! self::activo() ) {
			return array();
		}

		return array_keys( wc_get_order_statuses() );
	}

	/**
	 * Etiqueta legible del estado de un pedido.
	 *
	 * @param string $estado Estado con o sin el prefijo «wc-».
	 * @return string
	 */
	public static function etiqueta_estado_pedido( string $estado ): string {
		if ( ! self::activo() ) {
			return $estado;
		}

		$estados = wc_get_order_statuses();
		$clave   = str_starts_with( $estado, 'wc-' ) ? $estado : 'wc-' . $estado;

		return $estados[ $clave ] ?? $estado;
	}
}
