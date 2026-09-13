# Registro de cambios

Todos los cambios relevantes de este proyecto se documentan en este fichero.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y el proyecto utiliza [versionado semántico](https://semver.org/lang/es/).

## [No publicado]

## [0.1.0] - 2026-09-13

### Añadido

- Escritorio propio que sustituye al nativo: cabecera con saludo y accesos rápidos, indicadores de
  resumen y paneles con los últimos elementos de entradas, páginas, usuarios, productos y pedidos.
- Creación rápida de entradas, páginas, usuarios y productos mediante modal, con validación en el
  servidor y control de capacidades.
- Lanzador propio con ⌘K / Ctrl+K que sustituye a la paleta de comandos de wp-admin, con búsqueda
  de pantallas del menú, acciones rápidas y contenido (títulos, emails, SKU y números de pedido).
- Búsqueda unificada en el servidor mediante la ruta REST `escritoriowp/v1/buscar`.
- Personalización por usuario: paneles ocultables y reordenables, y tema claro, oscuro o automático.
- Página de ajustes en Ajustes › EscritorioWP.
- Integración opcional con WooCommerce, compatible con HPOS y con el almacenamiento clásico.
