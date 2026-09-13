<?php
/**
 * Fuente de búsqueda de productos de WooCommerce.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Busqueda;

use EscritorioWP\Capacidades;
use EscritorioWP\Woo\Woo;
use WC_Data_Store;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Busca productos por nombre, descripción o SKU, incluido el SKU de las variaciones.
 *
 * Se apoya en el almacén de datos de WooCommerce, que consulta la tabla de búsqueda de productos.
 * Cuando la coincidencia es una variación, se devuelve su producto padre, que es el que se edita.
 */
final class FuenteProductos implements Fuente {

	/**
	 * Clave de la fuente.
	 *
	 * @return string
	 */
	public function clave(): string {
		return 'productos';
	}

	/**
	 * Etiqueta del grupo.
	 *
	 * @return string
	 */
	public function etiqueta(): string {
		return Capacidades::etiqueta( 'productos' );
	}

	/**
	 * Requiere WooCommerce.
	 *
	 * @return bool
	 */
	public function disponible(): bool {
		return Woo::activo() && class_exists( 'WC_Data_Store' );
	}

	/**
	 * Solo busca quien puede editar productos.
	 *
	 * @return bool
	 */
	public function puede_buscar(): bool {
		return Capacidades::puede_ver( 'productos' );
	}

	/**
	 * Ejecuta la búsqueda.
	 *
	 * @param string $termino Texto buscado.
	 * @param int    $limite  Número máximo de resultados.
	 * @return Grupo
	 */
	public function buscar( string $termino, int $limite ): Grupo {
		$almacen = WC_Data_Store::load( 'product' );

		// Se piden más identificadores de los necesarios porque varias variaciones pueden colapsar
		// en el mismo producto padre.
		$ids = $almacen->search_products( $termino, '', true, false, $limite * 4 );
		$ids = is_array( $ids ) ? $ids : array();

		$padres = array();

		foreach ( $ids as $id ) {
			$id       = (int) $id;
			$padre    = wp_get_post_parent_id( $id );
			$padres[] = $padre > 0 ? $padre : $id;
		}

		$padres = array_values( array_unique( array_filter( $padres ) ) );
		$total  = count( $padres );

		$resultados = array();

		foreach ( array_slice( $padres, 0, $limite ) as $id ) {
			$producto = wc_get_product( $id );

			if ( $producto instanceof WC_Product ) {
				$resultados[] = $this->resultado( $producto );
			}
		}

		return new Grupo(
			'productos',
			$this->etiqueta(),
			$resultados,
			$total,
			Capacidades::url_listado( 'productos', $termino )
		);
	}

	/**
	 * Convierte un producto en un resultado.
	 *
	 * @param WC_Product $producto Producto encontrado.
	 * @return Resultado
	 */
	private function resultado( WC_Product $producto ): Resultado {
		$partes = array();
		$precio = $producto->get_price();

		if ( '' !== (string) $precio ) {
			$partes[] = Woo::precio( (float) $precio );
		}

		$sku = $producto->get_sku();

		if ( '' !== $sku ) {
			$partes[] = sprintf(
				/* translators: %s: código SKU del producto. */
				__( 'SKU %s', 'escritoriowp' ),
				$sku
			);
		}

		$partes[] = $producto->is_in_stock()
			? __( 'En stock', 'escritoriowp' )
			: __( 'Agotado', 'escritoriowp' );

		$estado = get_post_status_object( $producto->get_status() );

		if ( $estado ) {
			$partes[] = $estado->label;
		}

		return new Resultado(
			'productos',
			$producto->get_id(),
			Resultado::limpiar( $producto->get_name() ),
			implode( ' · ', $partes ),
			(string) get_edit_post_link( $producto->get_id(), 'raw' ),
			'publish' === $producto->get_status() ? (string) $producto->get_permalink() : '',
			Capacidades::icono( 'productos' ),
			''
		);
	}
}
