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

    // pokud je toast viditelny, zobrazit a zmenit jeho pozici
    if ($toast.is(":visible")) {
        $toast.stop(true, true).hide()
    }

    // animace
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
//  ------------------- AJAX Helper ---------------------- //
// ------------------------------------------------------- //
async function ajaxRequest(url, method, data, isFormData = false) {
    try {
        const response = await $.ajax({
            url,
            type: method,
            data: isFormData ? data : $.param(data), // serialize form data na query string
            processData: !isFormData,   // pokud se jedna o FormData, nezpracovavat
            contentType: isFormData ? false : "application/x-www-form-urlencoded; charset=UTF-8",
            dataType: "json"
        })

        // odpoved
        showToast(response.message || "Operace proběhla úspěšně.", "success")
        return response
    } catch (xhr) { // neuspech
        let res = {}
        try {
            res = xhr.responseJSON || JSON.parse(xhr.responseText)
        } catch (e) {
            // not valid json
        }
        showToast(res.message || "Operace se nezdařila.", "error")
        return false
    }
}

// ------------------------------------------------------- //
//  ------------- Tabs - Rozdeleni statusu --------------- //
// ------------------------------------------------------- //
$(".tabs .tab").on("click", function () {
    const $tab = $(this)
    const filter = $tab.data("filter")

    // zmenit aktivni tab
    $(".tabs .tab").removeClass("tab-active")
    $tab.addClass("tab-active")

    // map statusu
    const statusMap = {
        "accepted": "prijato",
        "denied": "zamitnuto",
        "pending": "cekajici"
    }

    // filtrovat karty
    if (filter === "all") {
        $(".article-card").show()
    } else {
        $(".article-card").hide()
        const actualStatus = statusMap[filter]
        $(`.article-card[data-status="${actualStatus}"]`).show()
    }

    // zkontrolovat prazdny stav
    checkEmptyState()
})

// ------------------------------------------------------- //
//  ------------ Formular - Recenze - inline ------------- //
// ------------------------------------------------------- //
$(document).on("click", ".edit-review-btn", function () {
    const $btn = $(this)
    const $card = $btn.closest(".article-card")
    const $formContainer = $card.find(".review-form-container")
    const $form = $card.find(".reviewForm")
    const articleId = $card.data("article-id") || $form.find(".article-id").val()
    const reviewId = $card.data("review-id") || $form.find(".review-id").val()

    // prepnout viditelnost formulare
    $formContainer.toggleClass("hidden")

    // tag pro editor
    const commentEditorId = `comment-${articleId}`

    // naplnit formular existujicimi daty
    if (reviewId) {
        const $reviewData = $card.find(".review-data")
        const comment = $reviewData.data("comment") || ""
        const cat1 = $reviewData.data("cat1")
        const cat2 = $reviewData.data("cat2")
        const cat3 = $reviewData.data("cat3")
        const cat4 = $reviewData.data("cat4")

        if (cat1) $form.find(`input[name='cat1'][value='${cat1}']`).prop("checked", true)
        if (cat2) $form.find(`input[name='cat2'][value='${cat2}']`).prop("checked", true)
        if (cat3) $form.find(`input[name='cat3'][value='${cat3}']`).prop("checked", true)
        if (cat4) $form.find(`input[name='cat4'][value='${cat4}']`).prop("checked", true)

        // nastavit editor
        const editor = (typeof tinymce !== "undefined") ? tinymce.get(commentEditorId) : null
        if (editor) {
            editor.setContent(comment)
        } else {
            // fallback na hidden input
            $form.find(`#${commentEditorId}`).val(comment)
        }
    }

    // ikony
    lucide.createIcons()
})

// schova inline formulare a zobrazi pouze summary
$(document).on("click", ".cancel-review-btn", function () {
    const $btn = $(this)
    const $card = $btn.closest(".article-card")
    const $formContainer = $card.find(".review-form-container")
    const $reviewSummary = $card.find(".review-summary")
    const $editBtn = $card.find(".edit-review-btn")
    const reviewId = $card.data("review-id")

    $formContainer.addClass("hidden")
    $reviewSummary.removeClass("hidden")
    $editBtn.html(`<i data-lucide="edit" class="w-4 h-4"></i> ${reviewId ? "Upravit recenzi" : "Recenzovat"}`)
    lucide.createIcons()
})

// aktualizace hodnot recenze
$(".article-card").on("change", ".rating input[type='radio']", function () {
    const name = $(this).attr("name")
    const value = $(this).val()
    const $card = $(this).closest(".article-card")
    $card.find(`.${name}-value`).text(value)
})

