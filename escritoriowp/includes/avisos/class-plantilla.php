<?php
/**
 * Plantilla del correo de aviso: marcadores y textos que se rellenan con ellos.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Avisos;

defined( 'ABSPATH' ) || exit;

/**
 * Funciones puras que convierten el asunto y el mensaje configurados en el texto que se envía.
 *
 * No llaman a WordPress, para poder probarlas sin cargarlo: los textos traducibles, como la
 * conjunción de una enumeración, llegan ya traducidos como parámetros.
 */
final class Plantilla {

	/**
	 * Marcadores que admite la plantilla, sin las llaves y en el orden en que los lista la leyenda.
	 *
	 * La lista es cerrada: cualquier otro texto entre llaves se envía tal cual.
	 */
	public const MARCADORES = array(
		'evento',
		'plugins',
		'lista',
		'cantidad',
		'usuario',
		'fecha',
		'sitio',
		'url',
		'url_plugins',
		'ip',
	);

	/**
	 * Sustituye los marcadores de una plantilla por sus valores.
	 *
	 * Se hace en una sola pasada con `strtr()`, así que el valor de un marcador nunca se vuelve a
	 * interpretar: un plugin llamado «Prueba {sitio}» se envía con ese nombre. Los marcadores sin
	 * valor y los que no están en `MARCADORES` se quedan como están, para que se vea la errata.
	 *
	 * @param string                    $plantilla Asunto o mensaje con marcadores.
	 * @param array<string, string|int> $valores   Valor de cada marcador, por su nombre sin llaves.
	 * @return string
	 */
	public static function rellenar( string $plantilla, array $valores ): string {
		$pares = array();

		foreach ( self::MARCADORES as $marcador ) {
			if ( array_key_exists( $marcador, $valores ) ) {
				$pares[ '{' . $marcador . '}' ] = (string) $valores[ $marcador ];
			}
		}

		return strtr( $plantilla, $pares );
	}

	/**
	 * Deja un texto en una sola línea: los saltos, tabuladores y demás caracteres de control pasan a
	 * ser un espacio, y los espacios seguidos se reducen a uno.
	 *
	 * Trabaja por bytes y solo toca caracteres ASCII, que en UTF-8 nunca forman parte de un carácter
	 * multibyte, así que no estropea las tildes aunque el texto no sea UTF-8 válido.
	 *
	 * @param string $texto Texto de una o varias líneas.
	 * @return string
	 */
	public static function una_linea( string $texto ): string {
		return trim( (string) preg_replace( '/[\x00-\x20\x7F]+/', ' ', $texto ) );
	}

	/**
	 * Enumera nombres para el asunto: todos si son pocos y, si no, los primeros y cuántos faltan.
	 *
	 * Con los formatos `%1$s y %2$s` y `%1$s y %2$d más`: «A», «A y B», «A, B y C» y «A, B, C y 2 más».
	 *
	 * @param array<int, string> $nombres       Nombres que enumerar, en orden.
	 * @param string             $formato_dos   Formato para unir los anteriores (`%1$s`) con el último (`%2$s`).
	 * @param string             $formato_resto Formato para los primeros (`%1$s`) y cuántos faltan (`%2$d`).
	 * @param int                $maximo        Cuántos nombres se muestran como mucho.
	 * @return string
	 */
	public static function resumir( array $nombres, string $formato_dos, string $formato_resto, int $maximo = 3 ): string {
		$nombres = array_values( $nombres );
		$total   = count( $nombres );
		$maximo  = max( 1, $maximo );

		if ( 0 === $total ) {
			return '';
		}

		if ( 1 === $total ) {
			return $nombres[0];
		}

		if ( $total <= $maximo ) {
			return sprintf( $formato_dos, implode( ', ', array_slice( $nombres, 0, -1 ) ), $nombres[ $total - 1 ] );
		}

		return sprintf( $formato_resto, implode( ', ', array_slice( $nombres, 0, $maximo ) ), $total - $maximo );
	}

	/**
	 * Línea de un plugin en la lista del mensaje: `- Nombre versión (fichero)`.
	 *
	 * @param string $nombre    Nombre del plugin.
	 * @param string $version   Versión, o vacía si el plugin no la declara.
	 * @param string $archivo   Fichero principal, relativo a la carpeta de plugins.
	 * @param string $marca_red Texto que se añade tras una raya si el cambio afecta a toda la red, o vacío.
	 * @return string
	 */
	public static function linea( string $nombre, string $version, string $archivo, string $marca_red ): string {
		$titulo = self::una_linea( $nombre . ' ' . $version );
		$linea  = '- ' . $titulo . ' (' . self::una_linea( $archivo ) . ')';
		$marca  = self::una_linea( $marca_red );

		return '' === $marca ? $linea : $linea . ' — ' . $marca;
	}
}
