<?php
/**
 * Pruebas del agregador de fuentes de búsqueda.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

use EscritorioWP\Busqueda\Buscador;
use EscritorioWP\Busqueda\Fuente;
use EscritorioWP\Busqueda\FuentePedidos;
use EscritorioWP\Busqueda\Grupo;
use EscritorioWP\Busqueda\Resultado;

/**
 * Fuente simulada que devuelve tantos resultados como se le pidan.
 */
final class FuenteFalsa implements Fuente {

	/**
	 * Constructor.
	 *
	 * @param string $clave        Clave de la fuente.
	 * @param int    $disponibles  Resultados que puede devolver.
	 * @param bool   $existe       Si la fuente está disponible.
	 * @param bool   $permitida    Si el usuario puede usarla.
	 */
	public function __construct(
		private string $clave,
		private int $disponibles = 10,
		private bool $existe = true,
		private bool $permitida = true
	) {}

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
		return ucfirst( $this->clave );
	}

	/**
	 * Disponibilidad.
	 *
	 * @return bool
	 */
	public function disponible(): bool {
		return $this->existe;
	}

	/**
	 * Permiso.
	 *
	 * @return bool
	 */
	public function puede_buscar(): bool {
		return $this->permitida;
	}

	/**
	 * Búsqueda simulada.
	 *
	 * @param string $termino Texto buscado.
	 * @param int    $limite  Límite de resultados.
	 * @return Grupo
	 */
	public function buscar( string $termino, int $limite ): Grupo {
		$resultados = array();

		for ( $i = 0; $i < min( $limite, $this->disponibles ); $i++ ) {
			$resultados[] = new Resultado( $this->clave, $i, $this->clave . " $i", '', 'url' );
		}

		return new Grupo( $this->clave, $this->etiqueta(), $resultados, $this->disponibles, 'listado' );
	}
}

grupo( 'Buscador · término' );

comprobar( '', Buscador::normalizar_termino( 'a' ), 'un carácter es demasiado corto' );
comprobar( '', Buscador::normalizar_termino( '  a  ' ), 'los espacios no cuentan como caracteres' );
comprobar( 'ab', Buscador::normalizar_termino( ' ab ' ), 'dos caracteres son válidos y se recortan' );
comprobar( '', Buscador::normalizar_termino( str_repeat( 'a', 101 ) ), 'más de 100 caracteres es demasiado largo' );
comprobar( str_repeat( 'a', 100 ), Buscador::normalizar_termino( str_repeat( 'a', 100 ) ), '100 caracteres son válidos' );

grupo( 'Buscador · agregación' );

$buscador = new Buscador(
	array(
		new FuenteFalsa( 'entradas', 40 ),
		new FuenteFalsa( 'paginas', 2 ),
		new FuenteFalsa( 'usuarios', 0 ),
		new FuenteFalsa( 'productos', 5, false ),
		new FuenteFalsa( 'pedidos', 5, true, false ),
	)
);

$grupos = $buscador->buscar( 'texto', array(), 5 );

comprobar( array( 'entradas', 'paginas' ), array_map( static fn ( Grupo $g ): string => $g->clave, $grupos ), 'solo los grupos con resultados, en el orden de las fuentes' );
comprobar( 5, count( $grupos[0]->resultados ), 'el límite recorta los resultados' );
comprobar( 40, $grupos[0]->total, 'el total refleja todas las coincidencias' );
comprobar( 2, count( $grupos[1]->resultados ), 'un grupo con menos coincidencias que el límite las devuelve todas' );

comprobar(
	array( 'entradas', 'paginas', 'usuarios' ),
	$buscador->claves_activas(),
	'se ofrecen las fuentes disponibles y permitidas, aunque no tengan resultados'
);

$solo_paginas = $buscador->buscar( 'texto', array( 'paginas' ), 5 );

comprobar( array( 'paginas' ), array_map( static fn ( Grupo $g ): string => $g->clave, $solo_paginas ), 'el filtro por tipo consulta solo esa fuente' );

$recortado = $buscador->buscar( 'texto', array( 'entradas' ), 999 );

comprobar( Buscador::LIMITE_MAXIMO, count( $recortado[0]->resultados ), 'el límite nunca supera el máximo' );

grupo( 'Buscador · serialización' );

$array = $grupos[0]->a_array();

comprobar( array( 'clave', 'etiqueta', 'total', 'urlListado', 'resultados' ), array_keys( $array ), 'el grupo expone las claves esperadas' );
comprobar( array( 'tipo', 'id', 'titulo', 'subtitulo', 'urlEditar', 'urlVer', 'icono', 'fecha' ), array_keys( $array['resultados'][0] ), 'el resultado expone las claves esperadas' );

grupo( 'Fuente de pedidos · número' );

comprobar( 1043, FuentePedidos::numero_pedido( '#1043' ), 'admite la almohadilla' );
comprobar( 1043, FuentePedidos::numero_pedido( ' 1043 ' ), 'admite espacios alrededor' );
comprobar( 1043, FuentePedidos::numero_pedido( '1043' ), 'admite el número pelado' );
comprobar( 0, FuentePedidos::numero_pedido( 'laura@' ), 'un texto no es un número de pedido' );
comprobar( 0, FuentePedidos::numero_pedido( '10a43' ), 'un número con letras no es un número de pedido' );
