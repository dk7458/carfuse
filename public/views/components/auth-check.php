<?php
/**
 * Authentication Check Component
 * 
 * A reusable component that handles authentication checks with both client and server side methods.
 * This is the standard way to protect pages in CarFuse - never use direct $_SESSION checks.
 * 
 * Usage: include this file with parameters:
 *   $auth_redirect - URL to redirect to if not authenticated (default: '/auth/login')
 *   $required_role - Role required for access (optional)
 *   $return_url    - Whether to include return URL in redirect (default: true)
 *   $api_role_check - Whether to use API-based role verification (default: true)
 *   $show_messages - Whether to show error messages before redirecting (default: false)
 */

// Default parameters
$auth_redirect = $auth_redirect ?? '/auth/login';
$return_url = isset($return_url) ? (bool)$return_url : true;
$required_role = $required_role ?? null;
$api_role_check = isset($api_role_check) ? (bool)$api_role_check : true;
$show_messages = isset($show_messages) ? (bool)$show_messages : false;

// Set the redirect URL with optional return path
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$redirect_url = $auth_redirect;
if ($return_url) {
    $redirect_url .= (strpos($redirect_url, '?') === false ? '?' : '&') . 'redirect=' . urlencode($current_url);
}

// Define unauthorized redirect based on role
$unauthorized_redirect = '/dashboard';
?>

<!-- Client-side Authentication Check -->
<script>
(function() {
    if (typeof window.AuthHelper === 'undefined') {
        console.error('AuthHelper is not loaded! Authentication check cannot be performed.');
        return;
    }
    
    const auth = window.AuthHelper;
    
    // Basic authentication check
    if (!auth.isAuthenticated()) {
        <?php if ($show_messages): ?>
        window.dispatchEvent(new CustomEvent('show-toast', {
            detail: {
                title: 'Wymagane logowanie',
                message: 'Musisz być zalogowany, aby zobaczyć tę stronę.',
                type: 'warning'
            }
        }));
        setTimeout(() => {
            window.location.href = '<?= $redirect_url ?>';
        }, 1500);
        <?php else: ?>
        window.location.href = '<?= $redirect_url ?>';
        <?php endif; ?>
        return;
    }
    
    <?php if ($required_role && $api_role_check): ?>
    // Enhanced role check using API
    auth.fetchUserData()
        .then(userData => {
            const userRole = userData.role;
            if (userRole !== '<?= $required_role ?>') {
                <?php if ($show_messages): ?>
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: {
                        title: 'Brak uprawnień',
                        message: 'Nie masz wystarczających uprawnień, aby zobaczyć tę stronę.',
                        type: 'error'
                    }
                }));
                setTimeout(() => {
                    window.location.href = '<?= $unauthorized_redirect ?>';
                }, 1500);
                <?php else: ?>
                window.location.href = '<?= $unauthorized_redirect ?>';
                <?php endif; ?>
            }
        })
        .catch(error => {
            console.error('Error verifying user role:', error);
            <?php if ($show_messages): ?>
            window.dispatchEvent(new CustomEvent('show-toast', {
                detail: {
                    title: 'Błąd weryfikacji',
                    message: 'Wystąpił problem z weryfikacją uprawnień.',
                    type: 'error'
                }
            }));
            setTimeout(() => {
                window.location.href = '<?= $redirect_url ?>';
            }, 1500);
            <?php else: ?>
            window.location.href = '<?= $redirect_url ?>';
            <?php endif; ?>
        });
    <?php elseif ($required_role): ?>
    // Basic role check using token data (less secure, but faster)
    if (auth.getUserRole() !== '<?= $required_role ?>') {
        <?php if ($show_messages): ?>
        window.dispatchEvent(new CustomEvent('show-toast', {
            detail: {
                title: 'Brak uprawnień',
                message: 'Nie masz wystarczających uprawnień, aby zobaczyć tę stronę.',
                type: 'error'
            }
        }));
        setTimeout(() => {
            window.location.href = '<?= $unauthorized_redirect ?>';
        }, 1500);
        <?php else: ?>
        window.location.href = '<?= $unauthorized_redirect ?>';
        <?php endif; ?>
    }
    <?php endif; ?>
})();
</script>

<!-- Server-side Authentication Check (for noscript and initial page load) -->
<?php
// Server-side auth check (for users with JavaScript disabled or initial page load)
$is_authenticated = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$has_required_role = true;

if ($required_role && $is_authenticated) {
    $has_required_role = isset($_SESSION['user_role']) && $_SESSION['user_role'] === $required_role;
}

// Perform server-side redirect if not authenticated or unauthorized
if (!$is_authenticated) {
    if ($show_messages) {
        // Store a message in session to be displayed on login page
        $_SESSION['auth_message'] = 'Musisz się zalogować, aby uzyskać dostęp do tej strony.';
    }
    header("Location: {$redirect_url}");
    exit;
} elseif ($required_role && !$has_required_role) {
    if ($show_messages) {
        // Store a message in session to be displayed on redirect page
        $_SESSION['auth_message'] = 'Nie masz wystarczających uprawnień, aby zobaczyć tę stronę.';
    }
    header("Location: {$unauthorized_redirect}");
    exit;
}
?>

<!-- Fallback for noscript browsers -->
<noscript>
    <?php if (!$is_authenticated): ?>
    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
        <p class="text-red-700">Musisz być zalogowany, aby zobaczyć tę stronę.</p>
        <p class="text-sm text-red-600 mt-1">
            JavaScript jest wyłączony. Przekierowywanie do strony logowania...
        </p>
    </div>
    <meta http-equiv="refresh" content="2;url=<?= $redirect_url ?>">
    <?php elseif ($required_role && !$has_required_role): ?>
    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
        <p class="text-red-700">Nie masz wystarczających uprawnień, aby zobaczyć tę stronę.</p>
        <p class="text-sm text-red-600 mt-1">
            JavaScript jest wyłączony. Przekierowywanie do dashboard...
        </p>
    </div>
    <meta http-equiv="refresh" content="2;url=<?= $unauthorized_redirect ?>">
    <?php endif; ?>
</noscript>
