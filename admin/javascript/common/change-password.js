Swal.fire({
    title: 'Change Your Password',
    html: `
        <input id="newPass" class="swal2-input" type="password" placeholder="New Password">
        <input id="newPass2" class="swal2-input" type="password" placeholder="Confirm New Password">
    `,
    icon: "warning",
    confirmButtonText: "Update Password",
    allowOutsideClick: false,
    allowEscapeKey: false,
    preConfirm: () => {
        const p1 = document.getElementById("newPass").value.trim();
        const p2 = document.getElementById("newPass2").value.trim();

        if (!p1 || !p2) {
            Swal.showValidationMessage("Both fields are required.");
            return false;
        }
        if (p1.length < 6) {
            Swal.showValidationMessage("Password must be at least 6 characters.");
            return false;
        }
        if (p1 !== p2) {
            Swal.showValidationMessage("Passwords do not match.");
            return false;
        }

        return p1;
    }
}).then(result => {
    if (!result.isConfirmed) return;

    const formData = new FormData();
    formData.append("new_password", result.value);

    fetch("./change_password.php", {
        method: "POST",
        headers: { "X-API-KEY": window.GM_API_KEY || "" },
        body: formData,
        credentials: "same-origin"
    })
    .then(res => {
        if (!res.ok) {
            throw new Error("Server error (" + res.status + ")");
        }
        return res.json();
    })
    .then(data => {
        if (data.error) {
            Swal.fire("Error", data.error, "error");
            return;
        }

        Swal.fire({
            icon: "success",
            title: "Password Updated",
            text: "You will now be logged out."
        }).then(() => {
            window.location.href = (window.GM_BASE_PATH || "") + "/login/logout.php";
        });
    })
    .catch(err => {
        Swal.fire("Error", err.message, "error");
    });
});
