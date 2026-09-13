<?php
/**
 * Fuente de búsqueda de pedidos de WooCommerce.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Busqueda;

use EscritorioWP\Capacidades;
use EscritorioWP\Escritorio\Recientes;
use EscritorioWP\Woo\Woo;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Busca pedidos por número o por los datos de facturación del cliente.
 *
 * La búsqueda por texto se delega en wc_order_search(), que resuelve el almacén activo y funciona
 * igual con HPOS que con el almacenamiento clásico.
 */
final class FuentePedidos implements Fuente {

	/**
	 * Clave de la fuente.
	 *
	 * @return string
	 */
	public function clave(): string {
		return 'pedidos';
	}

	/**
	 * Etiqueta del grupo.
	 *
	 * @return string
	 */
	public function etiqueta(): string {
		return Capacidades::etiqueta( 'pedidos' );
	}

	/**
	 * Requiere WooCommerce.
	 *
	 * @return bool
	 */
	public function disponible(): bool {
		return Woo::activo() && function_exists( 'wc_order_search' );
	}

	/**
	 * Solo busca quien puede editar pedidos.
	 *
	 * @return bool
	 */
	public function puede_buscar(): bool {
		return Capacidades::puede_ver( 'pedidos' );
	}

	/**
	 * Ejecuta la búsqueda.
	 *
	 * @param string $termino Texto buscado.
	 * @param int    $limite  Número máximo de resultados.
	 * @return Grupo
	 */
	public function buscar( string $termino, int $limite ): Grupo {
		$ids = array();

		$numero = self::numero_pedido( $termino );

		if ( $numero > 0 ) {
			$exacto = wc_get_order( $numero );

			if ( $exacto instanceof WC_Order ) {
				$ids[] = $exacto->get_id();
			}
		}

		$encontrados = wc_order_search( $termino );
		$encontrados = is_array( $encontrados ) ? array_map( 'intval', $encontrados ) : array();

		rsort( $encontrados );

		$ids   = array_values( array_unique( array_merge( $ids, $encontrados ) ) );
		$total = count( $ids );

		$resultados = array();

		foreach ( array_slice( $ids, 0, $limite ) as $id ) {
			$pedido = wc_get_order( $id );

			if ( $pedido instanceof WC_Order && 'shop_order' === $pedido->get_type() ) {
				$resultados[] = $this->resultado( $pedido );
			}
		}

		return new Grupo(
			'pedidos',
			$this->etiqueta(),
			$resultados,
			$total,
			Woo::url_listado_pedidos( $termino )
		);
	}

	/**
	 * Interpreta el texto buscado como número de pedido.
	 *
	 * Función pura: admite la almohadilla inicial y los espacios alrededor.
	 *
	 * @param string $termino Texto buscado.
	 * @return int Número de pedido, o 0 si el texto no es un número.
	 */
	public static function numero_pedido( string $termino ): int {
		$limpio = ltrim( trim( $termino ), '#' );

		return ctype_digit( $limpio ) ? (int) $limpio : 0;
	}

	/**
	 * Convierte un pedido en un resultado.
	 *
	 * @param WC_Order $pedido Pedido encontrado.
	 * @return Resultado
	 */
	private function resultado( WC_Order $pedido ): Resultado {
		$cliente = trim( $pedido->get_formatted_billing_full_name() );
		$cliente = '' !== $cliente ? $cliente : __( 'Invitado', 'escritoriowp' );

		$partes = array(
			$cliente,
			Woo::precio( (float) $pedido->get_total() ),
			Woo::etiqueta_estado_pedido( $pedido->get_status() ),
		);

		$creado = $pedido->get_date_created();

		return new Resultado(
			'pedidos',
			$pedido->get_id(),
			'#' . $pedido->get_order_number(),
			implode( ' · ', array_filter( $partes ) ),
			Woo::url_editar_pedido( $pedido->get_id() ),
			'',
			Capacidades::icono( 'pedidos' ),
			$creado ? Recientes::fecha_relativa( $creado->getTimestamp() ) : ''
		);
	}
}
