/**
 * StudentHub Portal — Practical 6: External JSON & Fetch API Module
 * Handles asynchronous data loading, live search, multi-field filtering,
 * client-side sorting, pagination, and robust error/empty states.
 */

document.addEventListener("DOMContentLoaded", () => {
    // State management object
    const state = {
        activeTab: "students", // "students" | "events" | "faqs"
        cache: {
            students: null,
            events: null,
            faqs: null
        },
        searchQuery: "",
        categoryFilter: "all",
        sortOrder: "default",
        currentPage: 1,
        pageSize: 6,
        isLoading: false
    };

    // DOM Elements
    const tabs = document.querySelectorAll(".tab-btn");
    const searchInput = document.getElementById("searchInput");
    const clearSearchBtn = document.getElementById("clearSearchBtn");
    const categoryFilter = document.getElementById("categoryFilter");
    const categoryFilterLabel = document.getElementById("categoryFilterLabel");
    const sortOrder = document.getElementById("sortOrder");
    const pageSizeSelect = document.getElementById("pageSizeSelect");
    const resetBtn = document.getElementById("resetBtn");
    const emptyResetBtn = document.getElementById("emptyResetBtn");
    const retryBtn = document.getElementById("retryBtn");

    const loadingSpinner = document.getElementById("loadingSpinner");
    const errorContainer = document.getElementById("errorContainer");
    const errorMessage = document.getElementById("errorMessage");
    const emptyContainer = document.getElementById("emptyContainer");
    const recordsGrid = document.getElementById("recordsGrid");
    const resultsCount = document.getElementById("resultsCount");
    const activeDatasetTag = document.getElementById("activeDatasetTag");

    const paginationWrapper = document.getElementById("paginationWrapper");
    const prevPageBtn = document.getElementById("prevPageBtn");
    const nextPageBtn = document.getElementById("nextPageBtn");
    const pageButtons = document.getElementById("pageButtons");

    // Filter and Sort Configuration per Tab
    const tabConfigs = {
        students: {
            datasetName: "data/students.json",
            filterLabel: "Department:",
            filterOptions: [
                { value: "all", text: "All Departments" },
                { value: "Computer Engineering", text: "Computer Engineering" },
                { value: "Information Technology", text: "Information Technology" },
                { value: "Computer Science", text: "Computer Science" },
                { value: "Electronics Engineering", text: "Electronics Engineering" }
            ],
            sortOptions: [
                { value: "default", text: "Default (Student ID)" },
                { value: "name_asc", text: "Name (A-Z)" },
                { value: "name_desc", text: "Name (Z-A)" },
                { value: "gpa_desc", text: "Highest GPA" },
                { value: "gpa_asc", text: "Lowest GPA" }
            ],
            searchPlaceholder: "Search students by name, ID, or department..."
        },
        events: {
            datasetName: "data/events.json",
            filterLabel: "Category:",
            filterOptions: [
                { value: "all", text: "All Categories" },
                { value: "Technical", text: "Technical" },
                { value: "Workshop", text: "Workshop" },
                { value: "Hackathon", text: "Hackathon" },
                { value: "Cultural", text: "Cultural" },
                { value: "Sports", text: "Sports" },
                { value: "Seminar", text: "Seminar" }
            ],
            sortOptions: [
                { value: "default", text: "Default (Upcoming First)" },
                { value: "title_asc", text: "Title (A-Z)" },
                { value: "title_desc", text: "Title (Z-A)" },
                { value: "date_asc", text: "Date (Earliest First)" },
                { value: "date_desc", text: "Date (Latest First)" }
            ],
            searchPlaceholder: "Search events by title, category, or venue..."
        },
        faqs: {
            datasetName: "data/faqs.json",
            filterLabel: "Topic:",
            filterOptions: [
                { value: "all", text: "All Topics" },
                { value: "Academics", text: "Academics" },
                { value: "Attendance", text: "Attendance" },
                { value: "Examination", text: "Examination" },
                { value: "Events", text: "Events" },
                { value: "Portal Support", text: "Portal Support" }
            ],
            sortOptions: [
                { value: "default", text: "Default (Topic Order)" },
                { value: "question_asc", text: "Question (A-Z)" }
            ],
            searchPlaceholder: "Search FAQs by question or keyword..."
        }
    };

    // Helper: Escape HTML to prevent XSS
    function escapeHtml(str) {
        if (str === null || str === undefined) return "";
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // UI Helper: Update Filter and Sort Select Elements
    function updateControlsForTab(tabKey) {
        const config = tabConfigs[tabKey];
        if (!config) return;

        searchInput.placeholder = config.searchPlaceholder;
        activeDatasetTag.textContent = "Dataset: " + config.datasetName;
        if (categoryFilterLabel) {
            categoryFilterLabel.textContent = config.filterLabel;
        }

        // Populate Category Filter
        categoryFilter.innerHTML = "";
        config.filterOptions.forEach(opt => {
            const option = document.createElement("option");
            option.value = opt.value;
            option.textContent = opt.text;
            categoryFilter.appendChild(option);
        });
        categoryFilter.value = "all";
        state.categoryFilter = "all";

        // Populate Sort Order
        sortOrder.innerHTML = "";
        config.sortOptions.forEach(opt => {
            const option = document.createElement("option");
            option.value = opt.value;
            option.textContent = opt.text;
            sortOrder.appendChild(option);
        });
        sortOrder.value = "default";
        state.sortOrder = "default";
    }

    // Fetch API Implementation with Error Handling
    async function fetchDataset(tabKey, forceReload = false) {
        if (!forceReload && state.cache[tabKey]) {
            renderView();
            return;
        }

        const config = tabConfigs[tabKey];
        if (!config) return;

        state.isLoading = true;
        showLoadingState();

        try {
            // Using relative URL to data/*.json
            const url = config.datasetName;
            const response = await fetch(url, { cache: "no-store" });

            if (!response.ok) {
                throw new Error(`HTTP Error: ${response.status} ${response.statusText} while fetching ${url}`);
            }

            const data = await response.json();

            if (!Array.isArray(data)) {
                throw new Error(`Invalid JSON format: expected array, received ${typeof data}`);
            }

            state.cache[tabKey] = data;
            state.isLoading = false;
            renderView();
        } catch (err) {
            console.error("Fetch API error:", err);
            state.isLoading = false;
            let displayMsg = err.message;
            if (window.location.protocol === "file:") {
                displayMsg = "Browser security blocks Fetch API requests over file:// protocol (CORS). Please access this page through your local Apache/PHP server (e.g., http://localhost/PRC_WDF/Final_Practical/Directory.html).";
            }
            showErrorState(displayMsg);
        }
    }

    // View States Display Helpers
    function showLoadingState() {
        loadingSpinner.style.display = "block";
        errorContainer.style.display = "none";
        emptyContainer.style.display = "none";
        recordsGrid.style.display = "none";
        paginationWrapper.style.display = "none";
        resultsCount.textContent = "Loading dataset...";
    }

    function showErrorState(msg) {
        loadingSpinner.style.display = "none";
        errorContainer.style.display = "flex";
        errorMessage.textContent = msg || "An error occurred while loading data.";
        emptyContainer.style.display = "none";
        recordsGrid.style.display = "none";
        paginationWrapper.style.display = "none";
        resultsCount.textContent = "Error loading records.";
    }

    function showEmptyState() {
        loadingSpinner.style.display = "none";
        errorContainer.style.display = "none";
        emptyContainer.style.display = "block";
        recordsGrid.style.display = "none";
        paginationWrapper.style.display = "none";
        resultsCount.textContent = "No records match your criteria.";
    }

    function showRecordsState() {
        loadingSpinner.style.display = "none";
        errorContainer.style.display = "none";
        emptyContainer.style.display = "none";
        recordsGrid.style.display = "grid";
        paginationWrapper.style.display = "flex";
    }

    // Filter, Search, and Sort Pipeline
    function getProcessedData() {
        const rawData = state.cache[state.activeTab] || [];
        const query = state.searchQuery.toLowerCase().trim();
        const category = state.categoryFilter;

        // 1. Filter by Category / Department
        let filtered = rawData.filter(item => {
            if (category === "all") return true;
            if (state.activeTab === "students") {
                return item.department === category;
            } else if (state.activeTab === "events") {
                return item.category === category;
            } else if (state.activeTab === "faqs") {
                return item.category === category;
            }
            return true;
        });

        // 2. Search Filter
        if (query !== "") {
            filtered = filtered.filter(item => {
                if (state.activeTab === "students") {
                    return (
                        (item.name && item.name.toLowerCase().includes(query)) ||
                        (item.student_id && item.student_id.toLowerCase().includes(query)) ||
                        (item.department && item.department.toLowerCase().includes(query)) ||
                        (item.course && item.course.toLowerCase().includes(query)) ||
                        (item.email && item.email.toLowerCase().includes(query))
                    );
                } else if (state.activeTab === "events") {
                    return (
                        (item.title && item.title.toLowerCase().includes(query)) ||
                        (item.event_id && item.event_id.toLowerCase().includes(query)) ||
                        (item.category && item.category.toLowerCase().includes(query)) ||
                        (item.venue && item.venue.toLowerCase().includes(query)) ||
                        (item.date && item.date.toLowerCase().includes(query)) ||
                        (item.description && item.description.toLowerCase().includes(query)) ||
                        (item.organizer && item.organizer.toLowerCase().includes(query))
                    );
                } else if (state.activeTab === "faqs") {
                    return (
                        (item.question && item.question.toLowerCase().includes(query)) ||
                        (item.faq_id && item.faq_id.toLowerCase().includes(query)) ||
                        (item.answer && item.answer.toLowerCase().includes(query)) ||
                        (item.category && item.category.toLowerCase().includes(query))
                    );
                }
                return false;
            });
        }

        // 3. Client-Side Sorting
        const sortType = state.sortOrder;
        filtered.sort((a, b) => {
            if (sortType === "name_asc") {
                return (a.name || "").localeCompare(b.name || "");
            } else if (sortType === "name_desc") {
                return (b.name || "").localeCompare(a.name || "");
            } else if (sortType === "gpa_desc") {
                return (b.gpa || 0) - (a.gpa || 0);
            } else if (sortType === "gpa_asc") {
                return (a.gpa || 0) - (b.gpa || 0);
            } else if (sortType === "title_asc") {
                return (a.title || "").localeCompare(b.title || "");
            } else if (sortType === "title_desc") {
                return (b.title || "").localeCompare(a.title || "");
            } else if (sortType === "date_asc") {
                return (a.date || "").localeCompare(b.date || "");
            } else if (sortType === "date_desc") {
                return (b.date || "").localeCompare(a.date || "");
            } else if (sortType === "question_asc") {
                return (a.question || "").localeCompare(b.question || "");
            } else if (sortType === "default") {
                if (state.activeTab === "students") {
                    return (a.student_id || "").localeCompare(b.student_id || "");
                } else if (state.activeTab === "events") {
                    return (a.date || "").localeCompare(b.date || "");
                } else if (state.activeTab === "faqs") {
                    return (a.faq_id || "").localeCompare(b.faq_id || "");
                }
            }
            return 0;
        });

        return filtered;
    }

    // Card Renderer for Students
    function renderStudentCard(student) {
        const initials = ((student.name || "ST").trim())
            .split(/\s+/)
            .map(part => part[0])
            .join("")
            .slice(0, 2)
            .toUpperCase();

        return `
            <div class="record-card student-card">
                <div>
                    <div class="card-top">
                        <div class="card-avatar">${escapeHtml(initials)}</div>
                        <div class="card-title-group">
                            <h3>${escapeHtml(student.name)}</h3>
                            <span class="card-badge">${escapeHtml(student.student_id)}</span>
                        </div>
                    </div>

                    <ul class="card-meta-list">
                        <li><strong>Department:</strong> <span>${escapeHtml(student.department)}</span></li>
                        <li><strong>Course:</strong> <span>${escapeHtml(student.course)}</span></li>
                        <li><strong>Academic Year:</strong> <span>${escapeHtml(student.year)}</span></li>
                        <li><strong>Semester:</strong> <span>${escapeHtml(student.semester)}</span></li>
                        <li><strong>Current GPA:</strong> <span class="gpa-chip">${Number(student.gpa).toFixed(2)}</span></li>
                    </ul>
                </div>

                <div class="card-footer-info">
                    📧 <a href="mailto:${escapeHtml(student.email)}">${escapeHtml(student.email)}</a><br>
                    📞 ${escapeHtml(student.mobile)}
                </div>
            </div>
        `;
    }

    // Card Renderer for Events
    function renderEventCard(evt) {
        const statusClass = evt.status === "Open" ? "status-open" : "status-closed";
        return `
            <div class="record-card event-card">
                <div>
                    <div class="event-header">
                        <span class="event-category-badge">${escapeHtml(evt.category)}</span>
                        <span class="event-status ${statusClass}">${escapeHtml(evt.status)}</span>
                    </div>

                    <h3 class="event-title">${escapeHtml(evt.title)}</h3>
                    <p class="event-description">${escapeHtml(evt.description)}</p>

                    <ul class="card-meta-list">
                        <li><strong>📅 Date:</strong> <span>${escapeHtml(evt.date)} at ${escapeHtml(evt.time)}</span></li>
                        <li><strong>📍 Venue:</strong> <span>${escapeHtml(evt.venue)}</span></li>
                        <li><strong>👥 Organizer:</strong> <span>${escapeHtml(evt.organizer)}</span></li>
                        <li><strong>🎟️ Seats Available:</strong> <span>${escapeHtml(evt.seats_available)}</span></li>
                    </ul>
                </div>

                <div class="card-footer-info">
                    ID: <code>${escapeHtml(evt.event_id)}</code>
                </div>
            </div>
        `;
    }

    // Card Renderer for FAQs
    function renderFaqCard(faq) {
        return `
            <div class="record-card faq-card">
                <div>
                    <span class="faq-category-tag">${escapeHtml(faq.category)}</span>
                    <h3 class="faq-question-title">❓ ${escapeHtml(faq.question)}</h3>
                    <div class="faq-answer-box">
                        <p>${escapeHtml(faq.answer)}</p>
                    </div>
                </div>

                <div class="card-footer-info">
                    FAQ Ref: <code>${escapeHtml(faq.faq_id)}</code>
                </div>
            </div>
        `;
    }

    // Pagination Renderer
    function renderPagination(totalItems, totalPages) {
        if (totalPages <= 1) {
            paginationWrapper.style.display = "none";
            return;
        }
        paginationWrapper.style.display = "flex";

        prevPageBtn.disabled = state.currentPage === 1;
        nextPageBtn.disabled = state.currentPage === totalPages;

        pageButtons.innerHTML = "";
        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = "page-num-btn" + (i === state.currentPage ? " active" : "");
            btn.textContent = i;
            btn.setAttribute("aria-label", `Page ${i}`);
            btn.addEventListener("click", () => {
                state.currentPage = i;
                renderView();
                window.scrollTo({ top: recordsGrid.offsetTop - 120, behavior: "smooth" });
            });
            pageButtons.appendChild(btn);
        }
    }

    // Master Render Function
    function renderView() {
        const processed = getProcessedData();
        const totalItems = processed.length;

        if (totalItems === 0) {
            showEmptyState();
            return;
        }

        showRecordsState();

        const totalPages = Math.ceil(totalItems / state.pageSize);
        if (state.currentPage > totalPages) {
            state.currentPage = totalPages;
        }

        const startIndex = (state.currentPage - 1) * state.pageSize;
        const endIndex = Math.min(startIndex + state.pageSize, totalItems);
        const pageItems = processed.slice(startIndex, endIndex);

        resultsCount.textContent = `Showing ${startIndex + 1}–${endIndex} of ${totalItems} records`;

        // Render card markup
        let cardsHtml = "";
        if (state.activeTab === "students") {
            cardsHtml = pageItems.map(renderStudentCard).join("");
        } else if (state.activeTab === "events") {
            cardsHtml = pageItems.map(renderEventCard).join("");
        } else if (state.activeTab === "faqs") {
            cardsHtml = pageItems.map(renderFaqCard).join("");
        }
        recordsGrid.innerHTML = cardsHtml;

        renderPagination(totalItems, totalPages);
    }

    // Reset All Search and Filter Inputs
    function resetAllFilters() {
        state.searchQuery = "";
        state.categoryFilter = "all";
        state.sortOrder = "default";
        state.currentPage = 1;

        searchInput.value = "";
        categoryFilter.value = "all";
        sortOrder.value = "default";
        clearSearchBtn.style.display = "none";

        renderView();
    }

    // Event Listeners: Tab Switching
    tabs.forEach(tab => {
        tab.addEventListener("click", function () {
            const selectedTab = this.getAttribute("data-tab");
            if (selectedTab === state.activeTab) return;

            tabs.forEach(t => {
                t.classList.remove("active");
                t.setAttribute("aria-selected", "false");
            });
            this.classList.add("active");
            this.setAttribute("aria-selected", "true");

            state.activeTab = selectedTab;
            state.currentPage = 1;
            state.searchQuery = "";
            searchInput.value = "";
            clearSearchBtn.style.display = "none";

            updateControlsForTab(selectedTab);
            fetchDataset(selectedTab);
        });
    });

    // Event Listener: Live Search with Debounce
    let searchDebounceTimeout = null;
    searchInput.addEventListener("input", function () {
        const val = this.value;
        clearSearchBtn.style.display = val.length > 0 ? "block" : "none";

        clearTimeout(searchDebounceTimeout);
        searchDebounceTimeout = setTimeout(() => {
            state.searchQuery = val;
            state.currentPage = 1;
            renderView();
        }, 200);
    });

    clearSearchBtn.addEventListener("click", () => {
        searchInput.value = "";
        clearSearchBtn.style.display = "none";
        state.searchQuery = "";
        state.currentPage = 1;
        renderView();
    });

    // Event Listener: Category Filter Dropdown
    categoryFilter.addEventListener("change", function () {
        state.categoryFilter = this.value;
        state.currentPage = 1;
        renderView();
    });

    // Event Listener: Sort Order Dropdown
    sortOrder.addEventListener("change", function () {
        state.sortOrder = this.value;
        renderView();
    });

    // Event Listener: Page Size Select
    pageSizeSelect.addEventListener("change", function () {
        state.pageSize = parseInt(this.value, 10);
        state.currentPage = 1;
        renderView();
    });

    // Event Listeners: Prev / Next Pagination
    prevPageBtn.addEventListener("click", () => {
        if (state.currentPage > 1) {
            state.currentPage--;
            renderView();
            window.scrollTo({ top: recordsGrid.offsetTop - 120, behavior: "smooth" });
        }
    });

    nextPageBtn.addEventListener("click", () => {
        const processed = getProcessedData();
        const totalPages = Math.ceil(processed.length / state.pageSize);
        if (state.currentPage < totalPages) {
            state.currentPage++;
            renderView();
            window.scrollTo({ top: recordsGrid.offsetTop - 120, behavior: "smooth" });
        }
    });

    // Event Listeners: Reset & Retry
    resetBtn.addEventListener("click", resetAllFilters);
    emptyResetBtn.addEventListener("click", resetAllFilters);
    retryBtn.addEventListener("click", () => {
        fetchDataset(state.activeTab, true);
    });

    // Initial Bootstrap with URL parameter support (?tab=events or ?tab=faqs)
    const urlParams = new URLSearchParams(window.location.search);
    const requestedTab = urlParams.get("tab");
    const validTabs = ["students", "events", "faqs"];
    const initialTab = validTabs.includes(requestedTab) ? requestedTab : "students";

    state.activeTab = initialTab;
    tabs.forEach(t => {
        const isMatch = t.getAttribute("data-tab") === initialTab;
        t.classList.toggle("active", isMatch);
        t.setAttribute("aria-selected", isMatch ? "true" : "false");
    });

    updateControlsForTab(initialTab);
    fetchDataset(initialTab);
});
