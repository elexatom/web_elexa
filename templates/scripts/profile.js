// validace hesla

const newPassword = $("#newPwd"), confirmPassword = $("#confirmPwd"), confirmHint = $("#confirmHint"),
    strengthFill = $("#strengthFill"), submitBtn = $("#submitPwd"), requirements = $("#pwdRequirements li")

// kontrola pozadavku na heslo pomoci regex
const checks = {
    length: pwd => pwd.length >= 8,
    lower: pwd => /[a-z]/.test(pwd),
    upper: pwd => /[A-Z]/.test(pwd),
    number: pwd => /[0-9]/.test(pwd),
    special: pwd => /[!@#$%^&*(),.?":{}|<>]/.test(pwd)
}

function updatePasswordStrength() {
    const pwd = newPassword.val()
    let passed = 0

    requirements.each(function () {
        const $li = $(this), key = $li.data("require"), $icon = $li.find(".reqIcon")

        if (checks[key](pwd)) {
            $li.removeClass("text-red-500").addClass("text-green-500")
            $icon.attr("data-lucide", "check")
            passed++
        } else {
            $li.removeClass("text-green-500").addClass("text-red-500")
            $icon.attr("data-lucide", "x")
        }
    })

    const percent = (passed / 5) * 100
    strengthFill.css("width", percent + "%")
        .removeClass("bg-error bg-warning bg-success")
        .addClass(percent < 40 ? "bg-error" : percent < 100 ? "bg-warning" : "bg-success")

    lucide.createIcons()
    checkFormValidity()
}

function checkPasswordMatch() {
    const newPwd = newPassword.val(), confirmPwd = confirmPassword.val()

    if (!confirmPwd) confirmHint.text("").attr("class", "text-xs mt-2 text-gray-500")
    else if (newPwd === confirmPwd) confirmHint.text("✓ Hesla se shoduji").attr("class", "text-xs mt-2 text-green-500")
    else confirmHint.text("✗ Hesla se neshoduji").attr("class", "text-xs mt-2 text-red-500")

    checkFormValidity()
}

function checkFormValidity() {
    const newPwd = newPassword.val(), confirmPwd = confirmPassword.val()
    const allPassed = Object.values(checks).every(fn => fn(newPwd))
    submitBtn.prop("disabled", !(allPassed && newPwd === confirmPwd && confirmPwd))
}

newPassword.on("input", updatePasswordStrength)
confirmPassword.on("input", checkPasswordMatch)

// prepinani viditelnosti hesla
$(".togglePwd").on("click", function () {
    const $input = $(this).siblings("input"), $icon = $(this).find("i")
    const type = $input.attr("type") === "password" ? "text" : "password"
    $input.attr("type", type)
    $icon.attr("data-lucide", type === "text" ? "eye-off" : "eye")
    lucide.createIcons()
})

async function ajaxRequest(url, method, data, isFormData = false) {
    try {
        const response = await $.ajax({
            url,
            type: method,
            data: isFormData ? data : $.param(data), // serialize objektu pokudn neni FormData
            processData: !isFormData,                // preskocit serializaci
            contentType: isFormData ? false : "application/x-www-form-urlencoded; charset=UTF-8",
            dataType: "json"
        })

        showToast(response.message || "Operace se provedla úspěšně.", "success")
        return response
    } catch (xhr) {
        let res = {}
        try {
            res = xhr.responseJSON || JSON.parse(xhr.responseText)
        } catch (e) {
            // json neni validni
            showToast(res.message || "Operace se nezdařila.", "error")
        }
        showToast(res.message || "Operace se nezdařila.", "error")
        return false
    }
}

// profilova fotka - nahled a upload
const $profilePictureInput = $("#profilePictureInput")
const $profilePreview = $("#profilePreview")
const $photoSubmitBtn = $("#photoSubmitBtn")
let selectedFile = null

if ($profilePictureInput.length) {
    // pri vyberu zobrazime pouze nahled
    $profilePictureInput.on("change", function (e) {
        const file = e.target.files[0]

        if (file) {
            // validace formatu
            const validTypes = ["image/jpeg", "image/png", "image/jpg", "image/webp"]
            if (!validTypes.includes(file.type)) {
                showToast("Prosím nahrajte obrázek ve formátu JPG, PNG nebo WEBP", "error")
                $(this).val("")
                selectedFile = null
                $photoSubmitBtn.prop("disabled", true)
                return
            }

            // validace velikosti (max 10MB)
            const maxSize = 10 * 1024 * 1024 // 10MB
            if (file.size > maxSize) {
                showToast("Soubor je příliš velký. Maximum je 10MB.", "error")
                $(this).val("")
                selectedFile = null
                $photoSubmitBtn.prop("disabled", true)
                return
            }

            // ulozeni souboru pro pozdejsi upload
            selectedFile = file

            // nahled obrazku
            const reader = new FileReader()
            reader.onload = function (e) {
                $profilePreview.attr("src", e.target.result)
            }
            reader.readAsDataURL(file)

            // aktivace upload btn
            $photoSubmitBtn.prop("disabled", false)
        } else {
            selectedFile = null
            $photoSubmitBtn.prop("disabled", true)
        }
    })
}

// upload fotky
$photoSubmitBtn.on("click", async function (e) {
    e.preventDefault()
    if (!selectedFile) {
        showToast("Vyberte soubor.", "error")
        return
    }

    // deaktivace upload btn
    $photoSubmitBtn.prop("disabled", true).html("<span class=\"loading loading-spinner\"></span> Nahrávám...")

    // vytvoreni FormData s fotkou
    const formData = new FormData()
    formData.append("profile_picture", selectedFile)

    // odeslani
    const res = await ajaxRequest("/profile/update-picture", "POST", formData, true)

    if (res) {
        // aktualizace nahledu s URL ze serveru
        if (res.imageUrl) $profilePreview.attr("src", res.imageUrl)

        // reset inputu
        $profilePictureInput.val("")
        selectedFile = null
    }

    // obnoveni tlacitka
    $photoSubmitBtn.html("<i data-lucide=\"upload\" class=\"h-4 w-4\"></i> Nahrát foto")
    lucide.createIcons()
})

/* ---------------------------------------------------------------------- */

function showToast(message, type = "success") {
    const $toast = $("#messageToast")
    const $text = $("#messageText")

    // text zpravy
    $text.text(message)

    // dynamicke nastaveni classu
    const alertClass =
        type === "error"
            ? "alert-error"
            : type === "info"
                ? "alert-info"
                : "alert-success"

    // reset a nastaveni class
    const $alert = $toast.find(".alert")
    $alert
        .removeClass("alert-success alert-error alert-info")
        .addClass(alertClass)

    // pokud je viditelna, ukonci se
    if ($toast.is(":visible")) {
        $toast.stop(true, true).hide()
    }

    // animace notifikace
    $toast
        .removeClass("hidden")
        .css({opacity: 0, bottom: "-50px", position: "fixed", right: "20px", "z-index": 9999})
        .show()
        .animate({opacity: 1, bottom: "30px"}, 300, "swing")
        .delay(3000)
        .animate({opacity: 0, bottom: "-50px"}, 400, "swing", function () {
            $toast.addClass("hidden").hide()
        })
}


// zmena jmena
$("#submitName").on("click", async function (e) {
    e.preventDefault()
    const newName = $("#username").val()
    const res = await ajaxRequest("/profile/update-name", "POST", {jmeno: newName})
    if (res) $("#jmeno").text(newName)
})

// zmena nicku
$("#submitNick").on("click", async function (e) {
    e.preventDefault()
    const newNick = $("#nickname").val()
    const res = await ajaxRequest("/profile/update-nick", "POST", {nick: newNick})
    if (res) $("#nick").text("@" + newNick)
})

submitBtn.on("click", async function (e) {
    e.preventDefault()
    const newPwd = $("#newPwd")
    const confPwd = $("#confirmPwd")
    const currPwd = $("#currPwd")
    const res = await ajaxRequest("/profile/update-password", "POST", {
        current_pwd: currPwd.val(),
        new_pwd: newPwd.val(),
        conf_pwd: confPwd.val()
    })
    if (res) {
        newPwd.val("")
        confPwd.val("")
        currPwd.val("")
        $("#confirmHint").text("")
        $("#strengthFill").css("width", "0%")
        $("#submitPwd").prop("disabled", true)
        updatePasswordStrength()
    }
})


