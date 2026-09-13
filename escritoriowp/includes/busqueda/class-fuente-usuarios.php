<?php
/**
 * Fuente de búsqueda de usuarios.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Busqueda;

use EscritorioWP\Capacidades;
use EscritorioWP\Escritorio\Recientes;
use WP_User;
use WP_User_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Busca usuarios por nombre visible, nombre de usuario, alias o email.
 *
 * Las columnas de búsqueda se declaran de forma explícita para que un fragmento de email como
 * «@empresa.com» encuentre resultados sin depender de la heurística que aplica WordPress cuando no
 * se indican.
 */
final class FuenteUsuarios implements Fuente {

	/**
	 * Columnas donde se busca el texto.
	 *
	 * @var string[]
	 */
	private const COLUMNAS = array( 'user_login', 'user_email', 'user_nicename', 'display_name' );

	/**
	 * Clave de la fuente.
	 *
	 * @return string
	 */
	public function clave(): string {
		return 'usuarios';
	}

	/**
	 * Etiqueta del grupo.
	 *
	 * @return string
	 */
	public function etiqueta(): string {
		return Capacidades::etiqueta( 'usuarios' );
	}

	/**
	 * Los usuarios existen siempre.
	 *
	 * @return bool
	 */
	public function disponible(): bool {
		return true;
	}

	/**
	 * Solo busca quien puede listar usuarios.
	 *
	 * @return bool
	 */
	public function puede_buscar(): bool {
		return Capacidades::puede_ver( 'usuarios' );
	}

	/**
	 * Ejecuta la búsqueda.
	 *
	 * @param string $termino Texto buscado.
	 * @param int    $limite  Número máximo de resultados.
	 * @return Grupo
	 */
	public function buscar( string $termino, int $limite ): Grupo {
		$consulta = new WP_User_Query(
			array(
				'search'         => '*' . $termino . '*',
				'search_columns' => self::COLUMNAS,
				'number'         => $limite,
				'orderby'        => 'display_name',
				'order'          => 'ASC',
				'count_total'    => true,
				'fields'         => 'all',
			)
		);

		$resultados = array();

		foreach ( $consulta->get_results() as $usuario ) {
			if ( $usuario instanceof WP_User ) {
				$resultados[] = $this->resultado( $usuario );
			}
		}

		return new Grupo(
			'usuarios',
			$this->etiqueta(),
			$resultados,
			(int) $consulta->get_total(),
			Capacidades::url_listado( 'usuarios', $termino )
		);
	}

	/**
	 * Convierte un usuario en un resultado.
	 *
	 * @param WP_User $usuario Usuario encontrado.
	 * @return Resultado
	 */
	private function resultado( WP_User $usuario ): Resultado {
		$partes = array_filter(
			array(
				$usuario->user_email,
				Recientes::nombre_rol( $usuario ),
			)
		);

		return new Resultado(
			'usuarios',
			(int) $usuario->ID,
			Resultado::limpiar( $usuario->display_name ),
			implode( ' · ', $partes ),
			(string) get_edit_user_link( $usuario->ID ),
			(string) get_author_posts_url( $usuario->ID ),
			Capacidades::icono( 'usuarios' ),
			''
		);
	}
}
