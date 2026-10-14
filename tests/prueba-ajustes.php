<?php
/**
 * Pruebas del saneado de ajustes.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

use EscritorioWP\Ajustes;
use EscritorioWP\Capacidades;
use EscritorioWP\Escritorio\Resumen;

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

grupo( 'Ajustes · paneles e indicadores activos' );

$indicadores = array_keys( Resumen::INDICADORES );
$paneles     = Capacidades::TIPOS_PANEL;

comprobar( array_fill_keys( $indicadores, true ), $defectos['indicadores_activos'], 'por defecto todos los indicadores están activos' );
comprobar( array_fill_keys( $paneles, true ), $defectos['paneles_activos'], 'por defecto todos los paneles están activos' );

$activos = Ajustes::sanear_activos( array( 'paginas' => '0', 'usuarios' => '1' ), $paneles );

comprobar( false, $activos['paginas'], 'la casilla desmarcada llega como «0» y se guarda como falso' );
comprobar( true, $activos['usuarios'], 'la casilla marcada llega como «1» y se guarda como verdadera' );
comprobar( true, $activos['pedidos'], 'una clave que no llega queda activa' );
comprobar( $paneles, array_keys( $activos ), 'se guarda una clave por cada panel conocido' );

comprobar(
	false,
	array_key_exists( 'inventado', Ajustes::sanear_activos( array( 'inventado' => '1' ), $paneles ) ),
	'una clave desconocida se descarta'
);
comprobar( array_fill_keys( $paneles, true ), Ajustes::sanear_activos( 'paginas', $paneles ), 'un texto deja todos activos' );
comprobar( array_fill_keys( $paneles, true ), Ajustes::sanear_activos( null, $paneles ), 'un valor nulo deja todos activos' );
comprobar(
	$paneles,
	array_keys( Ajustes::sanear_activos( array_fill_keys( array_reverse( $paneles ), '1' ), $paneles ) ),
	'el resultado sigue el orden de la lista conocida aunque llegue desordenado'
);

$con_mapas = Ajustes::sanear(
	array(
		'elementos_por_panel' => 50,
		'indicadores_activos' => array( 'ingresos' => '0' ),
		'paneles_activos'     => array( 'usuarios' => '0' ),
	)
);

comprobar( 20, $con_mapas['elementos_por_panel'], 'con los mapas, el número de elementos por panel se sigue acotando' );
comprobar( false, $con_mapas['indicadores_activos']['ingresos'], 'el saneado general aplica el de los indicadores' );
comprobar( true, $con_mapas['indicadores_activos']['pedidos'], 'desactivar los ingresos no desactiva los pedidos' );
comprobar( false, $con_mapas['paneles_activos']['usuarios'], 'el saneado general aplica el de los paneles' );

grupo( 'Ajustes · avisos por correo' );

foreach ( array( 'aviso_activar', 'aviso_desactivar', 'aviso_instalar', 'aviso_borrar' ) as $clave ) {
	comprobar( false, $defectos[ $clave ], "por defecto «{$clave}» está apagado" );
}

comprobar( '', $defectos['aviso_destinatarios'], 'por defecto no hay destinatarios: se usa el correo de administración' );
comprobar( '', $defectos['aviso_asunto'], 'por defecto el asunto está vacío: se usa el predeterminado' );
comprobar( '', $defectos['aviso_mensaje'], 'por defecto el mensaje está vacío: se usa el predeterminado' );

comprobar(
	'ana@ejemplo.es, luis@ejemplo.es',
	Ajustes::sanear_correos( 'Ana@Ejemplo.es; luis@ejemplo.es' ),
	'las direcciones se separan por punto y coma y pasan a minúsculas'
);
comprobar(
	'ana@ejemplo.es',
	Ajustes::sanear_correos( 'ana@ejemplo.es, no-es-un-correo, ana@ejemplo.es' ),
	'se descartan la dirección no válida y la repetida'
);
comprobar(
	array( 'no-es-un-correo' ),
	Ajustes::correos_descartados( 'ana@ejemplo.es, no-es-un-correo, ana@ejemplo.es' ),
	'correos_descartados() nombra la no válida'
);
comprobar(
	array( 'No-Es-Un-Correo' ),
	Ajustes::correos_descartados( 'No-Es-Un-Correo' ),
	'la descartada se nombra tal como se escribió'
);
comprobar( '', Ajustes::sanear_correos( 'no-es-un-correo' ), 'sin ninguna válida queda vacío' );
comprobar( 'a@ejemplo.es, b@ejemplo.es', Ajustes::sanear_correos( "a@ejemplo.es\n b@ejemplo.es" ), 'también se separan por saltos de línea y espacios' );

$doce = array();
for ( $i = 1; $i <= 12; $i++ ) {
	$doce[] = "persona{$i}@ejemplo.es";
}

comprobar(
	implode( ', ', array_slice( $doce, 0, 10 ) ),
	Ajustes::sanear_correos( implode( ',', $doce ) ),
	'doce direcciones se quedan en las diez primeras'
);
comprobar( '', Ajustes::sanear_correos( array( 'ana@ejemplo.es' ) ), 'un array no se acepta' );
comprobar( '', Ajustes::sanear_correos( 123 ), 'un número no se acepta' );
comprobar( array(), Ajustes::correos_descartados( array( 'x' ) ), 'un array no da direcciones descartadas' );

comprobar( '[Mi web] Aviso importante', Ajustes::sanear_asunto( "[Mi web]\nAviso <b>importante</b>" ), 'el asunto queda en una línea y sin etiquetas' );
comprobar( 200, mb_strlen( Ajustes::sanear_asunto( str_repeat( 'á', 300 ) ) ), 'un asunto de 300 caracteres se recorta a 200' );
comprobar( '', Ajustes::sanear_asunto( array( 'x' ) ), 'un asunto que no es texto queda vacío' );

comprobar( "Hola\n\n{lista}\nAdiós", Ajustes::sanear_mensaje( "Hola\r\n\r\n{lista}\r\nAdiós" ), 'el mensaje normaliza los saltos y conserva sus líneas' );
comprobar( 'Antes  después', Ajustes::sanear_mensaje( 'Antes <script>alert(1)</script> después' ), 'el mensaje pierde las etiquetas script con su contenido' );
comprobar( "a\tb", Ajustes::sanear_mensaje( "a\tb\x07" ), 'el mensaje conserva el tabulador y quita otros controles' );
comprobar( 5000, mb_strlen( Ajustes::sanear_mensaje( str_repeat( 'é', 6000 ) ) ), 'un mensaje de 6000 caracteres se recorta a 5000' );
comprobar( '', Ajustes::sanear_mensaje( '   ' ), 'un mensaje solo con espacios queda vacío' );

$avisos = Ajustes::sanear(
	array(
		'elementos_por_panel' => 50,
		'menu_debajo_de'      => 'upload.php',
		'menu_color_fondo'    => '#ABC',
		'aviso_activar'       => '1',
		'aviso_borrar'        => '0',
		'aviso_destinatarios' => 'ana@ejemplo.es, mal',
		'aviso_asunto'        => "Uno\nDos",
		'aviso_mensaje'       => "Uno\r\nDos",
	)
);

comprobar( 20, $avisos['elementos_por_panel'], 'con los ajustes de los avisos, el número de elementos se sigue acotando' );
comprobar( true, $avisos['aviso_activar'], 'la casilla de activar se guarda como verdadero' );
comprobar( false, $avisos['aviso_borrar'], 'la casilla de borrar se guarda como falso' );
comprobar( 'ana@ejemplo.es', $avisos['aviso_destinatarios'], 'el saneado general aplica el de los destinatarios' );
comprobar( 'Uno Dos', $avisos['aviso_asunto'], 'el saneado general aplica el del asunto' );
comprobar( "Uno\nDos", $avisos['aviso_mensaje'], 'el saneado general aplica el del mensaje' );

grupo( 'Ajustes · aviso de cambios en los administradores' );

comprobar( false, $defectos['aviso_administradores'], 'por defecto el aviso de administradores está apagado' );
comprobar(
	array( 'aviso_borrar', 'aviso_administradores', 'aviso_destinatarios' ),
	array_slice( array_keys( $defectos ), array_search( 'aviso_borrar', array_keys( $defectos ), true ), 3 ),
	'el aviso de administradores va detrás del de borrar y delante de los destinatarios'
);
comprobar( true, Ajustes::sanear( array( 'aviso_administradores' => '1' ) )['aviso_administradores'], 'la casilla marcada se guarda como verdadero' );
comprobar( false, Ajustes::sanear( array( 'aviso_administradores' => '0' ) )['aviso_administradores'], 'la casilla desmarcada se guarda como falso' );

$anterior = Ajustes::sanear(
	array(
		'aviso_activar'       => true,
		'aviso_destinatarios' => 'ana@ejemplo.es',
	)
);

comprobar( false, $anterior['aviso_administradores'], 'una opción guardada sin la clave toma el aviso apagado' );
comprobar( true, $anterior['aviso_activar'], 'y conserva el resto de avisos guardados' );
comprobar( 'ana@ejemplo.es', $anterior['aviso_destinatarios'], 'y conserva los destinatarios guardados' );
