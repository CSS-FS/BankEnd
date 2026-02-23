@extends('layouts.app')

@section('title', 'Countries')

@section('content')
    <div class="content">
        <div class="page-header">
            <div class="add-item d-flex">
                <div class="page-title">
                    <h4 class="fw-bold">Countries</h4>
                    <h6>Manage countries and their ISO codes.</h6>
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
                <a href="javascript:void(0)" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCountryModal">
                    <i class="ti ti-circle-plus me-1"></i>Add Country
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
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table datatable-custom">
                        <thead class="thead-light">
                        <tr>
                            <th class="w-100">Country</th>
                            <th class="text-center">Alpha-2</th>
                            <th class="text-center">Alpha-3</th>
                            <th class="text-center">Create Date</th>
                            <th class="no-sort"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($countries as $c)
                            <tr>
                                <td class="w-100">{{ $c->country }}</td>
                                <td class="text-center text-uppercase fw-semibold">{{ $c->alpha_2_code }}</td>
                                <td class="text-center text-uppercase fw-semibold">{{ $c->alpha_3_code }}</td>
                                <td class="text-center">{{ $c->created_at->format('d-m-Y') }}</td>
                                <td class="action-table-data">
                                    <div class="action-icon d-inline-flex">
                                        <a href="javascript:void(0)"
                                           class="p-2 border rounded me-2 edit-country"
                                           data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           title=""
                                           data-bs-original-title="Edit Country"
                                           data-country-id="{{ $c->id }}"
                                           data-country="{{ $c->country }}"
                                           data-alpha2="{{ $c->alpha_2_code }}"
                                           data-alpha3="{{ $c->alpha_3_code }}">
                                            <i class="ti ti-edit"></i>
                                        </a>

                                        <a href="javascript:void(0);"
                                           data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           title=""
                                           data-bs-original-title="Delete Country"
                                           data-country-id="{{ $c->id }}"
                                           data-country-name="{{ $c->country }}"
                                           class="p-2 open-delete-modal">
                                            <i data-feather="trash-2" class="feather-trash-2"></i>
                                        </a>
                                        <form action="{{ route('countries.destroy', $c->id) }}" method="POST" id="delete{{ $c->id }}">
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

    <!-- Add Country Modal -->
    <div class="modal fade" id="addCountryModal" tabindex="-1" aria-labelledby="addCountryModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('countries.store') }}" class="needs-validation" novalidate method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCountryModalLabel">Add Country</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="mb-3">
                                    <label for="country" class="form-label">Country Name<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" id="country" name="country" required maxlength="150" placeholder="e.g. Pakistan">
                                    <div class="invalid-feedback">Country name is required.</div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="alpha_2_code" class="form-label">Alpha-2 Code<span class="text-danger ms-1">*</span></label>
                                        <input type="text" class="form-control text-uppercase" id="alpha_2_code" name="alpha_2_code"
                                               required maxlength="2" placeholder="e.g. PK">
                                        <div class="form-text">2-letter ISO code (e.g. PK)</div>
                                        <div class="invalid-feedback">A valid 2-letter Alpha-2 code is required.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="alpha_3_code" class="form-label">Alpha-3 Code<span class="text-danger ms-1">*</span></label>
                                        <input type="text" class="form-control text-uppercase" id="alpha_3_code" name="alpha_3_code"
                                               required maxlength="3" placeholder="e.g. PAK">
                                        <div class="form-text">3-letter ISO code (e.g. PAK)</div>
                                        <div class="invalid-feedback">A valid 3-letter Alpha-3 code is required.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="ti ti-device-floppy me-2"></i>Save Country
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="ti ti-x me-2"></i>Close
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Country Modal -->
    <div class="modal fade" id="editCountryModal" tabindex="-1" aria-labelledby="editCountryModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editCountryForm" action="" class="needs-validation" novalidate method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editCountryModalLabel">Edit Country</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <input type="hidden" id="edit-country-id" name="id" value="">
                                <div class="mb-3">
                                    <label for="edit-country" class="form-label">Country Name<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" id="edit-country" name="country" required maxlength="150">
                                    <div class="invalid-feedback">Country name is required.</div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="edit-alpha2" class="form-label">Alpha-2 Code<span class="text-danger ms-1">*</span></label>
                                        <input type="text" class="form-control text-uppercase" id="edit-alpha2" name="alpha_2_code"
                                               required maxlength="2">
                                        <div class="form-text">2-letter ISO code (e.g. PK)</div>
                                        <div class="invalid-feedback">A valid 2-letter Alpha-2 code is required.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="edit-alpha3" class="form-label">Alpha-3 Code<span class="text-danger ms-1">*</span></label>
                                        <input type="text" class="form-control text-uppercase" id="edit-alpha3" name="alpha_3_code"
                                               required maxlength="3">
                                        <div class="form-text">3-letter ISO code (e.g. PAK)</div>
                                        <div class="invalid-feedback">A valid 3-letter Alpha-3 code is required.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="ti ti-device-floppy me-2"></i>Update Country
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="ti ti-x me-2"></i>Close
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
                        <h4 class="fs-20 fw-bold mb-2 mt-1">Delete Country</h4>
                        <p class="mb-0 fs-16" id="delete-modal-message">
                            Are you sure you want to delete this country?
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
            if ($('.datatable-custom').length > 0) {
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
                            next: ' <i class="fa fa-angle-right"></i>',
                            previous: '<i class="fa fa-angle-left"></i> '
                        },
                    },
                    initComplete: (settings, json) => {
                        $('.dataTables_filter').appendTo('#tableSearch');
                        $('.dataTables_filter').appendTo('.search-input');
                    },
                });
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.edit-country').forEach(function(button) {
                button.addEventListener('click', function() {
                    var id      = this.getAttribute('data-country-id');
                    var country = this.getAttribute('data-country');
                    var alpha2  = this.getAttribute('data-alpha2').toUpperCase();
                    var alpha3  = this.getAttribute('data-alpha3').toUpperCase();

                    var form = document.getElementById('editCountryForm');
                    form.action = '/admin/countries/' + id;

                    document.getElementById('edit-country-id').value = id;
                    document.getElementById('edit-country').value    = country;
                    document.getElementById('edit-alpha2').value     = alpha2;
                    document.getElementById('edit-alpha3').value     = alpha3;

                    document.getElementById('editCountryModalLabel').textContent = 'Edit Country — ' + country;

                    var modal = new bootstrap.Modal(document.getElementById('editCountryModal'));
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
                    deleteId = this.getAttribute('data-country-id');
                    const name = this.getAttribute('data-country-name');
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
