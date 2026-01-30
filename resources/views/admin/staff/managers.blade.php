@extends('layouts.app')

@section('title', 'Farm Managers')

@section('content')
    <div class="content">
        <div class="page-header">
            <div class="add-item d-flex">
                <div class="page-title">
                    <h4 class="fw-bold">Farm Managers</h4>
                    <h6>Assign or remove managers for your farms.</h6>
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
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createManagerModal">
                    <i class="ti ti-circle-plus me-1"></i>Create Manager
                </button>
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
                        <strong><i class="feather-alert-triangle flex-shrink-0 me-2"></i>Errors:</strong>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
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
                    <form method="GET" action="{{ route('admin.farm_managers.index') }}" class="d-flex gap-2 align-items-center">
                        <label class="mb-0">Farm:</label>
                        <select name="farm_id" class="form-select" onchange="this.form.submit()">
                            <option value="">All Farms</option>
                            @foreach($ownerFarms as $farm)
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
                        <thead>
                        <tr>
                            <th>Farm</th>
                            <th>Manager</th>
                            <th>Email</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($farms as $f)
                            <tr>
                                <td>{{ $f->name }}</td>
                                <td>{{ $f->manager_name ?? '—' }}</td>
                                <td>{{ $f->manager_email ?? '—' }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary open-assign-modal"
                                            data-farm-id="{{ $f->id }}"
                                            data-farm-name="{{ $f->name }}">
                                        Assign / Change
                                    </button>

                                    @if($f->manager_id)
                                        <form method="POST" action="{{ route('admin.farms.manager.unassign', $f->id) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Unassign</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <!-- Create Manager Modal -->
    <div class="modal fade" id="createManagerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.farm_managers.create_manager') }}" class="needs-validation" novalidate>
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Create Manager</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Farm<span class="text-danger ms-1">*</span></label>
                                <select name="farm_id" class="form-select" required>
                                    @foreach($ownerFarms as $farm)
                                        <option value="{{ $farm->id }}" {{ (int)$selectedFarmId === (int)$farm->id ? 'selected' : '' }}>
                                            {{ $farm->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Farm is required.</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="is_active" class="form-select">
                                    <option value="1" selected>Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name<span class="text-danger ms-1">*</span></label>
                                <input type="text" name="name" class="form-control" maxlength="191" required>
                                <div class="invalid-feedback">Name is required.</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone<span class="text-danger ms-1">*</span></label>
                                <input type="text" name="phone" class="form-control" maxlength="191" required>
                                <div class="invalid-feedback">Phone is required.</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email<span class="text-danger ms-1">*</span></label>
                                <input type="email" name="email" class="form-control" maxlength="191" required>
                                <div class="invalid-feedback">Email is required.</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password<span class="text-danger ms-1">*</span></label>
                                <input type="password" name="password" class="form-control" minlength="8" required>
                                <div class="invalid-feedback">Password (min 8) is required.</div>
                            </div>

                            <div class="col-md-12 mb-0">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" name="password_reset_required" id="prr">
                                    <label class="form-check-label" for="prr">
                                        Require password reset on next login
                                    </label>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="ti ti-device-floppy me-2"></i>Create & Assign
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="ti ti-x me-2"></i>Close
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign Modal -->
    <div class="modal fade" id="assignManagerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="assignManagerForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="assignManagerTitle">Assign Manager</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger mb-3">
                            Note: assigning a manager will automatically remove them from any other farm (one-farm rule).
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Manager</label>
                            <select name="manager_id" class="form-select" required>
                                <option value="">-- Select --</option>
                                @foreach($managers as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->email }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="ti ti-device-floppy me-2"></i>Assign Management
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="ti ti-x me-2"></i>Close
                        </button>
                    </div>
                </form>
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
            document.querySelectorAll('.open-assign-modal').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const farmId = this.getAttribute('data-farm-id');
                    const farmName = this.getAttribute('data-farm-name');

                    const form = document.getElementById('assignManagerForm');
                    form.action = '/admin/farms/' + farmId + '/manager';

                    document.getElementById('assignManagerTitle').textContent = 'Assign Manager - ' + farmName;

                    const modal = new bootstrap.Modal(document.getElementById('assignManagerModal'));
                    modal.show();
                });
            });
        });
    </script>
@endpush
