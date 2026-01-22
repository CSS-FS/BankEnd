@extends('layouts.app')
@section('title', 'FlockSense - Intelligent Poultry Management')

@push('css')
<style>
    .kpi-card-fs {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        transition: transform 0.3s, box-shadow 0.3s;
        height: 100%;
    }

    .kpi-card-fs:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }

    .kpi-card-fs.alert {
        border-left: 4px solid #f44336;
    }

    .kpi-card-fs.warning {
        border-left: 4px solid #ff9800;
    }

    .kpi-card-fs.success {
        border-left: 4px solid #4caf50;
    }

    .kpi-label-fs {
        color: #999;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 8px;
    }

    .kpi-value-fs {
        font-size: 28px;
        font-weight: 700;
        color: #333;
        margin-bottom: 5px;
    }

    .kpi-change-fs {
        font-size: 12px;
        color: #666;
    }

    .kpi-change-fs.positive {
        color: #4caf50;
    }

    .kpi-change-fs.negative {
        color: #f44336;
    }

    .env-card-fs {
        background: white;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        transition: transform 0.3s, box-shadow 0.3s;
        height: 100%;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .env-card-fs:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }

    .env-icon-fs {
        font-size: 32px;
        margin-bottom: 10px;
    }

    .env-value-fs {
        font-size: 32px;
        font-weight: 700;
        color: #333;
        margin-bottom: 5px;
    }

    .env-label-fs {
        color: #999;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .timeline-fs {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }

    .timeline-item-fs {
        display: flex;
        align-items: center;
        padding: 15px;
        border-left: 3px solid #667eea;
        margin-left: 20px;
        margin-bottom: 15px;
        background: #f8f6ff;
        border-radius: 8px;
    }

    .timeline-icon-fs {
        width: 40px;
        height: 40px;
        background: #667eea;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        flex-shrink: 0;
        font-size: 20px;
    }

    .ai-recommendations-fs {
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        padding: 20px;
        border-radius: 12px;
        margin-top: 20px;
    }

    .ai-recommendations-fs h3 {
        color: #1976d2;
        margin-bottom: 15px;
    }

    .ai-recommendations-fs ul {
        list-style: none;
        padding: 0;
    }

    .ai-recommendations-fs li {
        padding: 10px 0;
        border-bottom: 1px solid rgba(25, 118, 210, 0.2);
    }

    .chart-wrapper-fs {
        position: relative;
        height: 300px;
    }
</style>
@endpush

@section('content')
<div class="content">
    {{-- Header Section --}}
    <div class="d-lg-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="mb-1">Welcome, {{ $user->name }}</h2>
            <p>You are privileged as <span class="text-primary fw-bold">Farm Manager</span> | {{ $user->phone }}</p>
        </div>
        <ul class="table-top-head">
            <li>
                <a data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh" onclick="location.reload()">
                    <i class="ti ti-refresh"></i>
                </a>
            </li>
            <li>
                <a data-bs-toggle="tooltip" data-bs-placement="top" id="collapse-header" aria-label="Collapse" data-bs-original-title="Collapse">
                    <i data-feather="chevron-up" class="feather-16"></i>
                </a>
            </li>
        </ul>
    </div>

    {{-- Main Card with Tabs --}}
    <div class="card">
        <div class="card-body">
            {{-- Tab Navigation --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <ul class="nav nav-tabs tab-style-2 mb-0 d-sm-flex d-block flex-grow-1" id="flockSenseTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active"
                                id="executive-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#executive"
                                type="button"
                                role="tab"
                                aria-controls="executive-tab-pane"
                                aria-selected="true">
                            <i class="ti ti-chart-line me-1 align-middle"></i>Executive Overview
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link"
                                id="environmental-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#environmental"
                                type="button"
                                role="tab"
                                aria-controls="environmental-tab-pane"
                                aria-selected="false"
                                tabindex="-1">
                            <i class="ti ti-temperature me-1 align-middle"></i>Environmental Monitoring
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link"
                                id="health-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#health"
                                type="button"
                                role="tab"
                                aria-controls="health-tab-pane"
                                aria-selected="false"
                                tabindex="-1">
                            <i class="ti ti-heart-rate-monitor me-1 align-middle"></i>Flock Health
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link"
                                id="operational-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#operational"
                                type="button"
                                role="tab"
                                aria-controls="operational-tab-pane"
                                aria-selected="false"
                                tabindex="-1">
                            <i class="ti ti-settings me-1 align-middle"></i>Operational Efficiency
                        </button>
                    </li>
                </ul>
                <div class="ms-3" style="min-width: 200px;">
                    <select class="form-select" id="target-shed">
                        @foreach($farms as $farm)
                            <optgroup label="{{ $farm->name }}">
                            @foreach($farm->sheds as $shed)
                                <option value="{{ $shed->id }}" data-capacity="{{ $shed->capacity }}" data-flock="{{ $shed->latestFlock->name }}">
                                    {{ $shed->name }}
                                </option>
                            @endforeach
                            </optgroup>
                        @endforeach

                    </select>
                </div>
            </div>

            <div class="tab-content">
                {{-- Executive Overview Tab --}}
                <div class="tab-pane fade show active" id="executive" role="tabpanel">
                    {{-- Alert Banner --}}
                    @foreach([] as $alert)
                    <div class="alert alert-danger custom-alert-icon shadow-sm d-flex align-items-center justify-content-between">
                            <div class="text-danger">
                                <i class="feather-alert-triangle flex-shrink-0 me-2"></i>
                                <span><strong>Critical Alert:</strong> NH3 levels in House 2 exceeding safe threshold (28 ppm)</span>
                            </div>
                            <button type="button" class="btn btn-danger-light" onclick="alert('Clicked...');">
                                Mark Action
                            </button>
                        </div>
                    @endforeach

                    {{-- KPI Cards --}}
                    <div class="row g-3 mb-4">
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <div class="card border-0 w-100">
                                <div class="kpi-card-fs">
                                    <div class="kpi-label-fs">Current Flock Size</div>
                                    <div class="kpi-value-fs" id="current-flock-size">0</div>
                                    <div class="kpi-change-fs" id="current-flock-age">Day 0</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <div class="card border-0 w-100">
                                <div class="kpi-card-fs">
                                    <div class="kpi-label-fs">Mortality Rate</div>
                                    <div class="kpi-value-fs" id="mortality-rate">0.0%</div>
                                    <div class="kpi-change-fs negative" id="mortality-diff">↑ 0.0% from yesterday</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <div class="card border-0 w-100">
                            <div class="kpi-card-fs">
                                <div class="kpi-label-fs">Feed Conversion Ratio</div>
                                <div class="kpi-value-fs" id="fcr">0.0</div>
                                <div class="kpi-change-fs" id="fcr-diff">↓ 0.0 improvement</div>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <div class="card border-0 w-100">
                            <div class="kpi-card-fs">
                                <div class="kpi-label-fs">PEF Score</div>
                                <div class="kpi-value-fs" id="pef-score">0</div>
                                <div class="kpi-change-fs" id="pef-ratio">↑ 8 points from last cycle</div>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <div class="card border-0 w-100">
                            <div class="kpi-card-fs">
                                <div class="kpi-label-fs">Avg Daily Gain</div>
                                <div class="kpi-value-fs" id="adg">0.0g</div>
                                <div class="kpi-change-fs" id="adg-diff">On target</div>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <div class="card border-0 w-100">
                            <div class="kpi-card-fs">
                                <div class="kpi-label-fs">Uniformity (CV%)</div>
                                <div class="kpi-value-fs" id="cv">0.0%</div>
                                <div class="kpi-change-fs" id="cv-desc">Excellent</div>
                            </div>
                            </div>
                        </div>
                    </div>

                    {{-- Charts Growth and Consumption --}}
                    <div class="row g-3 mb-4">
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Growth Performance vs Target</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-wrapper-fs">
                                        <canvas id="growthChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Daily Feed & Water Consumption</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-wrapper-fs">
                                        <canvas id="consumptionChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Charts Row 2 --}}
                    <div class="row g-3">
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        FCR Trend <span class="badge badge-soft-info float-end" id="target-fcr">Target: 0.0</span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-wrapper-fs">
                                        <canvas id="fcrChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Cost Analysis Breakdown</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-wrapper-fs">
                                        <canvas id="costChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Environmental Monitoring Tab --}}
                <div class="tab-pane fade" id="environmental" role="tabpanel">
                    <h3 class="mb-4">Real-time Environmental Conditions</h3>

                    {{-- Environment Cards --}}
                    <div class="row g-3 mb-4">
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <div class="card border-0 w-100">
                            <div class="env-card-fs">
                                <div class="env-icon-fs">🌡️</div>
                                <div class="env-value-fs">32.5°C</div>
                                <div class="env-label-fs">Temperature</div>
                                <div class="text-success fs-12 mt-2">↓ 0.5°C last hour</div>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <div class="card border-0 w-100">
                            <div class="env-card-fs">
                                <div class="env-icon-fs">💧</div>
                                <div class="env-value-fs">65%</div>
                                <div class="env-label-fs">Humidity (RH)</div>
                                <div class="text-muted fs-12 mt-2">Optimal range</div>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <div class="card border-0 w-100">
                            <div class="env-card-fs">
                                <div class="env-icon-fs">⚗️</div>
                                <div class="env-value-fs">28 ppm</div>
                                <div class="env-label-fs">NH3 Level</div>
                                <div class="text-danger fs-12 mt-2">↑ Above threshold</div>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <div class="card border-0 w-100">
                            <div class="env-card-fs">
                                <div class="env-icon-fs">💨</div>
                                <div class="env-value-fs">2800 ppm</div>
                                <div class="env-label-fs">CO2 Level</div>
                                <div class="text-muted fs-12 mt-2">Within limits</div>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <div class="card border-0 w-100">
                            <div class="env-card-fs">
                                <div class="env-icon-fs">🌬️</div>
                                <div class="env-value-fs">2.3 m/s</div>
                                <div class="env-label-fs">Air Velocity</div>
                                <div class="text-muted fs-12 mt-2">Good circulation</div>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <div class="card border-0 w-100">
                            <div class="env-card-fs">
                                <div class="env-icon-fs">📊</div>
                                <div class="env-value-fs">1013 hPa</div>
                                <div class="env-label-fs">Air Pressure</div>
                                <div class="text-muted fs-12 mt-2">Stable</div>
                            </div>
                            </div>
                        </div>
                    </div>

                    {{-- Environment Charts --}}
                    <div class="row g-3 mb-4">
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">24-Hour Environmental Trends</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-wrapper-fs">
                                        <canvas id="envTrendChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Temperature Heat Map (House Layout)</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-wrapper-fs">
                                        <canvas id="heatMapChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Indoor vs Outdoor Conditions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-wrapper-fs">
                                        <canvas id="indoorOutdoorChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Flock Health Tab --}}
                <div class="tab-pane fade" id="health" role="tabpanel">
                    <h2 class="mb-4">Flock Health & Management</h2>

                    {{-- Health KPIs --}}
                    <div class="row g-3 mb-4">
                        <div class="col-xl-3 col-lg-6">
                            <div class="card border-0 w-100">
                            <div class="kpi-card-fs">
                                @php
                                    $healthScore = 85;
                                    $progressColor = $healthScore <= 50 ? 'danger' : ($healthScore <= 70 ? 'warning' : 'success');
                                    $textColor = $healthScore <= 50 ? 'danger' : ($healthScore <= 70 ? 'warning' : 'success');
                                @endphp
                                <div class="kpi-label-fs">Overall Health Score</div>
                                <div class="progress mb-3" style="height: 10px;">
                                    <div class="progress-bar bg-{{ $progressColor }}" role="progressbar" style="width: {{ $healthScore }}%;" aria-valuenow="{{ $healthScore }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <h5 class="text-{{ $textColor }} mb-1">{{ $healthScore }}% - Good Health</h5>
                                <p class="text-muted fs-12 mb-0">Based on 12 health indicators</p>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6">
                            <div class="card border-0 w-100">
                            <div class="kpi-card-fs">
                                <div class="kpi-label-fs">Weight Uniformity</div>
                                <div class="kpi-value-fs">CV: 8.4%</div>
                                <div class="kpi-change-fs positive">Excellent uniformity</div>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6">
                            <div class="card border-0 w-100">
                            <div class="kpi-card-fs">
                                <div class="kpi-label-fs">Today's Mortality</div>
                                <div class="kpi-value-fs">42 birds</div>
                                <div class="kpi-change-fs negative">↑ 12 from average</div>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6">
                            <div class="card border-0 w-100">
                            <div class="kpi-card-fs">
                                <div class="kpi-label-fs">Vaccination Status</div>
                                <div class="kpi-value-fs">On Schedule</div>
                                <div class="kpi-change-fs">Next: Day 28</div>
                            </div>
                            </div>
                        </div>
                    </div>

                    {{-- Health Charts --}}
                    <div class="row g-3 mb-4">
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Weight Distribution (Uniformity)</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-wrapper-fs">
                                        <canvas id="uniformityChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Mortality Pattern</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-wrapper-fs">
                                        <canvas id="mortalityPatternChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Timeline --}}
                    <div class="timeline-fs">
                        <h5 class="mb-3">Management Timeline</h5>
                        <div class="timeline-item-fs">
                            <div class="timeline-icon-fs">💉</div>
                            <div class="flex-grow-1">
                                <div class="fw-bold">Newcastle Vaccination</div>
                                <div class="text-muted fs-12">Scheduled for Day 28 (7 days remaining)</div>
                            </div>
                        </div>
                        <div class="timeline-item-fs">
                            <div class="timeline-icon-fs">💊</div>
                            <div class="flex-grow-1">
                                <div class="fw-bold">Vitamin Supplement Added</div>
                                <div class="text-muted fs-12">Day 19 - Completed</div>
                            </div>
                        </div>
                        <div class="timeline-item-fs">
                            <div class="timeline-icon-fs">🔄</div>
                            <div class="flex-grow-1">
                                <div class="fw-bold">Feed Change to Grower</div>
                                <div class="text-muted fs-12">Day 14 - Completed</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Operational Efficiency Tab --}}
                <div class="tab-pane fade" id="operational" role="tabpanel">
                    <h3 class="mb-4">Operational Efficiency & Analytics</h3>

                    {{-- Operational KPIs --}}
                    <div class="row g-3 mb-4">
                        <div class="col-xl-3 col-lg-6">
                            <div class="card border-0 w-100">
                            <div class="kpi-card-fs">
                                <div class="kpi-label-fs">Feed Efficiency</div>
                                <div class="kpi-value-fs">89.2%</div>
                                <div class="kpi-change-fs positive">↑ 2.1% from last cycle</div>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6">
                            <div class="card border-0 w-100">
                            <div class="kpi-card-fs">
                                <div class="kpi-label-fs">Energy Cost/Bird</div>
                                <div class="kpi-value-fs">$0.42</div>
                                <div class="kpi-change-fs negative">↑ $0.03 increase</div>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6">
                            <div class="card border-0 w-100">
                            <div class="kpi-card-fs">
                                <div class="kpi-label-fs">Water:Feed Ratio</div>
                                <div class="kpi-value-fs">1.82:1</div>
                                <div class="kpi-change-fs">Optimal</div>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6">
                            <div class="card border-0 w-100">
                            <div class="kpi-card-fs">
                                <div class="kpi-label-fs">Projected Harvest</div>
                                <div class="kpi-value-fs">Day 42</div>
                                <div class="kpi-change-fs">Weight: 2.45 kg avg</div>
                            </div>
                            </div>
                        </div>
                    </div>

                    {{-- Operational Charts --}}
                    <div class="row g-3 mb-4">
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Resource Consumption Trends</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-wrapper-fs">
                                        <canvas id="resourceChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Energy Usage Breakdown</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-wrapper-fs">
                                        <canvas id="energyChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Forecast Chart --}}
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">AI-Powered Growth Forecast</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-wrapper-fs">
                                        <canvas id="forecastChart"></canvas>
                                    </div>
                                    <div class="d-flex justify-content-center gap-3 mt-3 fs-12">
                                        <div class="d-flex align-items-center">
                                            <div style="width: 12px; height: 12px; background: #667eea; border-radius: 2px; margin-right: 6px;"></div>
                                            <span>Actual Weight</span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div style="width: 12px; height: 12px; background: #ffa726; border-radius: 2px; margin-right: 6px;"></div>
                                            <span>Predicted Weight</span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div style="width: 12px; height: 12px; background: rgba(255, 167, 38, 0.2); border-radius: 2px; margin-right: 6px;"></div>
                                            <span>Confidence Range</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- AI Recommendations --}}
                    <div class="ai-recommendations-fs">
                        <h3>🤖 AI Recommendations</h3>
                        <ul>
                            <li>
                                <strong>Ventilation Optimization:</strong> Increase air exchange rate by 15% to reduce NH3 levels
                            </li>
                            <li>
                                <strong>Feed Adjustment:</strong> Consider reducing protein content by 0.5% based on current growth rate
                            </li>
                            <li>
                                <strong>Temperature Management:</strong> Lower set point by 0.5°C during peak hours (14:00-16:00)
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('assets/plugins/chartjs/chart.min.js') }}"></script>

