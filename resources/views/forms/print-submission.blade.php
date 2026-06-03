<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Submission {{ $submission->reference_no }}</title>
    @include('forms._styles')
</head>
<body>
    <div class="page">
        <div class="toolbar">
            <a class="button" href="{{ $index_url }}">Kembali ke senarai</a>
            <button class="button" type="button" onclick="window.print()">Cetak / Simpan sebagai PDF</button>
        </div>

        @include('forms._document-header', [
            'eyebrow' => 'Cetakan Submission',
            'subtitle' => 'Rujukan: '.$submission->reference_no,
            'metaExtraLabel' => 'Tarikh Submission',
            'metaExtraValue' => $submission->submitted_at?->format('d/m/Y H:i'),
        ])

        @foreach ($sections as $section)
            <section class="print-section {{ $section['page_break_before'] ? 'print-section--page-break' : '' }}">
                <div class="print-section__heading">
                    <h2 class="print-section__title">{{ $section['title'] }}</h2>
                </div>
                @if ($section['description'])
                    <p class="print-section__description">{{ $section['description'] }}</p>
                @endif

                <div class="print-field-grid">
                    @foreach ($section['fields'] as $field)
                        @continue($field['display_mode'] === 'online_only')

                        <div class="print-field">
                            <p class="print-field__label">{{ $field['label'] }}</p>
                            @if ($field['type'] === 'note')
                                <div class="print-note">{{ $field['help_text'] }}</div>
                            @elseif ($field['file'] && $field['file']['is_signature'] && $field['file']['signature_data_url'])
                                <div class="signature-box">
                                    <img src="{{ $field['file']['signature_data_url'] }}" alt="Tandatangan">
                                </div>
                            @elseif ($field['file'])
                                <div class="print-value">{{ $field['file']['name'] }}</div>
                            @elseif ($field['type'] === 'agreement_checkbox')
                                @if ($field['agreement_text'])
                                    <div class="print-help">{{ $field['agreement_text'] }}</div>
                                @endif
                                <div class="print-value">{{ $field['value'] ? 'Disahkan' : 'Tidak disahkan' }}</div>
                            @elseif ($field['type'] === 'checkbox')
                                <div class="print-value">{{ is_array($field['value']) ? implode(', ', $field['value']) : '-' }}</div>
                            @elseif ($field['type'] === 'yes_no')
                                <div class="print-value">{{ $field['value'] === 'yes' ? 'Ya' : ($field['value'] === 'no' ? 'Tidak' : '-') }}</div>
                            @elseif (in_array($field['type'], ['address_my', 'member_address'], true))
                                @php $addr = $field['value'] ?? []; @endphp
                                <div class="print-value">
                                    {{ $addr['line1'] ?? '-' }}
                                    @if (($addr['line2'] ?? ''))
                                        <br>{{ $addr['line2'] }}
                                    @endif
                                    <br>{{ $addr['postcode'] ?? '' }} {{ $addr['city'] ?? '' }}
                                    @if (($addr['state'] ?? ''))
                                        <br>{{ $addr['state'] }}
                                    @endif
                                </div>
                            @elseif ($field['type'] === 'instruction_text')
                                <div class="print-note print-note--instruction">{{ $field['help_text'] }}</div>
                            @elseif ($field['type'] === 'office_use_box')
                                <div class="print-help">{{ $field['help_text'] ?: 'Ruangan kegunaan pejabat.' }}</div>
                                <div class="office-use-box"></div>
                            @else
                                <div class="print-value">{{ $field['value'] ?: '-' }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</body>
</html>