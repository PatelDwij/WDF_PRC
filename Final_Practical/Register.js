
let form = document.getElementById("registerForm");

form.addEventListener("submit", function(event) {

    event.preventDefault();

    let name = document.getElementById("fullName").value.trim();
    let email = document.getElementById("email").value.trim();
    let password = document.getElementById("password").value;
    let confirmPassword =
        document.getElementById("confirmPassword").value;
    let mobile = document.getElementById("mobile").value.trim();

    let gender = document.getElementById("gender").value;
    let year = document.getElementById("year").value;
    let course = document.getElementById("course").value;
    let semester = document.getElementById("semester").value;
    let role = document.getElementById("role").value;
    let terms = document.getElementById("terms").checked;

    let namePattern = /^[A-Za-z ]{3,50}$/;

    let emailPattern =/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;

    let mobilePattern = /^[6-9][0-9]{9}$/;

    let valid = true;

    document.getElementById("nameError").innerHTML = "";
    document.getElementById("emailError").innerHTML = "";
    document.getElementById("passwordError").innerHTML = "";
    document.getElementById("confirmPasswordError").innerHTML = "";
    document.getElementById("mobileError").innerHTML = "";
    document.getElementById("genderError").innerHTML = "";
    document.getElementById("courseError").innerHTML = "";
    document.getElementById("yearError").innerHTML = "";
    document.getElementById("semesterError").innerHTML = "";
    document.getElementById("roleError").innerHTML = "";
    document.getElementById("termsError").innerHTML = "";
    document.getElementById("successMessage").innerHTML = "";

    // Name
    if (name === "") {
        document.getElementById("nameError").innerHTML =
            "Please enter your name.";
        valid = false;
    } else if (!namePattern.test(name)) {
        document.getElementById("nameError").innerHTML =
            "Name should contain only letters and spaces.";
        valid = false;
    }

    // Email
    if (email === "") {
        document.getElementById("emailError").innerHTML =
            "Please enter your email.";
        valid = false;
    } else if (!emailPattern.test(email)) {
        document.getElementById("emailError").innerHTML =
            "Please enter a valid email address.";
        valid = false;
    }

    // Password
    if (password === "") {
        document.getElementById("passwordError").innerHTML =
            "Please enter a password.";
        valid = false;
    } else if (password.length < 8) {
        document.getElementById("passwordError").innerHTML =
            "Password must be at least 8 characters.";
        valid = false;
    }

    // Confirm Password
    if (confirmPassword === "") {
        document.getElementById("confirmPasswordError").innerHTML =
            "Please confirm your password.";
        valid = false;
    } else if (password !== confirmPassword) {
        document.getElementById("confirmPasswordError").innerHTML =
            "Passwords do not match.";
        valid = false;
    }

    // Mobile
    if (mobile === "") {
        document.getElementById("mobileError").innerHTML =
            "Please enter your mobile number.";
        valid = false;
    } else if (!/^[0-9]+$/.test(mobile)) {
        document.getElementById("mobileError").innerHTML =
            "Mobile number must contain only digits.";
        valid = false;
    } else if (!mobilePattern.test(mobile)) {
        document.getElementById("mobileError").innerHTML =
            "Enter a valid 10 digit mobile number.";
        valid = false;
    }

    // Gender
    if (gender === "") {
        document.getElementById("genderError").innerHTML =
            "Please select your gender.";
        valid = false;
    }

    // Course
    if (course === "") {
        document.getElementById("courseError").innerHTML =
            "Please select your course.";
        valid = false;
    }

    // Year
    if (year === "") {
        document.getElementById("yearError").innerHTML =
            "Please select your year.";
        valid = false;
    }

    // Semester
    if (semester === "") {
        document.getElementById("semesterError").innerHTML =
            "Please select your semester.";
        valid = false;
    }

    // Role
    if (role === "") {
        document.getElementById("roleError").innerHTML =
            "Please select your role.";
        valid = false;
    }

    // Terms
    if (!terms) {
        document.getElementById("termsError").innerHTML =
            "Please accept the terms and conditions.";
        valid = false;
    }

    // Success
    if (valid) {
        document.getElementById("successMessage").innerHTML =
            "Registration Successful!";

        form.reset();
    }
});


// Confirm Password
document.getElementById("confirmPassword").addEventListener(
    "keyup",
    function() {

        let password =
            document.getElementById("password").value;

        let confirmPassword = this.value;

        let error =
            document.getElementById("confirmPasswordError");

        if (confirmPassword === "") {
            error.innerHTML = "";
        } else if (password !== confirmPassword) {
            error.innerHTML = "Passwords do not match.";
        } else {
            error.innerHTML = "";
        }
    }
);

