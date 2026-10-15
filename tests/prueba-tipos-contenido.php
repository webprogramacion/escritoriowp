<?php
/**
 * Pruebas de las funciones puras de los tipos de contenido personalizados.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

use EscritorioWP\TiposContenido;

grupo( 'Tipos de contenido personalizados' );

comprobar( true, TiposContenido::es_clave( 'cpt-libro' ), 'una clave con prefijo y minúsculas es de tipo personalizado' );
comprobar( true, TiposContenido::es_clave( 'cpt-tribe_events' ), 'admite guiones bajos' );
comprobar( true, TiposContenido::es_clave( 'cpt-a-b' ), 'admite guiones' );
comprobar( true, TiposContenido::es_clave( 'cpt-' . str_repeat( 'a', 20 ) ), 'admite un identificador de 20 caracteres' );
comprobar( false, TiposContenido::es_clave( 'entradas' ), 'una clave propia no es de tipo personalizado' );
comprobar( false, TiposContenido::es_clave( 'cpt-' ), 'el prefijo solo no es una clave' );
comprobar( false, TiposContenido::es_clave( 'cpt-Libro' ), 'rechaza las mayúsculas' );
comprobar( false, TiposContenido::es_clave( 'cpt-con espacio' ), 'rechaza los espacios' );
comprobar( false, TiposContenido::es_clave( 'cpt-' . str_repeat( 'a', 21 ) ), 'rechaza un identificador de 21 caracteres' );
comprobar( false, TiposContenido::es_clave( 'xcpt-libro' ), 'el prefijo tiene que ir al principio' );
comprobar( false, TiposContenido::es_clave( "cpt-libro\n" ), 'rechaza un salto de línea final' );

comprobar( 'cpt-libro', TiposContenido::clave( 'libro' ), 'la clave es el prefijo seguido del identificador' );
comprobar( 'libro', TiposContenido::tipo( 'cpt-libro' ), 'el identificador sale de la clave' );
comprobar( '', TiposContenido::tipo( 'entradas' ), 'una clave propia no tiene identificador de tipo' );

comprobar( 'dashicons-book', TiposContenido::icono_menu( 'dashicons-book' ), 'conserva un icono de Dashicons' );
comprobar( 'dashicons-admin-post', TiposContenido::icono_menu( 'https://ejemplo.test/icono.png' ), 'una URL usa el icono por defecto' );
comprobar( 'dashicons-admin-post', TiposContenido::icono_menu( 'data:image/svg+xml;base64,PHN2Zy8+' ), 'un SVG en data: usa el icono por defecto' );
comprobar( 'dashicons-admin-post', TiposContenido::icono_menu( 'none' ), '«none» usa el icono por defecto' );
comprobar( 'dashicons-admin-post', TiposContenido::icono_menu( '' ), 'un icono vacío usa el icono por defecto' );
comprobar( 'dashicons-admin-post', TiposContenido::icono_menu( null ), 'sin icono se usa el icono por defecto' );
comprobar( 'dashicons-admin-post', TiposContenido::icono_menu( 'dashicons-<script>' ), 'una clase con caracteres extraños usa el icono por defecto' );

comprobar(
	array(
		'cpt-evento' => 'Eventos',
		'cpt-libro'  => 'Libros',
	),
	TiposContenido::desambiguar(
		array(
			'cpt-evento' => 'Eventos',
			'cpt-libro'  => 'Libros',
		)
	),
	'los nombres únicos no cambian'
);

comprobar(
	array(
		'cpt-evento'       => 'Eventos (evento)',
		'cpt-tribe_events' => 'Eventos (tribe_events)',
		'cpt-libro'        => 'Libros',
	),
	TiposContenido::desambiguar(
		array(
			'cpt-evento'       => 'Eventos',
			'cpt-tribe_events' => 'Eventos',
			'cpt-libro'        => 'Libros',
		)
	),
	'los nombres repetidos llevan su identificador, en el mismo orden'
);
