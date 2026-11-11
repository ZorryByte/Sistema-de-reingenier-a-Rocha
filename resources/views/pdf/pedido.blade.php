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

        td:nth-child(2),
        td:nth-child(3),
        td:last-child {
            text-align: right;
        }

        td:first-child {
            max-width: 90px;
        }

        .item-name {
            font-weight: 600;
        }

        .item-detail {
            font-size: 8px;
            color: #555;
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
            <span>Recibo de Pedido</span>
        </div>

        <div class="meta-row">
            <div><span class="meta-label">Pedido:</span> #{{ str_pad($pedido->id, 4, '0', STR_PAD_LEFT) }}</div>
            <div><span class="meta-label">Fecha:</span> {{ $fechaPedido }}</div>
        </div>

        <div class="meta-row">
            <div><span class="meta-label">Atendido:</span> {{ $nombreAtendido }}</div>
        </div>

        @if($fechaEntrega)
        <div class="meta-row">
            <div><span class="meta-label">Entrega:</span> {{ $fechaEntrega }}</div>
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
                    </td>
                    <td>{{ \App\Support\DisplayNumber::quantity((float) $item['cantidad']) }}</td>
                    <td>{{ \App\Support\DisplayNumber::money($item['precio_unitario']) }}</td>
                    <td>{{ \App\Support\DisplayNumber::money($item['subtotal_cents'] / 100) }}</td>
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
                <span class="total-value">{{ \App\Support\DisplayNumber::money($totalPedido) }}</span>
            </div>
        </div>

        @if($ventaId)
        <div class="meta-row" style="margin-top:6px;">
            <div><span class="meta-label">Venta:</span> #{{ $ventaId }}</div>
        </div>
        @endif

        @if($notas)
        <div class="divider" style="margin-top:8px;"></div>
        <div style="font-size:8px;"><span class="meta-label">Notas:</span> {{ $notas }}</div>
        @endif

        <div class="footer">
            <div class="footer-text">Gracias por su preferencia</div>
            <div class="footer-numbers">{{ $telefonoEmpresa }}</div>
        </div>
    </div>
</body>
</html>