<script>
// Initialize Executive Charts
function initExecutiveCharts(growth, consumption, fcr, costs) {
    // Growth Performance Chart
    const growthCtx = document.getElementById('growthChart');
    if (growthCtx) {
        new Chart(growthCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: growth.labels,
                datasets: [{
                    label: 'Actual Weight',
                    data: growth.actual_weights,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Target Weight',
                    data: growth.target_weights,
                    borderColor: '#4caf50',
                    borderDash: [5, 5],
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Weight (g)' }
                    }
                }
            }
        });
    }

    // Consumption Chart
    const consumptionCtx = document.getElementById('consumptionChart');
    if (consumptionCtx) {
        new Chart(consumptionCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: consumption.labels,
                datasets: [{
                    label: 'Feed (kg)',
                    data: consumption.feed_consumed,
                    backgroundColor: '#ffa726'
                }, {
                    label: 'Water (L)',
                    data: consumption.water_consumed,
                    backgroundColor: '#42a5f5'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // FCR Chart
    const fcrCtx = document.getElementById('fcrChart');
    if (fcrCtx) {
        new Chart(fcrCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: fcr.labels,
                datasets: [{
                    label: 'FCR',
                    data: fcr.actual_fcr,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Target',
                    data: fcr.target_fcr,
                    borderColor: '#4caf50',
                    borderDash: [5, 5],
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // Cost Chart
    const costCtx = document.getElementById('costChart');

    function generateColors(count) {
        return Array.from({ length: count }, (_, i) => {
            const hue = Math.round((360 * i) / Math.max(count, 1));
            return `hsl(${hue}, 70%, 55%)`;
        });
    }

    function formatPKR(value) {
        const num = Number(value ?? 0);
        return `PKR ${num.toLocaleString('en-PK', { maximumFractionDigits: 1 })}`;
    }

    if (costCtx) {
        const labels = costs.labels || [];
        const data = (costs.data || []).map(v => Number(v) || 0);

        new Chart(costCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data,
                    backgroundColor: generateColors(data.length),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            generateLabels(chart) {
                                const ds = chart.data.datasets[0] || {};
                                const chartLabels = chart.data.labels || [];
                                const colors = ds.backgroundColor || [];
                                const values = ds.data || [];

                                return chartLabels.map((lbl, i) => ({
                                    text: `${lbl} (${formatPKR(values[i])})`,
                                    fillStyle: colors[i] || '#ccc',
                                    strokeStyle: colors[i] || '#ccc',
                                    lineWidth: 1,

                                    // keep toggle working
                                    hidden: chart.getDataVisibility ? !chart.getDataVisibility(i) : false,
                                    index: i,
                                    datasetIndex: 0
                                }));
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                const label = ctx.label ?? '';
                                const value = ctx.parsed ?? 0;
                                return `${label}: ${formatPKR(value)}`;
                            }
                        }
                    }
                }
            }
        });
    }

}

// Initialize Environmental Charts
function initEnvironmentalCharts() {
    const envTrendCtx = document.getElementById('envTrendChart');
    if (envTrendCtx) {
        new Chart(envTrendCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: Array.from({length: 24}, (_, i) => `${i}:00`),
                datasets: [{
                    label: 'Temperature (°C)',
                    data: [28, 27, 27, 26, 26, 27, 28, 30, 31, 32, 33, 34, 34, 35, 34, 33, 32, 31, 30, 29, 29, 28, 28, 27],
                    borderColor: '#f44336',
                    yAxisID: 'y',
                    tension: 0.4
                }, {
                    label: 'Humidity (%)',
                    data: [70, 72, 73, 75, 74, 72, 68, 65, 62, 60, 58, 57, 56, 55, 56, 58, 60, 62, 64, 66, 68, 69, 70, 71],
                    borderColor: '#42a5f5',
                    yAxisID: 'y1',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Temperature (°C)' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Humidity (%)' },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    }

    const heatMapCtx = document.getElementById('heatMapChart');
    if (heatMapCtx) {
        new Chart(heatMapCtx.getContext('2d'), {
            type: 'bubble',
            data: {
                datasets: [{
                    label: 'Temperature Distribution',
                    data: [
                        {x: 1, y: 1, r: 15, v: 31.5},
                        {x: 2, y: 1, r: 15, v: 32.0},
                        {x: 3, y: 1, r: 15, v: 32.5},
                        {x: 1, y: 2, r: 15, v: 32.2},
                        {x: 2, y: 2, r: 15, v: 33.0},
                        {x: 3, y: 2, r: 15, v: 32.8},
                        {x: 1, y: 3, r: 15, v: 31.8},
                        {x: 2, y: 3, r: 15, v: 32.3},
                        {x: 3, y: 3, r: 15, v: 32.0}
                    ],
                    backgroundColor: function(context) {
                        const value = context.raw.v;
                        if (value < 31) return 'rgba(66, 165, 245, 0.6)';
                        if (value < 32) return 'rgba(102, 187, 106, 0.6)';
                        if (value < 33) return 'rgba(255, 167, 38, 0.6)';
                        return 'rgba(244, 67, 54, 0.6)';
                    }
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { min: 0, max: 4, title: { display: true, text: 'House Width' } },
                    y: { min: 0, max: 4, title: { display: true, text: 'House Length' } }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Temperature: ${context.raw.v}°C`;
                            }
                        }
                    }
                }
            }
        });
    }

    const indoorOutdoorCtx = document.getElementById('indoorOutdoorChart');
    if (indoorOutdoorCtx) {
        new Chart(indoorOutdoorCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['6 AM', '9 AM', '12 PM', '3 PM', '6 PM', '9 PM'],
                datasets: [{
                    label: 'Indoor Temp',
                    data: [28, 30, 33, 34, 32, 29],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    fill: true
                }, {
                    label: 'Outdoor Temp',
                    data: [24, 28, 35, 37, 30, 26],
                    borderColor: '#ffa726',
                    backgroundColor: 'rgba(255, 167, 38, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
}

// Initialize Health Charts
function initHealthCharts() {
    const uniformityCtx = document.getElementById('uniformityChart');
    if (uniformityCtx) {
        new Chart(uniformityCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['800g', '850g', '900g', '950g', '1000g', '1050g', '1100g'],
                datasets: [{
                    label: 'Number of Birds',
                    data: [120, 450, 1200, 2100, 1800, 600, 180],
                    backgroundColor: '#667eea'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'CV: 8.4% - Excellent Uniformity'
                    }
                }
            }
        });
    }


    // Normalize datasets: object -> array
    const raw = @json($datasets ?? []);
    const datasetsArr = Array.isArray(raw) ? raw : Object.values(raw);

    // Prepare series: ensure numbers + {x,y} parsing disabled
    const prepared = datasetsArr.map((ds, i) => ({
        label: ds.label ?? `Series ${i+1}`,
        data: (Array.isArray(ds.data) ? ds.data : []).map(p => ({
            x: Number(p.x),
            y: Number(p.y)
        })),
        parsing: false,
        borderWidth: 2,
        pointRadius: 2,
        tension: 0.2
    }));

    const el = document.getElementById('mortalityPatternChart');
    if (!el) return;

    // Plugin to draw a horizontal acceptable limit line at 0.274%
    const mortalityLimitLine = {
        id: 'mortalityLimitLine',
        afterDatasetsDraw(chart, args, pluginOptions) {
            const { ctx, chartArea, scales } = chart;
            if (!chartArea || !scales?.y) return;
            const value = typeof pluginOptions?.value === 'number' ? pluginOptions.value : 0.274;
            const y = scales.y.getPixelForValue(value);
            if (!isFinite(y)) return;
            ctx.save();
            ctx.strokeStyle = pluginOptions?.color || '#dc3545';
            ctx.lineWidth = 1;
            ctx.setLineDash([6, 4]);
            ctx.beginPath();
            ctx.moveTo(chartArea.left, y);
            ctx.lineTo(chartArea.right, y);
            ctx.stroke();
            ctx.setLineDash([]);
            // Label
            const label = pluginOptions?.label || 'Acceptable Limit (0.274%)';
            ctx.fillStyle = pluginOptions?.color || '#dc3545';
            ctx.font = '12px sans-serif';
            const textWidth = ctx.measureText(label).width;
            const textX = Math.max(chartArea.left + 6, chartArea.right - textWidth - 6);
            const textY = Math.max(chartArea.top + 12, y - 6);
            ctx.fillText(label, textX, textY);
            ctx.restore();
        }
    };

    new Chart(el, {
        type: 'line',
        data: { datasets: prepared },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'nearest', intersect: false },
            scales: {
                x: {
                    type: 'linear',               // age is numeric
                    title: { display: true, text: 'Age (days)' },
                    ticks: { precision: 0 }       // whole-day ticks
                },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Mortality Rate (%)' },
                    ticks: { callback: v => v + '%' }
                }
            },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: (c) => `${c.dataset.label}: ${(c.parsed.y ?? 0).toFixed(2)}% (Day ${c.parsed.x})`
                    }
                },
                // Plugin options
                mortalityLimitLine: {
                    value: 0.274,
                    color: '#dc3545',
                    label: 'Acceptable Limit (0.274%)'
                }
            }
        },
        plugins: [mortalityLimitLine]
    });
}

// Initialize Operational Charts
function initOperationalCharts() {
    const resourceCtx = document.getElementById('resourceChart');
    if (resourceCtx) {
        new Chart(resourceCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6'],
                datasets: [{
                    label: 'Feed (tons)',
                    data: [2.1, 4.5, 7.2, 10.5, 14.2, 18.5],
                    borderColor: '#ffa726',
                    tension: 0.4
                }, {
                    label: 'Water (k liters)',
                    data: [3.8, 8.1, 13.0, 18.9, 25.5, 33.3],
                    borderColor: '#42a5f5',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    const energyCtx = document.getElementById('energyChart');
    if (energyCtx) {
        new Chart(energyCtx.getContext('2d'), {
            type: 'pie',
            data: {
                labels: ['Heating', 'Cooling', 'Ventilation', 'Lighting', 'Equipment'],
                datasets: [{
                    data: [35, 25, 20, 10, 10],
                    backgroundColor: ['#f44336', '#42a5f5', '#66bb6a', '#ffeb3b', '#9e9e9e']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    const forecastCtx = document.getElementById('forecastChart');
    if (forecastCtx) {
        new Chart(forecastCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: Array.from({length: 42}, (_, i) => `Day ${i + 1}`),
                datasets: [{
                    label: 'Actual Weight',
                    data: Array.from({length: 21}, (_, i) => 42 + (i * 43)),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    fill: false
                }, {
                    label: 'AI Prediction',
                    data: Array.from({length: 42}, (_, i) => i <= 20 ? null : 920 + ((i-20) * 72)),
                    borderColor: '#ffa726',
                    borderDash: [5, 5],
                    fill: false
                }, {
                    label: 'Upper Confidence',
                    data: Array.from({length: 42}, (_, i) => i <= 20 ? null : 920 + ((i-20) * 72) + 50),
                    borderColor: 'rgba(255, 167, 38, 0.2)',
                    backgroundColor: 'rgba(255, 167, 38, 0.1)',
                    fill: '+1',
                    pointRadius: 0
                }, {
                    label: 'Lower Confidence',
                    data: Array.from({length: 42}, (_, i) => i <= 20 ? null : 920 + ((i-20) * 72) - 50),
                    borderColor: 'rgba(255, 167, 38, 0.2)',
                    backgroundColor: 'rgba(255, 167, 38, 0.1)',
                    fill: '-1',
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        title: { display: true, text: 'Weight (g)' }
                    }
                }
            }
        });
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const shedId = document.querySelector('#target-shed').value;
    $.get('/dashboard-stats/' + shedId, function(data) {
        document.querySelector('#current-flock-size').innerHTML = data.currentFlockSize;
        document.querySelector('#current-flock-age').innerHTML = `Day ${data.currentFlockAge}<span class="badge bg-soft-success text-dark float-end">${data.startFlockSize}</span>`;
        document.querySelector('#mortality-rate').innerHTML = `${data.cumulativeMortalityRate}%`;
        document.querySelector('#mortality-diff').innerHTML = `↑ ${data.dailyMortalityRate}% from yesterday`;
        document.querySelector('#fcr').innerHTML = `${data.feedConversionRatio}`;

        const fcrDiff = document.querySelector('#fcr-diff');
        if(data.fcrDiff > 0) {
            fcrDiff.innerHTML = `↓ ${data.fcrDiff}% improvement`;
            fcrDiff.classList.add('positive');
            fcrDiff.classList.remove('negative');
        } else {
            fcrDiff.innerHTML = `↑ ${Math.abs(data.fcrDiff)}% worsening`;
            fcrDiff.classList.add('negative');
            fcrDiff.classList.remove('positive');
        }

        document.querySelector('#target-fcr').innerHTML = `Target: ${data.targetFCR}`;

        document.querySelector('#pef-score').innerHTML = `${data.pefScore}`;

        const pefRatio = document.querySelector('#pef-ratio');
        if(data.pefRatio > 0) {
            pefRatio.innerHTML = `↑ ${data.pefRatio} above to standard`;
            pefRatio.classList.add('positive');
            pefRatio.classList.remove('negative');
        } else {
            pefRatio.innerHTML = `↓ ${Math.abs(data.pefRatio)} below to standard`;
            pefRatio.classList.add('negative');
            pefRatio.classList.remove('positive');
        }

        document.querySelector('#adg').innerHTML = `${data.avgDailyGain}g`;
        document.querySelector('#adg-diff').innerHTML = `${data.targetDG}g Targeted`;

        document.querySelector('#cv').innerHTML = `${data.cv}%`;
        document.querySelector('#cv-desc').innerHTML = `${data.cvDesc}`;

        console.log(data);
        initExecutiveCharts(data.growthPerformance, data.feedWaterConsumption, data.fcrComparison, data.currentFlockExpenses);
    });


});

// Re-initialize charts on tab change
document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tab => {
    tab.addEventListener('shown.bs.tab', function (event) {
        const target = event.target.getAttribute('data-bs-target');
        if (target === '#executive') initExecutiveCharts();
        if (target === '#environmental') initEnvironmentalCharts();
        if (target === '#health') initHealthCharts();
        if (target === '#operational') initOperationalCharts();
    });
});
</script>
@endpush
