<?php
/**
 * Pruebas del saneado de ajustes.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

use EscritorioWP\Ajustes;

grupo( 'Ajustes' );

$defectos = Ajustes::sanear( array() );

comprobar( Ajustes::defectos(), $defectos, 'una opción vacía devuelve todos los valores por defecto' );
comprobar( 5, $defectos['elementos_por_panel'], 'el número de elementos por defecto es 5' );

comprobar( 20, Ajustes::sanear( array( 'elementos_por_panel' => 50 ) )['elementos_por_panel'], '50 se recorta al máximo (20)' );
comprobar( 3, Ajustes::sanear( array( 'elementos_por_panel' => 1 ) )['elementos_por_panel'], '1 se eleva al mínimo (3)' );
comprobar( 7, Ajustes::sanear( array( 'elementos_por_panel' => '7' ) )['elementos_por_panel'], 'un número en texto se convierte' );
comprobar( 5, Ajustes::sanear( array( 'elementos_por_panel' => 'muchos' ) )['elementos_por_panel'], 'un valor no numérico cae en el defecto' );

comprobar( false, Ajustes::sanear( array( 'sustituir_escritorio' => '0' ) )['sustituir_escritorio'], 'la casilla desmarcada llega como «0» y se guarda como falso' );
comprobar( true, Ajustes::sanear( array( 'sustituir_escritorio' => '1' ) )['sustituir_escritorio'], 'la casilla marcada se guarda como verdadero' );

$parciales = Ajustes::sanear( array( 'lanzador_activo' => '0' ) );

comprobar( false, $parciales['lanzador_activo'], 'el ajuste enviado se respeta' );
comprobar( true, $parciales['desactivar_paleta_nativa'], 'un ajuste ausente toma su valor por defecto' );
