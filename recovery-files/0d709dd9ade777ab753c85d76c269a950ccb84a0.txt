<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <title>Ringkasan Permohonan Pembiayaan</title>
    <style>
        body { color: #1e293b; font-family: Arial, sans-serif; font-size: 12px; line-height: 1.5; margin: 24px; }
        h1 { font-size: 16px; margin: 0 0 4px; text-align: center; text-transform: uppercase; }
        .subtitle { font-size: 13px; margin: 0 0 20px; text-align: center; }
        h2 { border-bottom: 1px solid #cbd5e1; font-size: 13px; margin: 20px 0 8px; padding-bottom: 3px; text-transform: uppercase; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 4px; }
        td { padding: 3px 6px; vertical-align: top; }
        td:first-child { color: #475569; width: 160px; font-weight: 600; }
        .docs-table td:first-child { width: auto; }
        .docs-table th, .docs-table td { border: 1px solid #cbd5e1; padding: 4px 6px; text-align: left; }
        .docs-table th { background: #f1f5f9; font-size: 11px; }
        .badge { display: inline-block; padding: 1px 8px; border-radius: 10px; font-size: 10px; font-weight: 600; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-amber { background: #fef3c7; color: #92400e; }
        .badge-slate { background: #f1f5f9; color: #475569; }
        .footer { margin-top: 24px; padding-top: 8px; border-top: 1px solid #cbd5e1; text-align: center; font-size: 10px; color: #64748b; }
    </style>
</head>
<body>
    <h1>{{ $cooperative['name'] ?? 'Koperasi' }}</h1>
    <p class="subtitle">Ringkasan Permohonan Pembiayaan</p>

    <table>
        <tr><td>No. Rujukan</td><td><strong>{{ $application->reference_no }}</strong></td></tr>
        <tr><td>Status</td><td>{{ $application->status->label() }}</td></tr>
        <tr><td>Tarikh Dihantar</td><td>{{ $application->submitted_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
    </table>

    <h2>Ahli</h2>
    <table>
        <tr><td>Nama</td><td>{{ $memberName }}</td></tr>
        <tr><td>No. Ahli</td><td>{{ $application->member?->member_no ?? '-' }}</td></tr>
        <tr><td>No. Kad Pengenalan</td><td>{{ $application->member?->identity_no ?? '-' }}</td></tr>
        <tr><td>Telefon</td><td>{{ $application->member?->phone ?? '-' }}</td></tr>
        <tr><td>E-mel</td><td>{{ $application->member?->email ?? '-' }}</td></tr>
    </table>

    <h2>Permohonan</h2>
    <table>
        <tr><td>Produk</td><td>{{ $application->product?->name ?? '-' }}</td></tr>
        <tr><td>Kategori</td><td>{{ $application->category?->name ?? '-' }}</td></tr>
        <tr><td>Jumlah Dipohon</td><td>RM {{ number_format((float) $application->amount_requested, 2) }}</td></tr>
        <tr><td>Tempoh</td><td>{{ $application->tenure_months }} bulan</td></tr>
        <tr><td>Tujuan</td><td>{{ $application->purpose ?? '-' }}</td></tr>
        @if ($application->monthly_income)
        <tr><td>Pendapatan Bulanan</td><td>RM {{ number_format((float) $application->monthly_income, 2) }}</td></tr>
        @endif
        @if ($application->monthly_commitment)
        <tr><td>Komitmen Bulanan</td><td>RM {{ number_format((float) $application->monthly_commitment, 2) }}</td></tr>
        @endif
    </table>

    @if ($guarantors->isNotEmpty())
    <h2>Penjamin</h2>
    <table>
        @foreach ($guarantors as $g)
        <tr><td>Penjamin {{ $loop->iteration }}</td><td>{{ $g['name'] }} ({{ $g['status_label'] }})</td></tr>
        @endforeach
    </table>
    @endif

    <h2>Dokumen Dalam Pakej Ini</h2>
    <p style="font-size: 11px; color: #64748b;">Pakej ini mengandungi dokumen berikut untuk cetakan dan rujukan lengkap.</p>

    <div class="footer">
        <p>Dijana oleh sistem KoperasiHub pada {{ now()->format('d/m/Y H:i') }}</p>
        <p>Pakej ini adalah dokumen automatik dan tidak memerlukan tandatangan.</p>
    </div>
</body>
</html>
