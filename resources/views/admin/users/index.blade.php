@extends('layouts.app')
@push('css')
    <style>
        .profile-pic img {
            width: 100px;
            height: 100px;
            border-radius: 5%;
            object-fit: cover;
            display: block;
            margin: 0 auto 5px auto;
        }
        .profile-pic span {
            display: block;
            text-align: center;
        }
    </style>
@endpush
@section('title', 'System Users')
@section('content')
    <div class="content">
        <div class="page-header">
            <div class="add-item d-flex">
                <div class="page-title">
                    <h4 class="fw-bold">Users and Clients</h4>
                    <h6>Manage system users - owners and managers.</h6>
                </div>
            </div>
            <ul class="table-top-head">
                <li>
                    <a data-bs-toggle="tooltip" data-bs-placement="top" title="Collapse" id="collapse-header"><i class="ti ti-chevron-up"></i></a>
                </li>
            </ul>
            <div class="page-btn">
                <a href="javascript:void(0)" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="ti ti-circle-plus me-1"></i>Create User
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
                    <select id="rolesFilter" class="form-select me-2">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ ucfirst($role->name) }}">{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>

                    <select id="statusFilter" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Active">Active</option>
                        <option value="Blocked">Blocked</option>
                    </select>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table datatable-custom">
                        <thead class="thead-light">
                        <tr>
                            <th class="w-100">User Name</th>
                            <th>Email</th>
                            <th>Contact No</th>
                            <th>Roles</th>
                            <th>Farms Attached</th>
                            <th>Status</th>
                            <th class="no-sort"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td class="w-100">
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);" class="avatar avatar-md me-2">
                                        @php
                                            $media = $user->media()->orderBy('order_column')->first();
                                            $path = $media ? $media->url :  asset("assets/img/user.jpg");
                                        @endphp
                                            <img src="{{ $path }}" alt="avatar">
                                        </a>
                                        <a href="javascript:void(0);">
                                            {{ $user->name }}
                                        </a>
                                    </div>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->phone }}</td>
                                <td>
                                    @foreach($user->getRoleNames() as $role)
                                    <span class="badge bg-danger-transparent">{{ ucfirst($role) }}</span>
                                    @endforeach
                                </td>
                                <td>
                                @forelse($user->farms as $farm)
                                    <span class="badge bg-success-transparent">{{ $farm->name }}</span>
                                @empty
                                    @if($user->managedFarms->Count() > 0)
                                       <span class="badge bg-success-transparent">{{ $user->managedFarms->first()->name }}</span>
                                    @else
                                       <span class="text-danger fs-10">No Farm Attached</span>
                                    @endif

                                @endforelse
                                </td>
                                <td>
                                    @if($user->is_active)
                                    <span class="badge bg-success">
                                        Active
                                    </span>
                                    @else
                                    <span class="badge bg-danger">
                                        Blocked
                                    </span>
                                    @endif
                                </td>
                                <td class="action-table-data">
                                    <div class="edit-delete-action">
                                        <a class="me-2 edit-icon  p-2" href="{{ route('clients.show', $user) }}">
                                            <i data-feather="eye" class="feather-eye"></i>
                                        </a>
                                        <a class="me-2 p-2" href="javascript:void(0);" onclick="showEditUserModal({{ $user->id }})">
                                            <i data-feather="edit" class="feather-edit"></i>
                                        </a>

                                        @role('admin')
                                        <a class="me-2 p-2" href="{{ route('impersonate', $user->id) }}">
                                            <i data-feather="log-in" class="feather-log-in"></i>
                                        </a>

                                        <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete-modal"
                                           data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}" class="p-2 open-delete-modal">
                                            <i data-feather="trash-2" class="feather-trash-2"></i>
                                        </a>
                                        <form action="{{ route('clients.destroy', $user->id) }}" method="POST" id="delete{{ $user->id }}">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        @endrole
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

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('clients.store') }}" method="POST"
                      enctype="multipart/form-data" class="needs-validation" novalidate>
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addUserModalLabel">
                            <i class="ti ti-user text-primary me-2"></i>
                            Add User
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-3">
                                <div class="profile-pic-upload d-flex flex-column align-content-between mb-2">
                                    <div class="profile-pic" id="profilePicPreview">
                                        <!-- The preview image will go here -->
                                        <span>
                                            <i data-feather="plus-circle" class="plus-down-add"></i>Add Image
                                        </span>
                                    </div>
                                    <div class="mt-4 me-3">
                                        <button type="button" class="btn btn-sm btn-outline-success mt-2" onclick="$('#profileImageInput').click()">
                                            <i class="ti ti-upload"></i> Upload Image
                                        </button>
                                        <input type="file" id="profileImageInput" name="file" class="d-none" accept="image/*">
                                        <p class="text-center fs-10 mt-2">JPEG, PNG up to 2 MB</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-9">
                                <div class="mb-3">
                                    <label for="name" class="form-label">User Name<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                    <div class="invalid-feedback">
                                        You have to full user name.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Role<span class="text-danger ms-1">*</span></label>
                                    <select class="select"  id="role" name="role" required>
                                        <option value="" disabled selected>Select Role</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">
                                        Role needs to be selected to assign.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email<span class="text-danger ms-1">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                    <div class="invalid-feedback">
                                        Valid email of user must be provided.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone<span class="text-danger ms-1">*</span></label>
                                    <input type="tel" class="form-control" id="phone_no" name="phone" required>
                                    <div class="invalid-feedback">
                                        Valid phone no. of user must be provided.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Password<span class="text-danger ms-1">*</span></label>
                                    <div class="pass-group">
                                        <input type="password" class="pass-input form-control" id="password" name="password" required>
                                        <i class="ti ti-eye-off toggle-password"></i>
                                    </div>
                                    <div class="invalid-feedback">
                                        Password must be provided.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Confirm Password<span class="text-danger ms-1">*</span></label>
                                    <div class="pass-group">
                                        <input type="password" class="pass-input form-control" id="password_confirmation" name="password_confirmation" required>
                                        <i class="ti ti-eye-off toggle-password"></i>
                                    </div>
                                    <div class="invalid-feedback">
                                        Password needs to be confirmed.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="ti ti-device-floppy me-2"></i>Create User
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="ti ti-x me-2"></i>Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editUserForm" action="" class="needs-validation" novalidate
                      method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editUserModalLabel">
                            <i class="ti ti-user-edit text-primary me-2"></i>Edit User
                        </h5>
                        <button type="button" id="closeEditModalBtn" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-3">
                                <div class="profile-pic-upload d-flex flex-column align-content-between mb-2">
                                    <div class="profile-pic position-relative" id="profilePicPreview">
                                        <img src="" alt="Profile Preview" class="img-fluid d-none" style="width: 100%; height: 100%; object-fit: cover;" id="editProfileImgTag">
                                        <span id="editProfilePicPlaceholder">
                                        <i data-feather="plus-circle" class="plus-down-add"></i>Add Image
                                    </span>
                                    </div>
                                    <div class="mt-4 me-3">
                                        <button type="button" class="btn btn-sm btn-outline-success mt-2" onclick="$('#profileEditImageInput').click()">
                                            <i class="ti ti-upload"></i> Upload Image
                                        </button>
                                        <input type="file" id="profileEditImageInput" name="file" class="d-none" accept="image/*">
                                        <p class="text-center fs-10 mt-2">JPEG, PNG up to 2 MB</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-9">
                                <input type="hidden" id="edit-id" name="id" value="">
                                <div class="mb-3">
                                    <label for="edit-name" class="form-label">User Name<span class="text-danger ms-1">*</span></label>
                                    <input type="text" class="form-control" id="edit-name" name="name" required>
                                    <div class="invalid-feedback">
                                        You have to enter complete user name.
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Role<span class="text-danger ms-1">*</span></label>
                                    <select class="form-select edit-select" id="edit-role" name="role" required>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">
                                        Role needs to be selected to assign.
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email<span class="text-danger ms-1">*</span></label>
                                    <input type="email" class="form-control" id="edit-email" name="email" required>
                                    <div class="invalid-feedback">
                                        Valid email of user must be provided.
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone<span class="text-danger ms-1">*</span></label>
                                    <input type="tel" class="form-control" id="edit-phone" name="phone" required>
                                    <div class="invalid-feedback">
                                        Valid phone no. of user must be provided.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success me-2" id="updateBtn">
                            <span id="updateBtnText">Update User</span>
                            <span class="spinner-border spinner-border-sm d-none" id="updateSpinner" role="status" aria-hidden="true"></span>
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- User Delete Modal -->
    <div class="modal fade" id="delete-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="page-wrapper-new p-0">
                    <div class="p-5 px-3 text-center">
                    <span class="rounded-circle d-inline-flex p-2 bg-danger-transparent mb-2">
                        <i class="ti ti-trash fs-24 text-danger"></i>
                    </span>
                        <h4 class="fs-20 fw-bold mb-2 mt-1">Delete User</h4>
                        <p class="mb-0 fs-16" id="delete-modal-message">
                            Are you sure you want to delete this User?
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
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('profileImageInput');
            const previewDiv = document.getElementById('profilePicPreview');

            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        // Remove previous img if any
                        let img = previewDiv.querySelector('img');
                        if (!img) {
                            img = document.createElement('img');
                            previewDiv.insertBefore(img, previewDiv.firstChild);
                        }
                        img.src = ev.target.result;
                        // Optionally, hide the "Add Image" text/icon
                        previewDiv.querySelector('span').style.display = 'none';
                    };
                    reader.readAsDataURL(file);
                } else {
                    // Reset to original state if file is not valid
                    const img = previewDiv.querySelector('img');
                    if (img) img.remove();
                    previewDiv.querySelector('span').style.display = '';
                }
            });
        });
    </script>
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

                $('#rolesFilter').on('change', function() {
                    var selected = $(this).val();
                    table.column(3).search(selected).draw();
                });
                $('#statusFilter').on('change', function() {
                    var selected = $(this).val();
                    table.column(5).search(selected).draw();
                });
            }
        });
    </script>

    <script>
        document.getElementById('profileEditImageInput').onchange = function(event) {
            let file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    let img = document.getElementById('editProfileImgTag');
                    img.src = e.target.result;
                    img.classList.remove('d-none');
                    document.getElementById('editProfilePicPlaceholder').classList.add('d-none');
                };
                reader.readAsDataURL(file);
            }
        };

        // Show and populate edit modal
        function showEditUserModal(userId) {
            // Clear previous state
            document.getElementById('editProfileImgTag').src = '';
            document.getElementById('editProfileImgTag').classList.add('d-none');
            document.getElementById('editProfilePicPlaceholder').classList.remove('d-none');

            fetch('/admin/clients/' + userId + '/edit')
                .then(response => response.json())
                .then(user => {
                    // Populate fields
                    document.getElementById('edit-id').value = user.id;
                    document.getElementById('edit-name').value = user.name ?? '';
                    document.getElementById('edit-email').value = user.email ?? '';
                    document.getElementById('edit-phone').value = user.phone ?? '';

                    // Profile image preview (handle if media array is empty)
                    if (user.media && user.media.length > 0 && user.media[0].file_name) {
                        // Always use the correct absolute path
                        let imgPath = window.location.origin + '/storage/media/' + user.media[0].file_name;
                        let img = document.getElementById('editProfileImgTag');
                        img.src = imgPath;
                        img.classList.remove('d-none');
                        document.getElementById('editProfilePicPlaceholder').classList.add('d-none');
                    }

                    const roleName = (user.roles && user.roles[0]) ? user.roles[0].name : null; // use id
                    $('#edit-role').val(roleName).trigger('change');

                    $('#editUserForm').attr('action', '/admin/clients/' + user.id);
                    $('#editUserModal').modal('show');
                });
        }

        // Spinner on submit
        document.getElementById('editUserForm').addEventListener('submit', function() {
            document.getElementById('updateBtnText').classList.add('d-none');
            document.getElementById('updateSpinner').classList.remove('d-none');
            document.getElementById('updateBtn').setAttribute('disabled', 'disabled');
        });

        // Reset modal on close
        document.getElementById('closeEditModalBtn').addEventListener('click', function () {
            setTimeout(() => {
                document.getElementById('editUserForm').reset();
                document.getElementById('updateBtnText').classList.remove('d-none');
                document.getElementById('updateSpinner').classList.add('d-none');
                document.getElementById('updateBtn').removeAttribute('disabled');
                document.getElementById('editProfileImgTag').classList.add('d-none');
                document.getElementById('editProfilePicPlaceholder').classList.remove('d-none');
            }, 500);
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let deleteUserId = null;

            document.querySelectorAll('.open-delete-modal').forEach(function(el) {
                el.addEventListener('click', function() {
                    deleteUserId = this.getAttribute('data-user-id');
                    const userName = this.getAttribute('data-user-name');
                    document.getElementById('delete-modal-message').textContent =
                        `Are you sure you want to delete "${userName}"?`;
                });
            });

            document.getElementById('confirm-delete-btn').addEventListener('click', function() {
                if (deleteUserId) {
                    document.getElementById('delete' + deleteUserId).submit();
                }
            });

            $('.edit-select').select2({
                dropdownParent: $('#editUserModal'), // ensures it works inside Bootstrap modal
                width: '100%'
            });
        });
    </script>
@endpush
