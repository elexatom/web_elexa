// ------------------------------------------------------- //
//  ------------------ Toast notifikace ------------------ //
// ------------------------------------------------------- //
function showToast(message, type = "success") {
    const $toast = $("#messageToast")
    const $text = $("#messageText")
    $text.text(message) // zmenit text
    const alertClass = // priradit class dle typu
        type === "error"
            ? "alert-error"
            : type === "info"
                ? "alert-info"
                : "alert-success"

    const $alert = $toast.find(".alert")
    $alert  // nastavit class
        .removeClass("alert-success alert-error alert-info")
        .addClass(alertClass)

    if ($toast.is(":visible")) {
        $toast.stop(true, true).hide()
    }

    // pokud je toast viditelny, zobrazit a zmenit jeho pozici
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
async function ajaxRequest(url, method, data, isFormData = false) {
    try {
        const response = await $.ajax({ // ajax request
            url,
            type: method,
            data: isFormData ? data : $.param(data),
            processData: !isFormData,   // pokud se jedna o FormData, nezpracovavat
            contentType: isFormData ? false : "application/x-www-form-urlencoded; charset=UTF-8",
            dataType: "json"
        })

        // odpoved
        showToast(response.message || "Operace proběhla úspěšně.", "success")
        return response
    } catch (xhr) { // nastal problem
        let res = {}
        try {
            res = xhr.responseJSON || JSON.parse(xhr.responseText)
        } catch (e) {
            // neni validni json
        }
        showToast(res.message || "Operace se nezdařila.", "error")
        return false
    }
}

// ------------------------------------------------------- //
//  -------------- Tabs - Rozdeleni stavu ---------------- //
// ------------------------------------------------------- //
function updateTabCounts() {
    const total = $(".article-card").length
    const pending = $(".article-card[data-status=\"cekajici\"]").length
    const accepted = $(".article-card[data-status=\"prijato\"]").length
    const declined = $(".article-card[data-status=\"zamitnuto\"]").length

    $(".tab[data-filter=\"all\"] .badge").text(total)
    $(".tab[data-filter=\"cekajici\"] .badge").text(pending)
    $(".tab[data-filter=\"prijato\"] .badge").text(accepted)
    $(".tab[data-filter=\"zamitnuto\"] .badge").text(declined)
}


// filtrovani rozdeleni
$(".tab").on("click", function () {
    const $tab = $(this)
    const filter = $tab.data("filter")

    $(".tab").removeClass("tab-active")
    $tab.addClass("tab-active")

    if (filter === "all") {
        $(".article-card").show()
    } else {
        $(".article-card").hide()
        $(`.article-card[data-status="${filter}"]`).show()
    }
})

// ------------------------------------------------------- //
//  ------------------ Prijmout Clanek ------------------- //
// ------------------------------------------------------- //
$(document).on("click", ".accept-article-btn", async function () {
    const $btn = $(this)
    const articleId = $btn.data("article-id")
    if (!confirm("Opravdu chcete přijmout tento článek?")) return // overeni

    // ajax request
    const res = await ajaxRequest("/admin/change-status", "POST", {
        article_id: articleId,
        status: "prijato"
    })

    if (res) {
        updateArticleCard(articleId, "prijato")
        updateTabCounts()
    }
})

// ------------------------------------------------------- //
//  ----------------- Prehodnotit Clanek ----------------- //
// ------------------------------------------------------- //
$(document).on("click", ".reconsider-article-btn", async function () {
    const $btn = $(this)
    const articleId = $btn.data("article-id")
    if (!confirm("Opravdu chcete vrátit článek k přehodnocení?")) return // overeni

    // ajax request
    const res = await ajaxRequest("/admin/change-status", "POST", {
        article_id: articleId,
        status: "cekajici"
    })

    if (res) {
        updateArticleCard(articleId, "cekajici")
        updateTabCounts()
    }
})

// ------------------------------------------------------- //
//  ------------------ Zamitnout Clanek ------------------ //
// ------------------------------------------------------- //
$(document).on("click", ".decline-article-btn", async function () {
    const $btn = $(this)
    const articleId = $btn.data("article-id")

    if (!confirm("Opravdu chcete zamítnout tento článek?")) return // overeni

    // ajax request
    const res = await ajaxRequest("/admin/change-status", "POST", {
        article_id: articleId,
        status: "zamitnuto"
    })

    if (res) {
        updateArticleCard(articleId, "zamitnuto")
        updateTabCounts()
    }
})

// ------------------------------------------------------- //
//  ----------------- Pridat recenzenta ------------------ //
// ------------------------------------------------------- //
// Modal
$(document).on("click", ".add-reviewer-btn", function () {
    const $btn = $(this)
    const articleId = $btn.data("article-id")

    $("#reviewArticleId").val(articleId)
    document.getElementById("addReviewerModal").showModal()
})

// Submit
$("#addReviewerForm").on("submit", async function (e) {
    e.preventDefault()

    const formData = {
        article_id: $("#reviewArticleId").val(),
        reviewer_id: $("#reviewerSelect").val()
    }

    if (!formData.reviewer_id) {
        showToast("Vyberte prosím recenzenta.", "error")
        return
    }

    // ajax request
    const res = await ajaxRequest("/admin/assign", "POST", formData)

    if (res) {
        document.getElementById("addReviewerModal").close()
        location.reload()
    }
})

// ------------------------------------------------------- //
//  ------------------ Odstranit recenzi ----------------- //
// ------------------------------------------------------- //
$(document).on("click", ".remove-review-btn", async function () {
    const $btn = $(this)
    const reviewId = $btn.data("review-id")

    if (!confirm("Opravdu chcete odstranit tuto recenzi?")) return // overeni

    // ajax request
    const res = await ajaxRequest("/admin/delete-review", "POST", {review_id: reviewId})

    if (res) { // uspech
        const $row = $btn.closest("tr")
        $row.fadeOut(400, function () {
            $(this).remove()

            // upravit pocty v rozdeleni
            updateTabCounts()

            // pokud uz nejsou zadne recenze, zobrazit hlasku
            const $tbody = $btn.closest("tbody")
            if ($tbody.find("tr").length === 0) {
                const $reviewsSection = $btn.closest(".space-y-3")
                $reviewsSection.find(".overflow-x-auto").remove()
                $reviewsSection.append(`
                        <div class="alert alert-info">
                            <i data-lucide="info" class="w-5 h-5"></i>
                            <span>Čeká na přiřazení recenzentů</span>
                        </div>
                    `)
                lucide.createIcons()
            }
        })
    }
})

// vycistit formular po uzavreni modalu
const $modal = document.getElementById("addReviewerModal")
if ($modal) {
    $modal.addEventListener("close", function () {
        $("#reviewArticleId").val("")
        $("#reviewerSelect").val("")
    })
}

// aktualizace stavu clanku
function updateArticleCard(articleId, newStatus) {
    const $card = $(`.article-card[data-article-id="${articleId}"]`) // element karty clanku

    // update data-attribute
    $card.attr("data-status", newStatus)

    // definice badges
    const badgeHTML = {
        "cekajici": "<div class=\"badge badge-warning gap-1\"><i data-lucide=\"clock\" class=\"w-3 h-3\"></i>Čeká na rozhodnutí</div>",
        "prijato": "<div class=\"badge badge-success gap-1\"><i data-lucide=\"check\" class=\"w-3 h-3\"></i>Přijato</div>",
        "zamitnuto": "<div class=\"badge badge-error gap-1\"><i data-lucide=\"x\" class=\"w-3 h-3\"></i>Zamítnuto</div>"
    }

    // aktualizovat badge
    $card.find(".card-title").siblings(".badge").replaceWith(badgeHTML[newStatus])

    // definice tlacitek
    const buttonsHTML = newStatus === "cekajici"
        ? `<div class="flex gap-2">
             <button class="btn btn-success btn-sm gap-1 accept-article-btn" data-article-id="${articleId}">
               <i data-lucide="check" class="w-4 h-4"></i>Přijmout
             </button>
             <button class="btn btn-error btn-sm gap-1 decline-article-btn" data-article-id="${articleId}">
               <i data-lucide="x" class="w-4 h-4"></i>Zamítnout
             </button>
           </div>`
        : `<button class="btn btn-warning btn-sm gap-1 reconsider-article-btn" data-article-id="${articleId}">
             <i data-lucide="rotate-ccw" class="w-4 h-4"></i>Přehodnotit
           </button>`

    // aktualizovat tlacitka (cely container)
    $card.find(".action-buttons-container").html(buttonsHTML)

    // ikony
    lucide.createIcons()
}

