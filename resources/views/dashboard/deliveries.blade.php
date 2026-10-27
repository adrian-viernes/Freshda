<!-- ============ SUPPLIER DELIVERIES ============ -->
          <section id="deliveries-section" class="page-section hidden">
            <div class="page-header">
              <h1 data-i18n="page.deliveries.title">Supplier Deliveries</h1>
              <p class="text-muted" data-i18n="page.deliveries.sub">
                Local Vendor only · Record incoming fish and stock what you accept
              </p>
              <div class="page-header-actions">
                <button class="btn btn-primary" id="addDeliveryBtn">
                  <i class="ri-add-line"></i> Record Delivery
                </button>
                <input
                  type="search"
                  class="history-search"
                  id="deliverySearch"
                  placeholder="Search deliveries..."
                  aria-label="Search deliveries"
                />
                <div class="module-filters" id="deliveryFilters">
                  <span class="filter-chip-outline active" data-filter="all"
                    ><i class="ri-filter-3-line"></i> All</span
                  >
                  <span class="filter-chip-outline" data-filter="pending">Pending</span>
                  <span class="filter-chip-outline" data-filter="accepted">Accepted</span>
                  <span class="filter-chip-outline" data-filter="rejected">Rejected</span>
                </div>
              </div>
            </div>

            <div class="stats-grid">
              @include('dashboard.partials.stat-card', [
                  'variant' => 'total',
                  'icon' => 'ri-truck-line',
                  'label' => 'Total Deliveries',
                  'value' => $deliverySummary['total'],
                  'valueAttrs' => ['id' => 'delTotal'],
                  'sub' => 'Recorded Deliveries',
              ])
              @include('dashboard.partials.stat-card', [
                  'variant' => 'moderate',
                  'icon' => 'ri-time-line',
                  'label' => 'Pending',
                  'value' => $deliverySummary['pending'],
                  'valueAttrs' => ['id' => 'delPending'],
                  'sub' => 'Waiting To Be Checked In',
              ])
              @include('dashboard.partials.stat-card', [
                  'variant' => 'fresh',
                  'icon' => 'ri-checkbox-circle-line',
                  'label' => 'Accepted',
                  'value' => $deliverySummary['accepted'],
                  'valueAttrs' => ['id' => 'delAccepted'],
                  'sub' => 'Added To Inventory',
              ])
              @include('dashboard.partials.stat-card', [
                  'variant' => 'notfresh',
                  'icon' => 'ri-close-circle-line',
                  'label' => 'Rejected',
                  'value' => $deliverySummary['rejected'],
                  'valueAttrs' => ['id' => 'delRejected'],
                  'sub' => 'Refused Deliveries',
              ])
            </div>

            <div class="table-card">
              <table class="data-table" id="deliveryTable">
                <thead>
                  <tr>
                    <th>Fish Type</th>
                    <th>Supplier</th>
                    <th>Delivery Date</th>
                    <th>Expected</th>
                    <th>Received</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="deliveryBody">
                  @forelse ($deliveries as $delivery)
                    <tr
                      data-id="{{ $delivery->id }}"
                      data-status="{{ $delivery->status }}"
                      data-supplier-id="{{ $delivery->supplier_id }}"
                      data-search="{{ strtolower(trim($delivery->fish_type . ' ' . ($delivery->supplier?->name ?? '') . ' ' . $delivery->delivery_date?->format('Y-m-d'))) }}"
                    >
                      <td>
                        <div class="td-with-icon">
                          <i class="ri-price-tag-3-line"></i><span>{{ $delivery->fish_type }}</span>
                        </div>
                      </td>
                      <td>
                        <span class="mobile-label">Supplier</span>{{ $delivery->supplier?->name ?? '—' }}
                      </td>
                      <td>
                        <span class="mobile-label">Delivery Date</span
                        ><span class="inv-date">{{ $delivery->delivery_date?->format('Y-m-d') }}</span>
                      </td>
                      <td>
                        <span class="mobile-label">Expected</span
                        ><span class="qty">{{ number_format((float) $delivery->expected_quantity, 1) }}</span> kg
                      </td>
                      <td>
                        <span class="mobile-label">Received</span>
                        @if ($delivery->actual_quantity === null)
                          <span class="text-muted">Not checked yet</span>
                        @else
                          <span class="qty">{{ number_format((float) $delivery->actual_quantity, 1) }}</span> kg
                          <span class="badge {{ $delivery->varianceClass() }}">{{ $delivery->varianceLabel() }}</span>
                        @endif
                      </td>
                      <td>
                        <span class="mobile-label">Status</span
                        ><span class="badge {{ $delivery->statusClass() }}">{{ $delivery->statusLabel() }}</span>
                      </td>
                      <td>
                        <div class="row-actions">
                          @if ($delivery->isPending())
                            <button class="btn btn-primary btn-sm accept-delivery-btn">Check In</button>
                            <button class="btn btn-danger btn-sm reject-delivery-btn">Reject</button>
                          @endif
                          <div class="row-actions-menu">
                            <button
                              type="button"
                              class="row-actions-toggle"
                              aria-haspopup="true"
                              aria-expanded="false"
                              aria-label="Actions for the {{ $delivery->fish_type }} delivery"
                              title="Actions"
                            >
                              <i class="ri-more-2-fill"></i>
                            </button>
                            <div
                              class="row-actions-list"
                              data-scope="delivery"
                              data-id="{{ $delivery->id }}"
                            >
                              <button type="button" class="row-actions-item" data-action="details">
                                <i class="ri-eye-line"></i> Details
                              </button>
                              @if ($delivery->isPending())
                                <button type="button" class="row-actions-item" data-action="edit">
                                  <i class="ri-pencil-line"></i> Edit
                                </button>
                              @endif
                              <button type="button" class="row-actions-item danger" data-action="delete">
                                <i class="ri-delete-bin-line"></i> Delete
                              </button>
                            </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                  @empty
                    <tr class="delivery-empty-row">
                      <td colspan="7">
                        <p class="text-muted" style="text-align:center; padding:1.5rem 0">
                          No deliveries recorded yet. Click "Record Delivery" to log an incoming batch!
                        </p>
                      </td>
                    </tr>
                  @endforelse
                  <tr id="deliveryFilterEmpty" class="hidden">
                    <td colspan="7">
                      <p class="text-muted" style="text-align:center; padding:1.5rem 0">
                        No deliveries match your search.
                      </p>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
