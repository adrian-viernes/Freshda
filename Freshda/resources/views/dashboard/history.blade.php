<!-- ============ SCAN HISTORY ============ -->
          <section id="history-section" class="page-section hidden">
            <div class="page-header">
              <h1 data-i18n="page.history.title">Scan History</h1>
              <p class="text-muted" data-i18n="page.history.sub">
                Your previous fish scans and freshness results
              </p>
              <div class="page-header-actions" id="historyFilters">
                <input
                  type="search"
                  class="history-search"
                  id="historySearch"
                  placeholder="Search scans..."
                  aria-label="Search scans"
                />
                <span class="filter-chip-outline active" data-filter="all"
                  ><i class="ri-filter-3-line"></i> All</span
                >
                <span class="filter-chip-outline" data-filter="fresh"
                  >Fresh</span
                >
                <span class="filter-chip-outline" data-filter="moderate"
                  >Moderate</span
                >
                <span class="filter-chip-outline" data-filter="notfresh"
                  >Not Fresh</span
                >
                <select class="form-input history-sort" id="historySort" aria-label="Sort scans">
                  <option value="newest" data-i18n="page.history.sortNewest">Newest First</option>
                  <option value="oldest" data-i18n="page.history.sortOldest">Oldest First</option>
                  <option value="fresh-desc" data-i18n="page.history.sortFreshDesc">Freshness: High To Low</option>
                  <option value="fresh-asc" data-i18n="page.history.sortFreshAsc">Freshness: Low To High</option>
                </select>
              </div>
            </div>

            <!-- ============ EXPORT SCAN DATA (vendor-only) ============ -->
            @if ($user->isVendor())
            <div class="export-card" id="exportCard">
              <div class="export-card-header">
                <div class="export-card-icon">
                  <i class="ri-download-2-line"></i>
                </div>
                <div>
                  <h2 data-i18n="page.history.exportTitle">Export Scan Data</h2>
                  <p class="text-muted" data-i18n="page.history.exportSub">
                    Download your scan history reports
                  </p>
                </div>
              </div>

              <div class="export-card-body">
                <div class="export-buttons">

                  <!-- CSV BUTTON -->
                  <button
                    class="export-btn csv"
                    id="exportCsvBtn"
                    type="button"
                    data-url="{{ route('history.export.csv') }}"
                  >
                    <div class="export-btn-icon">
                      <i class="ri-file-excel-2-line"></i>
                    </div>
                    <div class="export-btn-label">
                      <strong>Export CSV</strong>
                      <span>For Excel &amp; spreadsheets</span>
                    </div>
                  </button>

                  <!-- PDF BUTTON -->
                  <button
                    class="export-btn pdf"
                    id="exportPdfBtn"
                    type="button"
                    data-url="{{ route('history.export.data') }}"
                  >
                    <div class="export-btn-icon">
                      <i class="ri-file-pdf-2-line"></i>
                    </div>
                    <div class="export-btn-label">
                      <strong>Export PDF</strong>
                      <span>Formatted Printable Report</span>
                    </div>
                  </button>

                </div>

                <div class="export-info">
                  <i class="ri-information-line"></i>
                  <span>
                    Your exported file includes all scan records — species,
                    freshness results, confidence scores, and timestamps.
                  </span>
                </div>
              </div>
            </div>
            @endif

            <div class="table-card">
              <table class="data-table" id="historyTable">
                <thead>
                  <tr>
                    <th>Fish</th>
                    <th>Species</th>
                    <th>Freshness</th>
                    <th>Confidence</th>
                    <th>Date</th>
                    <th>Type</th>
                  </tr>
                </thead>
                <tbody id="historyBody">
                  @forelse ($history as $item)
                    <tr
                      data-freshness="{{ $item['freshness'] }}"
                      data-search="{{ strtolower($item['name'] . ' ' . $item['species_label'] . ' ' . $item['label']) }}"
                      data-name="{{ $item['name'] }}"
                      data-latin="{{ $item['species_label'] }}"
                      data-species="{{ $item['species_label'] }}"
                      data-freshness-label="{{ $item['label'] }}"
                      data-percentage="{{ $item['percentage'] }}"
                      data-confidence="{{ number_format((float) $item['confidence'], 2) }}"
                      data-confidence-band="{{ ucfirst($item['confidence_band']) }}"
                      data-type="{{ ucfirst($item['type']) }}"
                      data-source="{{ $item['source'] }}"
                      data-image="{{ $item['image'] ?? '' }}"
                      data-time="{{ $item['time']->format('M j, Y · H:i') }}"
                      data-ts="{{ $item['time']->timestamp }}"
                    >
                      <td>
                        <div class="td-with-icon">
                          <i class="{{ $item['type'] === 'batch' ? 'ri-stack-line' : 'ri-image-line' }}"></i><span>{{ $item['name'] }}</span>
                        </div>
                      </td>
                      <td>
                        <span class="mobile-label">Species</span>{{ $item['species_label'] }}
                      </td>
                      <td>
                        <span class="mobile-label">Freshness</span
                        ><span class="badge {{ $item['freshness'] }}">{{ $item['label'] }} · {{ $item['percentage'] }}%</span>
                      </td>
                      <td>
                        <span class="mobile-label">Confidence</span
                        >{{ number_format((float) $item['confidence'], 2) }} · {{ ucfirst($item['confidence_band']) }}
                      </td>
                      <td>
                        <span class="mobile-label">Date</span>{{ $item['time']->format('Y-m-d H:i') }}
                      </td>
                      <td><span class="mobile-label">Type</span>{{ ucfirst($item['type']) }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="6">
                        <p class="text-muted" style="text-align:center; padding:1.5rem 0">
                          No scans yet. Head to Scan Fish to get started!
                        </p>
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </section>

@push('scripts')
    <script src="{{ asset('vendor/js/jspdf.umd.min.js') }}?v={{ filemtime(public_path('vendor/js/jspdf.umd.min.js')) }}"></script>
    <script src="{{ asset('vendor/js/jspdf.plugin.autotable.min.js') }}?v={{ filemtime(public_path('vendor/js/jspdf.plugin.autotable.min.js')) }}"></script>
@endpush