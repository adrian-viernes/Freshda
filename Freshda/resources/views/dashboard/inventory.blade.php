<!-- ============ INVENTORY ============ -->
          <section id="inventory-section" class="page-section hidden">
            <div class="page-header">
              <h1 data-i18n="page.inventory.title">Inventory Management</h1>
              <p class="text-muted" data-i18n="page.inventory.sub">
                Local Vendor only · Manage your fish stock
              </p>
              <div class="page-header-actions">
                <button class="btn btn-secondary" id="movementHistoryBtn">
                  <i class="ri-history-line"></i> Movement History
                </button>
                <button class="btn btn-primary" id="addFishBtn">
                  <i class="ri-add-line"></i> Add Fish
                </button>
              </div>
            </div>

            <div class="stats-grid">
              @include('dashboard.partials.stat-card', [
                  'variant' => 'total',
                  'icon' => 'ri-database-2-line',
                  'label' => 'Total Items',
                  'value' => $inventorySummary['total'],
                  'valueAttrs' => ['id' => 'invTotalItems'],
                  'sub' => '<span id="invTotalWeight">'.number_format($inventorySummary['total_kg'], 1).'</span> kg total weight',
              ])
              @include('dashboard.partials.stat-card', [
                  'variant' => 'fresh',
                  'icon' => 'ri-apps-2-line',
                  'label' => 'Species',
                  'value' => $inventorySummary['species'],
                  'valueAttrs' => ['id' => 'invSpeciesCount'],
                  'sub' => 'Different Kinds',
              ])
              @include('dashboard.partials.stat-card', [
                  'variant' => 'moderate',
                  'icon' => 'ri-alarm-warning-line',
                  'label' => 'Low Stock',
                  'value' => $inventorySummary['low_stock'],
                  'valueAttrs' => ['id' => 'invLowStockCount'],
                  'sub' => 'Items Below 3 Kg',
              ])
              @include('dashboard.partials.stat-card', [
                  'variant' => 'notfresh',
                  'icon' => 'ri-time-line',
                  'label' => 'Last Updated',
                  'value' => $inventorySummary['updated_at'] ? $inventorySummary['updated_at']->diffForHumans() : '—',
                  'valueAttrs' => ['style' => 'font-size: 1.15rem'],
                  'sub' => e($inventorySummary['updated_at'] ? $inventorySummary['updated_at']->format('h:i A') : 'no items yet'),
              ])
            </div>

            <div class="table-card">
              <table class="data-table" id="inventoryTable">
                <thead>
                  <tr>
                    <th>Fish</th>
                    <th>Species</th>
                    <th>Quantity</th>
                    <th>Freshness</th>
                    <th>Last Scan</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="inventoryBody">
                  @forelse ($inventory as $item)
                    <tr
                      data-id="{{ $item->id }}"
                      data-freshness="{{ $item->freshnessClass() }}"
                      data-species="{{ $item->species }}"
                      data-min-stock="{{ $item->min_stock ?? '' }}"
                    >
                      <td>
                        <div class="td-with-icon">
                          <i class="ri-image-line"></i><span>{{ $item->name }}</span>
                        </div>
                      </td>
                      <td>
                        <span class="mobile-label">Species</span>{{ $item->species }}
                      </td>
                      <td>
                        <span class="mobile-label">Quantity</span
                        ><span class="qty">{{ number_format((float) $item->quantity, 1) }}</span> kg
                      </td>
                      <td>
                        <span class="mobile-label">Freshness</span
                        ><span class="badge {{ $item->freshnessClass() }}">{{ $item->freshnessLabel() }} · {{ $item->freshness_percentage }}%</span>
                      </td>
                      <td>
                        <span class="mobile-label">Last Scan</span
                        ><span class="inv-date">{{ $item->last_scanned_at ? $item->last_scanned_at->format('Y-m-d H:i') : '—' }}</span>
                      </td>
                      <td>
                        <div class="row-actions">
                          <button class="btn btn-secondary btn-sm edit-inv-btn">Edit</button>
                          <button
                            class="btn btn-danger btn-sm delete-inv-btn"
                            data-confirm="Delete this inventory item?"
                            data-confirm-title="Delete inventory item"
                            data-confirm-note="This cannot be undone."
                            data-confirm-ok="Delete"
                            data-confirm-icon="ri-delete-bin-line"
                            data-confirm-danger="1"
                          >
                            Delete
                          </button>
                        </div>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="6">
                        <p class="text-muted" style="text-align:center; padding:1.5rem 0">
                          No inventory yet. Click "Add Fish" to get started!
                        </p>
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </section>