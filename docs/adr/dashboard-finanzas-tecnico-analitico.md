# ADR: Dashboard Finanzas Tecnico-Analitico

## Estado

Aceptado para implementacion.

## Contexto

El dashboard de Finanzas debe ayudar al administrador o dueno de panaderia a revisar el periodo con datos generados por la operacion real: dinero recibido, gastos, compras, inventario valorizado y trazabilidad contable.

El dashboard operativo usa storytelling, tabs de rango, insight unico por modo, limite de densidad, micro-status y semantica estricta. Finanzas usa esas mismas tecnicas, pero con una lectura mas analitica y menos texto.

## Decision

La lectura principal de Finanzas se basa en operacion real, no en contabilidad formal pura ni flujo de caja puro.

La pantalla usa tres rangos:

- Mes: lectura principal del cierre operativo actual.
- Trimestre: lectura de presion y continuidad.
- Ano: lectura amplia de tendencia.

El chart usa `GET /finanzas/resumen?range=mes|trimestre|anio` para conmutar sin recargar la pantalla. La vista precarga el rango Mes como estado inicial y conserva esos datos como fallback si la consulta dinamica falla.

El resultado principal se llama "resultado antes de costo" en reportes y pruebas porque calcula dinero recibido menos gastos. En la interfaz se resume como "Resultado" cuando el contexto ya esta claro. Las compras se muestran separadas como presion de reposicion e inventario, no como costo de venta formal.

## Consecuencias

- La pantalla evita presentar una utilidad contable definitiva.
- Los rangos mantienen paridad con el rediseno del dashboard operativo, pero adaptados a ciclos financieros.
- El micro-status resume confiabilidad del dato: datos completos, compras sin stock, productos sin receta y asientos.
- El dashboard no reemplaza reportes detallados; dirige hacia ellos cuando se necesita auditar.
- Las superficies del dashboard deben ser transparentes, con borde cafe translucidado. El color de estado puede apoyar, pero no debe convertir cada bloque en una tarjeta pesada.
- La historia visual debe leerse en este orden: estado del periodo, tendencia, alertas, rentabilidad, proveedores, tendencias finales e inventario.
- Cada texto visible debe ayudar a decidir o navegar. Si solo explica contabilidad, va al glosario o al reporte, no al dashboard.
- El dashboard no debe acumular KPI sueltos. La rentabilidad se evalua con graficos de margen, gastos y compras contra dinero recibido; los numeros detallados pertenecen a los reportes.
- La zona final es menos protagonista: una grafica de barras para ver tendencia y una dona para inventario. No debe competir con el estado principal.
- El lateral de analisis generico se reemplaza por proveedores del periodo, porque ayuda a entender hacia donde se fue el dinero de compras.

## Limites

- No sustituye el libro contable.
- No representa flujo de caja bancario.
- No calcula costo de venta real completo.
- No cambia reglas de ventas, compras, gastos, inventario ni asientos.
