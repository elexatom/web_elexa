// JS script pro registracni formular

const pwd = document.getElementById("password")
const confirmPwd = document.getElementById("confirmPassword")
const toggle = document.getElementById("togglePassword")
const strengthFill = document.getElementById("strengthFill")
const reqList = document.querySelectorAll("#pwdRequirements [data-require]")
const confirmHint = document.getElementById("confirmHint")

let confCheck, reqCheck = false

// funkce pro kontrolu pozadavku hesla pomoci regex
const checks = {
    length: v => v.length >= 8,
    lower: v => /[a-záčďéěíňóřšťúůýž]/.test(v),
    upper: v => /[A-ZÁČĎÉĚÍŇÓŘŠŤÚŮÝŽ]/.test(v),
    number: v => /[0-9]/.test(v),
    special: v => /[^A-Za-z0-9ÁČĎÉĚÍŇÓŘŠŤÚŮÝŽáčďéěíňóřšťúůýž]/.test(v)
}

// uprava informaci o pozadavcich hesla
function updateRequirements(val) {
    let splneno = 0                                          // pocet splnenych pozadavku
    reqList.forEach(li => {                        // projde array pozadavku a kazdy overi
        const key = li.getAttribute("data-require")
        const ok = checks[key](val)                         // zkontrolovat hodnotu
        const icon = li.querySelector(".reqIcon")
        if (ok) {                                           // zmena ikony ...
            splneno++
            li.classList.add("text-green-500")
        } else li.classList.remove("text-green-500")

        icon.setAttribute("data-lucide", ok ? "check" : "x")
    })
    lucide.createIcons() // update ikon
    return splneno
}

// uprava progress baru
function updateStrengthBar(score) {
    const val = Math.round((score / 5) * 100) // hodnota progress baru
    strengthFill.style.width = val + "%"

    // smazat predesle zbarveni
    strengthFill.classList.remove("bg-red-500", "bg-orange-400", "bg-yellow-400", "bg-lime-400", "bg-green-500")

    // nova barva v zavislosti na score
    if (score <= 1) strengthFill.classList.add("bg-red-500")
    else if (score === 2) strengthFill.classList.add("bg-orange-400")
    else if (score === 3) strengthFill.classList.add("bg-yellow-400")
    else if (score === 4) strengthFill.classList.add("bg-lime-400")
    else {
        strengthFill.classList.add("bg-green-500")
        reqCheck = true
    }
}

// kontrola shody hesel
function checkConfirmMatch() {
    const p = pwd.value
    const c = confirmPwd.value
    if (!c) {
        confirmHint.textContent = ""
        return
    }

    confirmHint.textContent = p === c ? "Hesla se shodují" : "Hesla se neshodují"
    confCheck = p === c

    if (p === c) { // zmenit barvu

        confirmHint.classList.remove("text-red-500")
        confirmHint.classList.add("text-green-500")
    } else {
        confirmHint.classList.remove("text-green-500")
        confirmHint.classList.add("text-red-500")
    }
}

function validate() {
    if (confCheck && reqCheck) document.querySelector("button[type='submit']").disabled = false
}

// event listener pro heslo a znovu-heslo
pwd.addEventListener("input", () => {
    const v = pwd.value                 // hodnota hesla
    const score = updateRequirements(v) // over
    updateStrengthBar(score)            // zmenit stav progress baru
    checkConfirmMatch()                 // overit shodu hesel
    validate()
})

// overit shodu hesel
confirmPwd.addEventListener("input", ()=>{
    checkConfirmMatch()
    validate()
}) // overit shodu hesel

// toggle viditelnosti hesla
toggle.addEventListener("click", () => {
    const type = pwd.type === "password" ? "text" : "password"
    pwd.type = type
    confirmPwd.type = type // to stejne i pro znovu-heslo
})

// uvodni kontrola, (autofill prohlizece)
if (pwd.value) {
    const initScore = updateRequirements(pwd.value)
    updateStrengthBar(initScore)
}
