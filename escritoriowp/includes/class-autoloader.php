<?php
/**
 * Cargador automático de clases del plugin.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP;

defined( 'ABSPATH' ) || exit;

/**
 * Resuelve los nombres de clase del espacio de nombres EscritorioWP a ficheros dentro de includes/.
 *
 * Convención: EscritorioWP\Busqueda\FuenteUsuarios se carga desde
 * includes/busqueda/class-fuente-usuarios.php (o interface-fuente-usuarios.php si es una interfaz).
 */
final class Autoloader {

	/**
	 * Prefijo del espacio de nombres que gestiona este cargador.
	 */
	private const PREFIJO = 'EscritorioWP\\';

	/**
	 * Directorio base donde viven las clases.
	 *
	 * @var string
	 */
	private static string $base = '';

	/**
	 * Registra el cargador automático.
	 *
	 * @param string $base Ruta absoluta del directorio includes/, con barra final.
	 * @return void
	 */
	public static function registrar( string $base ): void {
		self::$base = rtrim( $base, '/\\' ) . '/';
		spl_autoload_register( array( __CLASS__, 'cargar' ) );
	}

	/**
	 * Carga el fichero correspondiente a una clase.
	 *
	 * @param string $clase Nombre completo de la clase.
	 * @return void
	 */
	public static function cargar( string $clase ): void {
		$ruta = self::ruta_relativa( $clase );

		if ( '' === $ruta ) {
			return;
		}

		foreach ( array( 'class-', 'interface-', 'trait-' ) as $prefijo ) {
			$archivo = self::$base . dirname( $ruta ) . '/' . $prefijo . basename( $ruta );
			$archivo = str_replace( './', '', $archivo );

			if ( is_readable( $archivo ) ) {
				require_once $archivo;
				return;
			}
		}
	}

	/**
	 * Calcula la ruta relativa (sin prefijo de tipo ni extensión) de un nombre de clase.
	 *
	 * Función pura: no depende de WordPress ni del sistema de ficheros, para poder probarla.
	 *
	 * @param string $clase Nombre completo de la clase.
	 * @return string Ruta relativa como "busqueda/fuente-usuarios.php", o cadena vacía si la clase
	 *                no pertenece al plugin.
	 */
	public static function ruta_relativa( string $clase ): string {
		if ( ! str_starts_with( $clase, self::PREFIJO ) ) {
			return '';
		}

		$resto  = substr( $clase, strlen( self::PREFIJO ) );
		$partes = explode( '\\', $resto );
		$nombre = array_pop( $partes );

		$directorios = array_map( 'strtolower', $partes );
		$archivo     = self::kebab( $nombre ) . '.php';

		return ( array() === $directorios )
			? $archivo
			: implode( '/', $directorios ) . '/' . $archivo;
	}

	/**
	 * Convierte un nombre en CamelCase a kebab-case.
	 *
	 * @param string $nombre Nombre de la clase sin espacio de nombres.
	 * @return string Nombre en kebab-case.
	 */
	public static function kebab( string $nombre ): string {
		$kebab = preg_replace( '/(?<!^)[A-Z]/', '-$0', $nombre );

		return strtolower( (string) $kebab );
	}
}
