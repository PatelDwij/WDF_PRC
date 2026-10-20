document.addEventListener("DOMContentLoaded", () => {
    
    // 1. Light/Dark Theme Switcher (localStorage)
    const themeToggleBtn = document.getElementById("theme-toggle");
    const currentTheme = localStorage.getItem("theme");

    function applyTheme(theme) {
        if (theme === "dark") {
            document.body.classList.add("dark-mode");
            if (themeToggleBtn) themeToggleBtn.textContent = "☀️ Light";
        } else {
            document.body.classList.remove("dark-mode");
            if (themeToggleBtn) themeToggleBtn.textContent = "🌙 Theme";
        }
    }

    if (currentTheme === "dark") {
        applyTheme("dark");
    }

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener("click", () => {
            const isDark = document.body.classList.toggle("dark-mode");
            const newTheme = isDark ? "dark" : "light";
            localStorage.setItem("theme", newTheme);
            applyTheme(newTheme);
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

    // 4. Collapsible Logic (For Notices & FAQs)
    const collapsibles = document.querySelectorAll(".collapsible-btn, .faq-question");

    collapsibles.forEach(button => {
        button.addEventListener("click", function() {
            this.classList.toggle("active");
            const content = this.nextElementSibling;
            if (content) {
                if (content.style.display === "block") {
                    content.style.display = "none";
                } else {
                    content.style.display = "block";
                }
            }
        });
    });

    // 5. Content / Image Carousel Slider
    const slides = document.querySelectorAll(".carousel-slide");
    const prevBtn = document.querySelector(".carousel-prev");
    const nextBtn = document.querySelector(".carousel-next");
    const dots = document.querySelectorAll(".carousel-dot");
    let currentSlide = 0;

    function showSlide(index) {
        if (!slides.length) return;
        if (index >= slides.length) currentSlide = 0;
        else if (index < 0) currentSlide = slides.length - 1;
        else currentSlide = index;

        slides.forEach((slide, i) => {
            slide.classList.toggle("active", i === currentSlide);
        });

        dots.forEach((dot, i) => {
            dot.classList.toggle("active", i === currentSlide);
        });
    }

    if (slides.length > 0) {
        if (prevBtn) {
            prevBtn.addEventListener("click", () => {
                showSlide(currentSlide - 1);
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener("click", () => {
                showSlide(currentSlide + 1);
            });
        }
        dots.forEach((dot, i) => {
            dot.addEventListener("click", () => {
                showSlide(i);
            });
        });
        // Initial slide show
        showSlide(0);
    }

    // 6. Mobile Hamburger Navigation
    const hamburgerBtn = document.querySelector(".hamburger-btn");
    const navMenu = document.querySelector("nav ul");

    if (hamburgerBtn && navMenu) {
        hamburgerBtn.addEventListener("click", () => {
            navMenu.classList.toggle("nav-open");
            const isOpen = navMenu.classList.contains("nav-open");
            hamburgerBtn.setAttribute("aria-expanded", isOpen ? "true" : "false");
        });
    }
});