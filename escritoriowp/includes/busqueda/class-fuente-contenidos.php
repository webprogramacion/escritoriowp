<?php
/**
 * Fuente de búsqueda de entradas, páginas y otros tipos de contenido.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

namespace EscritorioWP\Busqueda;

use EscritorioWP\Capacidades;
use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Busca contenidos por título.
 *
 * Se instancia tres veces: una para entradas, otra para páginas y otra para el resto de tipos con
 * interfaz de administración. La coincidencia se limita al título mediante filtros sobre la
 * consulta, y el orden prioriza los títulos que empiezan por el texto buscado.
 */
final class FuenteContenidos implements Fuente {

	/**
	 * Tipos de contenido que nunca se buscan: adjuntos, estructuras del editor y datos internos
	 * de WooCommerce, que tienen sus propias fuentes.
	 *
	 * @var string[]
	 */
	private const TIPOS_EXCLUIDOS = array(
		'attachment',
		'revision',
		'nav_menu_item',
		'custom_css',
		'customize_changeset',
		'oembed_cache',
		'user_request',
		'wp_block',
		'wp_template',
		'wp_template_part',
		'wp_global_styles',
		'wp_navigation',
		'wp_font_family',
		'wp_font_face',
		'product',
		'product_variation',
		'shop_order',
		'shop_order_refund',
		'shop_order_placehold',
		'shop_coupon',
		'shop_subscription',
	);

	/**
	 * Clave de la fuente: entradas, paginas o contenidos.
	 *
	 * @var string
	 */
	private string $clave;

	/**
	 * Texto que está buscando la consulta en curso.
	 *
	 * @var string
	 */
	private string $termino = '';

	/**
	 * Constructor.
	 *
	 * @param string $clave Clave de la fuente.
	 */
	public function __construct( string $clave ) {
		$this->clave = $clave;
	}

	/**
	 * Clave de la fuente.
	 *
	 * @return string
	 */
	public function clave(): string {
		return $this->clave;
	}

	/**
	 * Etiqueta del grupo.
	 *
	 * @return string
	 */
	public function etiqueta(): string {
		return Capacidades::etiqueta( $this->clave );
	}

	/**
	 * Indica si hay algún tipo de contenido que consultar.
	 *
	 * @return bool
	 */
	public function disponible(): bool {
		return array() !== $this->tipos();
	}

	/**
	 * Indica si el usuario puede editar alguno de los tipos consultados.
	 *
	 * @return bool
	 */
	public function puede_buscar(): bool {
		return array() !== $this->tipos();
	}

	/**
	 * Tipos de contenido que consulta esta instancia, ya filtrados por capacidad.
	 *
	 * @return string[]
	 */
	private function tipos(): array {
		if ( 'entradas' === $this->clave ) {
			return current_user_can( 'edit_posts' ) ? array( 'post' ) : array();
		}

		if ( 'paginas' === $this->clave ) {
			return current_user_can( 'edit_pages' ) ? array( 'page' ) : array();
		}

		$tipos = array();

		foreach ( get_post_types( array( 'show_ui' => true ), 'objects' ) as $objeto ) {
			if ( in_array( $objeto->name, self::TIPOS_EXCLUIDOS, true ) ) {
				continue;
			}

			if ( in_array( $objeto->name, array( 'post', 'page' ), true ) ) {
				continue;
			}

			if ( ! current_user_can( $objeto->cap->edit_posts ) ) {
				continue;
			}

			$tipos[] = $objeto->name;
		}

		return $tipos;
	}

	/**
	 * Capacidad que permite ver contenidos ajenos no publicados.
	 *
	 * @return string
	 */
	private function capacidad_ajenos(): string {
		return 'paginas' === $this->clave ? 'edit_others_pages' : 'edit_others_posts';
	}

	/**
	 * Ejecuta la búsqueda.
	 *
	 * @param string $termino Texto buscado.
	 * @param int    $limite  Número máximo de resultados.
	 * @return Grupo
	 */
	public function buscar( string $termino, int $limite ): Grupo {
		$tipos = $this->tipos();

		if ( array() === $tipos ) {
			return new Grupo( $this->clave, $this->etiqueta(), array(), 0 );
		}

		$this->termino = $termino;

		add_filter( 'posts_where', array( $this, 'filtrar_where' ), 10, 2 );
		add_filter( 'posts_orderby', array( $this, 'filtrar_orderby' ), 10, 2 );

		$consulta = new WP_Query(
			array(
				'post_type'              => $tipos,
				'post_status'            => $this->estados(),
				'posts_per_page'         => $limite,
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'escritoriowp_termino'   => $termino,
			)
		);

		remove_filter( 'posts_where', array( $this, 'filtrar_where' ), 10 );
		remove_filter( 'posts_orderby', array( $this, 'filtrar_orderby' ), 10 );

		$resultados = array();

		foreach ( $consulta->posts as $entrada ) {
			if ( $entrada instanceof WP_Post ) {
				$resultados[] = $this->resultado( $entrada );
			}
		}

		return new Grupo(
			$this->clave,
			$this->etiqueta(),
			$resultados,
			(int) $consulta->found_posts,
			$this->url_listado( $termino, $resultados )
		);
	}

