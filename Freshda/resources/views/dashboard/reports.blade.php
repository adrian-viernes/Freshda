<!-- ============ REPORTS (vendor-only) ============ -->
          <section id="reports-section" class="page-section hidden">
            <div class="page-header">
              <h1 data-i18n="page.reports.title">Reports</h1>
              <p class="text-muted" data-i18n="page.reports.sub">
                Download your business records as CSV or printable PDF
              </p>
            </div>

            <!-- One card: pick the report, set the range, export it. -->
            @include('dashboard.partials.report-card', [
                  'icon' => 'ri-file-list-3-line',
                  'title' => 'Export Report',
                  'titleI18n' => 'page.reports.exportTitle',
                  'sub' => 'Pick a report, set the date range, then export it as CSV or printable PDF',
                  'subI18n' => 'page.reports.exportSub',
                  'types' => [
                      ['value' => 'assessment', 'label' => 'Fish Assessment Report', 'i18n' => 'page.reports.assessmentTitle'],
                      ['value' => 'inventory', 'label' => 'Inventory Report', 'i18n' => 'page.reports.inventoryTitle'],
                      ['value' => 'lowstock', 'label' => 'Low-Stock Report', 'i18n' => 'page.reports.lowstockTitle'],
                      ['value' => 'deliveries', 'label' => 'Supplier Delivery Report', 'i18n' => 'page.reports.deliveriesTitle'],
                  ],
              ])

            <div class="export-info">
              <i class="ri-information-line"></i>
              <span data-i18n="page.reports.footnote">
                Reports only include your own records. CSV opens in any
                spreadsheet; PDF is generated in your browser.
              </span>
            </div>
          </section>

          <script src="{{ asset('vendor/js/jspdf.umd.min.js') }}?v={{ filemtime(public_path('vendor/js/jspdf.umd.min.js')) }}"></script>
