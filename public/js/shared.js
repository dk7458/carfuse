document.addEventListener("DOMContentLoaded", function () {
    console.log("Minimal JavaScript Loaded");

    fetch("/api/test")
        .then(response => response.json())
        .then(data => console.log("API Response:", data))
        .catch(error => console.error("API Error:", error));
});
