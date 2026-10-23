{{--
  The single export card on the Reports page: header with icon and
  description, the report picker, an optional date range, optional remarks and
  the two export buttons.

    icon       Remix icon class for the card header
    title      heading text
    titleI18n  translation key for the heading
    sub        description text
    subI18n    translation key for the description
    types      rows of ['value' => 'assessment', 'label' => 'Fish Assessment
               Report', 'i18n' => 'page.reports.assessmentTitle'] for the
               picker; the first row is what the page starts on

  The date inputs and the remarks box have fixed ids (reportRange,
  reportFrom, reportTo, reportRangeNote, reportRemarks) because reports.js
  reads them straight off the page.
--}}
<div class="export-card report-card">
  <div class="export-card-header">
    <div class="export-card-icon">
      <i class="{{ $icon }}"></i>
    </div>
    <div>
      <h2 data-i18n="{{ $titleI18n }}">{{ $title }}</h2>
      <p class="text-muted" data-i18n="{{ $subI18n }}">
        {{ $sub }}
      </p>
    </div>
  </div>

  <div class="export-card-body">
    <div class="report-options">
      <div class="report-type">
        <label class="form-label" for="reportType" data-i18n="page.reports.typeLabel">Report Type</label>
        <select class="form-input" id="reportType">
@foreach ($types as $reportType)
          <option value="{{ $reportType['value'] }}" data-i18n="{{ $reportType['i18n'] }}">{{ $reportType['label'] }}</option>
@endforeach
        </select>
      </div>

      <div class="report-range" id="reportRange">
        <label class="form-label" for="reportFrom" data-i18n="page.reports.from">From</label>
        <input type="date" class="form-input" id="reportFrom" value="{{ now()->subDays(7)->toDateString() }}" />
        <label class="form-label" for="reportTo" data-i18n="page.reports.to">To</label>
        <input type="date" class="form-input" id="reportTo" value="{{ now()->toDateString() }}" />
      </div>
    </div>

    <p class="text-muted report-range-note" id="reportRangeNote" hidden data-i18n="page.reports.rangeNote">
      Point-in-time report — the date range does not apply.
    </p>

    <div class="report-remarks">
      <label class="form-label" for="reportRemarks" data-i18n="page.reports.remarksLabel">Report Remarks (Optional)</label>
      <textarea
        class="form-input"
        id="reportRemarks"
        rows="2"
        maxlength="500"
        data-i18n-placeholder="page.reports.remarksPlaceholder"
        placeholder="Notes printed in the exported PDF"
      ></textarea>
    </div>

    <div class="export-buttons">
      <button
        class="export-btn csv"
        type="button"
        data-csv-url="{{ route('reports.export.csv') }}"
        data-json-url="{{ route('reports.data') }}"
      >
        <div class="export-btn-icon">
          <i class="ri-file-excel-2-line"></i>
        </div>
        <div class="export-btn-label">
          <strong data-i18n="page.reports.csv">Export CSV</strong>
          <span data-i18n="page.reports.csvHint">For Excel &amp; spreadsheets</span>
        </div>
      </button>
      <button
        class="export-btn pdf"
        type="button"
        data-json-url="{{ route('reports.data') }}"
      >
        <div class="export-btn-icon">
          <i class="ri-file-pdf-2-line"></i>
        </div>
        <div class="export-btn-label">
          <strong data-i18n="page.reports.pdf">Export PDF</strong>
          <span data-i18n="page.reports.pdfHint">Formatted printable report</span>
        </div>
      </button>
    </div>
  </div>
</div>
