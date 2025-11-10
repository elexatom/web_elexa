// File: `templates/scripts/reviews.js`

// Toast notifikace
function showToast(message, type = "success") {
    const $toast = $("#messageToast")
    const $text = $("#messageText")

    $text.text(message)

    const alertClass =
        type === "error"
            ? "alert-error"
            : type === "info"
                ? "alert-info"
                : "alert-success"

    const $alert = $toast.find(".alert")
    $alert
        .removeClass("alert-success alert-error alert-info")
        .addClass(alertClass)

    if ($toast.is(":visible")) {
        $toast.stop(true, true).hide()
    }

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

// AJAX helper
async function ajaxRequest(url, method, data, isFormData = false) {
    try {
        const response = await $.ajax({
            url,
            type: method,
            data: isFormData ? data : $.param(data),
            processData: !isFormData,
            contentType: isFormData ? false : "application/x-www-form-urlencoded; charset=UTF-8",
            dataType: "json"
        })

        showToast(response.message || "Operace proběhla úspěšně.", "success")
        return response
    } catch (xhr) {
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

// Filter articles by status
$(".tabs .tab").on("click", function () {
    const $tab = $(this)
    const filter = $tab.data("filter")

    // Switch active tab
    $(".tabs .tab").removeClass("tab-active")
    $tab.addClass("tab-active")

    // Filter articles
    if (filter === "all") {
        $(".article-card").show()
    } else {
        $(".article-card").hide()
        $(`.article-card[data-status="${filter}"]`).show()
    }

    // Show empty state if no articles
    checkEmptyState()
})

// Open review form inline
$(document).on("click", ".edit-review-btn", function () {
    const $btn = $(this)
    const $card = $btn.closest(".article-card")
    const $formContainer = $card.find(".review-form-container")
    const $reviewSummary = $card.find(".review-summary")
    const $form = $card.find(".reviewForm")
    // prefer data on the card, fallback to hidden inputs inside the form
    const articleId = $card.data("article-id") || $form.find(".article-id").val()
    const reviewId = $card.data("review-id") || $form.find(".review-id").val()

    // Toggle form visibility
    $formContainer.toggleClass("hidden")

    const commentEditorId = `comment-${articleId}`

    // Load existing review data into form fields and editor
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

        const editor = (typeof tinymce !== "undefined") ? tinymce.get(commentEditorId) : null
        if (editor) {
            editor.setContent(comment)
        } else {
            // Fallback if editor not available yet
            $form.find(`#${commentEditorId}`).val(comment)
        }
    }

    lucide.createIcons()
})

// Cancel review form
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

// Update rating display
$(".article-card").on("change", ".rating input[type='radio']", function () {
    const name = $(this).attr("name")
    const value = $(this).val()
    const $card = $(this).closest(".article-card")
    $card.find(`.${name}-value`).text(value)
})

// Save review
$(document).on("submit", ".reviewForm", async function (e) {
    e.preventDefault()

    const $form = $(this)
    const $card = $form.closest(".article-card")

    // prefer data on the card, fallback to hidden inputs inside the form
    const articleId = $card.data("article-id") || $form.find(".article-id").val()
    const reviewId = $card.data("review-id") || $form.find(".review-id").val()
    const commentEditorId = `comment-${articleId}`
    const commentContent = (typeof tinymce !== "undefined" && tinymce.get(commentEditorId))
        ? tinymce.get(commentEditorId).getContent()
        : ($form.find(`#${commentEditorId}`).val() || "")

    console.log("articleId:", articleId, "reviewId:", reviewId)

    const formData = {
        article_id: articleId,
        review_id: reviewId || "",
        cat1: $form.find("input[name='cat1']:checked").val(),
        cat2: $form.find("input[name='cat2']:checked").val(),
        cat3: $form.find("input[name='cat3']:checked").val(),
        cat4: $form.find("input[name='cat4']:checked").val(),
        komentar: commentContent
    }

    if (!formData.cat1 || !formData.cat2 || !formData.cat3 || !formData.cat4) {
        showToast("Prosím vyplňte všechny kategorie.", "error")
        return
    }

    const res = await ajaxRequest("/reviews/save-review", "POST", formData)

    if (res) {
        // Use the submitted values as the new display values (server response fallback)
        const newCat1 = formData.cat1
        const newCat2 = formData.cat2
        const newCat3 = formData.cat3
        const newCat4 = formData.cat4
        const newComment = formData.komentar || ""
        const newReviewId = res.review_id || reviewId || $card.data("review-id") || ""

        // Update card attributes
        $card.attr("data-status", "accepted")
        $card.attr("data-review-id", newReviewId)

        // Update hidden .review-data attributes so editor load uses up-to-date values
        const $reviewData = $card.find(".review-data")
        $reviewData.attr("data-cat1", newCat1)
            .attr("data-cat2", newCat2)
            .attr("data-cat3", newCat3)
            .attr("data-cat4", newCat4)
            .attr("data-comment", newComment)

        // Update the summary star displays (there are four .rating.rating-sm blocks in order)
        const cats = [newCat1, newCat2, newCat3, newCat4]
        $card.find(".review-summary .rating.rating-sm").each(function (idx) {
            const val = parseInt(cats[idx] || 0, 10)
            let html = ""
            for (let i = 1; i <= 5; i++) {
                html += `<input type="radio" class="mask mask-star-2 bg-warning" disabled ${i <= val ? "checked" : ""} />`
            }
            $(this).html(html)
        })

        // Update or insert the comment block in the summary
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

        // Hide form and update button text
        $card.find(".review-form-container").addClass("hidden")
        $card.find(".edit-review-btn").text("Upravit recenzi")

        lucide.createIcons()
    }
})

// Check if there are articles to display
function checkEmptyState() {
    const visibleCards = $(".article-card:visible").length
    if (visibleCards === 0) {
        $("#emptyState").removeClass("hidden")
    } else {
        $("#emptyState").addClass("hidden")
    }
}

// Initialize on page load
$(document).ready(function () {
    lucide.createIcons()
    checkEmptyState()

    // Initialize TinyMCE for every comment textarea
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
                    // populate editor content from corresponding .review-data if present
                    const id = editor.id // should be comment-<articleId>
                    const articleId = id.replace("comment-", "")
                    const $card = $(`.article-card[data-article-id="${articleId}"]`)
                    const $reviewData = $card.find(".review-data")
                    const comment = $reviewData.data("comment") || ""
                    // set content
                    editor.setContent(comment)
                })
            }
        })
    }
})
