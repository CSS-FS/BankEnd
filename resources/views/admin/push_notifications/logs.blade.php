@extends('layouts.app')

@section('title', 'Notification Logs')
@section('content')
    <div class="content">
        <div class="page-header">
            <div class="add-item d-flex">
                <div class="page-title">
                    <h4 class="fw-bold">Notification Logs</h4>
                    <h6>Outbox delivery status, retries, and errors.</h6>
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
                <a href="{{ route('notifications.logs') }}" class="btn btn-primary">
                    <i class="ti ti-refresh me-1"></i> Refresh
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
                    <select id="logStatusFilter" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending">pending</option>
                        <option value="processing">processing</option>
                        <option value="sent">sent</option>
                        <option value="failed">failed</option>
                    </select>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table datatable-custom">
                        <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Target</th>
                            <th>Title / Body</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Attempts</th>
                            <th class="text-center">Scheduled</th>
                            <th class="text-center">Next Retry</th>
                            <th class="text-center">Sent</th>
                            <th>Error</th>
                            <th class="no-sort"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($items as $row)
                            <tr>
                                <td class="fw-semibold">#{{ $row->id }}</td>

                                <td>
                                    @if($row->target_type === 'topic')
                                        <span class="badge bg-info-subtle text-dark">topic</span>
                                        <div class="text-muted small">{{ $row->target_topic }}</div>
                                    @elseif($row->target_type === 'user')
                                        <span class="badge bg-warning-subtle text-dark">user</span>
                                        <div class="text-muted small">ID: {{ $row->target_id }}</div>
                                    @else
                                        <span class="badge bg-success-subtle text-dark">token</span>
                                        <div class="text-muted small">ID: {{ $row->target_id }}</div>
                                    @endif
                                </td>

                                <td style="min-width: 260px;">
                                    <div class="fw-semibold">{{ $row->title ?? '—' }}</div>
                                    <div class="text-muted">{{ \Illuminate\Support\Str::limit($row->body, 90) }}</div>
                                </td>

                                <td class="text-center">
                                    @php
                                        $status = $row->status;
                                        $badgeClass = match($status) {
                                            'sent' => 'bg-success-transparent text-success',
                                            'failed' => 'bg-danger-transparent text-danger',
                                            'processing' => 'bg-warning-transparent text-warning',
                                            default => 'bg-info-transparent text-info',
                                        };
                                    @endphp
                                    <span class="p-1 pe-2 rounded-1 fs-10 {{ $badgeClass }}">
                                        <i class="ti ti-circle-filled me-1 fs-11"></i>{{ $status }}
                                    </span>
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-dark">
                                        {{ $row->attempts }}/{{ $row->max_attempts }}
                                    </span>
                                </td>

                                <td class="text-center">
                                    {{ $row->scheduled_at ? $row->scheduled_at->format('d-m-Y H:i') : '—' }}
                                </td>

                                <td class="text-center">
                                    {{ $row->next_retry_at ? $row->next_retry_at->format('d-m-Y H:i') : '—' }}
                                </td>

                                <td class="text-center">
                                    {{ $row->sent_at ? $row->sent_at->format('d-m-Y H:i') : '—' }}
                                </td>

                                <td class="text-muted">{{ Str::limit($row->last_error, 90) }}</td>

                                <td class="action-table-data">
                                    <div class="action-icon d-inline-flex">
                                        <a href="javascript:void(0)"
                                           class="me-2 d-flex align-items-center p-2 border rounded view-payload"
                                           data-bs-toggle="tooltip"
                                           data-bs-placement="top"
                                           data-bs-original-title="View Payload"
                                           data-id="{{ $row->id }}"
                                           data-title="{{ e($row->title ?? '') }}"
                                           data-body="{{ e($row->body ?? '') }}"
                                           data-json='@json($row->data)'>
                                            <i class="ti ti-eye"></i>
                                        </a>
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

    {{-- Payload Modal --}}
    <div class="modal fade" id="payloadModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="payloadModalLabel">Payload</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-2">
                        <div class="fw-semibold" id="payloadTitle"></div>
                        <div class="text-muted" id="payloadBody"></div>
                    </div>

                    <label class="form-label">Data (JSON)</label>
                    <pre class="p-3 rounded bg-light border small mb-0" style="max-height: 320px; overflow:auto;"
                         id="payloadJson">{}</pre>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="ti ti-x me-2"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(function () {
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

                $('#logStatusFilter').on('change', function () {
                    table.column(3).search(this.value).draw();
                });
            }

            // View payload modal
            document.querySelectorAll('.view-payload').forEach(btn => {
                btn.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    const title = this.getAttribute('data-title');
                    const body = this.getAttribute('data-body');
                    const json = this.getAttribute('data-json');

                    document.getElementById('payloadModalLabel').textContent = 'Payload - #' + id;
                    document.getElementById('payloadTitle').textContent = title || '—';
                    document.getElementById('payloadBody').textContent = body || '—';

                    try {
                        const parsed = JSON.parse(json || '{}');
                        document.getElementById('payloadJson').textContent = JSON.stringify(parsed, null, 2);
                    } catch (e) {
                        document.getElementById('payloadJson').textContent = json || '{}';
                    }

                    new bootstrap.Modal(document.getElementById('payloadModal')).show();
                });
            });
        });
    </script>
@endpush