// ------------------------------------------------------- //
//  ----------------- Formular - Odeslat ----------------- //
// ------------------------------------------------------- //
$(document).on("submit", ".reviewForm", async function (e) {
    e.preventDefault()

    const $form = $(this)
    const $card = $form.closest(".article-card")

    // preferovat data z karty, fallback na hidden inputy
    const articleId = $card.data("article-id") || $form.find(".article-id").val()
    const reviewId = $card.data("review-id") || $form.find(".review-id").val()
    const commentEditorId = `comment-${articleId}`
    const commentContent = (typeof tinymce !== "undefined" && tinymce.get(commentEditorId))
        ? tinymce.get(commentEditorId).getContent()
        : ($form.find(`#${commentEditorId}`).val() || "")

    const formData = {
        article_id: articleId,
        review_id: reviewId || "",
        cat1: $form.find("input[name='cat1']:checked").val(),
        cat2: $form.find("input[name='cat2']:checked").val(),
        cat3: $form.find("input[name='cat3']:checked").val(),
        cat4: $form.find("input[name='cat4']:checked").val(),
        komentar: commentContent
    }

    // validace
    if (!formData.cat1 || !formData.cat2 || !formData.cat3 || !formData.cat4) {
        showToast("Prosím vyplňte všechny kategorie.", "error")
        return
    }

    // ajax request
    const res = await ajaxRequest("/reviews/save-review", "POST", formData)

    if (res) { // uspech
        // pouzit odeslane udaje jako nove
        const newCat1 = formData.cat1
        const newCat2 = formData.cat2
        const newCat3 = formData.cat3
        const newCat4 = formData.cat4
        const newComment = formData.komentar || ""
        const newReviewId = res.review_id || reviewId || $card.data("review-id") || ""

        // aktualizovat data-atributy karty
        $card.attr("data-status", "prijato")
        $card.attr("data-review-id", newReviewId)

        // aktualizovat skryta data v .review-data
        const $reviewData = $card.find(".review-data")
        $reviewData.attr("data-cat1", newCat1)
            .attr("data-cat2", newCat2)
            .attr("data-cat3", newCat3)
            .attr("data-cat4", newCat4)
            .attr("data-comment", newComment)

        // aktualizovat ratingy v summary
        const cats = [newCat1, newCat2, newCat3, newCat4]
        $card.find(".review-summary .rating.rating-sm").each(function (idx) {
            const val = parseInt(cats[idx] || 0, 10)
            let html = ""
            for (let i = 1; i <= 5; i++) {
                html += `<input type="radio" class="mask mask-star-2 bg-warning" disabled ${i <= val ? "checked" : ""} />`
            }
            $(this).html(html)
        })

        // akutalizovat komentar v summary
        const $existingCommentBlock = $card.find(".review-summary .pt-3.border-t")
        if (newComment) {
            const commentHtml = `<div class="pt-3 border-t border-base-300"><p class="text-sm"><strong>Komentář:</strong></p><p class="text-sm opacity-70 mt-1">${newComment}</p></div>`
            if ($existingCommentBlock.length) {
                $existingCommentBlock.replaceWith(commentHtml)
            } else {
                $card.find(".review-summary .bg-base-200").append(commentHtml)
            }
        } else {
            if ($existingCommentBlock.length) {
                $existingCommentBlock.remove()
            }
        }

        // schovat formular a zobrazit summary
        $card.find(".review-form-container").addClass("hidden")
        $card.find(".edit-review-btn").text("Upravit recenzi")

        lucide.createIcons()
    }
})

// kontrola zda lze zobrazit nejake recenze
function checkEmptyState() {
    const visibleCards = $(".article-card:visible").length
    if (visibleCards === 0) {
        $("#emptyState").removeClass("hidden")
    } else {
        $("#emptyState").addClass("hidden")
    }
}

// ------------------------------------------------------- //
//  -------------- Inicializace - TinyMCE ---------------- //
// ------------------------------------------------------- //
$(document).ready(function () {
    lucide.createIcons()
    checkEmptyState()

    // inicilizace editoru pro komentare - TinyMCE
    if (typeof tinymce !== "undefined") {
        tinymce.init({
            selector: "textarea.review-comment",
            license_key: "gpl",
            plugins: [
                "autolink", "charmap",
                "help", "insertdatetime",
                "preview", "searchreplace"
            ],
            toolbar: "undo redo | bold italic underline strikethrough",
            height: 400,
            setup: function (editor) {
                editor.on("init", function () {
                    // naplnit editor daty z .review-data pokud exsituje
                    const id = editor.id // comment-<articleId>
                    const articleId = id.replace("comment-", "")
                    const $card = $(`.article-card[data-article-id="${articleId}"]`)
                    const $reviewData = $card.find(".review-data")
                    const comment = $reviewData.data("comment") || ""
                    // nastavit obsah
                    editor.setContent(comment)
                })
            }
        })
    }
})
