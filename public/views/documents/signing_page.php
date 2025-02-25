<?php
// Sprawdzenie sesji użytkownika

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit();
}
?>

/*
|--------------------------------------------------------------------------
| Podpisywanie Dokumentów
|--------------------------------------------------------------------------
| Ten plik umożliwia użytkownikowi podpisywanie dokumentów online.
| Podpisane dokumenty są przechowywane w systemie.
|
| Ścieżka: App/Views/documents/signing_page.php
|
| Zależy od:
| - JavaScript: /js/dashboard.js (obsługa AJAX, podpis elektroniczny)
| - CSS: /css/dashboard.css (stylizacja interfejsu użytkownika)
| - PHP: csrf_field() (zabezpieczenie formularzy)
| - MySQL (dane dokumentów)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (Canvas API do podpisywania dokumentów)
| - HTML, CSS (interfejs)
*/

<h1 class="text-center">Podpisywanie Dokumentów</h1>

<div class="documents-sign-container">
    <h3 class="mt-4">Dokumenty do Podpisania</h3>
    <ul id="signDocumentList" class="list-group">
        <!-- Dokumenty ładowane dynamicznie -->
    </ul>

    <!-- Podgląd dokumentu i podpis elektroniczny -->
    <div id="signingArea" class="mt-4" style="display:none;">
        <h4>Podpisz Dokument</h4>
        <iframe id="documentPreview" style="width:100%; height:400px; border:1px solid #ccc;" onload="resizeIframe(this)"></iframe>
        
        <canvas id="signaturePad" class="border mt-3" style="width:100%; height:200px;" onmousedown="startDrawing(event)" onmouseup="stopDrawing()" onmousemove="draw(event)" ontouchstart="startDrawing(event)" ontouchend="stopDrawing()" ontouchmove="draw(event)"></canvas>
        <button class="btn btn-secondary mt-2" onclick="clearSignature()">Wyczyść</button>
        <button class="btn btn-primary mt-2" onclick="submitSignature()">Podpisz</button>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    function loadSignDocuments() {
        fetch("/api/user/get_signable_documents.php")
            .then(response => response.json())
            .then(data => {
                const documentList = document.getElementById("signDocumentList");
                documentList.innerHTML = "";

                if (data.length > 0) {
                    data.forEach(doc => {
                        documentList.innerHTML += `
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>${doc.name}</span>
                                <button class="btn btn-sm btn-success" onclick="openSigning('${doc.file_url}', ${doc.id})">Podpisz</button>
                            </li>
                        `;
                    });
                } else {
                    documentList.innerHTML = `<li class="list-group-item text-muted">Brak dokumentów do podpisania</li>`;
                }
            })
            .catch(error => {
                console.error("Błąd ładowania dokumentów:", error);
                alert("Błąd ładowania dokumentów.");
            });
    }

    function openSigning(fileUrl, documentId) {
        document.getElementById("signingArea").style.display = "block";
        document.getElementById("documentPreview").src = fileUrl;
        document.getElementById("signaturePad").dataset.documentId = documentId;
    }

    function clearSignature() {
        const canvas = document.getElementById("signaturePad");
        const ctx = canvas.getContext("2d");
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }

    function submitSignature() {
        const canvas = document.getElementById("signaturePad");
        const signature = canvas.toDataURL();
        const documentId = canvas.dataset.documentId;

        fetch("/api/user/sign_document.php", {
            method: "POST",
            body: JSON.stringify({ documentId, signature }),
            headers: { "Content-Type": "application/json" }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Dokument został podpisany!");
                loadSignDocuments();
                document.getElementById("signingArea").style.display = "none";
            } else {
                alert("Błąd: " + data.error);
            }
        })
        .catch(error => {
            console.error("Błąd podpisywania dokumentu:", error);
            alert("Błąd podpisywania dokumentu.");
        });
    }

    loadSignDocuments();
});

function resizeIframe(iframe) {
    iframe.style.height = iframe.contentWindow.document.documentElement.scrollHeight + 'px';
}

let isDrawing = false;
let lastX = 0;
let lastY = 0;
const canvas = document.getElementById("signaturePad");
const ctx = canvas.getContext("2d");

function startDrawing(event) {
    isDrawing = true;
    [lastX, lastY] = getCoordinates(event);
}

function stopDrawing() {
    isDrawing = false;
    ctx.beginPath();
}

function draw(event) {
    if (!isDrawing) return;
    const [x, y] = getCoordinates(event);
    ctx.lineWidth = 2;
    ctx.lineCap = "round";
    ctx.strokeStyle = "#000";
    ctx.lineTo(x, y);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(x, y);
    [lastX, lastY] = [x, y];
}

function getCoordinates(event) {
    const rect = canvas.getBoundingClientRect();
    const x = (event.clientX || event.touches[0].clientX) - rect.left;
    const y = (event.clientY || event.touches[0].clientY) - rect.top;
    return [x, y];
}
</script>
