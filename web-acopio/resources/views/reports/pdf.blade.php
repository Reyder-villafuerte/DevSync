<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #12325B
        }

        h1 {
            color: #191A1F
        }

        table {
            border-collapse: collapse;
            width: 100%
        }

        th {
            background: #F4F5F8
        }

        td,
        th {
            border: 1px solid #ddd;
            padding: 7px;
            text-align: left
        }

        .brand {
            color: #FF5A36
        }
    </style>
</head>

<body>
    <h1><span class="brand">MilkFlow</span> · {{ $definition['title'] }}</h1>
    <p>Huari · {{ $from->format('d/m/Y') }} a {{ $to->format('d/m/Y') }} · Emitido {{ now()->format('d/m/Y H:i') }}</p>@foreach($totals as $label=>$value)<p><b>{{ $label }}:</b> {{ number_format($value,2) }}</p>@endforeach<table>
        <thead>
            <tr>@foreach($definition['columns'] as $label)<th>{{ $label }}</th>@endforeach</tr>
        </thead>
        <tbody>@foreach($rows as $row)<tr>@foreach($definition['columns'] as $key=>$label)<td>{{ app(App\Services\DisplayValue::class)->get($row,$key) }}</td>@endforeach</tr>@endforeach</tbody>
    </table>
</body>

</html>
