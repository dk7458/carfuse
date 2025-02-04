<?php require_once __DIR__ . '/layouts/header.php'; ?>

<h1 class="text-center">User Management</h1>

<div class="admin-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Users</h3>
        <button class="btn btn-primary" id="addUserBtn">Add User</button>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>User ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="userList">
            <!-- User data will be loaded here dynamically -->
        </tbody>
    </table>
</div>

<!-- User Management Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalTitle">Add User</h2>
        <form id="userForm">
            <?= csrf_field() ?>
            <input type="hidden" id="userId" name="userId">

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <button type="submit" class="btn btn-success">Save User</button>
        </form>
    </div>
</div>

<script src="/js/admin.js"></script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
