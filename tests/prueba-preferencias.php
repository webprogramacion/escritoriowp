<?php
/**
 * Pruebas de las preferencias del escritorio.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

use EscritorioWP\Escritorio\Preferencias;

grupo( 'Preferencias' );

$paneles = array( 'entradas', 'paginas', 'usuarios', 'productos', 'pedidos' );

comprobar( 'auto', Preferencias::sanear( array( 'tema' => 'inventado' ), $paneles )['tema'], 'un tema inválido cae en «auto»' );
comprobar( 'auto', Preferencias::sanear( array(), $paneles )['tema'], 'sin tema se usa «auto»' );
comprobar( 'oscuro', Preferencias::sanear( array( 'tema' => 'oscuro' ), $paneles )['tema'], 'un tema válido se conserva' );

comprobar(
	array( 'paginas' ),
	Preferencias::sanear( array( 'paneles_ocultos' => array( 'paginas', 'inventado' ) ), $paneles )['paneles_ocultos'],
	'las claves de panel desconocidas se descartan'
);

comprobar(
	array( 'pedidos', 'entradas' ),
	Preferencias::sanear( array( 'orden_paneles' => array( 'pedidos', 'entradas', 'pedidos' ) ), $paneles )['orden_paneles'],
	'el orden elimina duplicados'
);

comprobar(
	array(),
	Preferencias::sanear( 'no es un array', $paneles )['paneles_ocultos'],
	'una entrada que no es un array devuelve los valores por defecto'
);

grupo( 'Preferencias · tipos de contenido personalizados' );

$con_tipos = Preferencias::sanear(
	array(
		'paneles_ocultos' => array( 'cpt-libro', 'cpt-Malo', 'inventado', 'cpt-libro' ),
		'orden_paneles'   => array( 'cpt-libro', 'entradas' ),
	),
	$paneles
);

comprobar( array( 'cpt-libro' ), $con_tipos['paneles_ocultos'], 'un panel de tipo personalizado oculto se conserva aunque no esté en la lista; los mal formados y los repetidos no' );
comprobar( array( 'cpt-libro', 'entradas' ), $con_tipos['orden_paneles'], 'el orden conserva los paneles de tipos personalizados' );

$muchos = array();

for ( $i = 1; $i <= 150; $i++ ) {
	$muchos[] = 'cpt-tipo' . $i;
}

$cortada = Preferencias::sanear( array( 'paneles_ocultos' => $muchos ), $paneles )['paneles_ocultos'];

comprobar( Preferencias::MAX_PANELES, count( $cortada ), 'una lista de 150 paneles se corta en 100' );
comprobar( 'cpt-tipo100', end( $cortada ), 'se conservan los primeros' );

grupo( 'Preferencias · orden de paneles' );

comprobar(
	array( 'pedidos', 'entradas', 'paginas', 'usuarios', 'productos' ),
	Preferencias::ordenar( $paneles, array( 'pedidos', 'entradas' ) ),
	'los paneles preferidos van primero y el resto conserva su orden'
);

comprobar(
	$paneles,
	Preferencias::ordenar( $paneles, array() ),
	'sin preferencia se mantiene el orden por defecto'
);

comprobar(
	$paneles,
	Preferencias::ordenar( $paneles, array( 'inventado' ) ),
	'un panel inexistente en la preferencia se ignora'
);
