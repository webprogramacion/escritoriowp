<?php
/**
 * Contrato de una fuente de búsqueda.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Busqueda;

defined( 'ABSPATH' ) || exit;

/**
 * Cada tipo de contenido que el lanzador puede encontrar se implementa como una fuente.
 *
 * Añadir una fuente nueva consiste en implementar esta interfaz y engancharla al filtro
 * «escritoriowp_fuentes_busqueda».
 */
interface Fuente {

	/**
	 * Clave con la que el cliente pide o filtra esta fuente.
	 *
	 * @return string
	 */
	public function clave(): string;

	/**
	 * Etiqueta traducida de la cabecera del grupo.
	 *
	 * @return string
	 */
	public function etiqueta(): string;

	/**
	 * Indica si la fuente existe en esta instalación (por ejemplo, si WooCommerce está activo).
	 *
	 * @return bool
	 */
	public function disponible(): bool;

	/**
	 * Indica si el usuario actual tiene capacidad para ver estos resultados.
	 *
	 * @return bool
	 */
	public function puede_buscar(): bool;

	/**
	 * Ejecuta la búsqueda.
	 *
	 * @param string $termino Texto buscado, ya recortado.
	 * @param int    $limite  Número máximo de resultados a devolver.
	 * @return Grupo
	 */
	public function buscar( string $termino, int $limite ): Grupo;
}
