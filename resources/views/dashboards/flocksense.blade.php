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
        height: 390px;
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
                <a data-bs-toggle="tooltip"
                   data-bs-placement="top"
                   aria-label="Refresh"
                   data-bs-original-title="Refresh"
                   onclick="location.reload()">
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
                    <h3 class="mb-4">Indoor/Outdoor Environmental Stat</h3>

                    {{-- Environment Cards --}}
                    <div class="row g-3 mb-4">
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <div class="card border-0 w-100">
                            <div class="env-card-fs">
                                <div class="env-icon-fs">🌡️</div>
                                <div class="env-value-fs" id="temperature">0.0°C</div>
                                <div class="env-label-fs">Temperature</div>
                                <div class="text-success fs-12 mt-2" id="temperature-outdoor">0.0°C Outdoor</div>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <div class="card border-0 w-100">
                            <div class="env-card-fs">
                                <div class="env-icon-fs">💧</div>
                                <div class="env-value-fs" id="humidity">0.0%</div>
                                <div class="env-label-fs">Humidity (RH)</div>
                                <div class="fs-12 mt-2" id="humidity-outdoor">0.0 Outdoor</div>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <div class="card border-0 w-100">
                            <div class="env-card-fs">
                                <div class="env-icon-fs">⚗️</div>
                                <div class="env-value-fs" id="nh">0 ppm</div>
                                <div class="env-label-fs">NH3 Level</div>
                                <div class="fs-12 mt-2" id="nh-desc">↑ Above threshold</div>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <div class="card border-0 w-100">
                            <div class="env-card-fs">
                                <div class="env-icon-fs">💨</div>
                                <div class="env-value-fs" id="co2">0 ppm</div>
                                <div class="env-label-fs">CO2 Level</div>
                                <div class="fs-12 mt-2" id="co2-desc">Within limits</div>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <div class="card border-0 w-100">
                            <div class="env-card-fs">
                                <div class="env-icon-fs">🌬️</div>
                                <div class="env-value-fs" id="air-vel">0.0 m/s</div>
                                <div class="env-label-fs">Outdoot Air Velocity</div>
                                <div class="fs-12 mt-2" id="indoor-air">0.0 m/s Indoor</div>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <div class="card border-0 w-100">
                            <div class="env-card-fs">
                                <div class="env-icon-fs">📊</div>
                                <div class="env-value-fs" id="air-pressure">0 hPa</div>
                                <div class="env-label-fs">Outdoor Air Pressure</div>
                                <div class="fs-12 mt-2" id="indoor-pressure">0 hPa Indoor</div>
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
                                    <h5 class="card-title mb-0">Temperature Heat Map</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-wrapper-fs">
                                        <div id="heatMapChart"></div>
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
                                <div class="kpi-label-fs">Overall Health Score</div>
                                <div class="progress mb-3" style="height: 10px;" id="progressBar">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 95%;" aria-valuenow="95" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <h5 class="mb-1" id="band">95% - Good Health</h5>
                                <p class="text-muted fs-12 mb-0">Based on 08 health indicators</p>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6">
                            <div class="card border-0 w-100">
                            <div class="kpi-card-fs">
                                <div class="kpi-label-fs">Weight Uniformity</div>
                                <div class="kpi-value-fs" id="uniformity">8.4%</div>
                                <div class="kpi-change-fs" id="uniformity-desc">Excellent uniformity</div>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6">
                            <div class="card border-0 w-100">
                                <div class="kpi-card-fs">
                                    <div class="kpi-label-fs">Today's Mortality</div>
                                    <div class="kpi-value-fs" id="mortality-today">42 birds</div>
                                    <div class="kpi-change-fs positive" id="mortality-desc">↑ 12 from average</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6">
                            <div class="card border-0 w-100">
                                <div class="kpi-card-fs">
                                    <div class="kpi-label-fs">Vaccination Status</div>
                                    <div class="kpi-value-fs" id="vac-status">On Schedule</div>
                                    <div class="kpi-change-fs" id="vac-since">Next: Day 28</div>
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
                                    <div class="kpi-value-fs" id="efficiency">0.0%</div>
                                    <div class="kpi-change-fs" id="efficiency-desc">Excellent</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6">
                            <div class="card border-0 w-100">
                                <div class="kpi-card-fs">
                                    <div class="kpi-label-fs">Flock Expenses</div>
                                    <div class="kpi-value-fs" id="expense-flock">PKR 1200000</div>
                                    <div class="kpi-change-fs" id="expense-desc">PKR 0.0 increase</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-6">
                            <div class="card border-0 w-100">
                            <div class="kpi-card-fs">
                                <div class="kpi-label-fs">Cost / Bird</div>
                                <div class="kpi-value-fs" id="bird-cost">PKR 0.0</div>
                                <div class="kpi-change-fs" id="cost-desc">From Last Flock</div>
                            </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-6">
                            <div class="card border-0 w-100">
                                <div class="kpi-card-fs">
                                    <div class="kpi-label-fs">Feed:Water Ratio</div>
                                    <div class="kpi-value-fs" id="fwratio">0 : 0</div>
                                    <div class="kpi-change-fs" id="fwratio-desc">Optimal</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-6">
                            <div class="card border-0 w-100">
                                <div class="kpi-card-fs">
                                    <div class="kpi-label-fs">Projected Harvest</div>
                                    <div class="kpi-value-fs" id="harvest">Day 42</div>
                                    <div class="kpi-change-fs" id="harvest-desc">Weight: 2.45 kg avg</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Operational Charts --}}
                    <div class="row g-3 mb-4">
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Feed Efficiency and Weight Gain</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-wrapper-fs">
                                        <canvas id="utilizationChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Growth Compliance</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-wrapper-fs">
                                        <canvas id="growthCompliancePie"></canvas>
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
<script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }}"></script>

