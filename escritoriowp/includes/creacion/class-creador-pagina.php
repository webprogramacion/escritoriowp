<?php
/**
 * Creación rápida de páginas.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Creacion;

defined( 'ABSPATH' ) || exit;

/**
 * Crea páginas desde el modal del escritorio.
 */
final class CreadorPagina extends CreadorContenido {

	/**
	 * Tipo de contenido de WordPress.
	 *
	 * @return string
	 */
	protected static function tipo_contenido(): string {
		return 'page';
	}

	/**
	 * Clave del tipo dentro del plugin.
	 *
	 * @return string
	 */
	protected static function tipo(): string {
		return 'paginas';
	}
}
