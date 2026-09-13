# EscritorioWP

Plugin de WordPress que sustituye el escritorio nativo por una interfaz propia y reemplaza la
paleta de comandos de wp-admin por un lanzador que además busca contenido real del sitio
(entradas, páginas, usuarios, productos y pedidos).

## Qué hace

- **Escritorio propio**: indicadores (entradas, páginas, usuarios, comentarios, actualizaciones y,
  con WooCommerce, productos, pedidos e ingresos del mes) y paneles con los últimos elementos de
  cada tipo.
- **Creación rápida**: crear entradas, páginas, usuarios y productos desde un modal sin salir del
  escritorio.
- **Lanzador ⌘K / Ctrl+K**: sustituye la paleta nativa del administrador. Busca pantallas del menú,
  acciones rápidas y contenido por título, email, SKU o número de pedido.
- **Personalización por usuario**: mostrar u ocultar paneles, reordenarlos y elegir tema claro,
  oscuro o automático.
- **WooCommerce opcional**: si está activo añade productos y pedidos; compatible con HPOS.

## Estructura del repositorio

```
<raíz>              Material de desarrollo. NO se sube a WordPress.
├── escritoriowp/   EL PLUGIN: esto es lo que se copia a wp-content/plugins/
├── openspec/       Specs y propuestas de cambio
├── tests/          Pruebas unitarias sin WordPress (php y node)
├── tools/          Utilidades de desarrollo (generación del .pot)
└── docs/           Documentación interna (lista de QA manual)
```

## Requisitos

- WordPress 6.7 o superior
- PHP 8.1 o superior
- WooCommerce 8.0 o superior (opcional)

## Instalación para desarrollo

1. Copia o enlaza el directorio `escritoriowp/` en `wp-content/plugins/` de tu instalación.
2. Actívalo desde Plugins.
3. Ajusta su comportamiento en Ajustes › EscritorioWP.

No hay paso de compilación: el CSS y el JavaScript se escriben a mano y se sirven tal cual.

## Comandos útiles

```bash
composer lint          # phpcs con el estándar WordPress
composer lint:fix      # phpcbf
composer make-pot      # regenera languages/escritoriowp.pot (requiere wp-cli)
php tools/generar-pot.php   # alternativa sin wp-cli
php tests/ejecutar.php      # pruebas unitarias PHP
node tests/ejecutar.js      # pruebas unitarias JavaScript
```

Consulta [CLAUDE.md](CLAUDE.md) para la arquitectura y las convenciones del proyecto.