<script>
let overviewCache = null;
let envMonitoringCache = null;
let healthCache = null;
let efficiencyCache = null;
let envHeatmapChart = null;

function getChartInstance(el) {
    if (typeof Chart === 'undefined') return null;
    if (typeof Chart.getChart === 'function') return Chart.getChart(el);
    return null;
}

function ensureArray(value) {
    return Array.isArray(value) ? value : [];
}

function normalizeTrend(trend) {
    const labels = ensureArray(trend?.labels);
    let tempData = [];
    let humidityData = [];

    if (Array.isArray(trend?.datasets)) {
        tempData = ensureArray(trend.datasets[0]?.data);
        humidityData = ensureArray(trend.datasets[1]?.data);
    } else if (trend?.datasets && typeof trend.datasets === 'object') {
        tempData = ensureArray(trend.datasets.temperature ?? trend.datasets.temp ?? trend.datasets.indoor ?? trend.datasets[0]);
        humidityData = ensureArray(trend.datasets.humidity ?? trend.datasets.rh ?? trend.datasets[1]);
    } else {
        tempData = ensureArray(trend?.temperature ?? trend?.temp ?? trend?.indoor);
        humidityData = ensureArray(trend?.humidity ?? trend?.rh);
    }

    const maxLen = Math.max(labels.length, tempData.length, humidityData.length);
    const finalLabels = labels.length ? labels : Array.from({ length: maxLen }, (_, i) => `${i + 1}`);

    return {
        labels: finalLabels,
        tempData,
        humidityData
    };
}

function normalizeTempIO(tempIO) {
    const labels = ensureArray(tempIO?.labels);
    const indoor = ensureArray(tempIO?.datasets?.indoor ?? tempIO?.indoor);
    const outdoor = ensureArray(tempIO?.datasets?.outdoor ?? tempIO?.outdoor);
    const maxLen = Math.max(labels.length, indoor.length, outdoor.length);
    const finalLabels = labels.length ? labels : Array.from({ length: maxLen }, (_, i) => `${i + 1}`);
    return {
        labels: finalLabels,
        indoor,
        outdoor
    };
}

function generateColors(count) {
    return Array.from({ length: count }, (_, i) => {
        const hue = Math.round((360 * i) / Math.max(count, 1));
        return `hsl(${hue}, 70%, 55%)`;
    });
}

