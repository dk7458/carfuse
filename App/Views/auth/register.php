<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<h1 class="text-center">Register</h1>

<div id="alert-container"></div>

<div class="auth-container">
    <form id="registerForm" class="auth-form">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" class="form-control" placeholder="Enter your full name" required>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
        </div>

        <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" class="form-control" placeholder="Enter your phone number" required>
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <textarea id="address" name="address" class="form-control" placeholder="Enter your address" rows="2" required></textarea>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Enter a strong password" required minlength="6">
        </div>

        <button type="submit" class="btn btn-primary btn-block">Register</button>
    </form>

    <div class="text-center mt-3">
        <a href="/auth/login">Already have an account? Login here</a>
    </div>
</div>

<script src="/js/auth.js"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
