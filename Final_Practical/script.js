document.addEventListener("DOMContentLoaded", () => {
    
    // 1. Light/Dark Theme Switcher (localStorage)
    const themeToggleBtn = document.getElementById("theme-toggle");
    const currentTheme = localStorage.getItem("theme");

    if (currentTheme === "dark") {
        document.body.classList.add("dark-mode");
    }

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener("click", () => {
            document.body.classList.toggle("dark-mode");
            let theme = "light";
            if (document.body.classList.contains("dark-mode")) {
                theme = "dark";
            }
            localStorage.setItem("theme", theme);
        });
    }

    // 2. Notification Banner Dismissal
    const banner = document.getElementById("notification-banner");
    const closeBannerBtn = document.getElementById("close-banner");

    if (closeBannerBtn && banner) {
        closeBannerBtn.addEventListener("click", () => {
            banner.style.display = "none";
        });
    }

    // 3. Modal Popup Logic
    const modal = document.getElementById("welcome-modal");
    const openModalBtn = document.getElementById("open-modal-btn");
    const closeModalBtn = document.querySelector(".close-modal");

    if (modal && openModalBtn && closeModalBtn) {
        openModalBtn.addEventListener("click", () => {
            modal.style.display = "flex";
        });
        closeModalBtn.addEventListener("click", () => {
            modal.style.display = "none";
        });
        window.addEventListener("click", (event) => {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        });
    }

    // 4. Collapsible Logic (For Notices)
    const collapsibles = document.querySelectorAll(".collapsible-btn");

    collapsibles.forEach(button => {
        button.addEventListener("click", function() {
            this.classList.toggle("active");
            const content = this.nextElementSibling;
            if (content.style.display === "block") {
                content.style.display = "none";
            } else {
                content.style.display = "block";
            }
        });
    });
});