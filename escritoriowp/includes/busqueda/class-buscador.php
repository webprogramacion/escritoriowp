<?php
/**
 * Agregador de fuentes de búsqueda.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Busqueda;

defined( 'ABSPATH' ) || exit;

/**
 * Recorre las fuentes solicitadas y devuelve sus grupos en el orden fijo de la interfaz.
 *
 * Esta clase no llama a WordPress: recibe las fuentes ya construidas, de modo que su lógica de
 * orden, filtrado y límites se puede probar de forma aislada.
 */
final class Buscador {

	/**
	 * Longitud mínima del texto buscado.
	 */
	public const MIN_CARACTERES = 2;

	/**
	 * Longitud máxima del texto buscado.
	 */
	public const MAX_CARACTERES = 100;

	/**
	 * Límite de resultados por grupo por defecto.
	 */
	public const LIMITE_DEFECTO = 5;

	/**
	 * Límite máximo de resultados por grupo.
	 */
	public const LIMITE_MAXIMO = 20;

	/**
	 * Fuentes disponibles, en el orden en que se presentan sus grupos.
	 *
	 * @var Fuente[]
	 */
	private array $fuentes;

	/**
	 * Constructor.
	 *
	 * @param Fuente[] $fuentes Fuentes en orden de presentación.
	 */
	public function __construct( array $fuentes ) {
		$this->fuentes = $fuentes;
	}

	/**
	 * Construye el buscador con todas las fuentes del plugin.
	 *
	 * @return Buscador
	 */
	public static function crear(): Buscador {
		$fuentes = array(
			new FuenteContenidos( 'entradas' ),
			new FuenteContenidos( 'paginas' ),
			new FuenteContenidos( 'contenidos' ),
			new FuenteUsuarios(),
			new FuenteProductos(),
			new FuentePedidos(),
		);

		/**
		 * Filtra las fuentes de búsqueda del lanzador.
		 *
		 * @param Fuente[] $fuentes Fuentes en orden de presentación.
		 */
		$fuentes = (array) apply_filters( 'escritoriowp_fuentes_busqueda', $fuentes );

		return new self(
			array_values(
				array_filter(
					$fuentes,
					static fn ( mixed $fuente ): bool => $fuente instanceof Fuente
				)
			)
		);
	}

	/**
	 * Claves de las fuentes que el usuario actual puede usar.
	 *
	 * @return string[]
	 */
	public function claves_activas(): array {
		$claves = array();

		foreach ( $this->fuentes as $fuente ) {
			if ( $fuente->disponible() && $fuente->puede_buscar() ) {
				$claves[] = $fuente->clave();
			}
		}

		return $claves;
	}

	/**
	 * Ejecuta la búsqueda en las fuentes solicitadas.
	 *
	 * @param string   $termino Texto buscado.
	 * @param string[] $tipos   Claves de fuente a consultar; vacío significa todas.
	 * @param int      $limite  Número máximo de resultados por grupo.
	 * @return Grupo[] Grupos con al menos un resultado, en orden de presentación.
	 */
	public function buscar( string $termino, array $tipos = array(), int $limite = self::LIMITE_DEFECTO ): array {
		$limite = max( 1, min( self::LIMITE_MAXIMO, $limite ) );
		$grupos = array();

		foreach ( $this->fuentes as $fuente ) {
			if ( array() !== $tipos && ! in_array( $fuente->clave(), $tipos, true ) ) {
				continue;
			}

			if ( ! $fuente->disponible() || ! $fuente->puede_buscar() ) {
				continue;
			}

			$grupo = $fuente->buscar( $termino, $limite );

			if ( ! $grupo->vacio() ) {
				$grupos[] = $grupo;
			}
		}

		return $grupos;
	}

	/**
	 * Normaliza el texto buscado y comprueba su longitud.
	 *
	 * Función pura.
	 *
	 * @param string $termino Texto recibido del cliente.
	 * @return string Texto recortado, o cadena vacía si no es válido.
	 */
	public static function normalizar_termino( string $termino ): string {
		$termino = trim( $termino );

		if ( mb_strlen( $termino ) < self::MIN_CARACTERES || mb_strlen( $termino ) > self::MAX_CARACTERES ) {
			return '';
		}

		return $termino;
	}
}
