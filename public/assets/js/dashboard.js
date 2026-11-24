"use strict";

/*
==========================================================
 Online Job Portal System
 Dashboard JavaScript
 File: assets/js/dashboard.js
==========================================================
*/

document.addEventListener("DOMContentLoaded", () => {

    const sidebar = document.querySelector(".dashboard-sidebar");
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebarOverlay = document.getElementById("sidebarOverlay");
    const userMenuToggle = document.getElementById("userMenuButton");
    const userDropdown = document.getElementById("userMenuDropdown");
    const searchForm = document.querySelector(".dashboard-search-form");
    const searchInput = document.querySelector(".dashboard-search input");
    const clearSearchButton = document.querySelector(".dashboard-search-clear");
    const sidebarLinks = document.querySelectorAll(".sidebar-link");

    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.add("mobile-open");
        if (sidebarOverlay) sidebarOverlay.classList.add("show");
        document.body.classList.add("sidebar-open");
    }

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove("mobile-open");
        if (sidebarOverlay) sidebarOverlay.classList.remove("show");
        document.body.classList.remove("sidebar-open");
    }

    function toggleSidebar() {
        if (!sidebar) return;
        if (sidebar.classList.contains("mobile-open")) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener("click", toggleSidebar);
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener("click", closeSidebar);
    }

    function openUserDropdown() {
        if (!userDropdown) return;
        userDropdown.classList.add("show");
        if (userMenuToggle) userMenuToggle.setAttribute("aria-expanded", "true");
    }

    function closeUserDropdown() {
        if (!userDropdown) return;
        userDropdown.classList.remove("show");
        if (userMenuToggle) userMenuToggle.setAttribute("aria-expanded", "false");
    }

    function toggleUserDropdown(event) {
        event.stopPropagation();
        if (!userDropdown) return;
        if (userDropdown.classList.contains("show")) {
            closeUserDropdown();
        } else {
            openUserDropdown();
        }
    }

    if (userMenuToggle) {
        userMenuToggle.addEventListener("click", toggleUserDropdown);
    }

    document.addEventListener("click", (event) => {
        if (!userDropdown || !userMenuToggle) return;

        const target = event.target;
        if (!(target instanceof Node)) return;

        const clickedInsideMenu = userDropdown.contains(target);
        const clickedToggle = userMenuToggle.contains(target);

        if (!clickedInsideMenu && !clickedToggle) {
            closeUserDropdown();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key !== "Escape") return;
        closeUserDropdown();
        closeSidebar();
    });

    function handleResize() {
        if (window.innerWidth > 992) {
            closeSidebar();
        }
    }

    window.addEventListener("resize", handleResize);

    sidebarLinks.forEach((link) => {
        link.addEventListener("click", function () {
            if (window.innerWidth <= 992) {
                closeSidebar();
            }
        });
    });

    function updateSearchState() {
        if (!searchForm || !searchInput) return;

        const hasValue = searchInput.value.trim().length > 0;
        searchForm.classList.toggle("search-active", hasValue);
    }

    if (searchInput) {
        updateSearchState();
        searchInput.addEventListener("input", updateSearchState);
    }

    if (clearSearchButton && searchForm && searchInput) {
        clearSearchButton.addEventListener("click", () => {
            searchInput.value = "";
            updateSearchState();
            searchInput.focus();
            searchForm.submit();
        });
    }

    if (userDropdown) {
        userDropdown.addEventListener("click", (event) => {
            event.stopPropagation();
        });
    }

    function updateBodyScroll() {
        if (window.innerWidth <= 992 && sidebar && sidebar.classList.contains("mobile-open")) {
            document.body.style.overflow = "hidden";
        } else {
            document.body.style.overflow = "";
        }
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener("click", updateBodyScroll);
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener("click", updateBodyScroll);
    }

    window.addEventListener("resize", updateBodyScroll);

    const countUrl = document.querySelector("[data-unread-count-url]")?.dataset.unreadCountUrl;

    if (countUrl) {
        const updateUnreadCounts = () => {
            fetch(countUrl, {
                credentials: "same-origin",
                headers: { Accept: "application/json" }
            })
                .then((response) => response.json().then((data) => {
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || "Unable to refresh unread counts.");
                    }

                    document.querySelectorAll("[data-unread-messages]").forEach((badge) => {
                        const count = Math.max(0, Number(data.unread_messages) || 0);
                        badge.textContent = count > 99 ? "99+" : String(count);
                        badge.classList.toggle("is-hidden", count === 0);
                        badge.setAttribute("aria-label", document.documentElement.lang === "ne"
                            ? `${count} नपढिएका सन्देशहरू`
                            : (count === 1 ? "1 unread message" : `${count} unread messages`));
                    });

                    document.querySelectorAll("[data-unread-count]").forEach((badge) => {
                        const count = Math.max(0, Number(data.unread_notifications) || 0);
                        badge.textContent = count > 99 ? "99+" : String(count);
                        badge.classList.toggle("is-hidden", count === 0);
                        badge.setAttribute("aria-label", count === 1 ? "1 unread notification" : `${count} unread notifications`);
                    });
                }))
                .catch((error) => console.error("Unread count refresh failed:", error));
        };

        updateUnreadCounts();
        window.setInterval(updateUnreadCounts, 15000);
    }

    handleResize();
    closeUserDropdown();
    updateBodyScroll();

    console.log("Online Job Portal Dashboard JavaScript Loaded Successfully.");
});