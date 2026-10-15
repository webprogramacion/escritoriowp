<?php
/**
 * Pruebas de las funciones puras del registro de avisos.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

use EscritorioWP\Avisos\Registro;

grupo( 'Avisos · filtros del registro' );

comprobar(
	array(
		'tipo'   => 'administradores',
		'estado' => 'fallido',
	),
	Registro::filtros( 'administradores', 'fallido' ),
	'un tipo y un estado válidos se conservan los dos'
);
comprobar(
	array(
		'tipo'   => 'activacion',
		'estado' => '',
	),
	Registro::filtros( 'activacion', 'inventado' ),
	'un estado inventado se ignora sin afectar al tipo'
);
comprobar(
	array(
		'tipo'   => '',
		'estado' => 'enviado',
	),
	Registro::filtros( 'inventado', 'enviado' ),
	'un tipo inventado se ignora sin afectar al estado'
);
comprobar(
	array(
		'tipo'   => 'borrado',
		'estado' => '',
	),
	Registro::filtros( 'borrado', 'Fallido' ),
	'los valores distinguen mayúsculas'
);
comprobar(
	array(
		'tipo'   => '',
		'estado' => 'pendiente',
	),
	Registro::filtros( '<script>', 'pendiente' ),
	'una etiqueta no es un tipo'
);
comprobar(
	array(
		'tipo'   => 'instalacion',
		'estado' => '',
	),
	Registro::filtros( 'instalacion', 3 ),
	'un entero no es un estado'
);
comprobar(
	array(
		'tipo'   => '',
		'estado' => 'fallido',
	),
	Registro::filtros( array( 'activacion' ), 'fallido' ),
	'un array no es un tipo'
);

grupo( 'Avisos · límite de los más recientes' );

comprobar( 5, Registro::limite( null ), 'sin valor da el límite por defecto' );
comprobar( 5, Registro::limite( 'abc' ), 'un texto da el límite por defecto' );
comprobar( 5, Registro::limite( 0 ), 'cero da el límite por defecto' );
comprobar( 5, Registro::limite( -3 ), 'un negativo da el límite por defecto' );
comprobar( 3, Registro::limite( '3' ), 'un número en texto se convierte' );
comprobar( 1, Registro::limite( 1 ), 'uno es válido' );
comprobar( 20, Registro::limite( 20 ), 'el máximo es válido' );
comprobar( 20, Registro::limite( 500 ), 'por encima del máximo se queda en el máximo' );

grupo( 'Avisos · paginación del registro' );

comprobar( 1, Registro::total_paginas( 0 ), 'sin avisos hay una página' );
comprobar( 1, Registro::total_paginas( 20 ), '20 avisos caben en una página' );
comprobar( 2, Registro::total_paginas( 21 ), '21 avisos ocupan dos páginas' );
comprobar( 3, Registro::total_paginas( 45 ), '45 avisos ocupan tres páginas' );

comprobar( 2, Registro::pagina( '2', 3 ), 'una página en texto se convierte' );
comprobar( 1, Registro::pagina( 0, 3 ), 'la página cero es la primera' );
comprobar( 1, Registro::pagina( -1, 3 ), 'una página negativa es la primera' );
comprobar( 1, Registro::pagina( 'x', 3 ), 'una página que no es un número es la primera' );
comprobar( 3, Registro::pagina( 99, 3 ), 'una página de más es la última' );

grupo( 'Avisos · motivo del fallo' );

comprobar( 'Could not instantiate mail function.', Registro::motivo( 'Could not instantiate mail function.' ), 'un motivo normal no cambia' );
comprobar( 'SMTP Error: Could not connect to SMTP host. Failed to connect to server', Registro::motivo( "SMTP Error:\nCould not connect to SMTP host.\r\nFailed to connect to server" ), 'un motivo de tres líneas queda en una' );
comprobar( 'Fallo grave', Registro::motivo( '<b>Fallo</b> grave' ), 'se quitan las etiquetas' );
comprobar( 500, mb_strlen( Registro::motivo( str_repeat( 'ñ', 600 ) ) ), 'se corta a 500 caracteres, contando los multibyte' );
comprobar( str_repeat( 'ñ', 500 ), Registro::motivo( str_repeat( 'ñ', 600 ) ), 'el corte no rompe ningún carácter' );
comprobar( '', Registro::motivo( '' ), 'sin motivo queda vacío' );
comprobar( '', Registro::motivo( "   \n\t " ), 'solo espacios queda vacío' );

grupo( 'Avisos · color del estado' );

comprobar( 'verde', Registro::tono( 'enviado' ), 'enviado es verde' );
comprobar( 'rojo', Registro::tono( 'fallido' ), 'fallido es rojo' );
comprobar( 'ambar', Registro::tono( 'pendiente' ), 'sin confirmar es ámbar' );
comprobar( 'neutro', Registro::tono( 'otro' ), 'un estado desconocido es neutro' );
