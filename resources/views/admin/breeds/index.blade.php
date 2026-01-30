@extends('layouts.app')

@section('title', 'Breeds')

@section('content')
    <div class="content">
        <div class="page-header">
            <div class="add-item d-flex">
                <div class="page-title">
                    <h4 class="fw-bold">Chicken Breeds</h4>
                    <h6>Manage data for chicken breeds.</h6>
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
                <a href="javascript:void(0)" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBreedModal">
                    <i class="ti ti-circle-plus me-1"></i>Add Breed
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
                    <select id="statusFilter" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $row)
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
                            <th class="w-100">Breed</th>
                            <th class="text-center">Category</th>
                            <th class="text-center">Flock Count</th>
                            <th class="text-center">Create Date</th>
                            <th class="no-sort"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($breeds as $breed)
                            <tr>
                                <td>
                                    {{ ucfirst($breed->name) }}
                                </td>
                                <td class="text-center">{{ ucfirst($breed->category) }}</td>
                                <td class="text-center">{{ $breed->flocks_count }}</td>
                                <td class="text-center">
                                   {{ $breed->created_at->format('d-m-Y') }}
                                </td>
                                <td class="action-table-data">
                                    <div class="action-icon d-inline-flex">
                                        <a href="javascript:void(0)"
                                           class="p-2 border rounded me-2 edit-breed"
                                           data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           title=""
                                           data-bs-original-title="Edit Breed"
                                           data-breed-id="{{ $breed->id }}"
                                           data-breed-name="{{ $breed->name }}">
                                            <i class="ti ti-edit"></i>
                                        </a>

                                        <a href="javascript:void(0);"
                                           data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           title=""
                                           data-bs-original-title="Delete Breed"
                                           data-breed-id="{{ $breed->id }}"
                                           data-breed-name="{{ $breed->name }}"
                                           class="p-2 open-delete-modal">
                                            <i data-feather="trash-2" class="feather-trash-2"></i>
                                        </a>
                                        <form action="{{ route('breeding.destroy', $breed->id) }}" method="POST" id="delete{{ $breed->id }}">
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

    <!-- Add Breed Modal -->
    <div class="modal fade" id="addBreedModal" tabindex="-1" aria-labelledby="addBreedModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('breeding.store') }}" class="needs-validation" novalidate method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addBreedModalLabel">Add Breed</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Breed Name<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                    <div class="invalid-feedback">
                                        Breed name is required.
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="name" class="form-label">Category<span class="text-danger ms-1">*</span></label>
                                    <select class="select2" id="category" name="category" required>
                                        @foreach($categories as $row)
                                            <option value="{{ $row }}">{{ ucfirst($row) }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">
                                        Bareed category is required.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success me-2">Save Breed</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Breed Modal -->
    <div class="modal fade" id="editBreedModal" tabindex="-1" aria-labelledby="editBreedModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editBreedForm" action="" class="needs-validation" novalidate method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editBreedModalLabel">Edit Breed</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <input type="hidden" id="edit-breed-id" name="id" value="">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Breed Name<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" id="edit-name" name="name" required>
                                    <div class="invalid-feedback">
                                        Breed name is required.
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="name" class="form-label">Category<span class="text-danger ms-1">*</span></label>
                                    <select class="select2" id="edit-category" name="category" required>
                                        @foreach($categories as $row)
                                            <option value="{{ $row }}">{{ ucfirst($row) }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">
                                        Breed category is required.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success me-2">Update Breed</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
                        <h4 class="fs-20 fw-bold mb-2 mt-1">Delete Breed</h4>
                        <p class="mb-0 fs-16" id="delete-modal-message">
                            Are you sure you want to delete this breed data?
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

                $('#statusFilter').on('change', function() {
                    var selected = $(this).val();
                    table.column(1).search(selected).draw();
                });
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.edit-breed').forEach(function(button) {
                button.addEventListener('click', function() {
                    var breedId = this.getAttribute('data-breed-id');
                    var breedName = this.getAttribute('data-breed-name');

                    var form = document.getElementById('editBreedForm');
                    form.action = '/admin/breeding/' + breedId;

                    // Set hidden and visible values
                    document.getElementById('edit-breed-id').value = breedId;
                    document.getElementById('edit-name').value = breedName;

                    document.getElementById('editBreedModalLabel').textContent = "Edit Breed - " + breedName;

                    // Show the modal
                    var modal = new bootstrap.Modal(document.getElementById('editBreedModal'));
                    modal.show();
                });
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let deleteId = null;

            document.querySelectorAll('.open-delete-modal').forEach(function(el) {
                el.addEventListener('click', function() {
                    deleteId = this.getAttribute('data-breed-id');
                    const breedName = this.getAttribute('data-breed-name');
                    document.getElementById('delete-modal-message').textContent =
                        `Are you sure you want to delete "${breedName}" data?`;

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
