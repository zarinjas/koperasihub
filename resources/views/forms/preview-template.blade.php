<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pratonton Borang - {{ $form->title }}</title>
    @include('forms._styles')
</head>
<body>
    <div class="page">
        <div class="toolbar">
            <a class="button" href="{{ $backUrl }}">Kembali ke borang</a>
            <button class="button" type="button" onclick="window.print()">Cetak / Simpan sebagai PDF</button>
        </div>

        @include('forms._document-header', [
            'eyebrow' => 'Pratonton Borang',
            'subtitle' => $form->description,
            'metaExtraLabel' => null,
            'metaExtraValue' => null,
        ])

        @foreach ($sections as $section)
            <section class="print-section {{ $section->page_break_before ? 'print-section--page-break' : '' }}">
                <div class="print-section__heading">
                    <h2 class="print-section__title">{{ $section->title }}</h2>
                </div>
                @if ($section->description)
                    <p class="print-section__description">{{ $section->description }}</p>
                @endif

                <div class="print-field-grid">
                    @foreach ($section->fields as $field)
                        @continue(! $field->showsPrint())

                        <div class="print-field">
                            <p class="print-field__label">{{ $field->label }}</p>

                            @if ($field->type->value === 'note')
                                <div class="print-note">{{ $field->help_text }}</div>
                            @elseif ($field->type->value === 'instruction_text')
                                <div class="print-note print-note--instruction">{{ $field->help_text }}</div>
                            @elseif ($field->type->value === 'office_use_box')
                                <div class="print-help">{{ $field->help_text ?: 'Ruangan ini disediakan untuk kegunaan pejabat.' }}</div>
                                <div class="office-use-box"></div>
                            @elseif (in_array($field->type->value, ['select', 'radio', 'checkbox'], true))
                                <div class="print-options">
                                    @foreach (($field->options_json ?? []) as $option)
                                        <span class="print-option">{{ $option }}</span>
                                    @endforeach
                                </div>
                                <div class="answer-line"></div>
                            @elseif ($field->type->value === 'agreement_checkbox')
                                @if ($field->help_text)
                                    <div class="print-help">{{ $field->help_text }}</div>
                                @endif
                                <div class="answer-line"></div>
                            @elseif (in_array($field->type->value, ['address_my', 'member_address'], true))
                                <div class="print-address">
                                    <div class="answer-line"></div>
                                    <div class="answer-line"></div>
                                    <div class="print-address__row">
                                        <div class="answer-line answer-line--short"></div>
                                        <div class="answer-line answer-line--short"></div>
                                    </div>
                                    <div class="answer-line answer-line--medium"></div>
                                </div>
                            @elseif ($field->type->value === 'signature')
                                <div class="signature-box">
                                    <span class="signature-box__label">Ruang tandatangan</span>
                                </div>
                            @else
                                <div class="answer-line"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</body>
</html>