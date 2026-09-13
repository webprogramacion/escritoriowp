<?php
/**
 * Pruebas del mapa de capacidades.
 *
 * @package EscritorioWP
 */

declare(strict_types=1);

use EscritorioWP\Capacidades;

grupo( 'Capacidades' );

$esperados = array( 'entradas', 'paginas', 'usuarios', 'productos', 'pedidos', 'comentarios', 'actualizaciones' );

comprobar( $esperados, Capacidades::tipos(), 'el mapa cubre los siete tipos de la especificación' );
comprobar( array( 'entradas', 'paginas', 'usuarios', 'productos', 'pedidos' ), Capacidades::TIPOS_PANEL, 'hay cinco paneles de últimos elementos' );

comprobar( array( 'edit_posts' ), Capacidades::MAPA['entradas']['ver'], 'las entradas exigen edit_posts' );
comprobar( array( 'edit_pages' ), Capacidades::MAPA['paginas']['ver'], 'las páginas exigen edit_pages' );
comprobar( array( 'list_users' ), Capacidades::MAPA['usuarios']['ver'], 'los usuarios exigen list_users' );
comprobar( array( 'edit_products' ), Capacidades::MAPA['productos']['ver'], 'los productos exigen edit_products' );
comprobar( array( 'edit_shop_orders' ), Capacidades::MAPA['pedidos']['ver'], 'los pedidos exigen edit_shop_orders' );
comprobar( array( 'moderate_comments' ), Capacidades::MAPA['comentarios']['ver'], 'los comentarios exigen moderate_comments' );

comprobar( 'create_users', Capacidades::MAPA['usuarios']['crear'], 'crear usuarios exige create_users' );
comprobar( 'publish_posts', Capacidades::MAPA['entradas']['publicar'], 'publicar entradas exige publish_posts' );
comprobar( 'publish_pages', Capacidades::MAPA['paginas']['publicar'], 'publicar páginas exige publish_pages' );
comprobar( 'publish_products', Capacidades::MAPA['productos']['publicar'], 'publicar productos exige publish_products' );

comprobar( true, Capacidades::MAPA['productos']['woo'], 'los productos requieren WooCommerce' );
comprobar( true, Capacidades::MAPA['pedidos']['woo'], 'los pedidos requieren WooCommerce' );
comprobar( false, Capacidades::MAPA['entradas']['woo'], 'las entradas no requieren WooCommerce' );

comprobar( true, Capacidades::existe( 'pedidos' ), 'un tipo conocido existe' );
comprobar( false, Capacidades::existe( 'inventado' ), 'un tipo desconocido no existe' );
