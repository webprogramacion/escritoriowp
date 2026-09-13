<?php
/**
 * Indicadores de resumen del escritorio.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Escritorio;

use EscritorioWP\Capacidades;
use EscritorioWP\Woo\Woo;

defined( 'ABSPATH' ) || exit;

/**
 * Calcula las tarjetas de indicadores que encabezan el escritorio.
 *
 * Los indicadores que dependen de WooCommerce se guardan en un transitorio porque recorren los
 * pedidos del mes; el resto se apoyan en contadores que WordPress ya mantiene cacheados.
 */
final class Resumen {

	/**
	 * Transitorio donde se cachean los indicadores de WooCommerce.
	 */
	private const TRANSITORIO_WOO = 'escritoriowp_resumen_woo';

	/**
	 * Duración de la caché de los indicadores de WooCommerce, en segundos.
	 */
	private const CADUCIDAD_WOO = 5 * MINUTE_IN_SECONDS;

	/**
	 * Estados de pedido que cuentan como ingresos.
	 *
	 * @var string[]
	 */
	public const ESTADOS_INGRESO = array( 'completed', 'processing' );

	/**
	 * Devuelve las tarjetas de indicadores visibles para el usuario actual.
	 *
	 * @param bool $refrescar Si es verdadero, recalcula los indicadores cacheados.
	 * @return array<int, array<string, string>>
	 */
	public static function obtener( bool $refrescar = false ): array {
		$tarjetas = array();

		if ( Capacidades::puede_ver( 'entradas' ) ) {
			$conteo     = wp_count_posts( 'post' );
			$borradores = (int) ( $conteo->draft ?? 0 );

			$tarjetas[] = self::tarjeta(
				'entradas',
				__( 'Entradas publicadas', 'escritoriowp' ),
				(int) ( $conteo->publish ?? 0 ),
				$borradores > 0
					? sprintf(
						/* translators: %s: número de entradas en borrador. */
						_n( '%s en borrador', '%s en borrador', $borradores, 'escritoriowp' ),
						number_format_i18n( $borradores )
					)
					: '',
				Capacidades::url_listado( 'entradas' ),
				'azul'
			);
		}

		if ( Capacidades::puede_ver( 'paginas' ) ) {
			$conteo = wp_count_posts( 'page' );

			$tarjetas[] = self::tarjeta(
				'paginas',
				__( 'Páginas publicadas', 'escritoriowp' ),
				(int) ( $conteo->publish ?? 0 ),
				'',
				Capacidades::url_listado( 'paginas' ),
				'azul'
			);
		}

		if ( Capacidades::puede_ver( 'usuarios' ) ) {
			$usuarios = count_users();

			$tarjetas[] = self::tarjeta(
				'usuarios',
				__( 'Usuarios registrados', 'escritoriowp' ),
				(int) ( $usuarios['total_users'] ?? 0 ),
				'',
				Capacidades::url_listado( 'usuarios' ),
				'neutro'
			);
		}

		if ( Capacidades::puede_ver( 'productos' ) || Capacidades::puede_ver( 'pedidos' ) ) {
			$tarjetas = array_merge( $tarjetas, self::tarjetas_woo( $refrescar ) );
		}

		if ( Capacidades::puede_ver( 'comentarios' ) ) {
			$comentarios = wp_count_comments();
			$pendientes  = (int) ( $comentarios->moderated ?? 0 );

			$tarjetas[] = self::tarjeta(
				'comentarios',
				__( 'Comentarios pendientes', 'escritoriowp' ),
				$pendientes,
				'',
				Capacidades::url_listado( 'comentarios' ),
				$pendientes > 0 ? 'ambar' : 'neutro'
			);
		}

		if ( Capacidades::puede_ver( 'actualizaciones' ) ) {
			$datos      = wp_get_update_data();
			$pendientes = (int) ( $datos['counts']['total'] ?? 0 );

			$tarjetas[] = self::tarjeta(
				'actualizaciones',
				__( 'Actualizaciones pendientes', 'escritoriowp' ),
				$pendientes,
				$pendientes > 0 ? '' : __( 'Todo al día', 'escritoriowp' ),
				Capacidades::url_listado( 'actualizaciones' ),
				$pendientes > 0 ? 'rojo' : 'verde'
			);
		}

		return $tarjetas;
	}

