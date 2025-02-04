<?php require_once __DIR__ . '/layouts/header.php'; ?>

<h1 class="text-center">Reset Password</h1>

<div id="alert-container"></div>

<div class="auth-container">
    <form id="passwordResetForm" class="auth-form">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= esc($token) ?>">

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required value="<?= esc($email ?? old('email')) ?>">
        </div>

        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Enter a new password" required minlength="8">
            <small class="form-text">Password must be at least 8 characters long.</small>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter your password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
    </form>

    <div class="text-center mt-3">
        <a href="/auth/login">Back to Login</a>
    </div>
</div>

<script src="/js/auth.js"></script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
