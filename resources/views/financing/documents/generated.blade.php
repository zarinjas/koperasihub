<!doctype html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <title>{{ $document->document_name }}</title>
    <style>
        body { color: #0f172a; font-family: Arial, sans-serif; font-size: 13px; line-height: 1.45; margin: 32px; }
        h1 { font-size: 18px; margin: 0 0 4px; text-align: center; text-transform: uppercase; }
        h2 { border-bottom: 1px solid #cbd5e1; font-size: 14px; margin: 24px 0 8px; padding-bottom: 4px; text-transform: uppercase; }
        table { border-collapse: collapse; width: 100%; }
        td, th { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f8fafc; }
        .meta td:first-child { color: #475569; width: 180px; }
        .signature { display: grid; gap: 48px; grid-template-columns: 1fr 1fr; margin-top: 64px; }
        .line { border-top: 1px solid #475569; padding-top: 6px; }
    </style>
</head>
<body>
    <h1>{{ $map['cooperative']['name'] ?? 'Koperasi' }}</h1>
    <p style="margin: 0; text-align: center;">{{ $document->document_name }}</p>
    <p style="margin: 4px 0 24px; text-align: center;">No. Rujukan: <strong>{{ $map['application']['reference_no'] ?? '-' }}</strong></p>

    <h2>Maklumat Ahli</h2>
    <table class="meta">
        <tr><td>Nama</td><td>{{ $map['member']['name'] ?? '-' }}</td></tr>
        <tr><td>No. Ahli</td><td>{{ $map['member']['member_no'] ?? '-' }}</td></tr>
        <tr><td>No. Kad Pengenalan</td><td>{{ $map['member']['identity_no'] ?? '-' }}</td></tr>
        <tr><td>Telefon</td><td>{{ $map['member']['phone'] ?? '-' }}</td></tr>
        <tr><td>E-mel</td><td>{{ $map['member']['email'] ?? '-' }}</td></tr>
    </table>

    <h2>Maklumat Permohonan</h2>
    <table class="meta">
        <tr><td>Produk</td><td>{{ $map['product']['name'] ?? '-' }}</td></tr>
        <tr><td>Kategori</td><td>{{ $map['product']['category'] ?? '-' }}</td></tr>
        <tr><td>Jumlah Dipohon</td><td>RM {{ $map['application']['amount_requested'] ?? '-' }}</td></tr>
        <tr><td>Tempoh</td><td>{{ $map['application']['tenure_months'] ?? '-' }} bulan</td></tr>
        <tr><td>Tujuan</td><td>{{ $map['application']['purpose'] ?? '-' }}</td></tr>
    </table>

    @if (! empty($map['answers']))
        <h2>Maklumat Tambahan</h2>
        @foreach ($map['answers'] as $key => $value)
            @if (is_array($value) && array_is_list($value))
                <p><strong>{{ str_replace('_', ' ', $key) }}</strong></p>
                <table>
                    @foreach ($value as $row)
                        @if ($loop->first && is_array($row))
                            <tr>
                                @foreach (array_keys($row) as $column)
                                    <th>{{ str_replace('_', ' ', $column) }}</th>
                                @endforeach
                            </tr>
                        @endif
                        <tr>
                            @foreach ((array) $row as $cell)
                                <td>{{ is_array($cell) ? json_encode($cell, JSON_UNESCAPED_UNICODE) : $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </table>
            @else
                <table class="meta">
                    <tr>
                        <td>{{ str_replace('_', ' ', $key) }}</td>
                        <td>{{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value }}</td>
                    </tr>
                </table>
            @endif
        @endforeach
    @endif

    @if (! empty($map['guarantors']))
        @foreach ($map['guarantors'] as $index => $guarantor)
            <h2>Penjamin {{ $index + 1 }}</h2>
            <table class="meta">
                <tr><td>Nama</td><td>{{ $guarantor['name'] ?? '-' }}</td></tr>
                <tr><td>No. Anggota</td><td>{{ $guarantor['member_no'] ?? '-' }}</td></tr>
                <tr><td>No. Kad Pengenalan</td><td>{{ $guarantor['identity_no'] ?? '-' }}</td></tr>
                <tr><td>Telefon</td><td>{{ $guarantor['phone'] ?? '-' }}</td></tr>
                <tr><td>Pekerjaan</td><td>{{ $guarantor['position'] ?? '-' }}</td></tr>
                <tr><td>Majikan</td><td>{{ $guarantor['employer'] ?? '-' }}</td></tr>
                <tr><td>Jabatan</td><td>{{ $guarantor['department'] ?? '-' }}</td></tr>
                <tr><td>Alamat</td><td>{{ $guarantor['address'] ?? '-' }}</td></tr>
            </table>
        @endforeach
    @endif

    <div class="signature">
        <div>
            <p class="line">Tandatangan Pemohon</p>
            @if (! empty($map['member']['signature_data_url']))
                <p><img src="{{ $map['member']['signature_data_url'] }}" alt="Tandatangan pemohon" style="max-height: 60px;" /></p>
            @else
                <p>_________________________</p>
            @endif
            <p>Nama: {{ $map['member']['name'] ?? '' }}</p>
            <p>Tarikh: {{ $map['application']['submitted_at'] ?? '____________________' }}</p>
        </div>
        @foreach ($map['guarantors'] ?? [] as $guarantor)
            <div>
                <p class="line">Tandatangan Penjamin</p>
                @if (! empty($guarantor['signature_data_url']))
                    <p><img src="{{ $guarantor['signature_data_url'] }}" alt="Tandatangan penjamin" style="max-height: 60px;" /></p>
                @else
                    <p>_________________________</p>
                @endif
                <p>Nama: {{ $guarantor['name'] ?? '' }}</p>
                <p>Tarikh: {{ $guarantor['consented_at'] ?? $guarantor['responded_at'] ?? '____________________' }}</p>
            </div>
        @endforeach
        <div>
            <p class="line">Untuk Kegunaan Pejabat</p>
            <p>Nama: ____________________</p>
            <p>Tarikh: ____________________</p>
        </div>
    </div>
</body>
</html>
