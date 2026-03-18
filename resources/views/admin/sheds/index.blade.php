@extends('layouts.app')

@section('title', 'Sheds')

@section('content')
    <div class="content">
        <div class="page-header">
            <div class="add-item d-flex">
                <div class="page-title">
                    <h4 class="fw-bold">Sheds</h4>
                    <h6>Manage sheds information.</h6>
                </div>
            </div>
            <ul class="table-top-head">
                <li>
                    <a data-bs-toggle="tooltip" data-bs-placement="top" title="Collapse" id="collapse-header">
                        <i class="ti ti-chevron-up"></i>
                    </a>
                </li>
            </ul>
            <div class="page-btn">
                <a href="javascript:void(0)" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addShedModal">
                    <i class="ti ti-circle-plus me-1"></i>Add Shed
                </a>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row mb-3">
                @if (session('success'))
                <div class="alert alert-success d-flex align-items-center justify-content-between" role="alert">
                    <div>
                        <i class="feather-check-circle flex-shrink-0 me-2"></i>
                        {{ session('success') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
                @endif

                @if (session('error'))
                <div class="alert alert-danger d-flex align-items-center justify-content-between" role="alert">
                    <div>
                        <i class="feather-alert-triangle flex-shrink-0 me-2"></i>
                        {{ session('error') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
                @endif

                @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>
                                <i class="feather-alert-triangle flex-shrink-0 me-2"></i>
                                There were some errors with your submission:
                            </strong>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                                <i class="fas fa-xmark"></i>
                            </button>
                        </div>
                @endif
            </div>
        </div>
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <div class="search-set">
                    <div class="search-input">
                        <span class="btn-searchset"><i class="ti ti-search fs-14 feather-search"></i></span>
                    </div>
                </div>
                <div class="d-flex my-xl-auto right-content align-items-center row-gap-3">
                    <select id="cityFilter" class="form-select me-2">
                        <option value="">All Cities</option>
                        @foreach($cities as $row)
                            <option value="{{ $row }}">{{ $row }}</option>
                        @endforeach
                    </select>

                    <select id="typeFilter" class="form-select">
                        <option value="">All Types</option>
                        @foreach($types as $row)
                            <option value="{{ $row }}">{{ ucfirst($row) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table datatable-custom">
                        <thead class="thead-light">
                        <tr>
                            <th>Shed</th>
                            <th>City</th>
                            <th class="text-center">Capacity</th>
                            <th>Type</th>
                            <th>Latest Flock</th>
                            <th class="no-sort"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($sheds as $shed)
                            <tr>
                                <td>
                                    {{ $shed->name }}
                                    <div class="text-info fs-10">
                                        <i class="ti ti-building me-2"></i>{{ $shed->farm?->name }}<br>
                                        <i class="ti ti-user me-2"></i>{{ $shed->farm?->owner?->name }}
                                    </div>
                                </td>
                                <td>{{ $shed->farm?->city?->name }}</td>
                                <td class="text-center">{{ number_format($shed->capacity, 0) }}</td>
                                <td>{{ ucwords($shed->type) }}</td>
                                <td>
                                    @if($shed->latestFlock?->count() > 0)
                                        {{ $shed->latestFlock->name }}
                                        <span class="badge bg-soft-success fw-normal float-end">{{ $shed->latestFlock->age }} Days</span>
                                        <div class="text-muted fs-10">
                                            {{ $shed->latestFlock->start_date->format('d-m-Y') }}
                                        </div>
                                    @else
                                        <span class="badge bg-soft-danger">No Flock Cycled</span>
                                    @endif
                                </td>
                                <td class="action-table-data">
                                    <div class="action-icon d-inline-flex float-end">
                                        <a href="javascript:void(0)"
                                           class="p-2 border rounded me-2 edit-shed-btn"
                                           data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           title=""
                                           data-bs-original-title="Edit Shed"
                                           data-shed-id="{{ $shed->id }}"
                                           data-shed-name="{{ $shed->name }}">
                                            <i class="ti ti-edit"></i>
                                        </a>

                                        <a href="javascript:void(0);"
                                           data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           title=""
                                           data-bs-original-title="Delete Shed"
                                           data-shed-id="{{ $shed->id }}"
                                           data-shed-name="{{ $shed->name }}"
                                           class="p-2 open-delete-modal">
                                            <i data-feather="trash-2" class="feather-trash-2"></i>
                                        </a>
                                        <form action="{{ route('admin.sheds.destroy', $shed->id) }}" method="POST" id="delete{{ $shed->id }}">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Shed Modal -->
    <div class="modal fade" id="addShedModal" tabindex="-1" aria-labelledby="addShedModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('admin.sheds.store') }}" method="POST"
                      class="needs-validation" novalidate>
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addShedModalLabel">Add Shed</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            {{-- Shed Name --}}
                            <div class="col-lg-6 mb-3">
                                <label for="name" class="form-label">Shed Name<span class="text-danger ms-1">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="invalid-feedback" id="add_name_feedback">Shed name is required.</div>
                            </div>

                            {{-- Farm --}}
                            <div class="col-lg-6 mb-3">
                                <label for="farm_id" class="form-label">Farm<span class="text-danger ms-1">*</span></label>
                                <select class="form-select basic-select" id="farm_id" name="farm_id" required>
                                    <option value="">Select Farm</option>
                                    @foreach($farms as $farm)
                                        <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Farm is required.</div>
                            </div>

                            {{-- Capacity --}}
                            <div class="col-lg-6 mb-3">
                                <label for="capacity" class="form-label">Capacity<span class="text-danger ms-1">*</span></label>
                                <input type="number" id="capacity" name="capacity" class="form-control" required>
                                <div class="invalid-feedback">Shed capacity is required to enter.</div>
                            </div>

                            {{-- Type --}}
                            <div class="col-lg-6 mb-3">
                                <label for="type" class="form-label">Shed Type<span class="text-danger ms-1">*</span></label>
                                <select class="form-select basic-select" id="type" name="type" required>
                                    <option value="">Select Type</option>
                                    @foreach($types as $t)
                                        <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Shed type is required.</div>
                            </div>

                            {{-- Description --}}
                            <div class="col-lg-12 mb-3">
                                <label for="description" class="form-label">Other Details<span class="text-danger ms-1">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success me-2">Save Shed</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Reusable Edit Shed Modal --}}
    <div class="modal fade" id="editShedModal" tabindex="-1" aria-labelledby="editShedModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editShedForm" method="POST" class="needs-validation" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h5 class="modal-title" id="editShedModalLabel">Edit Shed</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" id="edit_shed_id">

                        <div class="row">
                            {{-- Shed Name --}}
                            <div class="col-lg-6 mb-3">
                                <label for="edit_name" class="form-label">Shed Name <span class="text-danger ms-1">*</span></label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                                <div class="invalid-feedback" id="edit_name_feedback">Shed name is required.</div>
                            </div>

                            {{-- Farm --}}
                            <div class="col-lg-6 mb-3">
                                <label for="edit_farm_id" class="form-label">Farm <span class="text-danger ms-1">*</span></label>
                                <select class="form-select basic-select" id="edit_farm_id" name="farm_id" required>
                                    <option value="">Select Farm</option>
                                    {{-- options filled by JS --}}
                                </select>
                                <div class="invalid-feedback">Farm is required.</div>
                            </div>

                            {{-- Capacity --}}
                            <div class="col-lg-6 mb-3">
                                <label for="edit_capacity" class="form-label">Capacity <span class="text-danger ms-1">*</span></label>
                                <input type="number" id="edit_capacity" name="capacity" class="form-control" required>
                                <div class="invalid-feedback">Shed capacity is required to enter.</div>
                            </div>

                            {{-- Type --}}
                            <div class="col-lg-6 mb-3">
                                <label for="edit_type" class="form-label">Shed Type <span class="text-danger ms-1">*</span></label>
                                <select class="form-select basic-select" id="edit_type" name="type" required>
                                    <option value="">Select Type</option>
                                    {{-- options filled by JS --}}
                                </select>
                                <div class="invalid-feedback">Shed type is required.</div>
                            </div>

                            {{-- Description --}}
                            <div class="col-lg-12 mb-3">
                                <label for="edit_description" class="form-label">Other Details</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success me-2">Update Shed</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="delete-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="p-5 px-3 text-center">
                    <span class="rounded-circle d-inline-flex p-2 bg-danger-transparent mb-2">
                        <i class="ti ti-trash fs-24 text-danger"></i>
                    </span>
                        <h4 class="fs-20 fw-bold mb-2 mt-1">Delete Farm</h4>
                        <p class="mb-0 fs-16" id="delete-modal-message">
                            Are you sure you want to delete this farm data?
                        </p>
                        <div class="modal-footer-btn mt-3 d-flex justify-content-center">
                            <button type="button" class="btn btn-secondary fs-13 fw-medium p-2 px-3 me-2" data-bs-dismiss="modal">
                                Cancel
                            </button>
                            <button type="button" class="btn btn-danger fs-13 fw-medium p-2 px-3" id="confirm-delete-btn">
                                Yes Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')
    <script>
        // Existing sheds data for client-side uniqueness check
        const existingShedsData = @json($sheds->map(fn($s) => ['id' => $s->id, 'farm_id' => $s->farm_id, 'name' => $s->name]));

        function isDuplicateShedName(farmId, shedName, excludeShedId = null) {
            const trimmedName = shedName.trim().toLowerCase();
            return existingShedsData.some(s =>
                String(s.farm_id) === String(farmId) &&
                s.name.trim().toLowerCase() === trimmedName &&
                (excludeShedId === null || String(s.id) !== String(excludeShedId))
            );
        }
    </script>
    <script>
        $(function() {
            // Datatable
            if($('.datatable-custom').length > 0) {
                var table = $('.datatable-custom').DataTable({
                    "bFilter": true,
                    "sDom": 'fBtlpi',
                    "ordering": true,
                    "language": {
                        search: ' ',
                        sLengthMenu: '_MENU_',
                        searchPlaceholder: "Search",
                        sLengthMenu: 'Rows Per Page _MENU_ Entries',
                        info: "_START_ - _END_ of _TOTAL_ items",
                        paginate: {
                            next: ' <i class=" fa fa-angle-right"></i>',
                            previous: '<i class="fa fa-angle-left"></i> '
                        },
                    },
                    initComplete: (settings, json)=> {
                        $('.dataTables_filter').appendTo('#tableSearch');
                        $('.dataTables_filter').appendTo('.search-input');
                    },
                });

                $('#cityFilter').on('change', function() {
                    var selected = $(this).val();
                    table.column(1).search(selected).draw();
                });

                $('#typeFilter').on('change', function() {
                    var selected = $(this).val();
                    table.column(3).search(selected).draw();
                });
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Blade-provided data (minimal & fast)
            const farmsData = @json($farms->map(fn($f) => ['id' => $f->id, 'name' => $f->name]));
            const typesData = @json($types); // e.g. ['broiler','breeder','layer']

            const form   = document.getElementById('editShedForm');
            const modalEl = document.getElementById('editShedModal');
            const modal  = new bootstrap.Modal(modalEl);

            const idEl   = document.getElementById('edit_shed_id');
            const nameEl = document.getElementById('edit_name');
            const farmEl = document.getElementById('edit_farm_id');
            const capEl  = document.getElementById('edit_capacity');
            const typeEl = document.getElementById('edit_type');
            const descEl = document.getElementById('edit_description');

            // Populate static selects once
            function populateFarms(selectedId = null) {
                farmEl.innerHTML = `<option value="">Select Farm</option>`;
                farmsData.forEach(f => {
                    farmEl.innerHTML += `<option value="${f.id}" ${Number(f.id) === Number(selectedId) ? 'selected' : ''}>${f.name}</option>`;
                });
            }
            function populateTypes(selected = null) {
                typeEl.innerHTML = `<option value="">Select Type</option>`;
                typesData.forEach(t => {
                    typeEl.innerHTML += `<option value="${t}" ${t === selected ? 'selected' : ''}>${t.charAt(0).toUpperCase()+t.slice(1)}</option>`;
                });
            }

            // Open modal handler (event delegation in case rows are dynamic)
            document.body.addEventListener('click', async (e) => {
                const btn = e.target.closest('.edit-shed-btn');
                if (!btn) return;

                const shedId = btn.getAttribute('data-shed-id');
                if (!shedId) return;

                // Fetch shed data from show endpoint
                const res = await fetch(`/admin/sheds/${shedId}`, { headers: { 'Accept': 'application/json' }});
                if (!res.ok) {
                    alert('Failed to load shed.');
                    return;
                }
                const shed = await res.json();

                // Fill fields
                idEl.value   = shed.id;
                nameEl.value = shed.name ?? '';
                capEl.value  = shed.capacity ?? '';
                descEl.value = shed.description ?? '';

                // Populate selects with selected values
                populateFarms(shed.farm_id ?? null);
                populateTypes(shed.type ?? null);

                // Set form action to update route
                form.action = `/admin/sheds/${shed.id}`;

                // If you use Select2 on these selects, re-init safely:
                $('#edit_farm_id, #edit_type').each(function(){
                  if ($(this).hasClass('select2-hidden-accessible')) $(this).select2('destroy');
                  $(this).select2({ dropdownParent: $('#editShedModal'), width: '100%' });
                });

                modal.show();
            });

            // Client-side validation with uniqueness check
            form.addEventListener('submit', function (e) {
                const editFarmId = farmEl.value;
                const editName = nameEl.value;
                const editShedId = idEl.value;
                const feedbackEl = document.getElementById('edit_name_feedback');

                if (editFarmId && editName.trim() && isDuplicateShedName(editFarmId, editName, editShedId)) {
                    e.preventDefault();
                    e.stopPropagation();
                    nameEl.classList.add('is-invalid');
                    feedbackEl.textContent = 'Shed name must be unique within the selected farm.';
                    return false;
                }
                nameEl.classList.remove('is-invalid');
                feedbackEl.textContent = 'Shed name is required.';

                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            });

            // Clear error on name/farm change in Edit modal
            $(nameEl).add(farmEl).on('change input', function () {
                nameEl.classList.remove('is-invalid');
                document.getElementById('edit_name_feedback').textContent = 'Shed name is required.';
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let deleteId = null;

            document.querySelectorAll('.open-delete-modal').forEach(function(el) {
                el.addEventListener('click', function() {
                    deleteId = this.getAttribute('data-shed-id');
                    const shedName = this.getAttribute('data-shed-name');
                    document.getElementById('delete-modal-message').textContent =
                        `Are you sure you want to delete "${shedName}" data?`;

                    var modal = new bootstrap.Modal(document.getElementById('delete-modal'));
                    modal.show();
                });
            });

            document.getElementById('confirm-delete-btn').addEventListener('click', function() {
                if (deleteId) {
                    document.getElementById('delete' + deleteId).submit();
                }
            });
        });
    </script>
    <script>
        $(document).ready(function () {
            $('.basic-select').select2({
                dropdownParent: $('#addShedModal'), // ensures it works inside Bootstrap modal
                width: '100%'
            });

            // Add Shed form - uniqueness check before submit
            $('#addShedModal form').on('submit', function (e) {
                const nameInput = document.getElementById('name');
                const farmId = document.getElementById('farm_id').value;
                const feedbackEl = document.getElementById('add_name_feedback');

                if (farmId && nameInput.value.trim() && isDuplicateShedName(farmId, nameInput.value)) {
                    e.preventDefault();
                    e.stopPropagation();
                    nameInput.classList.add('is-invalid');
                    feedbackEl.textContent = 'Shed name must be unique within the selected farm.';
                    return false;
                }
                nameInput.classList.remove('is-invalid');
                feedbackEl.textContent = 'Shed name is required.';
            });

            // Clear error on name/farm change in Add modal
            $('#name, #farm_id').on('change input', function () {
                const nameInput = document.getElementById('name');
                const feedbackEl = document.getElementById('add_name_feedback');
                nameInput.classList.remove('is-invalid');
                feedbackEl.textContent = 'Shed name is required.';
            });
        });
    </script>
@endpush
