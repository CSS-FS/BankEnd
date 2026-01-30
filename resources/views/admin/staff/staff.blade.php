@extends('layouts.app')

@section('title', 'Staff')

@section('content')
    <div class="content">
        <div class="page-header">
            <div class="add-item d-flex">
                <div class="page-title">
                    <h4 class="fw-bold">Farm Staff</h4>
                    <h6>Manage staff members for your farm(s).</h6>
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
                <a href="javascript:void(0)" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                    <i class="ti ti-circle-plus me-1"></i>Add Staff
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
                    @hasanyrole('admin|owner')
                    <form method="GET" action="{{ route('admin.staff.index') }}" class="d-flex gap-2 align-items-center">
                        <label class="mb-0">Farm:</label>
                        <select name="farm_id" class="form-select" onchange="this.form.submit()">
                            @foreach($farms as $farm)
                                <option value="{{ $farm->id }}" {{ (int)$selectedFarmId === (int)$farm->id ? 'selected' : '' }}>
                                    {{ $farm->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                    @endhasanyrole
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table datatable-custom">
                        <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Linked</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($staff as $s)
                            <tr>
                                <td>{{ $s->name }}</td>
                                <td>{{ $s->email }}</td>
                                <td>{{ $s->phone }}</td>
                                <td>
                                    @if($s->is_active)
                                        <span class="badge bg-soft-success text-dark">Active</span>
                                    @else
                                        <span class="badge bg-soft-danger text-dark">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($s->link_date)->format('Y-m-d') }}</td>
                                <td class="text-end">
                                    <a href="javascript:void(0)"
                                       class="btn btn-sm btn-outline-primary edit-staff"
                                       data-staff-id="{{ $s->id }}"
                                       data-staff-name="{{ $s->name }}">
                                        Edit
                                    </a>

                                    <a href="javascript:void(0)"
                                       class="btn btn-sm btn-outline-danger open-delete-modal"
                                       data-staff-id="{{ $s->id }}"
                                       data-staff-name="{{ $s->name }}">
                                        Delete
                                    </a>

                                    <form id="delete{{ $s->id }}" method="POST" action="{{ route('admin.staff.destroy', $s->id) }}" class="d-none">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <!-- Add Staff Modal -->
    <div class="modal fade" id="addStaffModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.staff.store') }}" class="needs-validation" novalidate>
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Staff</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name<span class="text-danger ms-1">*</span></label>
                                <input type="text" name="name" class="form-control" required maxlength="191">
                                <div class="invalid-feedback">Name is required.</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone<span class="text-danger ms-1">*</span></label>
                                <input type="text" name="phone" class="form-control" required maxlength="191">
                                <div class="invalid-feedback">Phone is required.</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email<span class="text-danger ms-1">*</span></label>
                                <input type="email" name="email" class="form-control" required maxlength="191">
                                <div class="invalid-feedback">Email is required.</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password<span class="text-danger ms-1">*</span></label>
                                <input type="password" name="password" class="form-control" required minlength="8">
                                <div class="invalid-feedback">Password (min 8) is required.</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="is_active" class="form-select">
                                    <option value="1" selected>Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Farm<span class="text-danger ms-1">*</span></label>
                                <select name="farm_id" class="form-select" required>
                                    @foreach($farms as $farm)
                                    <option value="{{ $farm->id }}" {{ (int)$selectedFarmId === (int)$farm->id ? 'selected' : '' }}>
                                        {{ $farm->name }}
                                    </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Farm is required.</div>
                            </div>

                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="ti ti-device-floppy me-2"></i>Save Staff
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="ti ti-x me-2"></i>Close
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Staff Modal -->
    <div class="modal fade" id="editStaffModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="editStaffForm" class="needs-validation" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h5 class="modal-title" id="editStaffModalLabel">Edit Staff</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" id="edit_id">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name<span class="text-danger ms-1">*</span></label>
                                <input type="text" class="form-control" id="edit_name" name="name" required maxlength="191">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone<span class="text-danger ms-1">*</span></label>
                                <input type="text" class="form-control" id="edit_phone" name="phone" required maxlength="191">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email<span class="text-danger ms-1">*</span></label>
                                <input type="email" class="form-control" id="edit_email" name="email" required maxlength="191">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">New Password (optional)</label>
                                <input type="password" class="form-control" id="edit_password" name="password" minlength="8">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="is_active" class="form-select" id="edit_is_active">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="ti ti-device-floppy me-2"></i>Update Staff
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="ti ti-x me-2"></i>Close
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal (same style as your feeds page) -->
    <div class="modal fade" id="delete-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="p-5 px-3 text-center">
                    <span class="rounded-circle d-inline-flex p-2 bg-danger-transparent mb-2">
                        <i class="ti ti-trash fs-24 text-danger"></i>
                    </span>
                        <h4 class="fs-20 fw-bold mb-2 mt-1">Delete Staff</h4>
                        <p class="mb-0 fs-16" id="delete-modal-message"></p>
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
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.edit-staff').forEach(function(button) {
                button.addEventListener('click', function() {
                    var staffId = this.getAttribute('data-staff-id');
                    var staffName = this.getAttribute('data-staff-name');

                    var form = document.getElementById('editStaffForm');
                    form.action = '/admin/staff/' + staffId;

                    document.getElementById('editStaffModalLabel').textContent = "Edit Staff - " + staffName;

                    $.get('/admin/staff/' + staffId, function(data) {
                        $('#edit_id').val(data.id);
                        $('#edit_name').val(data.name);
                        $('#edit_phone').val(data.phone);
                        $('#edit_email').val(data.email);
                        $('#edit_is_active').val(data.is_active ? 1 : 0);
                        $('#edit_password').val('');
                    });

                    var modal = new bootstrap.Modal(document.getElementById('editStaffModal'));
                    modal.show();
                });
            });

            let deleteId = null;

            document.querySelectorAll('.open-delete-modal').forEach(function(el) {
                el.addEventListener('click', function() {
                    deleteId = this.getAttribute('data-staff-id');
                    const name = this.getAttribute('data-staff-name');
                    document.getElementById('delete-modal-message').textContent =
                        `Are you sure you want to delete "${name}"?`;

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
@endpush
