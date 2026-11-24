/**
 * ==========================================================
 * Online Job Portal System
 * Admin — Analytics Charts
 * File: assets/js/admin/analytics.js
 *
 * Requires:
 *   - Chart.js v4 (loaded via CDN in analytics.php)
 *   - window.adminAnalytics (JSON payload)
 * ==========================================================
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        if (typeof window.Chart === 'undefined') {
            console.warn('[analytics] Chart.js not loaded');
            return;
        }

        var data = window.adminAnalytics;
        if (!data || typeof data !== 'object') {
            console.warn('[analytics] payload missing');
            return;
        }

        /* ------------------------------------------------------
         | Palette
         * ------------------------------------------------------ */

        var palette = {
            primary:   '#2563eb',
            purple:    '#7c3aed',
            cyan:      '#0891b2',
            orange:    '#ea580c',
            green:     '#16a34a',
            emerald:   '#059669',
            red:       '#dc2626',
            amber:     '#d97706',
            slate:     '#94a3b8',
        };

        var gridColor   = 'rgba(148, 163, 184, 0.20)';
        var tickColor   = '#94a3b8';

        Chart.defaults.font.family = "'Poppins', sans-serif";
        Chart.defaults.font.size   = 11;
        Chart.defaults.color       = tickColor;

        /* ------------------------------------------------------
         | Helpers
         * ------------------------------------------------------ */

        function gradientFill(ctx, topColor, bottomColor) {
            var chart = ctx.chart;
            var area = chart.chartArea;
            if (!area) {
                return topColor;
            }
            var g = chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
            g.addColorStop(0, topColor);
            g.addColorStop(1, bottomColor);
            return g;
        }

        function isDarkMode() {
            return document.body.classList.contains('dark-mode');
        }

        /* ------------------------------------------------------
         | 1. Signups chart
         * ------------------------------------------------------ */

        var signupsCanvas = document.getElementById('signupsChart');
        if (signupsCanvas && data.signups) {

            new Chart(signupsCanvas, {
                type: 'line',
                data: {
                    labels: data.signups.labels,
                    datasets: [{
                        label: 'New Users',
                        data: data.signups.values,
                        borderColor: palette.primary,
                        backgroundColor: function (ctx) {
                            return gradientFill(ctx, 'rgba(37, 99, 235, 0.28)', 'rgba(37, 99, 235, 0)');
                        },
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: palette.primary,
                        pointHoverBorderWidth: 2,
                    }]
                },
                options: chartOptions()
            });
        }

        /* ------------------------------------------------------
         | 2. Jobs vs Applications
         * ------------------------------------------------------ */

        var jobsAppsCanvas = document.getElementById('jobsAppsChart');
        if (jobsAppsCanvas && data.jobs && data.applications) {

            new Chart(jobsAppsCanvas, {
                type: 'line',
                data: {
                    labels: data.jobs.labels,
                    datasets: [
                        {
                            label: 'Jobs posted',
                            data: data.jobs.values,
                            borderColor: palette.cyan,
                            backgroundColor: 'rgba(8, 145, 178, 0.10)',
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                        },
                        {
                            label: 'Applications',
                            data: data.applications.values,
                            borderColor: palette.orange,
                            backgroundColor: 'rgba(234, 88, 12, 0.10)',
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                        }
                    ]
                },
                options: chartOptions(true)
            });
        }

        /* ------------------------------------------------------
         | 3. Role distribution (doughnut)
         * ------------------------------------------------------ */

        var roleCanvas = document.getElementById('roleChart');
        if (roleCanvas && data.roleDistribution) {

            new Chart(roleCanvas, {
                type: 'doughnut',
                data: {
                    labels: data.roleDistribution.labels,
                    datasets: [{
                        data: data.roleDistribution.values,
                        backgroundColor: [
                            palette.amber,
                            palette.purple,
                            palette.primary,
                        ],
                        borderColor: isDarkMode() ? '#1e293b' : '#fff',
                        borderWidth: 3,
                        hoverOffset: 6,
                    }]
                },
                options: doughnutOptions()
            });
        }

        /* ------------------------------------------------------
         | 4. Application status (doughnut)
         * ------------------------------------------------------ */

        var statusCanvas = document.getElementById('statusChart');
        if (statusCanvas && data.statusDistribution) {

            new Chart(statusCanvas, {
                type: 'doughnut',
                data: {
                    labels: data.statusDistribution.labels,
                    datasets: [{
                        data: data.statusDistribution.values,
                        backgroundColor: [
                            '#3b82f6', // Applied
                            '#f59e0b', // Under Review
                            '#8b5cf6', // Shortlisted
                            '#06b6d4', // Interview
                            '#16a34a', // Selected
                            '#dc2626', // Rejected
                            '#94a3b8', // Withdrawn
                        ],
                        borderColor: isDarkMode() ? '#1e293b' : '#fff',
                        borderWidth: 3,
                        hoverOffset: 6,
                    }]
                },
                options: doughnutOptions()
            });
        }

        /* ------------------------------------------------------
         | 5. Jobs by category (horizontal bar)
         * ------------------------------------------------------ */

        var categoryCanvas = document.getElementById('categoryChart');
        if (categoryCanvas && data.jobsByCategory) {

            new Chart(categoryCanvas, {
                type: 'bar',
                data: {
                    labels: data.jobsByCategory.labels,
                    datasets: [{
                        label: 'Jobs',
                        data: data.jobsByCategory.values,
                        backgroundColor: 'rgba(37, 99, 235, 0.75)',
                        hoverBackgroundColor: palette.primary,
                        borderRadius: 6,
                        borderSkipped: false,
                    }]
                },
                options: horizontalBarOptions()
            });
        }

        /* ------------------------------------------------------
         | 6. Skills in demand (horizontal bar)
         * ------------------------------------------------------ */

        var skillsCanvas = document.getElementById('skillsChart');
        if (skillsCanvas && data.topSkills) {

            new Chart(skillsCanvas, {
                type: 'bar',
                data: {
                    labels: data.topSkills.labels,
                    datasets: [{
                        label: 'Open jobs',
                        data: data.topSkills.values,
                        backgroundColor: 'rgba(22, 163, 74, 0.75)',
                        hoverBackgroundColor: palette.green,
                        borderRadius: 6,
                        borderSkipped: false,
                    }]
                },
                options: horizontalBarOptions()
            });
        }

        /* ------------------------------------------------------
         | Option factories
         * ------------------------------------------------------ */

        function chartOptions(showLegend) {
            return {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: !!showLegend,
                        position: 'top',
                        align: 'end',
                        labels: {
                            boxWidth: 10,
                            boxHeight: 10,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 14,
                            font: { size: 11 },
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: true,
                        boxPadding: 4,
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: tickColor,
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 12,
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor, drawBorder: false },
                        ticks: {
                            color: tickColor,
                            precision: 0,
                        }
                    }
                },
                animation: {
                    duration: 600,
                    easing: 'easeOutQuart',
                }
            };
        }

        function doughnutOptions() {
            return {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 10,
                            boxHeight: 10,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 12,
                            font: { size: 11 },
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 10,
                        cornerRadius: 8,
                    }
                }
            };
        }

        function horizontalBarOptions() {
            return {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 10,
                        cornerRadius: 8,
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: gridColor, drawBorder: false },
                        ticks: {
                            color: tickColor,
                            precision: 0,
                        }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            color: tickColor,
                            font: { size: 11 },
                            autoSkip: false,
                        }
                    }
                },
                animation: {
                    duration: 600,
                    easing: 'easeOutQuart',
                }
            };
        }

        /* ------------------------------------------------------
         | Re-render doughnuts on theme change
         * ------------------------------------------------------ */

        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                if (m.attributeName === 'class') {
                    Chart.helpers.each(Chart.instances, function (chart) {
                        if (chart.config.type === 'doughnut') {
                            var bc = isDarkMode() ? '#1e293b' : '#fff';
                            chart.data.datasets.forEach(function (ds) {
                                ds.borderColor = bc;
                            });
                            chart.update('none');
                        }
                    });
                }
            });
        });

        observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
    });

})();