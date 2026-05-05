<section class="document-header">
    <div class="document-header__brand">
        <div class="document-header__logo">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $cooperative?->name }}">
            @else
                <span>Logo</span>
            @endif
        </div>

        <div class="document-header__copy">
            <div class="document-header__eyebrow">{{ $eyebrow }}</div>
            <h1 class="document-header__title">{{ $form->document_title ?: $form->title }}</h1>
            <p class="document-header__subtitle">{{ $cooperative?->name ?: 'KoperasiHub Demo' }}</p>
            @if (!empty($subtitle))
                <p class="document-header__subtitle">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    <div class="document-header__meta">
        <div class="document-header__meta-item">
            <div class="document-header__meta-label">Kod Dokumen</div>
            <div class="document-header__meta-value">{{ $form->document_code ?: '-' }}</div>
        </div>
        <div class="document-header__meta-item">
            <div class="document-header__meta-label">No. Semakan</div>
            <div class="document-header__meta-value">{{ $form->revision_no ?: '-' }}</div>
        </div>
        <div class="document-header__meta-item">
            <div class="document-header__meta-label">Tarikh Kuat Kuasa</div>
            <div class="document-header__meta-value">{{ $form->effective_date?->format('d/m/Y') ?: '-' }}</div>
        </div>
        @if (!empty($metaExtraLabel))
            <div class="document-header__meta-item">
                <div class="document-header__meta-label">{{ $metaExtraLabel }}</div>
                <div class="document-header__meta-value">{{ $metaExtraValue ?: '-' }}</div>
            </div>
        @endif
    </div>
</section>
