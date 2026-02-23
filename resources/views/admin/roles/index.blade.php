@extends('layouts.app')

@section('title', 'User Roles')
@section('content')
    <div class="content">
        <div class="page-header">
            <div class="add-item d-flex">
                <div class="page-title">
                    <h4 class="fw-bold">User Roles</h4>
                    <h6>Manage user roles and respective permissions.</h6>
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
                <a href="javascript:void(0)" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                    <i class="ti ti-circle-plus me-1"></i>Add Role
                </a>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row mb-3">
                @if (session('error'))
                <div class="alert alert-danger d-flex align-items-center justify-content-between" role="alert">
                    <div>
                        <i class="feather-alert-circle flex-shrink-0 me-2"></i>
                        {{ session('error') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
                @endif

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
                    <select id="statusFilter" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Active">Active</option>
                        <option value="Blocked">Blocked</option>
                    </select>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table datatable-custom">
                        <thead class="thead-light">
                        <tr>
                            <th class="w-100">Role</th>
                            <th class="text-center">Guard</th>
                            <th class="text-center">Users Attach</th>
                            <th class="text-center">Create Date</th>
                            <th class="no-sort"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($roles as $role)
                            <tr>
                                <td class="w-100">
                                    {{ ucfirst($role->name) }}
                                </td>
                                <td class="text-center">{{ ucfirst($role->guard_name) }}</td>
                                <td class="text-center">
                                    <a href="javascript:void(0)"
                                       class="btn btn-sm btn-outline-info users-data"
                                       data-role-id="{{ $role->id }}"
                                       data-role-name="{{ ucfirst($role->name) }}"
                                       data-bs-toggle="tooltip"
                                       data-bs-placement="top"
                                       title=""
                                       data-bs-original-title="View Attach Users">
                                        View
                                    </a>
                                </td>
                                <td class="text-center">
                                   {{ $role->created_at->format('d-m-Y') }}
                                </td>
                                <td class="action-table-data">
                                    <div class="action-icon d-inline-flex">
                                        <a href="{{ route('roles.permissions', $role) }}" class="me-2 d-flex align-items-center p-2 border rounded"
                                           data-bs-toggle="tooltip" data-bs-placement="top" title="" data-bs-original-title="Manage Permissions">
                                            <i class="ti ti-shield"></i>
                                        </a>
                                        <a href="javascript:void(0)"
                                           class="me-2 d-flex align-items-center p-2 border rounded edit-role"
                                           data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           title=""
                                           data-bs-original-title="Edit Role"
                                           data-role-id="{{ $role->id }}"
                                           data-role-name="{{ $role->name }}">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        @role('admin')
                                        <a href="javascript:void(0)"
                                           class="d-flex align-items-center p-2 border rounded text-danger delete-role"
                                           data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           title=""
                                           data-bs-original-title="Delete Role"
                                           data-role-id="{{ $role->id }}"
                                           data-role-name="{{ ucfirst($role->name) }}"
                                           data-delete-url="{{ route('roles.destroy', $role) }}">
                                            <i class="ti ti-trash"></i>
                                        </a>
                                        @endrole
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

    <!-- Add Role Modal -->
    <div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('roles.store') }}" class="needs-validation" novalidate method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addRoleModalLabel">Add Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Role Name<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                    <div class="invalid-feedback">
                                        Role name is required.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Save Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Role Modal -->
    <div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editRoleForm" action="" class="needs-validation" novalidate method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editRoleModalLabel">Edit Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <input type="hidden" id="edit-role-id" name="id" value="">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Role Name<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" id="edit-name" name="name" required>
                                    <div class="invalid-feedback">
                                        Role name is required.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Update Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Role Form (hidden) -->
    <form id="deleteRoleForm" action="" method="POST" style="display:none;">
        @csrf
        @method('DELETE')
    </form>

    <!-- Attach Users Modal -->
    <div class="modal fade" id="attachUsersModal" tabindex="-1" aria-labelledby="attachUsersModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="attachUsersModalLabel">Attach Users</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Close</button>
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

                $('#statusFilter').on('change', function() {
                    var selected = $(this).val();
                    table.column(5).search(selected).draw();
                });
            }
        });
    </script>
    <script>
        $(function(){
            $('.users-data').on('click', function() {
                var roleId = $(this).data('role-id');
                var roleName = $(this).data('role-name');

                // Show loading spinner in modal body
                $('#attachUsersModal .modal-body').html('<div class="text-center py-5"><div class="spinner-border text-success"></div></div>');
                $('#attachUsersModalLabel').text('Attach Users - ' + roleName);

                // Open the modal
                var modal = new bootstrap.Modal(document.getElementById('attachUsersModal'));
                modal.show();

                // Fetch the attached users via AJAX
                $.ajax({
                    url: '/admin/roles/' + roleId + '/users',
                    method: 'GET',
                    success: function(data) {
                        $('#attachUsersModalLabel').text('Attach Users - ' + data.role_name);
                        $('#attachUsersModal .modal-body').html(data.html);
                    },
                    error: function() {
                        $('#attachUsersModal .modal-body').html('<div class="alert alert-danger">Failed to load users.</div>');
                    }
                });
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.delete-role').forEach(function(button) {
                button.addEventListener('click', function() {
                    var roleName = this.getAttribute('data-role-name');
                    var deleteUrl = this.getAttribute('data-delete-url');

                    if (confirm('Are you sure you want to delete the role "' + roleName + '"?\n\nThis action cannot be undone.')) {
                        var form = document.getElementById('deleteRoleForm');
                        form.action = deleteUrl;
                        form.submit();
                    }
                });
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.edit-role').forEach(function(button) {
                button.addEventListener('click', function() {
                    var roleId = this.getAttribute('data-role-id');
                    var roleName = this.getAttribute('data-role-name');
                    // Set the form action dynamically
                    var form = document.getElementById('editRoleForm');
                    form.action = '/admin/roles/' + roleId;

                    // Set hidden and visible values
                    document.getElementById('edit-role-id').value = roleId;
                    document.getElementById('edit-name').value = roleName;
                    // Update modal title (optional)
                    document.getElementById('editRoleModalLabel').textContent = "Edit Role - " + roleName;

                    // Show the modal
                    var modal = new bootstrap.Modal(document.getElementById('editRoleModal'));
                    modal.show();
                });
            });
        });
    </script>
@endpush