	/**
	 * Calcula las tarjetas que dependen de WooCommerce, con caché.
	 *
	 * @param bool $refrescar Si es verdadero, ignora la caché y la reescribe.
	 * @return array<int, array<string, string>>
	 */
	private static function tarjetas_woo( bool $refrescar ): array {
		if ( ! Woo::activo() ) {
			return array();
		}

		$datos = $refrescar ? false : get_transient( self::TRANSITORIO_WOO );

		if ( ! is_array( $datos ) ) {
			$datos = self::calcular_woo();
			set_transient( self::TRANSITORIO_WOO, $datos, self::CADUCIDAD_WOO );
		}

		$tarjetas = array();

		if ( Capacidades::puede_ver( 'productos' ) ) {
			$tarjetas[] = self::tarjeta(
				'productos',
				__( 'Productos publicados', 'escritoriowp' ),
				(int) $datos['productos'],
				'',
				Capacidades::url_listado( 'productos' ),
				'azul'
			);
		}

		if ( Capacidades::puede_ver( 'pedidos' ) ) {
			$tarjetas[] = self::tarjeta(
				'pedidos',
				__( 'Pedidos este mes', 'escritoriowp' ),
				(int) $datos['pedidos'],
				'',
				Capacidades::url_listado( 'pedidos' ),
				'verde'
			);

			$tarjetas[] = array(
				'clave'    => 'ingresos',
				'etiqueta' => __( 'Ingresos este mes', 'escritoriowp' ),
				'valor'    => Woo::precio( (float) $datos['ingresos'] ),
				'detalle'  => sprintf(
					/* translators: %s: lista de estados de pedido que cuentan como ingresos. */
					__( 'Pedidos %s', 'escritoriowp' ),
					self::etiquetas_estados_ingreso()
				),
				'url'      => Capacidades::url_listado( 'pedidos' ),
				'icono'    => 'dashicons-chart-bar',
				'tono'     => 'verde',
			);
		}

		return $tarjetas;
	}

	/**
	 * Recorre los pedidos del mes en curso y calcula sus contadores.
	 *
	 * @return array{productos: int, pedidos: int, ingresos: float}
	 */
	private static function calcular_woo(): array {
		$conteo_productos = wp_count_posts( 'product' );

		// Primer instante del mes en curso según la zona horaria del sitio.
		$inicio_mes = ( new \DateTimeImmutable( 'first day of this month 00:00:00', wp_timezone() ) )->getTimestamp();

		$pedidos = wc_get_orders(
			array(
				'limit'        => -1,
				'type'         => 'shop_order',
				'status'       => Woo::estados_pedido(),
				'date_created' => '>=' . $inicio_mes,
			)
		);

		$pedidos = is_array( $pedidos ) ? $pedidos : array();

		return array(
			'productos' => (int) ( $conteo_productos->publish ?? 0 ),
			'pedidos'   => count( $pedidos ),
			'ingresos'  => self::calcular_ingresos( $pedidos ),
		);
	}

	/**
	 * Suma el total de los pedidos que cuentan como ingresos.
	 *
	 * Función pura: acepta cualquier objeto con get_status() y get_total(), para poder probarla sin
	 * WooCommerce.
	 *
	 * @param iterable $pedidos Pedidos a sumar; cada elemento debe tener get_status() y get_total().
	 * @return float
	 */
	public static function calcular_ingresos( iterable $pedidos ): float {
		$total = 0.0;

		foreach ( $pedidos as $pedido ) {
			if ( ! is_object( $pedido ) || ! method_exists( $pedido, 'get_status' ) || ! method_exists( $pedido, 'get_total' ) ) {
				continue;
			}

			if ( ! in_array( self::estado_sin_prefijo( (string) $pedido->get_status() ), self::ESTADOS_INGRESO, true ) ) {
				continue;
			}

			$total += (float) $pedido->get_total();
		}

		return $total;
	}

	/**
	 * Quita el prefijo «wc-» de un estado de pedido.
	 *
	 * @param string $estado Estado con o sin prefijo.
	 * @return string
	 */
	public static function estado_sin_prefijo( string $estado ): string {
		return str_starts_with( $estado, 'wc-' ) ? substr( $estado, 3 ) : $estado;
	}

	/**
	 * Lista legible de los estados que cuentan como ingresos.
	 *
	 * @return string
	 */
	private static function etiquetas_estados_ingreso(): string {
		$etiquetas = array_map(
			static fn ( string $estado ): string => Woo::etiqueta_estado_pedido( $estado ),
			self::ESTADOS_INGRESO
		);

		return implode( ' + ', $etiquetas );
	}

	/**
	 * Construye una tarjeta de indicador.
	 *
	 * @param string $clave    Identificador de la tarjeta.
	 * @param string $etiqueta Texto descriptivo.
	 * @param int    $valor    Valor numérico.
	 * @param string $detalle  Texto secundario.
	 * @param string $url      Pantalla a la que enlaza.
	 * @param string $tono     Tono de color.
	 * @return array<string, string>
	 */
	private static function tarjeta( string $clave, string $etiqueta, int $valor, string $detalle, string $url, string $tono ): array {
		return array(
			'clave'    => $clave,
			'etiqueta' => $etiqueta,
			'valor'    => number_format_i18n( $valor ),
			'detalle'  => $detalle,
			'url'      => $url,
			'icono'    => Capacidades::icono( $clave ),
			'tono'     => $tono,
		);
	}

	/**
	 * Borra la caché de los indicadores de WooCommerce.
	 *
	 * @return void
	 */
	public static function limpiar_cache(): void {
		delete_transient( self::TRANSITORIO_WOO );
	}
}
