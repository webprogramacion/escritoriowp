<?php
/**
 * Grupo de resultados de una fuente.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Busqueda;

defined( 'ABSPATH' ) || exit;

/**
 * Conjunto de resultados de una misma fuente, con su total real y el enlace al listado nativo.
 */
final class Grupo {

	/**
	 * Constructor.
	 *
	 * @param string      $clave       Clave de la fuente.
	 * @param string      $etiqueta    Etiqueta traducida del grupo.
	 * @param Resultado[] $resultados  Resultados, ya recortados al límite.
	 * @param int         $total       Número total de coincidencias.
	 * @param string      $url_listado URL del listado nativo con la búsqueda aplicada.
	 */
	public function __construct(
		public readonly string $clave,
		public readonly string $etiqueta,
		public readonly array $resultados,
		public readonly int $total,
		public readonly string $url_listado = ''
	) {}

	/**
	 * Indica si el grupo no tiene resultados.
	 *
	 * @return bool
	 */
	public function vacio(): bool {
		return array() === $this->resultados;
	}

	/**
	 * Convierte el grupo en el array que viaja al cliente.
	 *
	 * @return array<string, mixed>
	 */
	public function a_array(): array {
		return array(
			'clave'      => $this->clave,
			'etiqueta'   => $this->etiqueta,
			'total'      => $this->total,
			'urlListado' => $this->url_listado,
			'resultados' => array_map(
				static fn ( Resultado $resultado ): array => $resultado->a_array(),
				$this->resultados
			),
		);
	}
}
