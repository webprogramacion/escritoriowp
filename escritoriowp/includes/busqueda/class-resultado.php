<?php
/**
 * Resultado individual de una búsqueda.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Busqueda;

defined( 'ABSPATH' ) || exit;

/**
 * Objeto de transferencia con todo lo que el lanzador necesita para pintar una fila.
 *
 * Los textos se guardan siempre como texto plano: el cliente los inserta como contenido de texto,
 * nunca como HTML.
 */
final class Resultado {

	/**
	 * Constructor.
	 *
	 * @param string     $tipo       Clave del grupo al que pertenece.
	 * @param int|string $id         Identificador del elemento.
	 * @param string     $titulo     Título en texto plano.
	 * @param string     $subtitulo  Texto secundario en texto plano.
	 * @param string     $url_editar URL de la pantalla de edición.
	 * @param string     $url_ver    URL pública, o cadena vacía si no la tiene.
	 * @param string     $icono      Clase Dashicon.
	 * @param string     $fecha      Fecha legible asociada al elemento.
	 */
	public function __construct(
		public readonly string $tipo,
		public readonly int|string $id,
		public readonly string $titulo,
		public readonly string $subtitulo,
		public readonly string $url_editar,
		public readonly string $url_ver = '',
		public readonly string $icono = 'dashicons-marker',
		public readonly string $fecha = ''
	) {}

	/**
	 * Convierte el resultado en el array que viaja al cliente.
	 *
	 * @return array<string, mixed>
	 */
	public function a_array(): array {
		return array(
			'tipo'      => $this->tipo,
			'id'        => $this->id,
			'titulo'    => $this->titulo,
			'subtitulo' => $this->subtitulo,
			'urlEditar' => $this->url_editar,
			'urlVer'    => $this->url_ver,
			'icono'     => $this->icono,
			'fecha'     => $this->fecha,
		);
	}

	/**
	 * Limpia un texto de etiquetas y entidades para poder mostrarlo como texto plano.
	 *
	 * @param string $texto Texto de origen.
	 * @return string
	 */
	public static function limpiar( string $texto ): string {
		return trim( html_entity_decode( wp_strip_all_tags( $texto ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}
}
