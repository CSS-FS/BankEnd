@extends('layouts.app')

@section('title', 'Device Tokens')
@section('content')
    <div class="content">
        <div class="page-header">
            <div class="add-item d-flex">
                <div class="page-title">
                    <h4 class="fw-bold">Device Tokens</h4>
                    <h6>Manage registered devices and send targeted notifications.</h6>
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
                @can('notifications.send')
                    <a href="javascript:void(0)" class="btn btn-primary"
                       data-bs-toggle="modal" data-bs-target="#sendNotificationModal"
                       id="openSendGlobal">
                        <i class="ti ti-bell me-1"></i> Send Notification
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
                    <select id="platformFilter" class="form-select me-2">
                        <option value="">All Platforms</option>
                        <option value="android">Android</option>
                        <option value="ios">iOS</option>
                        <option value="web">Web</option>
                    </select>
                    <select id="statusFilter" class="form-select">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Revoked">Revoked</option>
                    </select>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table datatable-custom">
                        <thead class="thead-light">
                        <tr>
                            <th>User</th>
                            <th>Farm</th>
                            <th class="text-center">Platform</th>
                            <th>Device ID</th>
                            <th>Token</th>
                            <th class="text-center">Last Updated</th>
                            <th class="text-center">Status</th>
                            <th>Last Error</th>
                            <th class="no-sort"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($tokens as $t)
                            <tr>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-semibold">{{ optional($t->user)->name ?? '—' }}</span>
                                        <small class="text-muted">#{{ $t->user_id }}</small>
                                    </div>
                                </td>

                                <td>
                                    @if($t->farm)
                                        <div class="d-flex flex-column">
                                            <span class="fw-semibold">{{ $t->farm->name }}</span>
                                            <small class="text-muted">#{{ $t->farm_id }}</small>
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="text-center text-lowercase">
                                    <span class="badge bg-success-subtle text-dark">{{ $t->platform }}</span>
                                </td>

                                <td>
                                    <span class="badge bg-info-subtle text-dark">{{ $t->device_id ?? '—' }}</span>
                                </td>

                                <td style="max-width: 260px;">
                                    <span class="text-muted">{{ \Illuminate\Support\Str::limit($t->token, 40) }}</span>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary ms-2 copy-token"
                                            data-token="{{ $t->token }}">
                                        Copy
                                    </button>
                                </td>

                                <td class="text-center">
                                    {{ $t->last_updated_at ? $t->last_updated_at->format('d-m-Y H:i') : '—' }}
                                </td>

                                <td class="text-center">
                                    @if(is_null($t->revoked_at))
                                        <span class="p-1 pe-2 rounded-1 text-success bg-success-transparent fs-10">
                                            <i class="ti ti-check me-1 fs-11"></i>Active
                                        </span>
                                    @else
                                        <span class="p-1 pe-2 rounded-1 text-danger bg-danger-transparent fs-10">
                                            <i class="ti ti-ban me-1 fs-11"></i>Revoked
                                        </span>
                                    @endif
                                </td>

                                <td class="text-muted">{{ \Illuminate\Support\Str::limit($t->last_error, 80) }}</td>

                                <td class="action-table-data">
                                    <div class="action-icon d-inline-flex">
                                        @can('notifications.send')
                                            <a href="javascript:void(0)"
                                               class="me-2 d-flex align-items-center p-2 border rounded send-to-token"
                                               data-bs-toggle="tooltip"
                                               data-bs-placement="top"
                                               data-bs-original-title="Send to this device"
                                               data-target-type="token"
                                               data-device-token-id="{{ $t->id }}"
                                               data-user-id="{{ $t->user_id }}"
                                               data-label="Device #{{ $t->id }} ({{ $t->platform }})">
                                                <i class="ti ti-bell"></i>
                                            </a>

                                            <a href="javascript:void(0)"
                                               class="me-2 d-flex align-items-center p-2 border rounded send-to-user"
                                               data-bs-toggle="tooltip"
                                               data-bs-placement="top"
                                               data-bs-original-title="Send to this user (all devices)"
                                               data-target-type="user"
                                               data-user-id="{{ $t->user_id }}"
                                               data-label="User #{{ $t->user_id }} (All Devices)">
                                                <i class="ti ti-users"></i>
                                            </a>
                                        @endcan

                                        @can('device_tokens.manage')
                                            @if(is_null($t->revoked_at))
                                                <a href="javascript:void(0)"
                                                   class="p-2 d-flex align-items-center border rounded open-revoke-modal"
                                                   data-bs-toggle="tooltip"
                                                   data-bs-placement="top"
                                                   data-bs-original-title="Revoke token"
                                                   data-token-id="{{ $t->id }}"
                                                   data-token-label="Device #{{ $t->id }} ({{ $t->platform }})">
                                                    <i class="ti ti-trash"></i>
                                                </a>

                                                <form action="{{ route('device_tokens.revoke', $t->id) }}"
                                                      method="POST"
                                                      id="revoke{{ $t->id }}">
                                                    @csrf
                                                </form>
                                            @endif
                                        @endcan
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

    {{-- Revoke Modal --}}
    @can('device_tokens.manage')
        <div class="modal fade" id="revoke-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="page-wrapper-new p-0">
                        <div class="p-5 px-3 text-center">
                            <span class="rounded-circle d-inline-flex p-2 bg-danger-transparent mb-2">
                                <i class="ti ti-trash fs-24 text-danger"></i>
                            </span>
                            <h4 class="fs-20 fw-bold mb-2 mt-1">Revoke Token</h4>
                            <p class="mb-0 fs-16" id="revoke-modal-message">
                                Are you sure you want to revoke this token?
                            </p>
                            <div class="modal-footer-btn mt-3 d-flex justify-content-center">
                                <button type="button" class="btn btn-secondary fs-13 fw-medium p-2 px-3 me-2" data-bs-dismiss="modal">
                                    Cancel
                                </button>
                                <button type="button" class="btn btn-danger fs-13 fw-medium p-2 px-3" id="confirm-revoke-btn">
                                    Yes Revoke
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    {{-- Send Notification Modal --}}
    @can('notifications.send')
        <div class="modal fade" id="sendNotificationModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('notifications.enqueue') }}" method="POST" class="needs-validation" novalidate>
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="sendNotificationModalLabel">Send Notification</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="alert alert-info d-flex align-items-center justify-content-between" role="alert">
                                <div>
                                    <i class="ti ti-info-circle me-2"></i>
                                    Target: <span class="fw-semibold" id="notifyTargetLabel">—</span>
                                </div>
                                <small class="text-muted">This will queue a notification (outbox).</small>
                            </div>

                            <div class="row g-3">
                                <div class="col-lg-4">
                                    <label class="form-label">Target Type <span class="text-danger">*</span></label>
                                    <select class="form-select" name="target" id="notifyTargetType" required>
                                        <option value="">Select</option>
                                        <option value="token">Single Device</option>
                                        <option value="user">User (All Devices)</option>
                                        <option value="topic">Topic</option>
                                    </select>
                                    <div class="invalid-feedback">Target type is required.</div>
                                </div>

                                <div class="col-lg-4" id="notifyTokenWrap" style="display:none;">
                                    <label class="form-label">Device Token ID <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="device_token_id" id="notifyDeviceTokenId" min="1">
                                </div>

                                <div class="col-lg-4" id="notifyUserWrap" style="display:none;">
                                    <label class="form-label">User ID <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="user_id" id="notifyUserId" min="1">
                                </div>

                                <div class="col-lg-4" id="notifyTopicWrap" style="display:none;">
                                    <label class="form-label">Topic Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="topic" id="notifyTopic" placeholder="e.g. alerts">
                                </div>

                                <div class="col-lg-6">
                                    <label class="form-label">Title</label>
                                    <input type="text" class="form-control" name="title" placeholder="e.g. Alert">
                                </div>

                                <div class="col-lg-6">
                                    <label class="form-label">Schedule At</label>
                                    <input type="datetime-local" class="form-control" name="scheduled_at">
                                    <div class="form-text">Leave empty to send immediately.</div>
                                </div>

                                <div class="col-lg-12">
                                    <label class="form-label">Body</label>
                                    <textarea class="form-control" name="body" rows="3" placeholder="Short message..."></textarea>
                                </div>

                                <div class="col-lg-12">
                                    <label class="form-label">Payload (JSON)</label>
                                    <textarea class="form-control" name="payload" rows="5"
                                              placeholder='{"type":"flock","id":"123","route":"/flocks/123"}' required></textarea>
                                    <div class="form-text">Data values will be stringified for FCM. Keep JSON valid.</div>
                                </div>

                                <div class="col-lg-12" id="fanoutWrap" style="display:none;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="fanoutCheck" name="fanout">
                                        <label class="form-check-label" for="fanoutCheck">
                                            Fan-out per user tokens (create one job per device for faster delivery)
                                        </label>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success me-2">
                                <i class="ti ti-device-floppy me-2"></i>Queue Notification
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="ti ti-x me-2"></i>Cancel
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

                // Filters (column indices: 0=User, 1=Farm, 2=Platform, 3=DeviceID, 4=Token, 5=LastUpdated, 6=Status)
                $('#platformFilter').on('change', function () {
                    table.column(2).search(this.value).draw();
                });
                $('#statusFilter').on('change', function () {
                    table.column(6).search(this.value).draw();
                });
            }

            // Copy token
            document.querySelectorAll('.copy-token').forEach(btn => {
                btn.addEventListener('click', async () => {
                    try {
                        await navigator.clipboard.writeText(btn.getAttribute('data-token'));
                        btn.textContent = 'Copied';
                        setTimeout(() => btn.textContent = 'Copy', 900);
                    } catch (e) {
                        alert('Copy failed. Your browser may block clipboard access.');
                    }
                });
            });

            // Revoke modal
            let revokeId = null;
            document.querySelectorAll('.open-revoke-modal').forEach(el => {
                el.addEventListener('click', function () {
                    revokeId = this.getAttribute('data-token-id');
                    const label = this.getAttribute('data-token-label');
                    document.getElementById('revoke-modal-message').textContent =
                        `Are you sure you want to revoke "${label}"?`;

                    new bootstrap.Modal(document.getElementById('revoke-modal')).show();
                });
            });

            const revokeBtn = document.getElementById('confirm-revoke-btn');
            if (revokeBtn) {
                revokeBtn.addEventListener('click', function () {
                    if (revokeId) {
                        document.getElementById('revoke' + revokeId).submit();
                    }
                });
            }

            // Send notification modal helper
            function updateTargetUI(type) {
                $('#notifyTokenWrap,#notifyUserWrap,#notifyTopicWrap').hide();
                $('#fanoutWrap').hide();
                $('#notifyDeviceTokenId,#notifyUserId,#notifyTopic').prop('required', false);

                if (type === 'token') { $('#notifyTokenWrap').show(); $('#notifyDeviceTokenId').prop('required', true); }
                if (type === 'user')  { $('#notifyUserWrap').show();  $('#fanoutWrap').show(); $('#notifyUserId').prop('required', true); }
                if (type === 'topic') { $('#notifyTopicWrap').show(); $('#notifyTopic').prop('required', true); }
            }

            $('#notifyTargetType').on('change', function () {
                updateTargetUI(this.value);

                // Optional: auto label if user chooses target type manually
                const v = this.value;
                if (v === 'token') $('#notifyTargetLabel').text('Manual - Single Device');
                if (v === 'user')  $('#notifyTargetLabel').text('Manual - User (All Devices)');
                if (v === 'topic') $('#notifyTargetLabel').text('Manual - Topic Broadcast');
                if(v === '') $('#notifyTargetLabel').text('—');
            });

            $('#notifyTargetType').on('change', function () {
                updateTargetUI(this.value);
            });

            // Open global send modal
            $('#openSendGlobal').on('click', function () {
                $('#sendNotificationModalLabel').text('Send Notification');
                $('#notifyTargetLabel').text('Choose target type');
                $('#notifyTargetType').val('').trigger('change');
                $('#notifyDeviceTokenId').val('');
                $('#notifyUserId').val('');
                $('#notifyTopic').val('');
            });

            // Send to token button
            document.querySelectorAll('.send-to-token').forEach(btn => {
                btn.addEventListener('click', function () {
                    const deviceTokenId = this.getAttribute('data-device-token-id');
                    const label = this.getAttribute('data-label');

                    $('#sendNotificationModalLabel').text('Send Notification');
                    $('#notifyTargetType').val('token').trigger('change');
                    $('#notifyDeviceTokenId').val(deviceTokenId);
                    $('#notifyTargetLabel').text(label);

                    new bootstrap.Modal(document.getElementById('sendNotificationModal')).show();
                });
            });

            // Send to user button
            document.querySelectorAll('.send-to-user').forEach(btn => {
                btn.addEventListener('click', function () {
                    const userId = this.getAttribute('data-user-id');
                    const label = this.getAttribute('data-label');

                    $('#sendNotificationModalLabel').text('Send Notification');
                    $('#notifyTargetType').val('user').trigger('change');
                    $('#notifyUserId').val(userId);
                    $('#notifyTargetLabel').text(label);

                    new bootstrap.Modal(document.getElementById('sendNotificationModal')).show();
                });
            });

        });
    </script>
@endpush
