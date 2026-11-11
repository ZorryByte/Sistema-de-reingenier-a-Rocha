<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #000;
            background-color: #fff;
            width: 100%;
            line-height: 1.3;
        }

        .wrapper {
            width: 226px;
            margin: 0 auto;
            padding: 8px 6px;
        }

        .header {
            text-align: center;
            padding-bottom: 6px;
            border-bottom: 1px solid #000;
            margin-bottom: 8px;
        }

        .company-name {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .company-address {
            font-size: 8px;
            margin-top: 2px;
        }

        .document-title {
            text-align: center;
            padding: 4px 0;
            margin-bottom: 6px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .document-title span {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .meta-row div {
            font-size: 9px;
        }

        .meta-label {
            font-weight: 600;
        }

        .divider {
            height: 1px;
            background-color: #000;
            margin: 6px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 3px 2px;
            border-bottom: 1px solid #000;
            text-align: left;
        }

        thead th:nth-child(4),
        thead th:last-child {
            text-align: right;
        }

        tbody tr {
            border-bottom: 1px dotted #999;
        }

        tbody td {
            font-size: 9px;
            padding: 3px 2px;
            vertical-align: top;
        }

        td:nth-child(3),
        td:nth-child(4) {
            text-align: right;
        }

        .item-name {
            font-weight: 600;
        }

        .item-detail {
            font-size: 8px;
            color: #555;
        }

        .descuento-col {
            font-size: 8px;
            color: #c00;
        }

        .totals-section {
            margin-top: 6px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
        }

        .total-row.grand {
            border-top: 1px solid #000;
            margin-top: 4px;
            padding-top: 6px;
            font-weight: 700;
            font-size: 11px;
        }

        .totals-section {
            text-align: right;
        }

        .totals-section .total-row {
            display: block;
            text-align: right;
        }

        .total-label {
            font-size: 9px;
        }

        .total-value {
            font-size: 9px;
            font-weight: 600;
        }

        .grand .total-label {
            font-size: 11px;
        }

        .grand .total-value {
            font-size: 11px;
        }

        .pagos-section {
            margin-top: 8px;
        }

        .pagos-title {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .pago-row {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            padding: 2px 0;
        }

        .pago-metodo {
            text-transform: capitalize;
        }

        .footer {
            text-align: center;
            padding-top: 8px;
            border-top: 1px solid #000;
            margin-top: 10px;
        }

        .footer-text {
            font-size: 9px;
            font-style: italic;
        }

        .footer-numbers {
            font-size: 8px;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="company-name">Rocha Bakery & Coffee</div>
            <div class="company-address">{{ $direccionEmpresa }} · {{ $telefonoEmpresa }}</div>
        </div>

        <div class="document-title">
            <span>Factura</span>
        </div>

        <div class="meta-row">
            <div><span class="meta-label">Factura:</span> {{ $venta->numero_factura ?? 'N/A' }}</div>
            <div><span class="meta-label">Fecha:</span> {{ $fechaVenta }}</div>
        </div>

        <div class="meta-row">
            <div><span class="meta-label">Cajero:</span> {{ $nombreCajero }}</div>
            <div><span class="meta-label">Estado:</span> {{ $estadoVenta }}</div>
        </div>

        @if($pedidoId)
        <div class="meta-row">
            <div><span class="meta-label">Pedido:</span> #{{ $pedidoId }}</div>
        </div>
        @endif

        <div class="divider"></div>

        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cant.</th>
                    <th>Precio</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    <td>
                        <div class="item-name">{{ $item['nombre'] }}</div>
                        <div class="item-detail">{{ $item['presentacion'] }}</div>
                        @if($item['descuento'] > 0)
                        <div class="descuento-col">Desc: -{{ \App\Support\DisplayNumber::money($item['descuento']) }}</div>
                        @endif
                    </td>
                    <td>{{ \App\Support\DisplayNumber::quantity($item['cantidad']) }}</td>
                    <td>{{ \App\Support\DisplayNumber::money($item['precio_unitario']) }}</td>
                    <td>{{ \App\Support\DisplayNumber::money($item['subtotal']) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center;">Sin productos</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="divider"></div>

        <div class="totals-section">
            <div class="total-row grand">
                <span class="total-label">TOTAL</span>
                <span class="total-value">{{ \App\Support\DisplayNumber::money($totalVenta) }}</span>
            </div>
        </div>

        @if($pagos->count() > 0)
        <div class="pagos-section">
            <div class="pagos-title">Pagos</div>
            @foreach($pagos as $pago)
            <div class="pago-row">
                <span class="pago-metodo">{{ $pago['metodo'] }}</span>
                <span>{{ \App\Support\DisplayNumber::money($pago['monto']) }}</span>
            </div>
            @endforeach
        </div>
        @endif

        <div class="footer">
            <div class="footer-text">Gracias por su preferencia</div>
            <div class="footer-numbers">{{ $telefonoEmpresa }}</div>
        </div>
    </div>
</body>
</html>