<style>
    :root {
        color-scheme: light;
    }

    * {
        box-sizing: border-box;
    }

    @page {
        size: A4;
        margin: 14mm 12mm 16mm;
    }

    body {
        margin: 0;
        font-family: Inter, system-ui, sans-serif;
        background: #f1f5f9;
        color: #0f172a;
    }

    .page {
        max-width: 900px;
        margin: 0 auto;
        padding: 24px 18px 48px;
    }

    .toolbar {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }

    .button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #0f172a;
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
    }

    .document-header {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        padding: 18px 18px 16px;
    }

    .document-header__brand {
        display: flex;
        gap: 16px;
        justify-content: space-between;
        align-items: flex-start;
    }

    .document-header__logo {
        width: 72px;
        min-width: 72px;
        height: 72px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        font-size: 12px;
        color: #64748b;
    }

    .document-header__logo img {
        width: 60px;
        height: 60px;
        object-fit: contain;
    }

    .document-header__copy {
        flex: 1;
    }

    .document-header__eyebrow {
        font-size: 11px;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #475569;
        font-weight: 700;
    }

    .document-header__title {
        font-size: 24px;
        line-height: 1.25;
        margin: 6px 0 8px;
        font-weight: 700;
    }

    .document-header__subtitle {
        margin: 2px 0 0;
        color: #334155;
        font-size: 14px;
        line-height: 1.6;
    }

    .document-header__meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-top: 16px;
        padding-top: 14px;
        border-top: 1px solid #cbd5e1;
    }

    .document-header__meta-item {
        border: 1px solid #e2e8f0;
        padding: 10px 12px;
        background: #f8fafc;
    }

    .document-header__meta-label {
        font-size: 10px;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 700;
    }

    .document-header__meta-value {
        margin-top: 4px;
        font-size: 14px;
        font-weight: 600;
    }

    .print-section {
        margin-top: 16px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        padding: 16px;
    }

    .print-section--page-break {
        page-break-before: always;
        break-before: page;
    }

    .print-section__heading {
        padding-bottom: 10px;
        border-bottom: 1px solid #cbd5e1;
    }

    .print-section__title {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
    }

    .print-section__description {
        margin: 10px 0 0;
        font-size: 13px;
        line-height: 1.6;
        color: #475569;
    }

    .print-field-grid {
        display: grid;
        gap: 12px;
        margin-top: 14px;
    }

    .print-field {
        border: 1px solid #e2e8f0;
        background: #ffffff;
        padding: 12px 14px;
        page-break-inside: avoid;
        break-inside: avoid;
    }

    .print-field__label {
        font-size: 13px;
        font-weight: 700;
        margin: 0 0 8px;
    }

    .print-value,
    .print-help,
    .print-note {
        font-size: 13px;
        line-height: 1.7;
        color: #334155;
    }

    .print-note {
        white-space: pre-line;
    }

    .print-note--instruction {
        border-left: 3px solid #94a3b8;
        padding-left: 10px;
    }

    .answer-line {
        height: 42px;
        margin-top: 10px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
    }

    .office-use-box {
        min-height: 120px;
        margin-top: 10px;
        border: 1px dashed #94a3b8;
        background:
            linear-gradient(to bottom, transparent 0, transparent calc(100% - 1px), #e2e8f0 calc(100% - 1px), #e2e8f0 100%);
        background-size: 100% 28px;
    }

    .signature-box {
        width: 240px;
        min-height: 96px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .signature-box__label {
        font-size: 12px;
        color: #64748b;
    }

    .print-address {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .print-address__row {
        display: flex;
        gap: 8px;
    }

    .answer-line--short {
        flex: 1;
    }

    .answer-line--medium {
        max-width: 55%;
    }

    .signature-box img {
        width: 100%;
        height: auto;
    }

    .print-options {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .print-option {
        padding: 5px 10px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        font-size: 12px;
    }

    @media print {
        body {
            background: #ffffff;
        }

        .toolbar {
            display: none;
        }

        .page {
            max-width: none;
            padding: 0;
        }

        .document-header,
        .print-section,
        .print-field,
        .document-header__meta-item {
            box-shadow: none;
        }
    }

    @media (max-width: 720px) {
        .document-header__brand,
        .document-header__meta {
            grid-template-columns: 1fr;
            flex-direction: column;
        }
    }
</style>