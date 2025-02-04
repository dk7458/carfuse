<?php require_once __DIR__ . '/layouts/header.php'; ?>

<h1 class="text-center">Login</h1>

<div id="alert-container"></div>

<div class="auth-container">
    <form id="loginForm" class="auth-form">
        <?= csrf_field() ?>
        
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
        </div>

        <div class="form-group form-check">
            <input type="checkbox" id="remember" name="remember" class="form-check-input">
            <label for="remember" class="form-check-label">Remember Me</label>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Login</button>
    </form>

    <div class="text-center mt-3">
        <a href="/auth/password_reset_request">Forgot your password?</a>
    </div>

    <div class="text-center mt-3">
        <a href="/auth/register">Don't have an account? Register here</a>
    </div>
</div>

<script src="/js/auth.js"></script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
