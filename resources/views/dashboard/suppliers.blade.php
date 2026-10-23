<!-- ============ SUPPLIERS ============ -->
          <section id="suppliers-section" class="page-section hidden">
            <div class="page-header">
              <h1 data-i18n="page.suppliers.title">Supplier Management</h1>
              <p class="text-muted" data-i18n="page.suppliers.sub">
                Local Vendor only · Keep every fish supplier in one list
              </p>
              <div class="page-header-actions">
                <button class="btn btn-primary" id="addSupplierBtn">
                  <i class="ri-add-line"></i> Add Supplier
                </button>
                <input
                  type="search"
                  class="history-search"
                  id="supplierSearch"
                  placeholder="Search suppliers..."
                  aria-label="Search suppliers"
                />
                <div class="module-filters" id="supplierFilters">
                  <span class="filter-chip-outline active" data-filter="all"
                    ><i class="ri-filter-3-line"></i> All</span
                  >
                  <span class="filter-chip-outline" data-filter="active">Active</span>
                  <span class="filter-chip-outline" data-filter="inactive">Inactive</span>
                </div>
              </div>
            </div>

            <div class="stats-grid">
              @include('dashboard.partials.stat-card', [
                  'variant' => 'total',
                  'icon' => 'ri-team-line',
                  'label' => 'Total Suppliers',
                  'value' => $supplierSummary['total'],
                  'valueAttrs' => ['id' => 'supTotalSuppliers'],
                  'sub' => 'Registered On Your Account',
              ])
              @include('dashboard.partials.stat-card', [
                  'variant' => 'fresh',
                  'icon' => 'ri-user-follow-line',
                  'label' => 'Active',
                  'value' => $supplierSummary['active'],
                  'valueAttrs' => ['id' => 'supActiveCount'],
                  'sub' => 'Can Receive Deliveries',
              ])
              @include('dashboard.partials.stat-card', [
                  'variant' => 'moderate',
                  'icon' => 'ri-user-unfollow-line',
                  'label' => 'Inactive',
                  'value' => $supplierSummary['inactive'],
                  'valueAttrs' => ['id' => 'supInactiveCount'],
                  'sub' => 'Records Kept, Not Used',
              ])
              @include('dashboard.partials.stat-card', [
                  'variant' => 'notfresh',
                  'icon' => 'ri-truck-line',
                  'label' => 'Deliveries',
                  'value' => $supplierSummary['deliveries'],
                  'valueAttrs' => ['id' => 'supDeliveryCount'],
                  'sub' => 'Across All Suppliers',
              ])
            </div>

            <div class="table-card">
              <table class="data-table" id="supplierTable">
                <thead>
                  <tr>
                    <th>Supplier</th>
                    <th>Contact</th>
                    <th>Address</th>
                    <th>Deliveries</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="supplierBody">
                  @forelse ($suppliers as $supplier)
                    <tr
                      data-id="{{ $supplier->id }}"
                      data-status="{{ $supplier->status }}"
                      data-search="{{ strtolower(trim($supplier->name . ' ' . ($supplier->contact_person ?? '') . ' ' . $supplier->contactSummary() . ' ' . ($supplier->address ?? ''))) }}"
                    >
                      <td>
                        <div class="td-with-icon">
                          <i class="ri-store-2-line"></i><span>{{ $supplier->name }}</span>
                        </div>
                      </td>
                      <td>
                        <span class="mobile-label">Contact</span>{{ $supplier->contactSummary() ?: '—' }}
                      </td>
                      <td>
                        <span class="mobile-label">Address</span>{{ $supplier->address ?? '—' }}
                      </td>
                      <td>
                        <span class="mobile-label">Deliveries</span
                        ><span class="qty">{{ $supplier->deliveries_count }}</span>
                      </td>
                      <td>
                        <span class="mobile-label">Status</span
                        ><span class="badge {{ $supplier->statusClass() }}">{{ $supplier->statusLabel() }}</span>
                      </td>
                      <td>
                        <div class="row-actions">
                          <div class="row-actions-menu">
                            <button
                              type="button"
                              class="row-actions-toggle"
                              aria-haspopup="true"
                              aria-expanded="false"
                              aria-label="Actions for {{ $supplier->name }}"
                              title="Actions"
                            >
                              <i class="ri-more-2-fill"></i>
                            </button>
                            <div
                              class="row-actions-list"
                              data-scope="supplier"
                              data-id="{{ $supplier->id }}"
                            >
                              <button type="button" class="row-actions-item" data-action="details">
                                <i class="ri-eye-line"></i> Details
                              </button>
                              <button type="button" class="row-actions-item" data-action="edit">
                                <i class="ri-pencil-line"></i> Edit
                              </button>
                              <button type="button" class="row-actions-item" data-action="toggle">
                                <i class="ri-toggle-line"></i>
                                {{ $supplier->isActive() ? 'Deactivate' : 'Activate' }}
                              </button>
                              <button type="button" class="row-actions-item danger" data-action="delete">
                                <i class="ri-delete-bin-line"></i> Delete
                              </button>
                            </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                  @empty
                    <tr class="supplier-empty-row">
                      <td colspan="6">
                        <p class="text-muted" style="text-align:center; padding:1.5rem 0">
                          No suppliers yet. Click "Add Supplier" to register your first one!
                        </p>
                      </td>
                    </tr>
                  @endforelse
                  <tr id="supplierFilterEmpty" class="hidden">
                    <td colspan="6">
                      <p class="text-muted" style="text-align:center; padding:1.5rem 0">
                        No suppliers match your search.
                      </p>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
