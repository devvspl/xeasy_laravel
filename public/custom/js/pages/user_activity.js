$(document).ready(function () {
    let loginData = [];
    let filteredData = [];
    let charts = {};
    let currentFilters = {
        dateFrom: null,
        dateTo: null,
        userIds: [],
        filterType: "all",
    };

    const today = new Date();
    const datePicker = flatpickr("#dateRange", {
        mode: "range",
        dateFormat: "d M, Y",
        maxDate: new Date(today.setHours(23, 59, 59, 999)),
        defaultDate: [
            new Date(today.getFullYear(), today.getMonth() - 1, 1),
            today,
        ],
        onClose: function (selectedDates) {
            if (selectedDates.length === 2) {
                currentFilters.dateFrom = formatDateForAPI(selectedDates[0]);
                currentFilters.dateTo = formatDateForAPI(selectedDates[1]);
            } else {
                currentFilters.dateFrom = null;
                currentFilters.dateTo = null;
            }
            fetchDashboardData();
        },
    });

    function formatDateForAPI(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        return `${year}-${month}-${day}`;
    }

    if (datePicker.selectedDates.length === 2) {
        currentFilters.dateFrom = formatDateForAPI(datePicker.selectedDates[0]);
        currentFilters.dateTo = formatDateForAPI(datePicker.selectedDates[1]);
    }

    $("#filterType").on("change", function () {
        currentFilters.filterType = $(this).val();
        fetchDashboardData();
    });

    $("#sortBy").on("change", sortData);

    $("#selectAllUsers").on("change", function () {
        $(".user-checkbox").prop("checked", this.checked);
        updateSelectedUsersText();
        updateCurrentUserFilters();
        fetchDashboardData();
    });

    $(document).on("change", ".user-checkbox", function () {
        updateSelectedUsersText();
        updateCurrentUserFilters();
        fetchDashboardData();
    });

    function updateCurrentUserFilters() {
        const checkedUsers = $(".user-checkbox:checked");
        const selectAllUsers = $("#selectAllUsers");

        if (checkedUsers.length === 0 || selectAllUsers.prop("checked")) {
            currentFilters.userIds = [];
            selectAllUsers.prop("checked", true);
        } else {
            currentFilters.userIds = checkedUsers
                .map(function () {
                    return $(this).val();
                })
                .get();
            selectAllUsers.prop("checked", false);
        }
    }

    function updateSelectedUsersText() {
        const checkedUsers = $(".user-checkbox:checked");
        const selectAllUsers = $("#selectAllUsers");
        const selectedUsersSpan = $("#selectedUsers");
        const totalUsers = $(".user-checkbox").length;

        if (checkedUsers.length === 0 || checkedUsers.length === totalUsers) {
            selectedUsersSpan.text("All Users");
            selectAllUsers.prop("checked", true);
        } else if (checkedUsers.length === 1) {
            const userName = checkedUsers
                .closest("li")
                .find("label")
                .text()
                .split(" (")[0]
                .trim();
            selectedUsersSpan.text(userName);
        } else {
            selectedUsersSpan.text(`${checkedUsers.length} Users Selected`);
        }
    }

    function fetchDashboardData() {
        const params = new URLSearchParams();

        if (currentFilters.dateFrom && currentFilters.dateTo) {
            params.append("date_from", currentFilters.dateFrom);
            params.append("date_to", currentFilters.dateTo);
        }

        if (currentFilters.userIds.length > 0) {
            currentFilters.userIds.forEach((id) =>
                params.append("user_ids[]", id)
            );
        }

        if (currentFilters.filterType !== "all") {
            if (currentFilters.filterType === "multiple_ip") {
                params.append("filter_type", "multiple_ip");
            } else {
                params.append("login_method", currentFilters.filterType);
            }
        }

        $.ajax({
            url: `/user-activity/data?${params.toString()}`,
            method: "GET",
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    loginData = response.data.activities || [];
                    filteredData = [...loginData];
                    updateDashboard(response.data.stats || {});
                    createCharts();
                    sortData();
                } else {
                    console.error("Error in response:", response.message);
                    $("#loginTable tbody").html(
                        '<tr><td colspan="8" class="text-center text-muted py-4">Error fetching data</td></tr>'
                    );
                }
            },
            error: function (xhr, status, error) {
                console.error("Error fetching dashboard data:", error);
                $("#loginTable tbody").html(
                    '<tr><td colspan="8" class="text-center text-muted py-4">Error fetching data</td></tr>'
                );
            },
        });
    }

    function updateDashboard(stats) {
        $("#totalLogins").text(stats.total_logins || 0);
        $("#uniqueUsers").text(stats.unique_users || 0);
        $("#tokenLogins").text(stats.token_logins || 0);
        $("#uniqueIPs").text(stats.unique_ips || 0);
        $("#peakHour").text(stats.peak_hour ? `${stats.peak_hour}:00` : "N/A");
        $("#multiIPUsers").text(stats.multiple_ip_users || 0);
    }

    function sortData() {
        const sortBy = $("#sortBy").val();

        filteredData.sort(function (a, b) {
            switch (sortBy) {
                case "timestamp":
                    return new Date(b.timestamp) - new Date(a.timestamp);
                case "user_id":
                    return (a.user_name || "").localeCompare(b.user_name || "");
                case "login_method":
                    return (a.login_method || "").localeCompare(
                        b.login_method || ""
                    );
                default:
                    return 0;
            }
        });

        updateTable();
    }

    function updateTable() {
        const tbody = $("#loginTable tbody");
        tbody.empty();

        if (!filteredData.length) {
            tbody.append(
                '<tr><td colspan="8" class="text-center text-muted py-4">No login data found for the selected filters</td></tr>'
            );
            return;
        }

        filteredData.forEach((login, index) => {
            const employeeId =
                login.employee_id && login.employee_id !== "N/A"
                    ? login.employee_id
                    : "--";
            const row = `
                <tr>
                    <td>${index + 1}</td>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-bold">${
                                login.user_name || "Unknown User"
                            }</span>
                            <small class="text-muted">${
                                login.user_email || "N/A"
                            }</small>
                        </div>
                    </td>
                    <td>${employeeId}</td>
                    <td><code>${login.ip_address || "N/A"}</code></td>
                   <td>
                        <span class="badge ${
                            login.login_method === "token"
                                ? "bg-primary-subtle text-primary"
                                : "bg-secondary-subtle text-secondary"
                        }">
                            ${
                                login.login_method
                                    ? login.login_method
                                          .charAt(0)
                                          .toUpperCase() +
                                      login.login_method.slice(1)
                                    : "N/A"
                            }
                        </span>
                    </td>
                    <td>${
                        login.timestamp
                            ? new Date(login.timestamp).toLocaleString()
                            : "N/A"
                    }</td>
                    <td class="small text-muted" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;" title="${
                        login.user_agent || ""
                    }">
                        ${login.user_agent || "N/A"}
                    </td>
                    <td class="text-center"><span class="badge ${
                        login.is_success ? "bg-success" : "bg-danger"
                    }">
                        ${login.is_success ? "Success" : "Failed"}
                    </span></td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    function createCharts() {
        Object.values(charts).forEach((chart) => chart.destroy());
        charts = {};

        createHourlyChart();
        createMethodChart();
        createUserChart();
        createDailyChart();
    }

    function createHourlyChart() {
        const hourCounts = Array(24).fill(0);
        filteredData.forEach((login) => {
            if (login.timestamp) {
                const hour = new Date(login.timestamp).getHours();
                hourCounts[hour]++;
            }
        });

        const ctx = document.getElementById("hourlyChart").getContext("2d");
        charts.hourlyChart = new Chart(ctx, {
            type: "line",
            data: {
                labels: Array.from({ length: 24 }, (_, i) => `${i}:00`),
                datasets: [
                    {
                        label: "Logins per Hour",
                        data: hourCounts,
                        borderColor: "#4BC0C0",
                        backgroundColor: "rgba(75, 192, 192, 0.2)",
                        tension: 0.4,
                        fill: true,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                    },
                },
            },
        });
    }

    function createMethodChart() {
        const tokenCount = filteredData.filter(
            (login) => login.login_method === "token"
        ).length;
        const normalCount = filteredData.filter(
            (login) => login.login_method === "normal"
        ).length;

        const ctx = document.getElementById("methodChart").getContext("2d");
        charts.methodChart = new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: ["Token", "Normal"],
                datasets: [
                    {
                        data: [tokenCount, normalCount],
                        backgroundColor: ["#36A2EB", "#FF6384"],
                    },
                ],
            },
            options: { responsive: true, maintainAspectRatio: false },
        });
    }

    function createUserChart() {
        const userCounts = {};
        filteredData.forEach((login) => {
            const key = login.user_name || `User ${login.user_id || "Unknown"}`;
            userCounts[key] = (userCounts[key] || 0) + 1;
        });

        const sortedUsers = Object.entries(userCounts)
            .sort(([, a], [, b]) => b - a)
            .slice(0, 10);

        const ctx = document.getElementById("userChart").getContext("2d");
        charts.userChart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: sortedUsers.map(([name]) => name),
                datasets: [
                    {
                        label: "Logins",
                        data: sortedUsers.map(([, count]) => count),
                        backgroundColor: "#FFCE56",
                    },
                ],
            },
            options: { responsive: true, maintainAspectRatio: false },
        });
    }

    function createDailyChart() {
        const dailyCounts = {};
        filteredData.forEach((login) => {
            if (login.timestamp) {
                const date = new Date(login.timestamp).toDateString();
                dailyCounts[date] = (dailyCounts[date] || 0) + 1;
            }
        });
        const sortedDates = Object.keys(dailyCounts).sort(
            (a, b) => new Date(a) - new Date(b)
        );

        const ctx = document.getElementById("dailyChart").getContext("2d");
        charts.dailyChart = new Chart(ctx, {
            type: "line",
            data: {
                labels: sortedDates,
                datasets: [
                    {
                        label: "Logins per Day",
                        data: sortedDates.map((date) => dailyCounts[date]),
                        borderColor: "#9966FF",
                        backgroundColor: "rgba(153, 102, 255, 0.2)",
                        tension: 0.3,
                        fill: true,
                    },
                ],
            },
            options: { responsive: true, maintainAspectRatio: false },
        });
    }

    updateSelectedUsersText();
    fetchDashboardData();
});
