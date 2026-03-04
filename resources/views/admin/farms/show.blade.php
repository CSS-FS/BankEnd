@extends('layouts.app')

@section('title', $farm->name . ' — Farm Detail')

@section('content')
<div class="content">

    {{-- Page Header --}}
    <div class="page-header">
        <div class="add-item d-flex">
            <div class="page-title">
                <h4 class="fw-bold">
                    <i class="ti ti-building-farm me-2 text-primary"></i>{{ ucwords($farm->name) }}
                </h4>
                <h6>Farm detail — owner, managers, sheds & flocks.</h6>
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
            <a href="{{ route('admin.farms.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-left me-1"></i>Back to Farms
            </a>
        </div>
    </div>

    {{-- Row 1: Farm Info + Owner --}}
    <div class="row g-3 mb-3">

        {{-- Farm Information --}}
        <div class="col-lg-8">
            <div class="card h-100 mb-0">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-info-circle me-2 text-primary"></i>Farm Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <p class="text-muted small mb-1">Farm Name</p>
                            <p class="fw-semibold mb-0">{{ ucwords($farm->name) }}</p>
                        </div>
                        <div class="col-sm-6">
                            <p class="text-muted small mb-1">Country</p>
                            <p class="fw-semibold mb-0">{{ $farm->country ?? '—' }}</p>
                        </div>
                        <div class="col-sm-4">
                            <p class="text-muted small mb-1">Province</p>
                            <p class="fw-semibold mb-0">{{ $farm->province?->name ?? '—' }}</p>
                        </div>
                        <div class="col-sm-4">
                            <p class="text-muted small mb-1">District</p>
                            <p class="fw-semibold mb-0">{{ $farm->district?->name ?? '—' }}</p>
                        </div>
                        <div class="col-sm-4">
                            <p class="text-muted small mb-1">City</p>
                            <p class="fw-semibold mb-0">{{ $farm->city?->name ?? '—' }}</p>
                        </div>
                        <div class="col-sm-12">
                            <p class="text-muted small mb-1">Address</p>
                            <p class="fw-semibold mb-0">{{ ucfirst($farm->address) }}</p>
                        </div>
                        <div class="col-sm-6">
                            <p class="text-muted small mb-1">Phone Number</p>
                            <p class="fw-semibold mb-0">{{ $farm->phone_number ?? '—' }}</p>
                        </div>
                        <div class="col-sm-6">
                            <p class="text-muted small mb-1">Contact Person</p>
                            <p class="fw-semibold mb-0">{{ $farm->contact_person ? ucwords($farm->contact_person) : '—' }}</p>
                        </div>
                        <div class="col-sm-6">
                            <p class="text-muted small mb-1">Alerts</p>
                            @if($farm->alerts)
                                <span class="badge bg-success"><i class="ti ti-check me-1"></i>Enabled</span>
                            @else
                                <span class="badge bg-secondary"><i class="ti ti-x me-1"></i>Disabled</span>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <p class="text-muted small mb-1">Notifications</p>
                            @if($farm->notifications)
                                <span class="badge bg-success"><i class="ti ti-check me-1"></i>Enabled</span>
                            @else
                                <span class="badge bg-secondary"><i class="ti ti-x me-1"></i>Disabled</span>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <p class="text-muted small mb-1">Registered On</p>
                            <p class="fw-semibold mb-0">{{ $farm->created_at->format('d M Y') }}</p>
                        </div>
                        <div class="col-sm-6">
                            <p class="text-muted small mb-1">Last Updated</p>
                            <p class="fw-semibold mb-0">{{ $farm->updated_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Owner Info --}}
        <div class="col-lg-4">
            <div class="card h-100 mb-0">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-user-circle me-2 text-success"></i>Farm Owner
                    </h5>
                </div>
                <div class="card-body">
                    @if($farm->owner)
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar avatar-lg bg-soft-success text-success rounded-circle me-3 d-flex align-items-center justify-content-center fs-20 fw-bold"
                                  style="width:52px;height:52px;">
                                {{ strtoupper(substr($farm->owner->name, 0, 1)) }}
                            </span>
                            <div>
                                <p class="fw-bold mb-0">{{ ucwords($farm->owner->name) }}</p>
                                <small class="text-muted">Owner</small>
                            </div>
                        </div>
                        <div class="border-top pt-3">
                            <p class="text-muted small mb-1">Email</p>
                            <p class="fw-semibold small mb-2">{{ $farm->owner->email }}</p>
                            <p class="text-muted small mb-1">Phone</p>
                            <p class="fw-semibold small mb-2">{{ $farm->owner->phone ?? '—' }}</p>
                            <p class="text-muted small mb-1">Account Status</p>
                            @if($farm->owner->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('clients.show', $farm->owner->id) }}"
                               class="btn btn-sm btn-outline-primary w-100">
                                <i class="ti ti-external-link me-1"></i>View Owner Profile
                            </a>
                        </div>
                    @else
                        <div class="text-center py-3">
                            <i class="ti ti-user-off fs-30 text-muted d-block mb-2"></i>
                            <p class="text-muted small">No owner assigned</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Row 2: Managers + Stats --}}
    <div class="row g-3 mb-3">

        {{-- Managers --}}
        <div class="col-lg-4">
            <div class="card h-100 mb-0">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-users me-2 text-warning"></i>Farm Managers
                    </h5>
                    <span class="badge bg-soft-warning text-dark">{{ $farm->managers->count() }}</span>
                </div>
                <div class="card-body p-0">
                    @forelse($farm->managers as $manager)
                        <div class="d-flex align-items-center p-3 border-bottom">
                            <span class="avatar bg-soft-warning text-warning rounded-circle me-3 d-flex align-items-center justify-content-center fw-bold"
                                  style="width:38px;height:38px;min-width:38px;">
                                {{ strtoupper(substr($manager->name, 0, 1)) }}
                            </span>
                            <div class="flex-grow-1 overflow-hidden">
                                <p class="fw-semibold mb-0 small text-truncate">{{ ucwords($manager->name) }}</p>
                                <small class="text-muted text-truncate d-block">{{ $manager->email }}</small>
                                @if($manager->pivot?->link_date)
                                    <small class="text-muted">Since {{ \Carbon\Carbon::parse($manager->pivot->link_date)->format('d M Y') }}</small>
                                @endif
                            </div>
                            <a href="{{ route('clients.show', $manager->id) }}"
                               class="ms-2 p-1 border rounded text-primary"
                               data-bs-toggle="tooltip" title="View Profile">
                                <i class="ti ti-external-link fs-14"></i>
                            </a>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="ti ti-user-off fs-30 text-muted d-block mb-2"></i>
                            <p class="text-muted small mb-0">No managers assigned</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="col-lg-8">
            <div class="row g-3 h-100">
                <div class="col-sm-4">
                    <div class="card mb-0 h-100">
                        <div class="card-body text-center">
                            <div class="rounded-circle bg-soft-primary d-inline-flex align-items-center justify-content-center mb-2"
                                 style="width:48px;height:48px;">
                                <i class="ti ti-home fs-22 text-primary"></i>
                            </div>
                            <h3 class="fw-bold mb-0">{{ $farm->sheds->count() }}</h3>
                            <p class="text-muted small mb-0">Total Sheds</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card mb-0 h-100">
                        <div class="card-body text-center">
                            <div class="rounded-circle bg-soft-success d-inline-flex align-items-center justify-content-center mb-2"
                                 style="width:48px;height:48px;">
                                <i class="ti ti-feather fs-22 text-success"></i>
                            </div>
                            <h3 class="fw-bold mb-0">
                                {{ $farm->sheds->filter(fn($s) => $s->latestFlock !== null)->count() }}
                            </h3>
                            <p class="text-muted small mb-0">Active Flocks</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card mb-0 h-100">
                        <div class="card-body text-center">
                            <div class="rounded-circle bg-soft-info d-inline-flex align-items-center justify-content-center mb-2"
                                 style="width:48px;height:48px;">
                                <i class="ti ti-device-desktop fs-22 text-info"></i>
                            </div>
                            <h3 class="fw-bold mb-0">
                                {{ $farm->sheds->sum(fn($s) => $s->devices->count()) }}
                            </h3>
                            <p class="text-muted small mb-0">IoT Devices</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card mb-0 h-100">
                        <div class="card-body text-center">
                            <div class="rounded-circle bg-soft-warning d-inline-flex align-items-center justify-content-center mb-2"
                                 style="width:48px;height:48px;">
                                <i class="ti ti-users fs-22 text-warning"></i>
                            </div>
                            <h3 class="fw-bold mb-0">{{ $farm->managers->count() }}</h3>
                            <p class="text-muted small mb-0">Managers</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card mb-0 h-100">
                        <div class="card-body text-center">
                            <div class="rounded-circle bg-soft-danger d-inline-flex align-items-center justify-content-center mb-2"
                                 style="width:48px;height:48px;">
                                <i class="ti ti-calculator fs-22 text-danger"></i>
                            </div>
                            <h3 class="fw-bold mb-0">
                                {{ number_format($farm->sheds->sum('capacity')) }}
                            </h3>
                            <p class="text-muted small mb-0">Total Capacity</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 3: Sheds Table --}}
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">
                <i class="ti ti-home me-2 text-info"></i>Sheds & Flocks
            </h5>
            <span class="badge bg-soft-info text-dark">{{ $farm->sheds->count() }} sheds</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Shed Name</th>
                            <th class="text-center">Type</th>
                            <th class="text-center">Capacity</th>
                            <th>Current Flock</th>
                            <th class="text-center">Flock Age</th>
                            <th class="text-center">Start Count</th>
                            <th class="text-center">IoT Devices</th>
                            <th>Past Flocks</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($farm->sheds as $shed)
                        <tr>
                            <td class="fw-semibold">{{ $shed->name }}</td>
                            <td class="text-center">
                                <span class="badge bg-soft-secondary text-dark">{{ ucfirst($shed->type ?? '—') }}</span>
                            </td>
                            <td class="text-center">{{ number_format($shed->capacity) }}</td>
                            <td>
                                @if($shed->latestFlock)
                                    <span class="fw-semibold small">{{ $shed->latestFlock->name }}</span>
                                    <div class="text-muted" style="font-size:11px;">
                                        {{ $shed->latestFlock->breed?->name ?? '—' }}
                                        · Started {{ $shed->latestFlock->start_date->format('d M Y') }}
                                    </div>
                                @else
                                    <span class="badge bg-soft-danger text-dark">No Active Flock</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($shed->latestFlock)
                                    <span class="badge bg-soft-info text-dark">{{ $shed->latestFlock->age }} days</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($shed->latestFlock)
                                    {{ number_format($shed->latestFlock->chicken_count) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-soft-primary text-dark">{{ $shed->devices->count() }}</span>
                            </td>
                            <td>
                                @if($shed->latestFlocks->count() > 1)
                                    <span class="text-muted small">{{ $shed->latestFlocks->count() - 1 }} previous</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="ti ti-home-off fs-30 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-0">No sheds assigned to this farm</p>
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
