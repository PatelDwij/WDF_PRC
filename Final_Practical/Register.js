document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("registerForm");
    if (!form) return;

    const studentIdInput = document.getElementById("studentId");
    const nameInput = document.getElementById("fullName");
    const emailInput = document.getElementById("email");
    const passwordInput = document.getElementById("password");
    const confirmPasswordInput = document.getElementById("confirmPassword");
    const mobileInput = document.getElementById("mobile");
    const genderInput = document.getElementById("gender");
    const yearInput = document.getElementById("year");
    const semesterInput = document.getElementById("semester");
    const courseInput = document.getElementById("course");
    const roleInput = document.getElementById("role");
    const termsInput = document.getElementById("terms");

    const namePattern = /^[A-Za-z ]{3,50}$/;
    const emailPattern = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;
    const mobilePattern = /^[6-9][0-9]{9}$/;

    function setError(elementId, message) {
        const el = document.getElementById(elementId);
        if (el) {
            el.textContent = message;
        }
    }

    function clearAllErrors() {
        const errorIds = [
            "studentIdError",
            "nameError",
            "emailError",
            "passwordError",
            "confirmPasswordError",
            "mobileError",
            "genderError",
            "yearError",
            "semesterError",
            "courseError",
            "roleError",
            "termsError",
            "successMessage"
        ];
        errorIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = "";
        });
    }

    // Password strength calculation
    function evaluatePasswordStrength(password) {
        if (!password) {
            return { label: "", className: "" };
        }
        let score = 0;
        if (password.length >= 8) score++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;

        if (password.length < 8 || score <= 1) {
            return { label: "Strength: Weak (min 8 chars with mixed cases & numbers)", className: "strength-weak" };
        } else if (score === 2 || score === 3) {
            return { label: "Strength: Moderate (add symbols for a stronger password)", className: "strength-medium" };
        } else {
            return { label: "Strength: Strong password", className: "strength-strong" };
        }
    }

    if (passwordInput) {
        passwordInput.addEventListener("input", function () {
            const strengthEl = document.getElementById("passwordStrength");
            if (!strengthEl) return;
            const val = this.value;
            const result = evaluatePasswordStrength(val);
            strengthEl.textContent = result.label;
            strengthEl.className = "strength-meter " + result.className;
        });
    }

    // Real-time Confirm Password check
    if (confirmPasswordInput && passwordInput) {
        const checkConfirmPassword = function () {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            if (confirmPassword === "") {
                setError("confirmPasswordError", "");
            } else if (password !== confirmPassword) {
                setError("confirmPasswordError", "Passwords do not match.");
            } else {
                setError("confirmPasswordError", "");
            }
        };

        confirmPasswordInput.addEventListener("input", checkConfirmPassword);
        passwordInput.addEventListener("input", function () {
            if (confirmPasswordInput.value !== "") {
                checkConfirmPassword();
            }
        });
    }

    // Form Submission
    form.addEventListener("submit", function (event) {
        event.preventDefault();
        clearAllErrors();

        const name = nameInput ? nameInput.value.trim() : "";
        const email = emailInput ? emailInput.value.trim() : "";
        const password = passwordInput ? passwordInput.value : "";
        const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value : "";
        const mobile = mobileInput ? mobileInput.value.trim() : "";
        const gender = genderInput ? genderInput.value : "";
        const year = yearInput ? yearInput.value : "";
        const semester = semesterInput ? semesterInput.value : "";
        const course = courseInput ? courseInput.value : "";
        const role = roleInput ? roleInput.value : "";
        const terms = termsInput ? termsInput.checked : false;

        let valid = true;

        // Student ID Validation (if field exists)
        if (studentIdInput) {
            const studentId = studentIdInput.value.trim();
            if (studentId === "") {
                setError("studentIdError", "Please enter your Student ID.");
                valid = false;
            } else if (!/^[0-9]{2}[A-Za-z]{2,4}[0-9]{3}$/i.test(studentId)) {
                setError("studentIdError", "Student ID format is invalid (e.g., 25CE077 or 25DCE001).");
                valid = false;
            }
        }

        // Full Name Validation
        if (name === "") {
            setError("nameError", "Please enter your full name.");
            valid = false;
        } else if (!namePattern.test(name)) {
            setError("nameError", "Name should contain only letters and spaces (3-50 characters).");
            valid = false;
        }

        // Email Validation
        if (email === "") {
            setError("emailError", "Please enter your email address.");
            valid = false;
        } else if (!emailPattern.test(email)) {
            setError("emailError", "Please enter a valid email address.");
            valid = false;
        }

        // Password Validation
        if (password === "") {
            setError("passwordError", "Please enter a password.");
            valid = false;
        } else if (password.length < 8) {
            setError("passwordError", "Password must be at least 8 characters long.");
            valid = false;
        }

        // Confirm Password Validation
        if (confirmPassword === "") {
            setError("confirmPasswordError", "Please confirm your password.");
            valid = false;
        } else if (password !== confirmPassword) {
            setError("confirmPasswordError", "Passwords do not match.");
            valid = false;
        }

        // Mobile Number Validation
        if (mobile === "") {
            setError("mobileError", "Please enter your mobile number.");
            valid = false;
        } else if (!/^[0-9]+$/.test(mobile)) {
            setError("mobileError", "Mobile number must contain only digits.");
            valid = false;
        } else if (!mobilePattern.test(mobile)) {
            setError("mobileError", "Enter a valid 10-digit Indian mobile number starting with 6-9.");
            valid = false;
        }

        // Gender Validation
        if (gender === "") {
            setError("genderError", "Please select your gender.");
            valid = false;
        }

        // Year Validation
        if (year === "") {
            setError("yearError", "Please select your academic year.");
            valid = false;
        }

        // Semester Validation
        if (semester === "") {
            setError("semesterError", "Please select your semester.");
            valid = false;
        }

        // Course Validation
        if (course === "") {
            setError("courseError", "Please select your course.");
            valid = false;
        }

        // Role Validation
        if (role === "") {
            setError("roleError", "Please select your role.");
            valid = false;
        }

        // Terms and Conditions Acceptance
        if (!terms) {
            setError("termsError", "You must accept the terms and conditions to proceed.");
            valid = false;
        }

        if (valid) {
            const action = form.getAttribute("action");
            if (action && action.endsWith(".php")) {
                // Submit form to PHP backend handler
                form.submit();
                return;
            }

            const successEl = document.getElementById("successMessage");
            if (successEl) {
                successEl.textContent = "Registration Successful! Welcome to StudentHub Portal.";
            }
            form.reset();
            const strengthEl = document.getElementById("passwordStrength");
            if (strengthEl) {
                strengthEl.textContent = "";
                strengthEl.className = "strength-meter";
            }
        }
    });
});
