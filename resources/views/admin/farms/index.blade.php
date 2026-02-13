@extends('layouts.app')

@section('title', 'Farms')

@section('content')
    <div class="content">
        <div class="page-header">
            <div class="add-item d-flex">
                <div class="page-title">
                    <h4 class="fw-bold">Farms</h4>
                    <h6>Manage poultry farms information.</h6>
                </div>
            </div>
            <ul class="table-top-head">
                <li>
                    <a data-bs-toggle="tooltip" data-bs-placement="top" title="Collapse" id="collapse-header">
                        <i class="ti ti-chevron-up"></i>
                    </a>
                </li>
            </ul>
            @if(auth()->user()->hasRole('admin'))
            <div class="page-btn">
                <a href="javascript:void(0)" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFarmModal">
                    <i class="ti ti-circle-plus me-1"></i>Add Farm
                </a>
            </div>
            @endif
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
                    <select id="ownerFilter" class="form-select me-2">
                        <option value="">All Owners</option>
                        @foreach($owners as $row)
                            <option value="{{ $row->name }}">{{ $row->name }}</option>
                        @endforeach
                    </select>

                    <select id="cityFilter" class="form-select">
                        <option value="">All Cities</option>
                        @foreach($cities as $row)
                            <option value="{{ $row?->name }}">{{ $row?->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table datatable-custom">
                        <thead class="thead-light">
                        <tr>
                            <th>Farm</th>
                            <th>Owner</th>
                            <th>Manager</th>
                            <th>Sheds</th>
                            <th>City</th>
                            <th>Address</th>
                            <th class="text-center">Latitude</th>
                            <th class="text-center">Longitude</th>
                            <th class="no-sort"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($farms as $farm)
                            <tr>
                                <td>{{ ucwords($farm?->name) }}</td>
                                <td>{{ ucwords($farm?->owner?->name) }}</td>
                                <td>
                                    @if($farm?->managers->count() > 0)
                                        {{ ucwords($farm->managers->first()?->name) }}
                                        <div class="text-muted fs-10">{{ $farm->managers->first()?->pivot->link_date }}</div>
                                    @else
                                        <span class="text-danger fs-10">No Manager Added</span>
                                    @endif
                                </td>
                                <td>
                                    @forelse($farm?->sheds as $shed)
                                        <span class="badge bg-soft-info">{{ $shed->name }}</span>
                                    @empty
                                        <span class='text-danger fs-10'>No Shed Attached</span>
                                    @endforelse
                                </td>
                                <td>{{ ucfirst($farm?->city?->name) }}</td>
                                <td class="text-wrap">{{ ucfirst($farm->address) }}</td>
                                <td class="text-center">{{ round($farm->latitude, 4) }}</td>
                                <td class="text-center">{{ round($farm->longitude, 4) }}</td>
                                <td class="action-table-data">
                                    <div class="action-icon d-inline-flex">
                                        @if(auth()->user()->hasRole('admin'))
                                        <a href="javascript:void(0)"
                                           class="p-2 border rounded me-2 edit-farm"
                                           data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           title=""
                                           data-bs-original-title="Edit Farm"
                                           data-farm-id="{{ $farm->id }}"
                                           data-farm-name="{{ $farm->name }}">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        @endif

                                        <a href="javascript:void(0)"
                                           class="p-2 border rounded me-2 manage-staff"
                                           data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           title="Manage Staff"
                                           data-farm-id="{{ $farm->id }}"
                                           data-manager-id="{{ optional($farm->managers->first())->id }}"
                                           data-farm-name="{{ $farm->name }}">
                                            <i class="ti ti-users"></i>
                                        </a>

                                        @if(auth()->user()->hasRole('admin'))
                                        <a href="javascript:void(0);"
                                           data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           title=""
                                           data-bs-original-title="Delete Farm"
                                           data-farm-id="{{ $farm->id }}"
                                           data-farm-name="{{ $farm->name }}"
                                           class="p-2 open-delete-modal">
                                            <i data-feather="trash-2" class="feather-trash-2"></i>
                                        </a>
                                        <form action="{{ route('admin.farms.destroy', $farm->id) }}" method="POST" id="delete{{ $farm->id }}">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        @endif
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

    <!-- Add Farm Modal -->
    <div class="modal fade" id="addFarmModal" tabindex="-1" aria-labelledby="addFarmModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('admin.farms.store') }}" class="needs-validation" novalidate method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addFarmModalLabel">Add Farm</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            {{-- Farm Name --}}
                            <div class="col-lg-6 mb-3">
                                <label for="name" class="form-label">Farm Name<span class="text-danger ms-1">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="invalid-feedback">Farm name is required.</div>
                            </div>

                            {{-- Owner --}}
                            <div class="col-lg-6 mb-3">
                                <label for="owner_id" class="form-label">Farm Owner<span class="text-danger ms-1">*</span></label>
                                <select class="form-select basic-select" id="owner_id" name="owner_id" required>
                                    <option value="" selected disabled>Select Owner</option>
                                    @foreach($owners as $owner)
                                        <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Farm owner is required.</div>
                            </div>

                            {{-- Province --}}
                            <div class="col-lg-4 mb-3">
                                <label for="province" class="form-label">Province<span class="text-danger ms-1">*</span></label>
                                <select class="form-select basic-select" id="province" name="province_id" required>
                                    <option value="" selected disabled>Select Province</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province->id }}">{{ $province->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Province is required.</div>
                            </div>

                            {{-- District --}}
                            <div class="col-lg-4 mb-3">
                                <label for="district" class="form-label">District<span class="text-danger ms-1">*</span></label>
                                <select class="form-select basic-select" id="district" name="district_id" required>
                                    <option value="" selected disabled>Select District</option>
                                </select>
                                <div class="invalid-feedback">District is required.</div>
                            </div>

                            {{-- City --}}
                            <div class="col-lg-4 mb-3">
                                <label for="city" class="form-label">City<span class="text-danger ms-1">*</span></label>
                                <select class="form-select select2" id="city" name="city_id" required>
                                    <option value="" selected disabled>Select City</option>
                                </select>
                                <div class="invalid-feedback">City is required.</div>
                            </div>

                            {{-- Address --}}
                            <div class="col-lg-12 mb-3">
                                <label for="address" class="form-label">Address<span class="text-danger ms-1">*</span></label>
                                <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                                <div class="invalid-feedback">Address is required.</div>
                            </div>

                            {{-- Latitude --}}
                            <div class="col-lg-6 mb-3">
                                <label for="latitude" class="form-label">Latitude</label>
                                <input type="text" class="form-control" id="latitude" name="latitude">
                            </div>

                            {{-- Longitude --}}
                            <div class="col-lg-6 mb-3">
                                <label for="longitude" class="form-label">Longitude</label>
                                <input type="text" class="form-control" id="longitude" name="longitude">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="ti ti-device-floppy me-2"></i>Save Farm
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="ti ti-x me-2"></i>Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Farm Modal -->
    <div class="modal fade" id="editFarmModal" tabindex="-1" aria-labelledby="editFarmModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editFarmForm" action="" method="POST"
                      class="needs-validation" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h5 class="modal-title" id="editFarmModalLabel">Edit Farm</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" id="edit_farm_id">

                        <div class="row">
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Farm Name<span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                                <div class="invalid-feedback">Farm name is required.</div>
                            </div>

                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Owner<span class="text-danger">*</span></label>
                                <select id="edit_owner_id" class="form-select basic-select2" name="owner_id" required></select>
                                <div class="invalid-feedback">Farm owner is required.</div>
                            </div>

                            <div class="col-lg-4 mb-3">
                                <label class="form-label">Province<span class="text-danger">*</span></label>
                                <select id="edit_province_id" class="form-select basic-select2" name="province_id" required></select>
                                <div class="invalid-feedback">Select province first.</div>
                            </div>

                            <div class="col-lg-4 mb-3">
                                <label class="form-label">District<span class="text-danger">*</span></label>
                                <select id="edit_district_id" class="form-select basic-select2" name="district_id" required></select>
                                <div class="invalid-feedback">Select district first.</div>
                            </div>

                            <div class="col-lg-4 mb-3">
                                <label class="form-label">City<span class="text-danger">*</span></label>
                                <select id="edit_city_id" class="form-select basic-select2" name="city_id" required></select>
                                <div class="invalid-feedback">Select city first.</div>
                            </div>

                            <div class="col-lg-12 mb-3">
                                <label class="form-label">Address<span class="text-danger">*</span></label>
                                <textarea class="form-control" id="edit_address" name="address" required></textarea>
                                <div class="invalid-feedback">Address line should be mentioned.</div>
                            </div>

                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Latitude</label>
                                <input type="text" class="form-control" id="edit_latitude" name="latitude">
                            </div>

                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Longitude</label>
                                <input type="text" class="form-control" id="edit_longitude" name="longitude">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="ti ti-device-floppy me-2"></i>Update Farm
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="ti ti-x me-2"></i>Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Staff Management Modal -->
    <div class="modal fade" id="manageStaffModal" tabindex="-1" aria-labelledby="manageStaffModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="manageStaffForm" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h5 class="modal-title" id="manageStaffModalLabel">Manage Farm Staff</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="staff_farm_id" name="farm_id">

                        <div class="mb-3">
                            <label class="form-label">Select Manager</label>
                            <select id="manager_id" name="manager_id" class="form-select basic-select3" required>
                                @foreach($managers as $manager)
                                    <option value="{{ $manager->id }}">{{ $manager->name }} ({{ $manager->email }})</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Please select a manager.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="ti ti-device-floppy me-2"></i>Save Manager
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="ti ti-x me-2"></i>Cancel
                        </button>
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

                $('#ownerFilter').on('change', function() {
                    var selected = $(this).val();
                    table.column(1).search(selected).draw();
                });

                $('#cityFilter').on('change', function() {
                    var selected = $(this).val();
                    table.column(4).search(selected).draw();
                });
            }
        });

        function resetSelect(selectElement, placeholder) {
            // Clear existing options
            $(selectElement).empty();

            // Create a new Option element
            const placeholderOption = new Option(placeholder, '');

            // Set the disabled and selected attributes on the placeholder option
            // so it cannot be chosen and serves as a default.
            $(placeholderOption).prop('disabled', true).prop('selected', true);

            // Append the placeholder option to the select element
            $(selectElement).append(placeholderOption);

            // Trigger a change to update the UI
            $(selectElement).val('').trigger('change');
        }

        function populateSelect(selectElement, data, placeholder, selectedId = null) {
            // Reset the select with the disabled placeholder
            resetSelect(selectElement, placeholder);

            // Check if there is data to populate
            if (data && data.length > 0) {
                data.forEach(item => {
                    const option = new Option(item.name, item.id, false, item.id == selectedId);
                    $(selectElement).append(option);
                });
            }

            // Trigger change after populating
            $(selectElement).trigger('change');

            // If a specific option was selected, remove the disabled attribute from the placeholder
            // This isn't strictly necessary but can be a good practice for some UIs
            if (selectedId !== null && selectedId !== '') {
                $(selectElement).find('option:disabled').prop('selected', false);
            }
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const provinceSelect = document.getElementById('province');
            const districtSelect = document.getElementById('district');
            const citySelect = document.getElementById('city');

            $(provinceSelect).on('change', function () {
                const provinceId = this.value;
                resetSelect(districtSelect, 'Select District');
                resetSelect(citySelect, 'Select City');

                if (provinceId) {
                    fetch(`/api/v1/districts/${provinceId}`)
                        .then(response => response.json())
                        .then(data => {
                            populateSelect(districtSelect, data, 'Select District');
                        });
                }
            });

            $(districtSelect).on('change', function () {
                const districtId = this.value;
                resetSelect(citySelect, 'Select City');

                if (districtId) {
                    fetch(`/api/v1/cities/${districtId}`)
                        .then(response => response.json())
                        .then(data => {
                            populateSelect(citySelect, data, 'Select City');
                        });
                }
            });
        });
    </script>
    <script>
        const ownersData = @json($owners);

        document.addEventListener('DOMContentLoaded', function() {
            const province = document.getElementById('edit_province_id');
            const district = document.getElementById('edit_district_id');
            const city = document.getElementById('edit_city_id');

            async function loadOwners(selectedId = null) {
                const ownersSelect = document.getElementById('edit_owner_id'); // Or use a param
                if (!ownersSelect) return;

                ownersSelect.innerHTML = `<option>Select Owner</option>`;
                ownersData.forEach(item => {
                    const option = new Option(item.name, item.id, false, item.id == selectedId);
                    $(ownersSelect).append(option);
                });
                $(ownersSelect).trigger('change');
            }

            async function loadProvinces(selectedId = null) {
                fetch('/api/v1/provinces')
                    .then(res => res.json())
                    .then(data => {
                        populateSelect(province, data, 'Select Province', selectedId);
                    });

                $(province).trigger('change');
            }

            $(province).on('change', function () {
                const provinceId = this.value;
                resetSelect(district, 'Select District');
                resetSelect(city, 'Select City');

                if (provinceId) {
                    fetch(`/api/v1/districts/${provinceId}`)
                        .then(response => response.json())
                        .then(data => {
                            populateSelect(district, data, 'Select District');
                        });
                }
            });

            async function loadDistricts(provinceId, selectedId = null) {
                fetch(`/api/v1/districts/${provinceId}`)
                    .then(res => res.json())
                    .then(data => {
                        populateSelect(district, data, 'Select Province', selectedId);
                    });

                $(district).trigger('change');
            }

            $(district).on('change', function () {
                const districtId = this.value;
                resetSelect(city, 'Select City');

                if (districtId) {
                    fetch(`/api/v1/cities/${districtId}`)
                        .then(response => response.json())
                        .then(data => {
                            populateSelect(city, data, 'Select City');
                        });
                }
            });

            async function loadCities(districtId, selectedId = null) {
                fetch(`/api/v1/cities/${districtId}`)
                    .then(res => res.json())
                    .then(data => {
                        populateSelect(city, data, 'Select City', selectedId);
                    });
            }

            document.querySelectorAll('.edit-farm').forEach(function(button) {
                button.addEventListener('click', function() {
                    var farmId = this.getAttribute('data-farm-id');
                    var farmName = this.getAttribute('data-farm-name');
                    var form = document.getElementById('editFarmForm');
                    form.action = '/admin/farms/' + farmId;

                    fetch(`/admin/farms/${farmId}`)
                        .then(res => res.json())
                        .then(data => {
                            document.getElementById('editFarmForm').action = `/admin/farms/${data.id}`;

                            // Populate modal fields
                            document.getElementById('editFarmModalLabel').innerText = `Edit Farm - ${farmName}`;
                            document.getElementById('edit_farm_id').value = data.id;
                            document.getElementById('edit_name').value = data.name;
                            document.getElementById('edit_owner_id').value = data.owner_id;
                            document.getElementById('edit_address').value = data.address;
                            document.getElementById('edit_latitude').value = data.latitude || '';
                            document.getElementById('edit_longitude').value = data.longitude || '';

                            loadOwners(data.owner_id);
                            loadProvinces(data.province_id);
                            loadDistricts(data.province_id, data.district_id);
                            loadCities(data.district_id, data.city_id);

                            new bootstrap.Modal(document.getElementById('editFarmModal')).show();
                        });
                });
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let deleteId = null;

            document.querySelectorAll('.open-delete-modal').forEach(function(el) {
                el.addEventListener('click', function() {
                    deleteId = this.getAttribute('data-farm-id');
                    const farmName = this.getAttribute('data-farm-name');
                    document.getElementById('delete-modal-message').textContent =
                        `Are you sure you want to delete "${farmName}" data?`;

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
                dropdownParent: $('#addFarmModal'), // ensures it works inside Bootstrap modal
                width: '100%'
            });
        });
        $(document).ready(function () {
            $('.basic-select2').select2({
                dropdownParent: $('#editFarmModal'), // ensures it works inside Bootstrap modal
                width: '100%'
            });
        });
        $(document).ready(function () {
            $('.basic-select3').select2({
                dropdownParent: $('#manageStaffModal'), // ensures it works inside Bootstrap modal
                width: '100%'
            });
        });
    </script>

    <script>
        $(document).on('click', '.manage-staff', function ()
        {
            const farmId = $(this).data('farm-id');
            const managerId = $(this).data('manager-id');

            $('#staff_farm_id').val(farmId);
            $('#manageStaffForm').attr('action', '/admin/farms/' + farmId + '/assign-manager');

            // Reset and preselect manager
            $('#manager_id').val(managerId).trigger('change');

            var modal = new bootstrap.Modal(document.getElementById('manageStaffModal'));
            modal.show();
        });
    </script>
@endpush
