document.addEventListener("DOMContentLoaded", function () {
    const cookieBanner = document.getElementById("cookie-banner");
    const acceptCookies = document.getElementById("accept-cookies");
    const manageConsent = document.getElementById("manage-consent");

    // ✅ Check if user has already given consent
    if (document.cookie.includes("cookie_consent=true")) {
        cookieBanner.style.display = "none";
    }

    // ✅ Handle Consent Acceptance
    acceptCookies.addEventListener("click", function () {
        fetch("/api/consent/accept.php", { method: "POST" })
            .then(() => {
                document.cookie = "cookie_consent=true; path=/; max-age=31536000";
                cookieBanner.style.display = "none";
            });
    });

    // ✅ Manage Consent Preferences
    manageConsent.addEventListener("click", function () {
        window.location.href = "/cookie_policy.php";
    });
});
