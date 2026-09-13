<?php
/**
 * Creación rápida de productos de WooCommerce.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Creacion;

use EscritorioWP\Capacidades;
use EscritorioWP\Escritorio\Resumen;
use EscritorioWP\Woo\Woo;
use WC_Data_Exception;
use WC_Product_Simple;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Crea productos simples desde el modal del escritorio.
 */
final class CreadorProducto {

	/**
	 * Crea el producto.
	 *
	 * @param array<string, mixed> $datos Datos del formulario.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function crear( array $datos ): array|WP_Error {
		if ( ! Woo::activo() ) {
			return Errores::no_encontrado();
		}

		$nombre = trim( sanitize_text_field( (string) ( $datos['titulo'] ?? '' ) ) );
		$sku    = trim( sanitize_text_field( (string) ( $datos['sku'] ?? '' ) ) );
		$precio = trim( (string) ( $datos['precio'] ?? '' ) );

		$campos = array();

		if ( '' === $nombre ) {
			$campos['titulo'] = __( 'El nombre del producto es obligatorio.', 'escritoriowp' );
		}

		if ( '' !== $precio ) {
			$normalizado = wc_format_decimal( $precio );

			if ( '' === $normalizado || ! is_numeric( $normalizado ) || (float) $normalizado < 0 ) {
				$campos['precio'] = __( 'El precio debe ser un número mayor o igual que cero.', 'escritoriowp' );
			} else {
				$precio = $normalizado;
			}
		}

		if ( '' !== $sku && ! wc_product_has_unique_sku( 0, $sku ) ) {
			$campos['sku'] = __( 'Ya existe un producto con ese SKU.', 'escritoriowp' );
		}

		if ( array() !== $campos ) {
			return Errores::validacion( $campos );
		}

		$estado = 'publish' === ( $datos['estado'] ?? 'draft' ) ? 'publish' : 'draft';

		if ( 'publish' === $estado && ! Capacidades::puede_publicar( 'productos' ) ) {
			return Errores::sin_permiso( __( 'Tu rol solo permite guardar borradores.', 'escritoriowp' ) );
		}

		$producto = new WC_Product_Simple();
		$producto->set_name( $nombre );
		$producto->set_status( $estado );

		try {
			if ( '' !== $sku ) {
				$producto->set_sku( $sku );
			}

			if ( '' !== $precio ) {
				$producto->set_regular_price( $precio );
				$producto->set_price( $precio );
			}

			$id = $producto->save();
		} catch ( WC_Data_Exception $excepcion ) {
			return Errores::validacion( array( 'sku' => $excepcion->getMessage() ) );
		}

		if ( ! $id ) {
			return Errores::guardado( '' );
		}

		Resumen::limpiar_cache();

		return array(
			'id'        => (int) $id,
			'tipo'      => 'productos',
			'titulo'    => $nombre,
			'estado'    => $estado,
			'urlEditar' => (string) get_edit_post_link( (int) $id, 'raw' ),
			'urlVer'    => 'publish' === $estado ? (string) $producto->get_permalink() : '',
		);
	}
}