// Initialize Executive Charts
function initExecutiveCharts(growth, consumption, fcr, costs) {
    if (!growth || !consumption || !fcr || !costs) {
        return;
    }
    // Growth Performance Chart
    const growthCtx = document.getElementById('growthChart');
    if (growthCtx) {
        const existing = getChartInstance(growthCtx);
        if (existing) existing.destroy();
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
        const existing = getChartInstance(consumptionCtx);
        if (existing) existing.destroy();
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
        const existing = getChartInstance(fcrCtx);
        if (existing) existing.destroy();
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
        const existing = getChartInstance(costCtx);
        if (existing) existing.destroy();
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
function initEnvironmentalCharts(thTrend, payload, tempIO) {
    var options;
    if (payload) {
        options = {
            chart: { type: 'heatmap', height: 400 },
            plotOptions: {
                heatmap: {
                    radius: 4,
                    enableShades: false,
                    colorScale: {
                        ranges: [
                            { from: 0,  to: 10, color: '#42A5F5' },
                            { from: 10, to: 21, color: '#60B5E5' },
                            { from: 21, to: 25, color: '#F5F3F3' },
                            { from: 25,  to: 30, color: '#FFE3CB' },
                            { from: 30, to: 35, color: '#FE9F43' },
                            { from: 35, to: 60, color: '#f44336' },
                        ],
                    },
                }
            },
            legend: { show: false },
            dataLabels: { enabled: false },
            grid: { padding: { top: -20, bottom: 0, left: 0, right: 0 } },
            yaxis: { labels: { offsetX: -15 } },
            series: payload
        };

        if (envHeatmapChart && typeof envHeatmapChart.destroy === 'function') {
            envHeatmapChart.destroy();
        }
        const heatMapEl = document.querySelector("#heatMapChart");
        if (heatMapEl) {
            heatMapEl.innerHTML = '';
            envHeatmapChart = new ApexCharts(heatMapEl, options);
            envHeatmapChart.render();
        }
    }

    const envTrendCtx = document.getElementById('envTrendChart');
    if (envTrendCtx) {
        const trend = normalizeTrend(thTrend || {});
        if (trend.labels.length && (trend.tempData.length || trend.humidityData.length)) {
            const existing = getChartInstance(envTrendCtx);
            if (existing) existing.destroy();
            new Chart(envTrendCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: trend.labels,
                    datasets: [{
                        label: 'Temperature (°C)',
                        data: trend.tempData,
                        borderColor: '#f44336',
                        yAxisID: 'y',
                        tension: 0.4
                    }, {
                        label: 'Humidity (%)',
                        data: trend.humidityData,
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
                            title: { display: true, text: 'Relative Humidity (%)' },
                            grid: { drawOnChartArea: false }
                        }
                    }
                }
            });
        }
    }

    const indoorOutdoorCtx = document.getElementById('indoorOutdoorChart');
    if (indoorOutdoorCtx) {
        const io = normalizeTempIO(tempIO || {});
        if (io.labels.length && (io.indoor.length || io.outdoor.length)) {
            const existing = getChartInstance(indoorOutdoorCtx);
            if (existing) existing.destroy();
            new Chart(indoorOutdoorCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: io.labels,
                    datasets: [{
                        label: 'Indoor Temp',
                        data: io.indoor,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true
                    }, {
                        label: 'Outdoor Temp',
                        data: io.outdoor,
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
}

// Initialize Health Charts
function initHealthCharts(cv, desc, cvDist, mr) {
    const uniformityCtx = document.getElementById('uniformityChart');
    if (uniformityCtx) {
        new Chart(uniformityCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: cvDist.labels,
                datasets: [{
                    label: 'Uniformity',
                    data: cvDist.data,
                    backgroundColor: '#667eea'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        type: 'linear',               // age is numeric
                        title: { display: true, text: 'Age (days)' },
                        ticks: { precision: 0 }
                    },
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Weight Distribution (%)' }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: `CV: ${cv}% | ${desc}`
                    }
                }
            }
        });
    }

    // Transform the data from separate x,y arrays to the format Chart.js expects
    const transformedData = mr.x.map((xValue, index) => ({
        x: Number(xValue),
        y: Number(mr.y[index])
    }));

// Prepare series: ensure numbers + {x,y} parsing disabled
    const prepared = [{
        label: 'Mortality Rate',
        data: transformedData,
        parsing: false,
        borderWidth: 2,
        pointRadius: 2,
        tension: 0.2,
        borderColor: '#3b82f6', // Optional: add color
        backgroundColor: 'rgba(59, 130, 246, 0.1)', // Optional: fill color
    }];

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

// Initialize the chart
    const chart = new Chart(el, {
        type: 'line',
        data: {
            datasets: prepared
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    type: 'linear',
                    title: {
                        display: true,
                        text: 'Age (days)'
                    }
                },
                y: {
                    type: 'linear',
                    title: {
                        display: true,
                        text: 'Mortality Rate (%)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(3) + '%';
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: true
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Mortality: ${context.parsed.y.toFixed(3)}% at day ${context.parsed.x}`;
                        }
                    }
                }
            }
        },
        plugins: [mortalityLimitLine]
    });
}

// Initialize Operational Charts
function initOperationalCharts(util, complyData, forecast) {
    const utilCtx = document.getElementById('utilizationChart');
    if (utilCtx) {
        new Chart(utilCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: util.feed,
                datasets: [{
                    label: 'Feed Efficiency',
                    data: util.feed_efficiency,
                    borderColor: '#ffa726',
                    yAxisID: 'y0',
                    tension: 0.4
                }, {
                    label: 'Weight Gain (g)',
                    data: util.weight_gain,
                    borderColor: '#42a5f5',
                    yAxisID: 'y1',
                    tension: 0.4
                },{
                    label: 'Expected Gain (g)',
                    data: util.expected_gain,
                    borderColor: '#f44336',
                    yAxisID: 'y1',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        type: 'linear',
                        title: { display: true, text: 'Feed Consumed (kg)' },
                        ticks: { precision: 0 }
                    },
                    y0: {
                        type: 'linear',
                        beginAtZero: true,
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Feed Efficiency' },
                    },
                    y1: {
                        type: 'linear',
                        beginAtZero: true,
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Weight Gain (g)' },
                        grid: { drawOnChartArea: false }
                    }
                },
            }
        });
    }

    const complyCtx = document.getElementById('growthCompliancePie');
    if (complyCtx) {
        new Chart(complyCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: complyData.labels,
                datasets: [{
                    data: complyData.data,
                    backgroundColor: generateColors(complyData.data.length),
                    borderWidth: 1,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' },
                    tooltip: {
                        callbacks: {
                            label: (c) => `${c.label}: ${c.parsed} days`
                        }
                    }
                }
            }
        });
    }

    const forecastCtx = document.getElementById('forecastChart');
    if (forecastCtx) {
        new Chart(forecastCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: forecast.labels,
                datasets: [
                    {
                        label: 'Actual Weight',
                        data: forecast.actual_weights,
                        spanGaps: true,
                        tension: 0.2,
                    },
                    {
                        label: 'Target Weight',
                        data: forecast.target_weights,
                        spanGaps: true,
                        tension: 0.2,
                    },
                    {
                        label: `Forecast Weight (Next ${forecast.forecast_days} Days)`,
                        data: forecast.forecast_weights,
                        spanGaps: true,
                        tension: 0.2,
                        borderDash: [6, 6], // dashed forecast
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: { title: { display: true, text: 'Age (Day)' } },
                    y: { title: { display: true, text: 'Weight' } }
                }
            }
        });
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const shedSelect = document.querySelector('#target-shed');
    if (!shedSelect) return;

    let currentShedId = shedSelect.value;
    let activeRequest = null;

    function loadDashboard(shedId) {
        if (!shedId) return;

        // Abort any in-flight request to avoid out-of-order updates
        if (activeRequest && activeRequest.readyState !== 4) {
            activeRequest.abort();
        }

        // (Optional) show loading state
        // document.body.classList.add('loading');

        activeRequest = $.get('/dashboard-stats/' + shedId)
            .done(function (data) {
                // Always check keys exist (defensive)
                if (data?.overview)   SetOverview(data.overview);
                if (data?.monitoring) SetEnvironment(data.monitoring);
                if (data?.health)     SetFlockHealth(data.health);
                if (data?.efficiency) SetEfficiency(data.efficiency);
            })
            .fail(function (xhr) {
                if (xhr.statusText === 'abort') return; // ignore aborted requests
                console.error('Dashboard fetch failed:', xhr);
            })
            .always(function () {
                // document.body.classList.remove('loading');
            });
    }

    // Initial load
    loadDashboard(currentShedId);

    // Refresh dashboard when shed changes
    shedSelect.addEventListener('change', function (e) {
        const newShedId = e.target.value;
        if (newShedId === currentShedId) return;

        currentShedId = newShedId;
        loadDashboard(currentShedId);
    });

    function SetOverview(res) {
        overviewCache = res;
        const overview = res;
        document.querySelector('#current-flock-size').innerHTML = overview.currentFlockSize;
        document.querySelector('#current-flock-age').innerHTML = `Day ${overview.currentFlockAge}<span class="badge bg-soft-success text-dark float-end">${overview.startFlockSize}</span>`;
        document.querySelector('#mortality-rate').innerHTML = `${overview.cumulativeMortalityRate}%`;
        document.querySelector('#mortality-diff').innerHTML = `↑ ${overview.dailyMortalityRate}% from yesterday`;
        document.querySelector('#fcr').innerHTML = `${overview.feedConversionRatio}`;

        const fcrDiff = document.querySelector('#fcr-diff');
        if(overview.fcrDiff > 0) {
            fcrDiff.innerHTML = `↓ ${overview.fcrDiff}% improvement`;
            fcrDiff.classList.add('positive');
            fcrDiff.classList.remove('negative');
        } else {
            fcrDiff.innerHTML = `↑ ${Math.abs(overview.fcrDiff)}% worsening`;
            fcrDiff.classList.add('negative');
            fcrDiff.classList.remove('positive');
        }

        document.querySelector('#target-fcr').innerHTML = `Target: ${overview.targetFCR}`;

        document.querySelector('#pef-score').innerHTML = `${overview.pefScore}`;

        const pefRatio = document.querySelector('#pef-ratio');
        if(overview.pefRatio > 0) {
            pefRatio.innerHTML = `↑ ${overview.pefRatio} above to standard`;
            pefRatio.classList.add('positive');
            pefRatio.classList.remove('negative');
        } else {
            pefRatio.innerHTML = `↓ ${Math.abs(overview.pefRatio)} below to standard`;
            pefRatio.classList.add('negative');
            pefRatio.classList.remove('positive');
        }

        document.querySelector('#adg').innerHTML = `${overview.avgDailyGain}g`;
        document.querySelector('#adg-diff').innerHTML = `${overview.targetDG}g Targeted`;

        document.querySelector('#cv').innerHTML = `${overview.cv}%`;
        document.querySelector('#cv-desc').innerHTML = `${overview.cvDesc}`;

        initExecutiveCharts(overview.growthPerformance, overview.feedWaterConsumption, overview.fcrComparison, overview.currentFlockExpenses);
    }

    function SetEnvironment(res) {
        if (!res) return;
        envMonitoringCache = res;
        const monitoring = res;

        document.querySelector('#temperature').innerHTML = `${monitoring.inTemperature}°C`;
        document.querySelector('#temperature-outdoor').innerHTML = `${monitoring.outTemperature}°C Outdoor`;

        document.querySelector('#humidity').innerHTML = `${monitoring.inHumidity}%`;
        document.querySelector('#humidity-outdoor').innerHTML = `${monitoring.outHumidity}% Outdoor`;

        const inNH3 = monitoring.inNH3 || {};
        const nhValue = inNH3.value ?? inNH3.v ?? 0;
        const nhDesc = inNH3.desc ?? inNH3.d ?? '';
        const nhClass = inNH3.class ?? inNH3.c ?? '';
        document.querySelector('#nh').innerHTML = `${nhValue} ppm`;
        document.querySelector('#nh-desc').innerHTML = `${nhDesc}`;
        document.querySelector('#nh-desc').classList.remove(...['text-success', 'text-danger']);
        if (nhClass) document.querySelector('#nh-desc').classList.add(nhClass);

        const inCO2 = monitoring.inCO2 || {};
        const co2Value = inCO2.value ?? inCO2.v ?? 0;
        const co2Desc = inCO2.desc ?? inCO2.d ?? '';
        const co2Class = inCO2.class ?? inCO2.c ?? '';
        document.querySelector('#co2').innerHTML = `${co2Value} ppm`;
        document.querySelector('#co2-desc').innerHTML = `${co2Desc}`;
        document.querySelector('#co2-desc').classList.remove(...['text-success', 'text-danger']);
        if (co2Class) document.querySelector('#co2-desc').classList.add(co2Class);

        document.querySelector('#air-vel').innerHTML = `${monitoring.outAirVelocity} m/s`;
        document.querySelector('#indoor-air').innerHTML = `${monitoring.inAirVelocity} m/s Indoor`;

        document.querySelector('#air-pressure').innerHTML = `${monitoring.outAirPressure} hPa`;
        document.querySelector('#indoor-pressure').innerHTML = `${monitoring.inAirPressure} hPa Indoor`;

        initEnvironmentalCharts(monitoring.temperatureHumidityTrend, monitoring.heatMapData, monitoring.temperatureIO);
    }

    function SetFlockHealth(res) {
        if (!res) return;
        healthCache = res;
        const health = res;
        let score = health.healthScore.score;
        let band = health.healthScore.band;

        document.querySelector('#progressBar').innerHTML = `<div class="progress-bar bg-${band.progress}" role="progressbar" style="width: ${score}%;" aria-valuenow="${score}" aria-valuemin="0" aria-valuemax="100"></div>`;
        let bandCtrl = document.querySelector('#band');
        bandCtrl.innerHTML = `${score}% - ${band.level} Health`;

        document.querySelector('#uniformity').innerHTML = `${health.uniformity}%`;
        document.querySelector('#uniformity-desc').innerHTML = `${health.cvDesc}`;

        document.querySelector('#mortality-today').innerHTML = `${health.todayMortality} Birds`;
        document.querySelector('#mortality-desc').innerHTML = `${health.totalMortality} Total Mortality`;

        document.querySelector('#vac-status').innerHTML = `${health.vaccinationStatus.status}`;
        document.querySelector('#vac-since').innerHTML = `${health.vaccinationStatus.since} Days`;

        initHealthCharts(health.cv, health.cvDesc, health.weightDistribution, health.mortalityRate)
    }

    function SetEfficiency(res) {
        efficiencyCache = res;
        const efficiency = res;

        document.querySelector('#efficiency').innerHTML = `${efficiency.feedEfficiency.v}%`;
        let efficiencyDesc = document.querySelector('#efficiency-desc');
        efficiencyDesc.innerHTML = `${efficiency.feedEfficiency.desc}`;
        efficiencyDesc.classList.remove(...['positive', 'negative']);
        efficiencyDesc.classList.add(efficiency.feedEfficiency.level);

        document.querySelector('#fwratio').innerHTML = `${efficiency.feedWaterRatio}`;
        document.querySelector('#fwratio-desc').innerHTML = `Optimial`;

        document.querySelector('#expense-flock').innerHTML = `${efficiency.expense.totalExpense}`;
        let diffExpense = document.querySelector('#expense-desc');
        diffExpense.innerHTML = `${efficiency.expense.diffExpense}`;
        diffExpense.classList.remove(...['positive', 'negative']);
        diffExpense.classList.add(efficiency.expense.levelExpense);

        document.querySelector('#bird-cost').innerHTML = `${efficiency.cost.birdCost}`;
        let costDiff = document.querySelector('#cost-desc');
        costDiff.innerHTML = `${efficiency.cost.diffBirdCost}`;
        costDiff.classList.remove(...['positive', 'negative']);
        costDiff.classList.add(efficiency.cost.levelBirdCost);

        document.querySelector('#harvest').innerHTML = `${efficiency.projectedHarvest.age}`;
        document.querySelector('#harvest-desc').innerHTML = `CW/EW : ${efficiency.projectedHarvest.weight} / ${efficiency.projectedHarvest.expected_weight}`;

        initOperationalCharts(efficiency.feedUtilization, efficiency.complianceChart, efficiency.aiPoweredGrowth);
    }
});

// Re-initialize charts on tab change
document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tab => {
    tab.addEventListener('shown.bs.tab', function (event) {
        const target = event.target.getAttribute('data-bs-target');
        if (target === '#executive' && overviewCache) {
            initExecutiveCharts(
                overviewCache.growthPerformance,
                overviewCache.feedWaterConsumption,
                overviewCache.fcrComparison,
                overviewCache.currentFlockExpenses
            );
        }
        if (target === '#environmental' && envMonitoringCache) {
            initEnvironmentalCharts(
                envMonitoringCache.temperatureHumidityTrend,
                envMonitoringCache.heatMapData,
                envMonitoringCache.temperatureIO
            );
        }
        if (target === '#health' && healthCache) {
            initHealthCharts(
                healthCache.weightDistribution,
                healthCache.mortalityRate);
        }
        if (target === '#operational' && efficiencyCache) {
            initOperationalCharts(
                efficiencyCache.feedUtilization
            );
        }
    });
});
</script>
@endpush
