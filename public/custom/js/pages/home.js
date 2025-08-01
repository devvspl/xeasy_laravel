$(document).ready(function () {
    const financialQuarters = [
        { label: "Q1 (Apr - Jun)", startMonth: 3, endMonth: 5 },
        { label: "Q2 (Jul - Sep)", startMonth: 6, endMonth: 8 },
        { label: "Q3 (Oct - Dec)", startMonth: 9, endMonth: 11 },
        { label: "Q4 (Jan - Mar)", startMonth: 0, endMonth: 2 },
    ];

    const today = new Date();
    const currentMonth = today.getMonth();
    const currentYear = today.getFullYear();
    const dateInput = document.getElementById("dateRange");
    let currentAllowedRange = null; // track valid selection range

    // Initialize Flatpickr
    const datePicker = flatpickr(dateInput, {
        mode: "range",
        dateFormat: "d M, Y",
        maxDate: today,
        onClose: function (selectedDates) {
            if (selectedDates.length === 2 && currentAllowedRange) {
                const [start, end] = selectedDates;
                if (
                    start < currentAllowedRange[0] ||
                    end > currentAllowedRange[1]
                ) {
                    alert(
                        "Selected dates must be within the allowed quarter or month range."
                    );
                    datePicker.clear();
                }
            }
        },
    });

    const container = document.getElementById("quarter-buttons");
    const datePickerDiv = document.getElementById("date-picker-wrapper");

    function getDateRange(startMonth, endMonth, year) {
        const crossYear = startMonth > endMonth;
        const startYear = year;
        const endYear = crossYear ? year + 1 : year;

        const start = new Date(startYear, startMonth, 1);
        const end = new Date(endYear, endMonth + 1, 0);

        return [start, end];
    }

    function createButton(label, className, dateRange, isDefault = false) {
        const div = document.createElement("div");
        div.className = "col-auto";
        div.innerHTML = `<button type="button" class="btn ${className} material-shadow-none btn-sm">${label}</button>`;
        const button = div.querySelector("button");

        button.addEventListener("click", () => {
            document
                .querySelectorAll("#quarter-buttons button")
                .forEach((btn) => {
                    btn.classList.remove("btn-primary", "text-white");
                    btn.classList.add("btn-soft-secondary");
                });

            button.classList.remove("btn-soft-secondary");
            button.classList.add("btn-primary", "text-white");

            currentAllowedRange = dateRange.length ? dateRange : null;

            if (currentAllowedRange) {
                const maxEnd = new Date();
                const rangeEnd =
                    currentAllowedRange[1] > maxEnd
                        ? maxEnd
                        : currentAllowedRange[1];
                datePicker.set("minDate", currentAllowedRange[0]);
                datePicker.set("maxDate", rangeEnd);
                datePicker.setDate([currentAllowedRange[0], rangeEnd], true);
            } else {
                datePicker.set("minDate", null);
                datePicker.set("maxDate", today);
                datePicker.clear();
            }
        });

        container.insertBefore(div, datePickerDiv);

        if (isDefault) button.click();
    }

    // Set financial year range for "All"
    // From 1 April currentYear to 31 March nextYear
    const fyStart = new Date(currentYear, 3, 1); // April 1 current year
    const fyEnd = new Date(currentYear + 1, 2, 31); // March 31 next year

    createButton("All", "btn-soft-secondary", [fyStart, fyEnd]);

    // Current Month button active by default
    const firstDay = new Date(currentYear, currentMonth, 1);
    const lastDay = new Date(currentYear, currentMonth + 1, 0);
    createButton(
        "Current Month",
        "btn-soft-secondary",
        [firstDay, lastDay],
        true
    );

    // Calculate current quarter index
    let currentQuarterIndex = -1;
    financialQuarters.forEach((q, index) => {
        if (q.startMonth > q.endMonth) {
            if (currentMonth >= q.startMonth || currentMonth <= q.endMonth) {
                currentQuarterIndex = index;
            }
        } else {
            if (currentMonth >= q.startMonth && currentMonth <= q.endMonth) {
                currentQuarterIndex = index;
            }
        }
    });

    // Add current and past quarters
    financialQuarters.forEach((q, index) => {
        if (index <= currentQuarterIndex) {
            const range = getDateRange(q.startMonth, q.endMonth, currentYear);
            const adjustedRange = [
                range[0],
                range[1] > today ? today : range[1],
            ];
            createButton(q.label, "btn-soft-secondary", adjustedRange);
        }
    });
    // Function to format numbers in Indian format (e.g., 1,49,66,921) without .00 for zero
    function formatNumber(value) {
        const numValue = Number(value || 0);
        return numValue === 0
            ? "0"
            : numValue.toLocaleString("en-IN", {
                  maximumFractionDigits: 0,
              });
    }

    // Function to get chart colors
    function getChartColorsArray(e) {
        if ($("#" + e).length) {
            var theme =
                document.documentElement.getAttribute("data-theme") || "";
            var attr = "data-colors" + (theme ? "-" + theme : "");
            var colors =
                $("#" + e).attr(attr) || $("#" + e).attr("data-colors");
            if (colors) {
                return JSON.parse(colors).map(function (color) {
                    color = color.replace(" ", "");
                    return color.indexOf(",") === -1
                        ? getComputedStyle(
                              document.documentElement
                          ).getPropertyValue(color) || color
                        : "rgba(" +
                              getComputedStyle(
                                  document.documentElement
                              ).getPropertyValue(color.split(",")[0]) +
                              "," +
                              color.split(",")[1] +
                              ")";
                });
            }
            console.warn("data-colors attributes not found on", e);
            return ["#5156be", "#ffbf53", "#2ab57d", "#fd625e"];
        }
    }
    var claimTypeChart;
    // AJAX call to fetch dashboard data
    $.ajax({
        url: "home-data",
        type: "GET",
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
        },
        success: function (data) {
            if (data.error) {
                console.error(data.error);
                return;
            }

            const cardData = data.cardData || {};
            const monthlyTotals = data.monthlyTotals || [];
            const departmentTotals = data.departmentTotals || [];
            const totalAllMonths = data.totalAllMonths || {};
            const yearId = data.yearId;
            const previousYearId = yearId - 1;
            const claimTypeTotals = data.claimTypeTotals || [];
            const yearlyComparison = data.yearlyComparison;
            console.log(yearlyComparison);

            $("#cyExpense").text(
                Number(yearlyComparison.CY_Expense).toLocaleString()
            );
            $("#pyExpense").text(
                Number(yearlyComparison.PY_Expense).toLocaleString()
            );
            $("#variancePercent").text(
                Number(yearlyComparison.Variance_Percentage).toFixed(2) + "%"
            );

            // Populate cards
            const defaultCardData = {
                "Total Expense": 0,
                Deactivate: 0,
                Draft: 0,
                Submitted: 0,
                Filled: 0,
                Verified: 0,
                Approved: 0,
                Financed: 0,
            };
            const mergedCardData = {
                ...defaultCardData,
                ...cardData,
            };
            const cards = [
                {
                    label: "Total Expense",
                    icon: "ri-wallet-3-fill",
                    color: "text-primary",
                    value: mergedCardData["Total Expense"],
                },
                {
                    label: "Deactivate",
                    icon: "ri-archive-line",
                    color: "text-secondary",
                    value: mergedCardData["Deactivate"],
                },
                {
                    label: "Draft",
                    icon: "ri-time-line",
                    color: "text-warning",
                    value: mergedCardData["Draft"],
                },
                {
                    label: "Submitted",
                    icon: "ri-send-plane-fill",
                    color: "text-dark",
                    value: mergedCardData["Submitted"],
                },
                {
                    label: "Filled",
                    icon: "ri-file-list-3-line",
                    color: "text-info",
                    value: mergedCardData["Filled"],
                },
                {
                    label: "Verified",
                    icon: "ri-eye-fill",
                    color: "text-success",
                    value: mergedCardData["Verified"],
                },
                {
                    label: "Approved",
                    icon: "ri-check-double-fill",
                    color: "text-success",
                    value: mergedCardData["Approved"],
                },
                {
                    label: "Financed",
                    icon: "ri-hand-coin-fill",
                    color: "text-primary",
                    value: mergedCardData["Financed"],
                },
            ];

            const $cardContainer = $("#card-container");
            $cardContainer.html(
                cards
                    .map(
                        (card) => `
                    <div class="col-lg-3 col-md-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-light ${
                                            card.color
                                        } rounded-circle fs-3 material-shadow">
                                            <i class="${
                                                card.icon
                                            } align-middle"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">${
                                            card.label
                                        }</p>
                                        <h4 class="mb-0"><span class="counter-value" data-target="${
                                            card.value
                                        }">${formatNumber(
                            card.value
                        )}</span></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `
                    )
                    .join("")
            );

            // Populate modal table
            const $modalTableBody = $("#modal-table-body");
            const $modalTableFooter = $("#modal-table-footer");
            $modalTableBody.html(
                monthlyTotals.length
                    ? monthlyTotals
                          .map(
                              (item) => `
                        <tr>
                            <td>${item.MonthName || "N/A"}</td>
                            <td>${formatNumber(item.FilledTotal)}</td>
                            <td>${formatNumber(item.VerifiedTotal)}</td>
                            <td>${formatNumber(item.ApprovedTotal)}</td>
                            <td>${formatNumber(item.FinancedTotal)}</td>
                        </tr>
                    `
                          )
                          .join("")
                    : `
                        <tr>
                            <td colspan="5" class="text-center">No data available</td>
                        </tr>
                    `
            );
            $modalTableFooter.html(`
                <tr>
                    <td><strong>Total</strong></td>
                    <td><strong>${formatNumber(
                        monthlyTotals.reduce(
                            (sum, item) =>
                                sum + parseFloat(item.FilledTotal || 0),
                            0
                        )
                    )}</strong></td>
                    <td><strong>${formatNumber(
                        monthlyTotals.reduce(
                            (sum, item) =>
                                sum + parseFloat(item.VerifiedTotal || 0),
                            0
                        )
                    )}</strong></td>
                    <td><strong>${formatNumber(
                        monthlyTotals.reduce(
                            (sum, item) =>
                                sum + parseFloat(item.ApprovedTotal || 0),
                            0
                        )
                    )}</strong></td>
                    <td><strong>${formatNumber(
                        monthlyTotals.reduce(
                            (sum, item) =>
                                sum + parseFloat(item.FinancedTotal || 0),
                            0
                        )
                    )}</strong></td>
                </tr>
            `);

            // Populate department table
            const $departmentTableBody = $("#department-table-body");
            const $departmentTableFooter = $("#department-table-footer");

            if (departmentTotals.length) {
                $departmentTableBody.html(
                    departmentTotals
                        .map(
                            (dept) => `
                        <tr>
                            <td style="text-align:left">${
                                dept.department_name
                            } (${dept.department_code})</td>
                            <td>${formatNumber(dept.TotalFinancedTAmt_Y7)}</td>
                            <td>${formatNumber(dept.TotalFinancedTAmt_Y6)}</td>
                            <td>${
                                dept.VariationPercentage !== null
                                    ? formatNumber(dept.VariationPercentage) +
                                      "%"
                                    : "-"
                            }</td>
                        </tr>
                    `
                        )
                        .join("")
                );

                const totalY7 = departmentTotals.reduce(
                    (sum, dept) =>
                        sum + parseFloat(dept.TotalFinancedTAmt_Y7 || 0),
                    0
                );
                const totalY6 = departmentTotals.reduce(
                    (sum, dept) =>
                        sum + parseFloat(dept.TotalFinancedTAmt_Y6 || 0),
                    0
                );
                const variation =
                    totalY6 > 0
                        ? (((totalY7 - totalY6) / totalY6) * 100).toFixed(2)
                        : "-";

                $departmentTableFooter.html(`
                    <tr>
                        <td style="text-align:left"><strong>Total</strong></td>
                        <td><strong>${formatNumber(totalY7)}</strong></td>
                        <td><strong>${formatNumber(totalY6)}</strong></td>
                        <td><strong>${
                            variation !== "-"
                                ? formatNumber(variation) + "%"
                                : "-"
                        }</strong></td>
                    </tr>
                `);
            } else {
                $departmentTableBody.html(`
                    <tr>
                        <td colspan="4" class="text-center">No data available</td>
                    </tr>
                `);
            }

            const $claimTypeTableBody = $("#claim-type-table-body");
            const $claimTypeTableFooter = $("#claim-type-table-footer");

            if (claimTypeTotals.length) {
                $claimTypeTableBody.html(
                    claimTypeTotals
                        .map(
                            (claim) => `
            <tr>
                <td style="text-align:left">${
                    claim.ClaimName || claim.ClaimCode
                }</td>
                <td>${formatNumber(claim.FilledTotal)}</td>
                <td>${formatNumber(claim.VerifiedTotal)}</td>
                <td>${formatNumber(claim.ApprovedTotal)}</td>
                <td>${formatNumber(claim.FinancedTotal)}</td>
            </tr>
        `
                        )
                        .join("")
                );

                const filledTotal = claimTypeTotals.reduce(
                    (sum, c) => sum + parseFloat(c.FilledTotal || 0),
                    0
                );
                const verifiedTotal = claimTypeTotals.reduce(
                    (sum, c) => sum + parseFloat(c.VerifiedTotal || 0),
                    0
                );
                const approvedTotal = claimTypeTotals.reduce(
                    (sum, c) => sum + parseFloat(c.ApprovedTotal || 0),
                    0
                );
                const financedTotal = claimTypeTotals.reduce(
                    (sum, c) => sum + parseFloat(c.FinancedTotal || 0),
                    0
                );

                $claimTypeTableFooter.html(`
        <tr>
            <td style="text-align:left"><strong>Total</strong></td>
            <td><strong>${formatNumber(filledTotal)}</strong></td>
            <td><strong>${formatNumber(verifiedTotal)}</strong></td>
            <td><strong>${formatNumber(approvedTotal)}</strong></td>
            <td><strong>${formatNumber(financedTotal)}</strong></td>
        </tr>
    `);
            } else {
                $claimTypeTableBody.html(`
                <tr>
                    <td colspan="5" class="text-center">No data available</td>
                </tr>
            `);
            }

            // Expense Monthly Chart
            var expenseChartColors = getChartColorsArray(
                "expense-monthly-chart"
            );
            var options = {
                series: [
                    {
                        name: "Filled Total",
                        type: "bar",
                        data: monthlyTotals.map(
                            (item) => item.FilledTotal || 0
                        ),
                    },
                    {
                        name: "Verified Total",
                        type: "bar",
                        data: monthlyTotals.map(
                            (item) => item.VerifiedTotal || 0
                        ),
                    },
                    {
                        name: "Approved Total",
                        type: "bar",
                        data: monthlyTotals.map(
                            (item) => item.ApprovedTotal || 0
                        ),
                    },
                    {
                        name: "Financed Total",
                        type: "area",
                        data: monthlyTotals.map(
                            (item) => item.FinancedTotal || 0
                        ),
                    },
                ],
                chart: {
                    height: 374,
                    type: "line",
                    toolbar: {
                        show: false,
                    },
                },
                stroke: {
                    curve: "smooth",
                    dashArray: [0, 0, 0, 3],
                    width: [0, 0, 0, 1],
                },
                fill: {
                    opacity: [1, 1, 1, 0.1],
                },
                markers: {
                    size: [0, 0, 0, 4],
                    strokeWidth: 2,
                    hover: {
                        size: 4,
                    },
                },
                xaxis: {
                    categories: monthlyTotals.map(
                        (item) => item.MonthName || ""
                    ),
                    axisTicks: {
                        show: false,
                    },
                    axisBorder: {
                        show: false,
                    },
                },
                grid: {
                    show: true,
                    xaxis: {
                        lines: {
                            show: true,
                        },
                    },
                    yaxis: {
                        lines: {
                            show: false,
                        },
                    },
                    padding: {
                        top: 0,
                        right: -2,
                        bottom: 15,
                        left: 10,
                    },
                },
                legend: {
                    show: true,
                    horizontalAlign: "center",
                    offsetX: 0,
                    offsetY: -5,
                    markers: {
                        width: 9,
                        height: 9,
                        radius: 6,
                    },
                    itemMargin: {
                        horizontal: 10,
                        vertical: 0,
                    },
                },
                plotOptions: {
                    bar: {
                        columnWidth: "30%",
                        barHeight: "70%",
                    },
                },
                colors: expenseChartColors,
                tooltip: {
                    shared: true,
                    y: [
                        {
                            formatter: function (val) {
                                return val !== undefined
                                    ? formatNumber(val)
                                    : val;
                            },
                        },
                        {
                            formatter: function (val) {
                                return val !== undefined
                                    ? formatNumber(val)
                                    : val;
                            },
                        },
                        {
                            formatter: function (val) {
                                return val !== undefined
                                    ? formatNumber(val)
                                    : val;
                            },
                        },
                        {
                            formatter: function (val) {
                                return val !== undefined
                                    ? formatNumber(val)
                                    : val;
                            },
                        },
                    ],
                },
            };
            var chart = new ApexCharts(
                document.querySelector("#expense-monthly-chart"),
                options
            );
            chart.render();

            // Department-wise Comparison Chart
            var chartMultiColors = getChartColorsArray("multi_chart");
            var multiOptions = {
                series: [
                    {
                        name: "Current Year",
                        type: "column",
                        data: departmentTotals.map(
                            (item) => item["TotalFinancedTAmt_Y" + yearId] || 0
                        ),
                    },
                    {
                        name: "Previous Year",
                        type: "column",
                        data: departmentTotals.map(
                            (item) =>
                                item["TotalFinancedTAmt_Y" + previousYearId] ||
                                0
                        ),
                    },
                    {
                        name: "Variation (%)",
                        type: "line",
                        data: departmentTotals.map((item) => {
                            var current =
                                item["TotalFinancedTAmt_Y" + yearId] || 0;
                            var previous =
                                item["TotalFinancedTAmt_Y" + previousYearId] ||
                                0;
                            return previous
                                ? (
                                      ((current - previous) / previous) *
                                      100
                                  ).toFixed(2)
                                : 0;
                        }),
                    },
                ],
                chart: {
                    height: 350,
                    type: "line",
                    stacked: false,
                    toolbar: {
                        show: false,
                    },
                },
                dataLabels: {
                    enabled: false,
                },
                stroke: {
                    width: [1, 1, 4],
                },
                xaxis: {
                    categories: departmentTotals.map(
                        (item) => item.department_code || "N/A"
                    ),
                    labels: {
                        rotate: -45,
                        rotateAlways: true,
                        trim: false,
                        style: {
                            fontSize: "11px",
                        },
                    },
                    tooltip: {
                        enabled: true,
                    },
                },
                yaxis: [
                    {
                        axisTicks: {
                            show: true,
                        },
                        axisBorder: {
                            show: true,
                            color: chartMultiColors[0],
                        },
                        labels: {
                            style: {
                                colors: chartMultiColors[0],
                            },
                        },
                        title: {
                            text: "Current Year Expenses",
                            style: {
                                color: chartMultiColors[0],
                                fontWeight: 600,
                            },
                        },
                        tooltip: {
                            enabled: true,
                        },
                    },
                    {
                        seriesName: "Previous Year",
                        opposite: true,
                        axisTicks: {
                            show: true,
                        },
                        axisBorder: {
                            show: true,
                            color: chartMultiColors[1],
                        },
                        labels: {
                            style: {
                                colors: chartMultiColors[1],
                            },
                        },
                        title: {
                            text: "Previous Year Expenses",
                            style: {
                                color: chartMultiColors[1],
                                fontWeight: 600,
                            },
                        },
                    },
                    {
                        seriesName: "Variation",
                        opposite: true,
                        axisTicks: {
                            show: true,
                        },
                        axisBorder: {
                            show: true,
                            color: chartMultiColors[2],
                        },
                        labels: {
                            style: {
                                colors: chartMultiColors[2],
                            },
                        },
                        title: {
                            text: "Variation (%)",
                            style: {
                                color: chartMultiColors[2],
                                fontWeight: 600,
                            },
                        },
                        formatter: function (val) {
                            return formatNumber(val.toFixed(2)) + "%";
                        },
                    },
                ],
                tooltip: {
                    fixed: {
                        enabled: true,
                        position: "topLeft",
                        offsetY: 30,
                        offsetX: 60,
                    },
                    y: [
                        {
                            formatter: function (val) {
                                return val !== undefined
                                    ? formatNumber(val)
                                    : val;
                            },
                        },
                        {
                            formatter: function (val) {
                                return val !== undefined
                                    ? formatNumber(val)
                                    : val;
                            },
                        },
                        {
                            formatter: function (val) {
                                return val !== undefined
                                    ? formatNumber(val.toFixed(2)) + "%"
                                    : val;
                            },
                        },
                    ],
                },
                legend: {
                    horizontalAlign: "center",
                    position: "bottom",
                    fontSize: "14px",
                    fontWeight: 500,
                    markers: {
                        width: 12,
                        height: 12,
                        radius: 12,
                    },
                },
                colors: chartMultiColors,
            };
            var multiChart = new ApexCharts(
                document.querySelector("#multi_chart"),
                multiOptions
            );
            multiChart.render();

            // Claim Type Chart (Modeled for Claim Type-wise Data)
            var claimTypeColors = getChartColorsArray("claim-type-chart");
            var claimTypeOptions = {
                series: [
                    {
                        name: "Filled",
                        type: "area",
                        data: claimTypeTotals.map((item) =>
                            parseFloat(item.FilledTotal || 0)
                        ),
                    },
                    {
                        name: "Verified",
                        type: "bar",
                        data: claimTypeTotals.map((item) =>
                            parseFloat(item.VerifiedTotal || 0)
                        ),
                    },
                    {
                        name: "Approved",
                        type: "line",
                        data: claimTypeTotals.map((item) =>
                            parseFloat(item.ApprovedTotal || 0)
                        ),
                    },
                    {
                        name: "Financed",
                        type: "line",
                        data: claimTypeTotals.map((item) =>
                            parseFloat(item.FinancedTotal || 0)
                        ),
                    },
                ],
                chart: {
                    height: 370,
                    type: "line",
                    toolbar: {
                        show: false,
                    },
                },
                stroke: {
                    curve: "smooth",
                    width: [2, 0, 2.2, 2.2],
                    dashArray: [0, 0, 5, 3],
                },
                fill: {
                    opacity: [0.2, 1, 1, 1],
                },
                markers: {
                    size: [4, 0, 5, 5],
                    hover: {
                        size: 6,
                    },
                },
                xaxis: {
                    categories: claimTypeTotals.map((item) => item.ClaimCode),
                    axisTicks: {
                        show: false,
                    },
                    axisBorder: {
                        show: false,
                    },
                    labels: {
                        rotate: -45,
                        style: {
                            fontSize: "12px",
                        },
                    },
                },
                grid: {
                    show: true,
                    xaxis: {
                        lines: {
                            show: true,
                        },
                    },
                    yaxis: {
                        lines: {
                            show: true,
                        },
                    },
                    padding: {
                        top: 0,
                        right: -2,
                        bottom: 15,
                        left: 10,
                    },
                },
                legend: {
                    show: true,
                    horizontalAlign: "center",
                    offsetX: 0,
                    offsetY: -5,
                    markers: {
                        width: 9,
                        height: 9,
                        radius: 6,
                    },
                    itemMargin: {
                        horizontal: 10,
                        vertical: 0,
                    },
                },
                colors: claimTypeColors,
                plotOptions: {
                    bar: {
                        columnWidth: "30%",
                    },
                },
                tooltip: {
                    shared: true,
                    y: {
                        formatter: function (val) {
                            return val != null ? formatNumber(val) : val;
                        },
                    },
                },
            };

            if (claimTypeChart && typeof claimTypeChart.destroy === "function")
                claimTypeChart.destroy();
            claimTypeChart = new ApexCharts(
                document.querySelector("#claim-type-chart"),
                claimTypeOptions
            );
            claimTypeChart.render();
        },
        error: function (xhr, status, error) {
            console.error("Error fetching dashboard data:", error);
        },
    });
});
