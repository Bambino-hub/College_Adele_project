// assets/surveillance.js

window.generateSurveillance = function (examId) {
    const button = document.getElementById("generate-btn");

    button.disabled = true;
    button.innerText = "Génération en cours...";

    fetch(`/exam/${examId}/generate-surveillance`, {
        method: "POST",
        headers: {
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                showMessage(data.message, "success");

                // Recharge la page pour voir le nouveau tableau
                setTimeout(() => {
                    window.location.reload();
                }, 800);
            } else {
                showMessage(data.message, "danger");
            }
        })
        .catch((error) => {
            showMessage("Une erreur est survenue.", "danger");
            console.error(error);
        })
        .finally(() => {
            button.disabled = false;
            button.innerText = "Générer le tableau";
        });
};

function showMessage(message, type) {
    const container = document.getElementById("message");

    container.innerHTML = `
        <div class="alert alert-${type}">
            ${message}
        </div>
    `;
}
