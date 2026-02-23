@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="content">
        <div class="page-header">
            <div class="add-item d-flex">
                <div class="page-title">
                    <h4 class="fw-bold">Notifications</h4>
                    <h6>View all your notifications.</h6>
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
                <form action="{{ route('notifications.mark-all-read') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-checks me-1"></i>Mark All as Read
                    </button>
                </form>
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
                    <select id="typeFilter" class="form-select">
                        <option value="">All Types</option>
                        <option value="Report">Report</option>
                        <option value="Alert">Alert</option>
                        <option value="Info">Info</option>
                    </select>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table datatable-custom">
                        <thead class="thead-light">
                        <tr>
                            <th class="text-center no-sort" style="width: 60px;">Status</th>
                            <th class="text-center" style="width: 100px;">Type</th>
                            <th>Notification</th>
                            <th class="text-center" style="width: 160px;">Date</th>
                            <th class="text-center no-sort" style="width: 100px;">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($notifications as $notification)
                            <tr class="{{ $notification->is_read ? '' : 'table-active' }}">
                                <td class="text-center">
                                    @if($notification->is_read)
                                        <span class="badge bg-soft-secondary text-dark border">
                                            <i class="ti ti-check"></i> Read
                                        </span>
                                    @else
                                        <span class="badge bg-soft-primary text-primary border">
                                            <i class="ti ti-point-filled"></i> New
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($notification->type === 'report_submitted')
                                        <span class="badge bg-soft-info text-dark border">
                                            <i class="ti ti-file-text me-1"></i>Report
                                        </span>
                                    @elseif($notification->type === 'device_failure')
                                        <span class="badge bg-soft-danger text-dark border">
                                            <i class="ti ti-alert-triangle me-1"></i>Alert
                                        </span>
                                    @else
                                        <span class="badge bg-soft-secondary text-dark border">
                                            <i class="ti ti-bell me-1"></i>Info
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div>
                                        <h6 class="mb-1">{{ $notification->title }}</h6>
                                        <p class="text-muted small mb-0">{{ $notification->message }}</p>
                                        @if($notification->farm)
                                            <small class="text-muted">
                                                <i class="ti ti-building-farm me-1"></i>{{ $notification->farm->name }}
                                            </small>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    <small class="text-muted d-block">{{ $notification->created_at->format('d-m-Y') }}</small>
                                    <small class="text-muted d-block">{{ $notification->created_at->format('h:i A') }}</small>
                                    <small class="text-primary">{{ $notification->created_at->diffForHumans() }}</small>
                                </td>
                                <td class="text-center action-table-data">
                                    <div class="action-icon d-inline-flex">
                                        @if(!$notification->is_read)
                                            <form action="{{ route('notifications.mark-read', $notification) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit"
                                                        class="d-flex align-items-center bg-danger p-2 border rounded"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title=""
                                                        data-bs-original-title="Mark as Read">
                                                    <i class="ti ti-check"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="ti ti-bell-off fs-40 text-muted mb-3 d-block"></i>
                                    <p class="text-muted mb-0">No notifications yet</p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
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
                    "order": [[3, 'desc']],
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

                $('#typeFilter').on('change', function() {
                    var selected = $(this).val();
                    table.column(1).search(selected).draw();
                });
            }
        });
    </script>
@endpush