	/**
	 * Estados de contenido que se buscan: los que aparecen en los listados del administrador.
	 *
	 * @return string[]
	 */
	private function estados(): array {
		$estados = array_keys( get_post_stati( array( 'show_in_admin_all_list' => true ) ) );

		return array_values( array_diff( $estados, array( 'trash', 'auto-draft' ) ) );
	}

	/**
	 * Limita la consulta a coincidencias en el título y a lo que el usuario puede ver.
	 *
	 * @param string   $where    Cláusula WHERE actual.
	 * @param WP_Query $consulta Consulta en curso.
	 * @return string
	 */
	public function filtrar_where( string $where, WP_Query $consulta ): string {
		global $wpdb;

		$termino = (string) $consulta->get( 'escritoriowp_termino' );

		if ( '' === $termino ) {
			return $where;
		}

		$where .= $wpdb->prepare(
			" AND {$wpdb->posts}.post_title LIKE %s",
			'%' . $wpdb->esc_like( $termino ) . '%'
		);

		// Los contenidos ajenos que no están publicados solo los ve quien puede editarlos.
		if ( ! current_user_can( $this->capacidad_ajenos() ) ) {
			$where .= $wpdb->prepare(
				" AND ( {$wpdb->posts}.post_author = %d OR {$wpdb->posts}.post_status = 'publish' )",
				get_current_user_id()
			);
		}

		return $where;
	}

	/**
	 * Ordena priorizando los títulos que empiezan por el texto buscado.
	 *
	 * @param string   $orderby  Cláusula ORDER BY actual.
	 * @param WP_Query $consulta Consulta en curso.
	 * @return string
	 */
	public function filtrar_orderby( string $orderby, WP_Query $consulta ): string {
		global $wpdb;

		$termino = (string) $consulta->get( 'escritoriowp_termino' );

		if ( '' === $termino ) {
			return $orderby;
		}

		return $wpdb->prepare(
			"CASE WHEN {$wpdb->posts}.post_title LIKE %s THEN 0 ELSE 1 END ASC, {$wpdb->posts}.post_modified DESC",
			$wpdb->esc_like( $termino ) . '%'
		);
	}

	/**
	 * Convierte una entrada en un resultado.
	 *
	 * @param WP_Post $entrada Entrada encontrada.
	 * @return Resultado
	 */
	private function resultado( WP_Post $entrada ): Resultado {
		$titulo = Resultado::limpiar( (string) get_the_title( $entrada ) );
		$titulo = '' !== $titulo ? $titulo : __( '(sin título)', 'escritoriowp' );

		$partes = array();

		if ( 'contenidos' === $this->clave ) {
			$objeto = get_post_type_object( $entrada->post_type );

			if ( $objeto ) {
				$partes[] = $objeto->labels->singular_name;
			}
		}

		$estado = get_post_status_object( $entrada->post_status );

		if ( $estado ) {
			$partes[] = $estado->label;
		}

		$autor = get_the_author_meta( 'display_name', (int) $entrada->post_author );

		if ( '' !== (string) $autor ) {
			$partes[] = sprintf(
				/* translators: %s: nombre del autor. */
				__( 'por %s', 'escritoriowp' ),
				$autor
			);
		}

		return new Resultado(
			$this->clave,
			(int) $entrada->ID,
			$titulo,
			implode( ' · ', $partes ),
			(string) get_edit_post_link( $entrada->ID, 'raw' ),
			'publish' === $entrada->post_status ? (string) get_permalink( $entrada ) : '',
			$this->icono( $entrada ),
			(string) get_the_modified_date( (string) get_option( 'date_format' ), $entrada )
		);
	}

	/**
	 * Icono del resultado según su tipo de contenido.
	 *
	 * @param WP_Post $entrada Entrada encontrada.
	 * @return string
	 */
	private function icono( WP_Post $entrada ): string {
		return match ( $entrada->post_type ) {
			'post'  => Capacidades::icono( 'entradas' ),
			'page'  => Capacidades::icono( 'paginas' ),
			default => Capacidades::icono( 'contenidos' ),
		};
	}

	/**
	 * URL del listado nativo con la búsqueda aplicada.
	 *
	 * Para el grupo mixto de «otros contenidos» se usa el listado del tipo del primer resultado,
	 * que es el más relevante.
	 *
	 * @param string      $termino    Texto buscado.
	 * @param Resultado[] $resultados Resultados encontrados.
	 * @return string
	 */
	private function url_listado( string $termino, array $resultados ): string {
		if ( 'entradas' === $this->clave || 'paginas' === $this->clave ) {
			return Capacidades::url_listado( $this->clave, $termino );
		}

		if ( array() === $resultados ) {
			return '';
		}

		$primero = get_post( (int) $resultados[0]->id );

		if ( ! $primero instanceof WP_Post ) {
			return '';
		}

		return add_query_arg(
			array(
				'post_type' => $primero->post_type,
				's'         => rawurlencode( $termino ),
			),
			admin_url( 'edit.php' )
		);
	}
}
