<?php
/**
 * Creación rápida de entradas.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Creacion;

defined( 'ABSPATH' ) || exit;

/**
 * Crea entradas del blog desde el modal del escritorio.
 */
final class CreadorEntrada extends CreadorContenido {

	/**
	 * Tipo de contenido de WordPress.
	 *
	 * @return string
	 */
	protected static function tipo_contenido(): string {
		return 'post';
	}

	/**
	 * Clave del tipo dentro del plugin.
	 *
	 * @return string
	 */
	protected static function tipo(): string {
		return 'entradas';
	}
}
