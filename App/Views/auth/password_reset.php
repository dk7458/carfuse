<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<h1 class="text-center">Request Password Reset</h1>

<div id="alert-container"></div>

<div class="auth-container">
    <form id="passwordResetRequestForm" class="auth-form">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
    </form>

    <div class="text-center mt-3">
        <a href="/auth/login">Back to Login</a>
    </div>
</div>

<script src="/js/auth.js"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
