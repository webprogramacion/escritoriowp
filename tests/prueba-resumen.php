<?php
/**
 * Pruebas del cálculo de ingresos del mes.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

use EscritorioWP\Escritorio\Recientes;
use EscritorioWP\Escritorio\Resumen;

/**
 * Pedido simulado con la interfaz mínima que usa el cálculo.
 */
final class PedidoFalso {

	/**
	 * Constructor.
	 *
	 * @param string $estado Estado del pedido.
	 * @param string $total  Importe total.
	 */
	public function __construct( private string $estado, private string $total ) {}

	/**
	 * Estado del pedido.
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->estado;
	}

	/**
	 * Importe total.
	 *
	 * @return string
	 */
	public function get_total(): string {
		return $this->total;
	}
}

grupo( 'Resumen · ingresos' );

$pedidos = array(
	new PedidoFalso( 'completed', '900.00' ),
	new PedidoFalso( 'processing', '350.00' ),
	new PedidoFalso( 'cancelled', '500.00' ),
	new PedidoFalso( 'refunded', '120.00' ),
	new PedidoFalso( 'failed', '80.00' ),
	new PedidoFalso( 'pending', '40.00' ),
	new PedidoFalso( 'on-hold', '60.00' ),
);

comprobar( 1250.0, Resumen::calcular_ingresos( $pedidos ), 'suma completados y en proceso, y excluye el resto' );
comprobar( 0.0, Resumen::calcular_ingresos( array() ), 'sin pedidos los ingresos son cero' );
comprobar( 900.0, Resumen::calcular_ingresos( array( new PedidoFalso( 'wc-completed', '900.00' ) ) ), 'admite estados con el prefijo «wc-»' );
comprobar( 0.0, Resumen::calcular_ingresos( array( 'no es un pedido' ) ), 'ignora los elementos que no son pedidos' );

comprobar( 'completed', Resumen::estado_sin_prefijo( 'wc-completed' ), 'quita el prefijo «wc-»' );
comprobar( 'completed', Resumen::estado_sin_prefijo( 'completed' ), 'deja intacto el estado sin prefijo' );

grupo( 'Recientes · tonos de estado' );

comprobar( 'verde', Recientes::tono_estado( 'publish' ), 'publicado en verde' );
comprobar( 'ambar', Recientes::tono_estado( 'draft' ), 'borrador en ámbar' );
comprobar( 'azul', Recientes::tono_estado( 'pending' ), 'pendiente en azul' );
comprobar( 'neutro', Recientes::tono_estado( 'inventado' ), 'un estado desconocido es neutro' );

comprobar( 'verde', Recientes::tono_estado_pedido( 'completed' ), 'pedido completado en verde' );
comprobar( 'azul', Recientes::tono_estado_pedido( 'wc-processing' ), 'pedido en proceso en azul, con prefijo' );
comprobar( 'rojo', Recientes::tono_estado_pedido( 'cancelled' ), 'pedido cancelado en rojo' );
comprobar( 'morado', Recientes::tono_estado_pedido( 'refunded' ), 'pedido reembolsado en morado' );
