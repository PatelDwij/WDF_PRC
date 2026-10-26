/**
 * StudentHub Portal - Asynchronous API Client
 * Practical 13: Fetch API Helper with CSRF & Error Handling
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

const StudentHubAPI = {
    csrfToken: "",

    init(token) {
        this.csrfToken = token || document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
    },

    async request(url, options = {}) {
        const headers = {
            "Accept": "application/json",
            "X-CSRF-Token": this.csrfToken,
            ...(options.headers || {})
        };

        if (options.body && typeof options.body === "object" && !(options.body instanceof FormData)) {
            headers["Content-Type"] = "application/json";
            options.body = JSON.stringify(options.body);
        }

        try {
            const response = await fetch(url, { ...options, headers });
            const data = await response.json();

            if (!response.ok) {
                const error = new Error(data.message || `Request failed with status ${response.status}`);
                error.status = response.status;
                error.data = data;
                throw error;
            }

            return data;
        } catch (err) {
            console.error("[StudentHub API Error]", err);
            throw err;
        }
    },

    // Students REST
    getStudents(params = {}) {
        const qs = new URLSearchParams(params).toString();
        return this.request(`api/students.php?${qs}`);
    },

    getStudent(id) {
        return this.request(`api/students.php?id=${encodeURIComponent(id)}`);
    },

    createStudent(payload) {
        return this.request("api/students.php", {
            method: "POST",
            body: payload
        });
    },

    updateStudent(payload) {
        return this.request("api/students.php", {
            method: "PUT",
            body: payload
        });
    },

    deleteStudent(id) {
        return this.request(`api/students.php?id=${encodeURIComponent(id)}`, {
            method: "DELETE"
        });
    },

    // Events REST
    getEvents(params = {}) {
        const qs = new URLSearchParams(params).toString();
        return this.request(`api/events.php?${qs}`);
    },

    registerForEvent(eventId) {
        return this.request("api/registrations.php", {
            method: "POST",
            body: { event_id: eventId }
        });
    },

    cancelEventRegistration(eventId) {
        return this.request(`api/registrations.php?event_id=${encodeURIComponent(eventId)}`, {
            method: "DELETE"
        });
    }
};

// Debounce helper
function debounce(func, delay = 300) {
    let timer;
    return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => func.apply(this, args), delay);
    };
}
