@extends('layouts.app')

@section('title', 'Push Topics')
@section('content')
    <div class="content">
        <div class="page-header">
            <div class="add-item d-flex">
                <div class="page-title">
                    <h4 class="fw-bold">Push Notification Topics</h4>
                    <h6>Create and manage topics for broadcast notifications.</h6>
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
                @can('topics.manage')
                    <a href="javascript:void(0)" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createTopicModal">
                        <i class="ti ti-plus me-1"></i> Add Topic
                    </a>
                @endcan
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
                    <select id="activeFilter" class="form-select">
                        <option value="">All</option>
                        <option value="Active">Active</option>
                        <option value="Disabled">Disabled</option>
                    </select>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table datatable-custom">
                        <thead class="thead-light">
                        <tr>
                            <th>Title</th>
                            <th>Topic Name</th>
                            <th class="text-center">Status</th>
                            <th>Description</th>
                            <th class="text-center">Created</th>
                            <th class="no-sort"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($topics as $topic)
                            <tr>
                                <td class="fw-semibold">{{ $topic->title }}</td>
                                <td>
                                    <span class="badge bg-info-subtle text-dark">{{ $topic->name }}</span>
                                </td>
                                <td class="text-center">
                                    @if($topic->is_active)
                                        <span class="p-1 pe-2 rounded-1 text-success bg-success-transparent fs-10">
                                            <i class="ti ti-check me-1 fs-11"></i>Active
                                        </span>
                                    @else
                                        <span class="p-1 pe-2 rounded-1 text-danger bg-danger-transparent fs-10">
                                            <i class="ti ti-x me-1 fs-11"></i>Disabled
                                        </span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ Str::limit($topic->description, 80) }}</td>
                                <td class="text-center">{{ optional($topic->created_at)->format('d-m-Y') }}</td>
                                <td class="action-table-data float-end">
                                    @can('topics.manage')
                                        <div class="action-icon d-inline-flex">
                                            <a href="javascript:void(0)"
                                               class="me-2 d-flex align-items-center p-2 border rounded edit-topic"
                                               data-bs-toggle="tooltip"
                                               data-bs-placement="top"
                                               data-bs-original-title="Edit Topic"
                                               data-topic-id="{{ $topic->id }}"
                                               data-topic-title="{{ e($topic->title) }}"
                                               data-topic-name="{{ e($topic->name) }}"
                                               data-topic-description="{{ e($topic->description) }}"
                                               data-topic-active="{{ $topic->is_active ? 1 : 0 }}">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                        </div>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    @if($topics->isEmpty())
                        <div class="p-4 text-center text-muted">
                            No topics found.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Create Topic Modal --}}
    @can('topics.manage')
        <div class="modal fade" id="createTopicModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('topics.store') }}" method="POST" class="needs-validation" novalidate>
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Create Topic</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <label class="form-label">Topic Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control"
                                           placeholder="e.g. farm-1, alerts, shed_2"
                                           required>
                                    <div class="form-text">Allowed: lowercase letters, digits, dash (-), underscore (_), dot (.)</div>
                                    <div class="invalid-feedback">Topic name is required.</div>
                                </div>

                                <div class="col-lg-6">
                                    <label class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" placeholder="e.g. Farm #1 Alerts" required>
                                    <div class="invalid-feedback">Title is required.</div>
                                </div>

                                <div class="col-lg-12">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="3"
                                              placeholder="Short description for admins/operators..."></textarea>
                                </div>

                                <div class="col-lg-12">
                                    <div class="status-toggle modal-status d-flex align-items-center">
                                        <input type="checkbox" id="topicActiveCreate" name="is_active" value="1" class="check" checked>
                                        <label for="topicActiveCreate" class="checktoggle me-2"></label>
                                        <span class="status-label">Active</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success me-2">
                                <i class="ti ti-device-floppy me-2"></i>Save Topic
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="ti ti-x me-2"></i>Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Edit Topic Modal --}}
        <div class="modal fade" id="editTopicModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form id="editTopicForm" method="POST" class="needs-validation" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="modal-header">
                            <h5 class="modal-title" id="editTopicModalLabel">Edit Topic</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <label class="form-label">Topic Name</label>
                                    <input type="text" id="edit-topic-name" class="form-control" disabled>
                                    <div class="form-text">Topic name cannot be changed.</div>
                                </div>

                                <div class="col-lg-6">
                                    <label class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" id="edit-topic-title" name="title" class="form-control" required>
                                    <div class="invalid-feedback">Title is required.</div>
                                </div>

                                <div class="col-lg-12">
                                    <label class="form-label">Description</label>
                                    <textarea id="edit-topic-description" name="description" class="form-control" rows="3"></textarea>
                                </div>

                                <div class="col-lg-12">
                                    <div class="status-toggle modal-status d-flex align-items-center">
                                        <input type="checkbox" id="topicActiveEdit" name="is_active" value="1" class="check">
                                        <label for="topicActiveEdit" class="checktoggle me-2"></label>
                                        <span class="status-label">Active</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success me-2">
                                <i class="ti ti-device-floppy me-2"></i>Update Topic
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="ti ti-x me-2"></i>Close
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection

@push('js')
    <script>
        $(function () {
            // Datatable
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
                            next: ' <i class=" fa fa-angle-right"></i>',
                            previous: '<i class="fa fa-angle-left"></i> '
                        },
                    },
                    initComplete: (settings, json) => {
                        $('.dataTables_filter').appendTo('.search-input');
                    },
                    columnDefs: [{ targets: 'no-sort', orderable: false }],
                });

                // Filter by status text (Active/Disabled)
                $('#activeFilter').on('change', function () {
                    table.column(2).search(this.value).draw();
                });
            }

            // Edit Topic Modal
            document.querySelectorAll('.edit-topic').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var id = this.getAttribute('data-topic-id');
                    var title = this.getAttribute('data-topic-title');
                    var name = this.getAttribute('data-topic-name');
                    var description = this.getAttribute('data-topic-description');
                    var active = this.getAttribute('data-topic-active') === '1';

                    var form = document.getElementById('editTopicForm');
                    form.action = "{{ url('/admin/topics') }}/" + id;

                    document.getElementById('edit-topic-title').value = title;
                    document.getElementById('edit-topic-name').value = name;
                    document.getElementById('edit-topic-description').value = description || '';
                    document.getElementById('topicActiveEdit').checked = active;

                    document.getElementById('editTopicModalLabel').textContent = "Edit Topic - " + title;

                    var modal = new bootstrap.Modal(document.getElementById('editTopicModal'));
                    modal.show();
                });
            });
        });
    </script>
@endpush
