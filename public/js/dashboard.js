// dashboard.js

document.addEventListener('DOMContentLoaded', () => {
    // Handle tab switching dynamically
    const tabs = document.querySelectorAll('[data-tab-target]');
    const tabContents = document.querySelectorAll('[data-tab-content]');

    tabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            const target = document.querySelector(tab.dataset.tabTarget);

            tabContents.forEach(content => content.classList.add('hidden'));
            tabs.forEach(t => t.classList.remove('active'));

            target.classList.remove('hidden');
            tab.classList.add('active');
        });
    });

    // Handle AJAX form submissions
    document.querySelectorAll('.ajax-form').forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const formData = new FormData(form);
            const action = form.action;
            const method = form.method;

            fetch(action, {
                method: method,
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showModal('Success', data.message);
                    } else {
                        showModal('Error', data.message);
                    }

                    // Optional: reload or refresh specific sections dynamically
                    if (data.reload) {
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showModal('Error', 'Something went wrong. Please try again later.');
                });
        });
    });

    // Function to show modal messages
    function showModal(title, message) {
        const modalTitle = document.querySelector('#responseModalLabel');
        const modalBody = document.querySelector('#responseMessage');
        const responseModal = new bootstrap.Modal(document.querySelector('#responseModal'));

        modalTitle.textContent = title;
        modalBody.textContent = message;

        responseModal.show();
    }

    // Confirmation prompts for actions
    document.querySelectorAll('[data-confirm]').forEach(button => {
        button.addEventListener('click', (e) => {
            const confirmationMessage = button.dataset.confirm;
            if (!confirm(confirmationMessage)) {
                e.preventDefault();
            }
        });
    });
});

