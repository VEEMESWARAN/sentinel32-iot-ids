async function loadAlerts() {

    try {

        const response = await fetch("alerts.php");

        if (!response.ok) {
            throw new Error("Failed to fetch alerts");
        }

        const data = await response.json();

        // Update Statistics Cards
        document.getElementById("totalAlerts").textContent = data.total;
        document.getElementById("criticalAlerts").textContent = data.critical;
        document.getElementById("highAlerts").textContent = data.high;

        // Build Table
        let rows = "";

        data.alerts.forEach(alert => {

            let badgeClass = "";

            switch(alert.threat_level) {

                case "LOW":
                    badgeClass = "bg-success";
                    break;

                case "MEDIUM":
                    badgeClass = "bg-warning text-dark";
                    break;

                case "HIGH":
                    badgeClass = "bg-danger";
                    break;

                case "CRITICAL":
                    badgeClass = "bg-dark text-danger border border-danger";
                    break;

                default:
                    badgeClass = "bg-secondary";
            }

            rows += `
                <tr>
                    <td>${alert.id}</td>
                    <td>${alert.ip_address}</td>
                    <td>${alert.attack_type}</td>
                    <td>
                        <span class="badge ${badgeClass}">
                            ${alert.threat_level}
                        </span>
                    </td>
                    <td>${alert.description}</td>
                    <td>${alert.created_at}</td>
                </tr>
            `;
        });

        document.getElementById("alertTable").innerHTML = rows;

    } catch(error) {

        console.error("Error:", error);

        document.getElementById("alertTable").innerHTML =
        `
        <tr>
            <td colspan="6" class="text-center text-danger">
                Failed to load alerts
            </td>
        </tr>
        `;
    }
}

// Load immediately
loadAlerts();

// Refresh every second
setInterval(loadAlerts, 1000);