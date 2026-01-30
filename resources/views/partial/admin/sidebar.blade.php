<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <!-- Logo -->
    <div class="sidebar-logo">
        <a href="{{ route('dashboard') }}" class="logo logo-normal">
            <img src="{{ asset('assets/img/logo.svg') }}" alt="Img">
        </a>
        <a href="{{ route('dashboard') }}" class="logo logo-white">
            <img src="{{ asset('assets/img/logo-white.svg') }}" alt="Img">
        </a>
        <a href="{{ route('dashboard') }}" class="logo-small">
            <img src="{{ asset('assets/img/logo-small.png') }}" alt="Img">
        </a>
        <a id="toggle_btn" href="javascript:void(0);" class="active">
            <i data-feather="chevrons-right" class="feather-16"></i>
        </a>
    </div>
    <!-- /Logo -->

    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                <li class="submenu-open">
                    <h6 class="submenu-hdr">PLATFORM</h6>
                    <ul>
                        <li class="{{ Route::is('dashboard') ? 'active' : '' }}">
                            <a href="{{ route('dashboard') }}">
                                <i class="ti ti-home fs-16 me-2"></i><span>Dashboard</span>
                            </a>
                        </li>
                        @admin
                        <li class="submenu">
                            <a href="javascript:void(0);"
                               class="{{ Route::is('charts.*') ||
                                         Route::is('breeding.*') ||
                                         Route::is('feeds.*') ||
                                         Route::is('expense.heads.*') ||
                                         Route::is('pricing-plans.*') ||
                                         Route::is('partners.*') ||
                                         Route::is('shortcuts.*') ||
                                         Route::is('admin.medicines.*') ? 'subdrop active' : '' }}">
                                <i class="ti ti-database-cog fs-16 me-2"></i>
                                <span>Master Data</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li>
                                    <a href="{{ route('charts.index') }}"
                                       class="{{ request()->routeIs('charts.*') ? 'active' : '' }}">
                                        Standard Data
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('breeding.index') }}"
                                       class="{{ request()->routeIs('breeding.*') ? 'active' : '' }}">
                                        Breeds
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('feeds.index') }}"
                                       class="{{ request()->routeIs('feeds.*') ? 'active' : '' }}">
                                        Feeds Types
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.medicines.index') }}"
                                       class="{{ request()->routeIs('admin.medicines.*') ? 'active' : '' }}">
                                        Poultry Medicines
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('expense.heads.index') }}"
                                       class="{{ request()->routeIs('expense.heads.index') ? 'active' : '' }}">
                                        Expense Heads
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('pricing-plans.index') }}"
                                       class="{{ request()->routeIs('pricing-plans.*') ? 'active' : '' }}">
                                        Pricing Plans
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('partners.index') }}"
                                       class="{{ request()->routeIs('partners.*') ? 'active' : '' }}">
                                        Partners
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('shortcuts.index') }}"
                                       class="{{ request()->routeIs('shortcuts.*') ? 'active' : '' }}">
                                        Quick Actions
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ Route::is('clients.*') || Route::is('roles.*') ? 'subdrop active' : '' }}">
                                <i class="ti ti-users-group fs-16 me-2"></i><span>Users and Clients</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li>
                                    <a href="{{ route('clients.index') }}"
                                       class="{{ Route::is('clients.index') ? 'active' : '' }}">
                                        Users List
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('roles.index') }}"
                                       class="{{ Route::is('roles.*') ? 'active' : '' }}">
                                        User Roles
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('clients.activities') }}"
                                        @class(['active' => Route::is('clients.activities')])>
                                        User Activities
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="submenu">
                            <a href="javascript:void(0);"
                               class="{{ Route::is('iot.index') ||
                                         Route::is('iot.create') ||
                                         Route::is('iot.edit') ||
                                         Route::is('farm.devices') ||
                                         Route::is('devices.map') ||
                                         Route::is('iot.alerts') ||
                                         Route::is('qr-code.*') ? 'subdrop active' : '' }}">
                                <i class="ti ti-devices fs-16 me-2"></i>
                                <span>IoT & Devices</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li>
                                    <a href="{{ route('iot.index') }}"
                                       class="{{ request()->routeIs('iot.index') ||
                                                 request()->routeIs('iot.create') ||
                                                 request()->routeIs('iot.edit') ? 'active' : '' }}">
                                        IoT Inventory
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('farm.devices') }}"
                                       class="{{ request()->routeIs('farm.devices') ? 'active' : '' }}">
                                        Farm Devices
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('devices.map') }}"
                                       class="{{ request()->routeIs('devices.map') ? 'active' : '' }}">
                                        Devices Map
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('iot.alerts') }}"
                                       class="{{ request()->routeIs('iot.alerts') ? 'active' : '' }}">
                                        IoT Alerts
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('qr-code.index') }}"
                                       class="{{ request()->routeIs('qr-code.*') ? 'active' : '' }}">
                                        Print QR Code
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endadmin

                        <li class="submenu">
                            <a href="javascript:void(0);"
                               class="{{ Route::is('admin.farms.*') ||
                                         Route::is('admin.sheds.*') ||
                                         Route::is('admin.flocks.*') ||
                                         Route::is('admin.farm_managers.*') ||
                                         Route::is('admin.staff.*') ? 'subdrop active' : '' }}">
                                <i class="ti ti-building-warehouse fs-16 me-2"></i><span>Farm Structure</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                                <li>
                                    <a href="{{ route('admin.farms.index') }}"
                                       class="{{ request()->routeIs('admin.farms.*') ? 'active' : '' }}">
                                        Farms
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.sheds.index') }}"
                                       class="{{ request()->routeIs('admin.sheds.*') ? 'active' : '' }}">
                                        Sheds
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.flocks.index') }}"
                                       class="{{ request()->routeIs('admin.flocks.*') ? 'active' : '' }}">
                                        Flock Management
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.farm_managers.index') }}"
                                       class="{{ request()->routeIs('admin.farm_managers.*') ? 'active' : '' }}">
                                        Farm Managers
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.staff.index') }}"
                                       class="{{ request()->routeIs('admin.staff.*') ? 'active' : '' }}">
                                        Staff Management
                                    </a>
                                </li>
                                @endif

                                @if(Auth::user()->hasRole('manager'))
                                <li>
                                    <a href="{{ route('admin.flocks.index') }}"
                                       class="{{ request()->routeIs('admin.flocks.*') ? 'active' : '' }}">
                                        Flock Management
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.staff.index') }}"
                                       class="{{ request()->routeIs('admin.staff.*') ? 'active' : '' }}">
                                        Staff Management
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </li>
                    </ul>
                </li>
                @admin
                <li class="submenu-open">
                    <h6 class="submenu-hdr">CRM</h6>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);"
                               class="{{ Route::is('newsletters.*') ? 'subdrop active' : '' }}">
                                <i class="ti ti-send fs-16 me-2"></i>
                                <span>Newsletters</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li>
                                    <a href="{{ route('newsletters.index') }}"
                                       class="{{ request()->routeIs('newsletters.index') ||
                                                 request()->routeIs('newsletters.create') ||
                                                 request()->routeIs('newsletters.edit') ? 'active' : '' }}">
                                        Newsletters List
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('newsletters.subscribers') }}"
                                       class="{{ request()->routeIs('newsletters.subscribers') ? 'active' : '' }}">
                                        Subscribers
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <a href="#">
                                <i class="ti ti-wallpaper fs-16 me-2"></i><span>Blogging</span>
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <i class="ti ti-table-plus fs-16 me-2"></i><span>Events</span>
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <i class="ti ti-star fs-16 me-2"></i><span>Testimonials</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="submenu-open">
                    <h6 class="submenu-hdr">MANAGEMENT</h6>

                    <ul>
                        <li class="{{ Route::is('web-settings.*') ? 'active' : '' }}">
                            <a href="{{ route('web-settings.index') }}">
                                <i class="ti ti-settings-automation fs-16 me-2"></i>
                                <span>System Settings</span>
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <i data-feather="box"></i><span>Subscriptions</span>
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <i class="ti ti-table-plus fs-16 me-2"></i><span>Support Requests</span>
                            </a>
                        </li>
                    </ul>
                </li>
                @endadmin

                @if(auth()->user()->hasAnyRole(['admin', 'owner', 'manager']))
                    <li class="submenu-open">
                        <h6 class="submenu-hdr">OPERATIONS</h6>
                        <ul>
                            <li class="submenu">
                                <a href="javascript:void(0);"
                                   class="{{ Route::is('productions.*') ||
                                         Route::is('iot.logs') ? 'subdrop active' : '' }}">
                                    <i class="ti ti-devices fs-16 me-2"></i>
                                    <span>Logs & Analysis</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul>
                                    <li>
                                        <a href="{{ route('productions.index') }}"
                                           class="{{ Route::is('productions.index') ? 'active' : '' }}">
                                            Production Logs
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('iot.logs') }}"
                                           class="{{ Route::is('iot.logs') ? 'active' : '' }}">
                                            IoT Logs
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#"
                                           class="">
                                            Sheds Performance
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="{{ request()->routeIs('farm.expenses.*') ? 'active' : '' }}">
                                <a href="{{ route('farm.expenses.index') }}">
                                    <i class="ti ti-file-stack fs-16 me-2"></i><span>Farm Expenses</span>
                                </a>
                            </li>
                            <li class="{{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                                <a href="{{ route('inventory.index') }}">
                                    <i class="ti ti-stack-3 fs-16 me-2"></i><span>Feed Inventory</span>
                                </a>
                            </li>
                            <li class="submenu">
                                <a href="javascript:void(0);"
                                   class="{{ Route::is('daily.reports') ||
                                         Route::is('reports.*') ? 'subdrop active' : '' }}">
                                    <i class="ti ti-chart-bar fs-16 me-2"></i>
                                    <span>Reports</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul>
                                    <li>
                                        <a href="{{ route('daily.reports') }}"
                                           class="{{ request()->routeIs('daily.reports') ? 'active' : '' }}">
                                            Daily Reports
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('reports.income') }}"
                                           class="{{ request()->routeIs('reports.income') ? 'active' : '' }}">
                                            Income Report
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('reports.expenses') }}"
                                           class="{{ request()->routeIs('reports.expenses') ? 'active' : '' }}">
                                            Expense Report
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('reports.tax') }}"
                                           class="{{ request()->routeIs('reports.tax') ? 'active' : '' }}">
                                            Tax Report
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('reports.devices.sales') }}"
                                           class="{{ request()->routeIs('reports.devices.sales') ? 'active' : '' }}">
                                            Sales Report
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('reports.annual') }}"
                                           class="{{ request()->routeIs('reports.annual') ? 'active' : '' }}">
                                            Annual Report
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</div>
