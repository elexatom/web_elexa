// ------------------------------------------------------- //
//  ---------------- Toast notifikace -------------------- //
// ------------------------------------------------------- //
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

// ------------------------------------------------------- //
//  -------------------- AJAX helper --------------------- //
// ------------------------------------------------------- //
async function ajaxRequest(url, method, data) {
    try {
        const response = await $.ajax({
            url,
            type: method,
            data: $.param(data),
            dataType: "json"
        })

        // odpoved
        showToast(response.message || "Operace proběhla úspěšně.", "success")
        return response
    } catch (xhr) { // problem
        let res = {}
        try {
            res = xhr.responseJSON || JSON.parse(xhr.responseText)
        } catch (e) {
            // neni validni json
            showToast(res.message || "Operace se nezdařila.", "error")
        }
        showToast(res.message || "Operace se nezdařila.", "error")
        return false
    }
}

// ------------------------------------------------------- //
//  ------------ Razeni tabulky podle sloupcu ------------ //
// ------------------------------------------------------- //
let currentSort = {column: "userid", direction: "asc"}

$(".sortable").on("click", function () {
    const $th = $(this)
    const column = $th.data("column")

    // prepnuti smeru razeni pokud je zvolen stejny sloupec
    if (currentSort.column === column) {
        currentSort.direction = currentSort.direction === "asc" ? "desc" : "asc"
    } else {
        currentSort.column = column
        currentSort.direction = "asc"
    }

    // ikony
    $(".sortable .sort-icon").attr("data-lucide", "arrow-up-down")
    const icon = currentSort.direction === "asc" ? "arrow-up" : "arrow-down"
    $th.find(".sort-icon").attr("data-lucide", icon)
    lucide.createIcons()

    // seradit tabulku
    sortTable(column, currentSort.direction)
})

// Seradit tabulku podle zadaneho sloupce a smeru
function sortTable(column, direction) {
    const $tbody = $("#usersTableBody")
    const $rows = $tbody.find("tr").get()

    // razeni
    $rows.sort(function (a, b) {
        let valA, valB

        if (column === "userid") {
            valA = parseInt($(a).data("user-id"))
            valB = parseInt($(b).data("user-id"))
        } else if (column === "jmeno") {
            valA = $(a).data("jmeno").toLowerCase()
            valB = $(b).data("jmeno").toLowerCase()
        } else if (column === "role") {
            valA = $(a).data("role").toLowerCase()
            valB = $(b).data("role").toLowerCase()
        } else if (column === "schvaleno") {
            valA = parseInt($(a).data(column))
            valB = parseInt($(b).data(column))
        }

        if (valA < valB) return direction === "asc" ? -1 : 1
        if (valA > valB) return direction === "asc" ? 1 : -1
        return 0
    })

    // prepsat tbody se serazenymi radky
    $.each($rows, function (index, row) {
        $tbody.append(row)
    })
}

// ------------------------------------------------------- //
// -------- Modal pro zobrazeni info o uzivateli --------- //
// ------------------------------------------------------- //
$(document).on("click", ".user-info-btn", function () {
    const $btn = $(this)

    // naplneni dat do modalu
    $("#modalProfilePic").attr("src", $btn.data("profile"))
    $("#modalName").text($btn.data("name"))
    $("#modalNick").text("@" + $btn.data("nick"))
    $("#modalEmail").text($btn.data("email")).attr("href", "mailto:" + $btn.data("email"))
    $("#modalRole").text($btn.data("role").toUpperCase())
    $("#modalCreated").text(new Date($btn.data("created")).toLocaleDateString("cs-CZ"))
    $("#modalUserId").text($btn.data("user-id"))

    // ikony
    lucide.createIcons()

    // otevreni modalu
    document.getElementById("userInfoModal").showModal()
})

// ------------------------------------------------------- //
// ---------------- Zmena role uzivatele ----------------- //
// ------------------------------------------------------- //
$(document).on("change", ".role-select", async function () {
    const $select = $(this)
    const userId = $select.data("user-id")
    const newRole = $select.val()
    const originalRole = $select.data("original-role")

    // ajax request
    const res = await ajaxRequest("/users/change-role", "POST", {
        user_id: userId,
        role: newRole
    })

    if (res) { // uspech
        // aktualizace puvodni hodnoty
        $select.data("original-role", newRole)
        // aktualizace data atributu v radce
        $select.closest("tr").data("role", newRole)
    } else {
        // navrat na puvodni hodnotu pri chybe
        $select.val(originalRole)
    }
})

// ------------------------------------------------------- //
//  ------------ Zmena schvaleno/neschvaleno  ------------ //
// ------------------------------------------------------- //
$(document).on("click", ".status-toggle", async function () {
    const $btn = $(this)
    const userId = $btn.data("user-id")
    const isApproved = $btn.data("schvaleno") === 1

    // ajax request
    const res = await ajaxRequest("/users/toggle-status", "POST", {
        user_id: userId,
        status: isApproved ? 0 : 1
    })

    if (res) { // uspech
        // zmena v UI
        if (isApproved) {
            // zmenit na neschvaleno
            $btn
                .removeClass("btn-success")
                .addClass("btn-error")
                .data("schvaleno", 0)
                .html("<i data-lucide=\"x-circle\" class=\"w-4 h-4\"></i> Neschváleno")
            $btn.closest("tr").data("schvaleno", 0)
        } else {
            // zmenit na schvaleno
            $btn
                .removeClass("btn-error")
                .addClass("btn-success")
                .data("schvaleno", 1)
                .html("<i data-lucide=\"check-circle\" class=\"w-4 h-4\"></i> Schváleno")
            $btn.closest("tr").data("schvaleno", 1)
        }

        // ikony
        lucide.createIcons()
    }
})

// inicializace ikon pri nacteni
$(document).ready(function () {
    lucide.createIcons()
})
