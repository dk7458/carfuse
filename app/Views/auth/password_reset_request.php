<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - CarFuse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Request Password Reset</h2>
                        
                        <?php if (isset($validation)): ?>
                            <div class="alert alert-danger">
                                <?= $validation->listErrors() ?>
                            </div>
                        <?php endif; ?>

                        <?php if (session()->has('success')): ?>
                            <div class="alert alert-success">
                                <?= esc(session('success')) ?>
                            </div>
                        <?php endif; ?>

                        <form action="<?= site_url('password/request') ?>" method="post" class="needs-validation" novalidate>
                            <?= csrf_field() ?>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required value="<?= esc(old('email')) ?>">
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="<?= site_url('login') ?>">Back to Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
