// File Path: /assets/js/dynamic_pricing.js
// Description: Handles AJAX actions for creating, editing, and deleting dynamic pricing rules in the admin dynamic pricing manager.
// Changelog:
// - Initial creation of the dynamic pricing manager script.

document.addEventListener("DOMContentLoaded", function () {
    const pricingRuleForm = document.getElementById("pricingRuleForm");

    /**
     * Function: Submit the pricing rule form.
     */
    pricingRuleForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const formData = new FormData(pricingRuleForm);
        const data = Object.fromEntries(formData.entries());

        const action = data.rule_id ? "edit_rule" : "create_rule";

        fetch("/public/api.php?endpoint=pricing&action=" + action, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                ...data,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    alert(data.success);
                    location.reload();
                } else {
                    alert(data.error || "Wystąpił błąd podczas zapisywania reguły.");
                }
            })
            .catch((error) => {
                console.error("Błąd:", error);
                alert("Błąd sieci. Spróbuj ponownie później.");
            });
    });

    /**
     * Function: Edit an existing pricing rule.
     */
    document.querySelectorAll(".edit-rule").forEach((button) => {
        button.addEventListener("click", function () {
            const ruleId = this.dataset.id;
            const ruleName = this.dataset.name;
            const criteria = this.dataset.criteria;
            const adjustment = this.dataset.adjustment;

            document.getElementById("rule_id").value = ruleId;
            document.getElementById("rule_name").value = ruleName;
            document.getElementById("criteria").value = criteria;
            document.getElementById("adjustment").value = adjustment;
        });
    });

    /**
     * Function: Delete a pricing rule.
     */
    document.querySelectorAll(".delete-rule").forEach((button) => {
        button.addEventListener("click", function () {
            const ruleId = this.dataset.id;

            if (!ruleId || !confirm("Czy na pewno chcesz usunąć tę regułę?")) {
                return;
            }

            fetch("/public/api.php?endpoint=pricing&action=delete_rule", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    rule_id: ruleId,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        alert(data.success);
                        location.reload();
                    } else {
                        alert(data.error || "Wystąpił błąd podczas usuwania reguły.");
                    }
                })
                .catch((error) => {
                    console.error("Błąd:", error);
                    alert("Błąd sieci. Spróbuj ponownie później.");
                });
        });
    });

    fetch('/public/api.php?endpoint=pricing&action=fetch_dynamic')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch dynamic pricing');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('Dynamic Pricing:', data.pricing);
            } else {
                console.error('Error:', data.error);
            }
        })
        .catch(error => {
            console.error('Unexpected error:', error);
        });

    document.getElementById('filterButton').addEventListener('click', () => {
        const search = document.getElementById('searchInput').value;
        const startDate = document.getElementById('startDateInput').value;
        const endDate = document.getElementById('endDateInput').value;

        fetch(`/public/api.php?endpoint=dynamic_pricing&action=fetch_pricing&search=${search}&startDate=${startDate}&endDate=${endDate}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const pricingTableBody = document.getElementById('pricingTableBody');
                    pricingTableBody.innerHTML = '';
                    data.pricing.forEach(price => {
                        const row = `<tr>
                            <td>${price.id}</td>
                            <td>${price.name}</td>
                            <td>${price.value}</td>
                            <td>${price.date}</td>
                        </tr>`;
                        pricingTableBody.innerHTML += row;
                    });
                }
            })
            .catch(error => console.error('Error:', error));
    });
});
