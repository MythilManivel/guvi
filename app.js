const API_BASE = "php";

function showFeedback(selector, message, type = "danger") {
    const container = document.querySelector(selector);
    if (!container) return;
    container.innerHTML = message
        ? `<div class="alert alert-${type} alert-sm">${message}</div>`
        : "";
}

function submitForm(formId, data) {
    return $.ajax({
        url: `${API_BASE}/${formId}.php`,
        method: "POST",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify(data),
        cache: false,
    });
}

$(function () {
    $("#signup-form").on("submit", function (event) {
        event.preventDefault();
        const formData = {
            firstName: $("#firstName").val().trim(),
            lastName: $("#lastName").val().trim(),
            email: $("#email").val().trim().toLowerCase(),
            password: $("#password").val(),
            confirmPassword: $("#confirmPassword").val(),
            age: $("#age").val() || null,
            dob: $("#dob").val() || null,
            contact: $("#contact").val().trim() || null,
        };

        if (formData.password !== formData.confirmPassword) {
            showFeedback("#signup-feedback", "Passwords do not match.");
            return;
        }

        submitForm("register", formData)
            .done((response) => {
                if (response.success) {
                    showFeedback("#signup-feedback", response.message, "success");
                    setTimeout(() => {
                        window.location.href = "login.html";
                    }, 900);
                } else {
                    showFeedback("#signup-feedback", response.message);
                }
            })
            .fail(() => {
                showFeedback("#signup-feedback", "Unable to reach the server.");
            });
    });

    $("#login-form").on("submit", function (event) {
        event.preventDefault();
        const formData = {
            email: $("#loginEmail").val().trim().toLowerCase(),
            password: $("#loginPassword").val(),
        };

        submitForm("login", formData)
            .done((response) => {
                if (response.success) {
                    localStorage.setItem("sessionToken", response.sessionToken);
                    localStorage.setItem("profileEmail", response.email);
                    window.location.href = "profile.html";
                } else {
                    showFeedback("#login-feedback", response.message);
                }
            })
            .fail(() => {
                showFeedback("#login-feedback", "Unable to reach the server.");
            });
    });

    if (window.location.pathname.includes("profile.html")) {
        const sessionToken = localStorage.getItem("sessionToken");
        if (!sessionToken) {
            showFeedback("#profile-feedback", "Authentication required.", "warning");
            setTimeout(() => (window.location.href = "login.html"), 1000);
            return;
        }

        function fetchProfile() {
            return $.ajax({
                url: `${API_BASE}/profile.php`,
                method: "GET",
                dataType: "json",
                headers: { "X-Session-Token": sessionToken },
                cache: false,
            });
        }

        fetchProfile()
            .done((response) => {
                if (!response.success) {
                    localStorage.removeItem("sessionToken");
                    localStorage.removeItem("profileEmail");
                    window.location.href = "login.html";
                    return;
                }
                $("#profileFirstName").val(response.data.firstName);
                $("#profileLastName").val(response.data.lastName);
                $("#profileEmail").val(response.data.email);
                $("#profileAge").val(response.data.profile.age || "");
                $("#profileDob").val(response.data.profile.dob || "");
                $("#profileContact").val(response.data.profile.contact || "");
                $("#profileAddress").val(response.data.profile.address || "");
            })
            .fail(() => {
                showFeedback("#profile-feedback", "Unable to fetch profile.");
            });

        $("#profile-form").on("submit", function (event) {
            event.preventDefault();
            const payload = {
                firstName: $("#profileFirstName").val().trim(),
                lastName: $("#profileLastName").val().trim(),
                age: $("#profileAge").val() || null,
                dob: $("#profileDob").val() || null,
                contact: $("#profileContact").val().trim() || null,
                address: $("#profileAddress").val().trim() || null,
            };

            $.ajax({
                url: `${API_BASE}/profile.php`,
                method: "POST",
                contentType: "application/json",
                dataType: "json",
                data: JSON.stringify(payload),
                headers: { "X-Session-Token": sessionToken },
            })
                .done((response) => {
                    showFeedback("#profile-feedback", response.message, response.success ? "success" : "danger");
                })
                .fail(() => {
                    showFeedback("#profile-feedback", "Unable to reach the server.");
                });
        });

        $("#logout-btn").on("click", function () {
            const token = localStorage.getItem("sessionToken");
            localStorage.removeItem("sessionToken");
            localStorage.removeItem("profileEmail");
            if (!token) {
                window.location.href = "login.html";
                return;
            }
            $.ajax({
                url: `${API_BASE}/logout.php`,
                method: "POST",
                contentType: "application/json",
                dataType: "json",
                data: JSON.stringify({}),
                headers: { "X-Session-Token": token },
            }).always(() => {
                window.location.href = "login.html";
            });
        });
    }
});
