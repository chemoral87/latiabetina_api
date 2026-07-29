<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $filename }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #1976D2; color: white; padding: 6px 8px; text-align: left; font-size: 10px; }
        td { padding: 4px 8px; border: 1px solid #ddd; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        h2 { color: #333; margin-bottom: 5px; }
        .date { color: #666; font-size: 9px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <h2>{{ ucfirst(str_replace('-', ' ', $filename)) }}</h2>
    <div class="date">Generado: {{ now()->format('d/m/Y H:i') }}</div>

    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
