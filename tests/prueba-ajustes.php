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

comprobar( 'index.php', $defectos['menu_debajo_de'], 'por defecto el menú va debajo del Escritorio' );
comprobar( '', $defectos['menu_color_fondo'], 'por defecto la entrada del menú no tiene color propio' );

comprobar( 'edit.php?post_type=page', Ajustes::sanear_menu_debajo_de( 'edit.php?post_type=page' ), 'un menú con consulta se conserva' );
comprobar( 'woocommerce', Ajustes::sanear_menu_debajo_de( 'woocommerce' ), 'el identificador de un plugin se conserva' );
comprobar( '', Ajustes::sanear_menu_debajo_de( '' ), 'la cadena vacía se conserva como «Al final»' );
comprobar( 'index.php', Ajustes::sanear_menu_debajo_de( '  index.php  ' ), 'los espacios de los extremos se recortan' );
comprobar( 'index.php', Ajustes::sanear_menu_debajo_de( '<script>' ), 'un identificador con caracteres no válidos cae en el defecto' );
comprobar( 'index.php', Ajustes::sanear_menu_debajo_de( 'escritoriowp' ), 'el propio menú no puede ser la referencia' );
comprobar( 'index.php', Ajustes::sanear_menu_debajo_de( array( 'index.php' ) ), 'un array cae en el defecto' );
comprobar( 'index.php', Ajustes::sanear_menu_debajo_de( str_repeat( 'a', 201 ) ), 'más de 200 caracteres cae en el defecto' );
comprobar( str_repeat( 'a', 200 ), Ajustes::sanear_menu_debajo_de( str_repeat( 'a', 200 ) ), '200 caracteres se aceptan' );

comprobar( '#aabbcc', Ajustes::sanear_color( '#ABC' ), 'el formato corto se amplía y pasa a minúsculas' );
comprobar( '#7a1f5c', Ajustes::sanear_color( '#7A1F5C' ), 'el formato largo pasa a minúsculas' );
comprobar( '', Ajustes::sanear_color( 'rojo' ), 'un nombre de color no se acepta' );
comprobar( '', Ajustes::sanear_color( '#12345' ), 'cinco cifras no se aceptan' );
comprobar( '', Ajustes::sanear_color( '7a1f5c' ), 'sin almohadilla no se acepta' );
comprobar( '', Ajustes::sanear_color( 123456 ), 'un número no se acepta' );

$menu = Ajustes::sanear(
	array(
		'elementos_por_panel' => 50,
		'menu_debajo_de'      => 'upload.php',
		'menu_color_fondo'    => '#ABC',
		'lanzador_activo'     => '1',
	)
);

comprobar( 20, $menu['elementos_por_panel'], 'con los ajustes de texto, el número de elementos se sigue acotando' );
comprobar( 'upload.php', $menu['menu_debajo_de'], 'la posición se guarda como texto' );
comprobar( '#aabbcc', $menu['menu_color_fondo'], 'el color se guarda saneado' );
comprobar( 'index.php', Ajustes::sanear( array( 'menu_debajo_de' => '<b>' ) )['menu_debajo_de'], 'el saneado general aplica el de la posición' );
comprobar( '', Ajustes::sanear( array( 'menu_color_fondo' => 'rojo' ) )['menu_color_fondo'], 'el saneado general aplica el del color' );
