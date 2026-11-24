(function () {
    'use strict';

    const palette = {
        blue: '#3b82f6',
        violet: '#8b5cf6',
        amber: '#f59e0b',
        green: '#10b981',
        teal: '#14b8a6',
        red: '#ef4444',
        slate: '#94a3b8'
    };

    function readAnalyticsData() {
        const element = document.getElementById('employerAnalyticsData');
        if (!element) {
            return null;
        }

        try {
            return JSON.parse(element.textContent || '{}');
        } catch (error) {
            console.error('Unable to read employer analytics data.', error);
            return null;
        }
    }

    function toNumber(value) {
        const number = Number(value);
        return Number.isFinite(number) && number >= 0 ? number : 0;
    }

    function showChartMessage(canvas, message) {
        const messageElement = document.createElement('div');
        messageElement.className = 'analytics-chart-message';
        messageElement.textContent = message;
        canvas.replaceWith(messageElement);
    }

    function showChartWarning() {
        const warning = document.createElement('div');
        warning.className = 'analytics-chart-warning';
        warning.setAttribute('role', 'status');
        warning.textContent = 'Chart visualizations are unavailable right now. Your analytics and tables are still available below.';

        const hero = document.querySelector('.analytics-hero');
        if (hero) {
            hero.insertAdjacentElement('afterend', warning);
        }
    }

    function buildDoughnutChart(canvasId, statusData, colors) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            return;
        }

        const labels = Object.keys(statusData || {});
        const values = labels.map(function (label) {
            return toNumber(statusData[label]);
        });

        if (values.every(function (value) { return value === 0; })) {
            showChartMessage(canvas, 'No data available yet');
            return;
        }

        new window.Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderColor: document.body.classList.contains('dark-mode')
                        ? '#111c2e'
                        : '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '64%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 15,
                            boxWidth: 8,
                            font: { size: 10, weight: '600' }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const total = context.dataset.data.reduce(function (sum, value) {
                                    return sum + toNumber(value);
                                }, 0);
                                const value = toNumber(context.raw);
                                const percent = total ? (value / total * 100).toFixed(1) : '0.0';
                                return ' ' + context.label + ': ' + value + ' (' + percent + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    function truncateLabel(label, maxLength) {
        const text = String(label || '');
        return text.length > maxLength ? text.slice(0, maxLength - 1).trim() + '…' : text;
    }

    function buildJobChart(data) {
        const canvas = document.getElementById('jobPerformanceChart');
        if (!canvas) {
            return;
        }

        const labels = Array.isArray(data.jobLabels) ? data.jobLabels : [];
        const applications = Array.isArray(data.jobApplications) ? data.jobApplications : [];
        const selected = Array.isArray(data.jobSelected) ? data.jobSelected : [];

        if (labels.length === 0 || applications.every(function (value) {
            return toNumber(value) === 0;
        })) {
            showChartMessage(canvas, 'No job application data available yet');
            return;
        }

        new window.Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels.map(function (label) {
                    return truncateLabel(label, 32);
                }),
                datasets: [
                    {
                        label: 'Applications',
                        data: applications.map(toNumber),
                        backgroundColor: 'rgba(59, 130, 246, .78)',
                        borderRadius: 5,
                        barPercentage: .72,
                        categoryPercentage: .75
                    },
                    {
                        label: 'Selected',
                        data: selected.map(toNumber),
                        backgroundColor: 'rgba(16, 185, 129, .82)',
                        borderRadius: 5,
                        barPercentage: .72,
                        categoryPercentage: .75
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 14,
                            boxWidth: 8,
                            font: { size: 10, weight: '600' }
                        }
                    },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grid: { color: 'rgba(148, 163, 184, .14)' }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }

    function initializeCharts() {
        const data = readAnalyticsData();
        if (!data) {
            return;
        }

        if (!window.Chart) {
            showChartWarning();
            document.querySelectorAll('.analytics-chart-wrap canvas').forEach(function (canvas) {
                showChartMessage(canvas, 'Chart unavailable');
            });
            return;
        }

        window.Chart.defaults.font.family = "'Poppins', 'Segoe UI', system-ui, sans-serif";
        window.Chart.defaults.color = '#64748b';

        try {
            buildDoughnutChart(
                'applicationStatusChart',
                data.applicationStatus,
                [palette.blue, palette.amber, palette.violet, palette.teal, palette.green, palette.red, palette.slate]
            );
            buildDoughnutChart(
                'interviewStatusChart',
                data.interviewStatus,
                [palette.blue, palette.green, palette.red, palette.amber]
            );
            buildJobChart(data);
        } catch (error) {
            console.error('Unable to render employer analytics charts.', error);
            showChartWarning();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeCharts, { once: true });
    } else {
        initializeCharts();
    }
})();
