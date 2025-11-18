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
//  --------------------- AJAX helper -------------------- //
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
//  --------------- Tabs - rozdeleni stavu --------------- //
// ------------------------------------------------------- //
// prepnuti tabu
$(".tabs .tab").on("click", function () {
    const $tab = $(this)
    const filter = $tab.data("filter")

    // prepnuti aktivniho tabu
    $(".tabs .tab").removeClass("tab-active")
    $tab.addClass("tab-active")

    // filtrovani clanku
    if (filter === "all") {
        $(".article-card").show()
    } else {
        $(".article-card").hide()
        $(`.article-card[data-status="${filter}"]`).show()
    }
})

// ------------------------------------------------------- //
//  -------------- Vytvoreni noveho clanku --------------- //
// ------------------------------------------------------- //
$("#newArticleForm").on("submit", async function (e) {
    e.preventDefault()

    const formData = new FormData(this) // FormData - soubor

    // validace PDF souboru
    const pdfFile = formData.get("pdf")
    if (pdfFile && pdfFile.size > 20 * 1024 * 1024) { // 20 MB
        showToast("PDF soubor je příliš velký. Maximum je 20 MB.", "error")
        return
    }

    // ajax request
    const res = await ajaxRequest("/articles/create-article", "POST", formData, true)

    if (res) {
        // zavrit modal a obnovit stranku
        document.getElementById("newArticleModal").close()
        location.reload()
    }
})

// ------------------------------------------------------- //
//  ------------------- Uprava Clanku -------------------- //
// ------------------------------------------------------- //
// otevreni modalu pro upravu clanku
$(document).on("click", ".edit-article-btn", function () {
    const $btn = $(this)
    const articleId = $btn.data("article-id")
    const title = $btn.data("title")
    const abstract = $btn.data("abstract")

    // naplnit form daty
    $("#editArticleId").val(articleId)
    $("#editTitle").val(title)
    $("#editAbstract").val(abstract)

    // otevrit modal
    document.getElementById("editArticleModal").showModal()
})

// ulozeni upravenych dat clanku
$("#editArticleForm").on("submit", async function (e) {
    e.preventDefault()

    const formData = {
        article_id: $("#editArticleId").val(),
        title: $("#editTitle").val(),
        abstract: $("#editAbstract").val()
    }

    // ajax request
    const res = await ajaxRequest("/articles/edit-article", "POST", formData)

    if (res) { // uspech
        const articleId = formData.article_id

        // aktualizovat UI
        $(`#title-${articleId}`).text(formData.title)
        $(`#abstract-${articleId}`).text(formData.abstract)

        // aktualizovat data atributy tlacitka pro upravu
        $(`.edit-article-btn[data-article-id="${articleId}"]`)
            .data("title", formData.title)
            .data("abstract", formData.abstract)

        // zavrit modal
        document.getElementById("editArticleModal").close()
    }
})

// ------------------------------------------------------- //
//  ------------------ Smazat Clanek --------------------- //
// ------------------------------------------------------- //
$(document).on("click", ".delete-article-btn", async function () {
    const $btn = $(this)
    const articleId = $btn.data("article-id")

    if (!confirm("Opravdu chcete smazat tento článek? Tato akce je nevratná.")) return // overeni

    // ajax request
    const res = await ajaxRequest("/articles/delete-article", "POST", {article_id: articleId})

    if (res) { // uspech
        // odstranit kartu clanku s animaci
        const $card = $(`.article-card[data-article-id="${articleId}"]`)
        $card.fadeOut(400, function () {
            $(this).remove()

            // pokud nejsou zadne clanky, zobrazit prazdny stav
            if ($(".article-card").length === 0) {
                $("#articlesContainer").html(`
                    <div class="text-center py-12">
                        <i data-lucide="inbox" class="w-16 h-16 mx-auto opacity-30 mb-4"></i>
                        <p class="text-lg opacity-70">Zatím nemáte žádné články</p>
                        <button class="btn btn-primary mt-4" onclick="newArticleModal.showModal()">
                            Vytvořit první článek
                        </button>
                    </div>
                `)
                // ikony
                lucide.createIcons()
            }
        })

        // aktualizovat pocty v tabech
        updateTabCounts()
    }
})

// ------------------------------------------------------- //
//  ------------- Tabs - Aktualizace poctu --------------- //
// ------------------------------------------------------- //
function updateTabCounts() {
    const total = $(".article-card").length
    const pending = $(".article-card[data-status=\"pending\"]").length
    const accepted = $(".article-card[data-status=\"accepted\"]").length
    const denied = $(".article-card[data-status=\"denied\"]").length

    // aktualizovat pocty v tabech dle stavu
    $(".tab[data-filter=\"all\"] .badge").text(total)
    $(".tab[data-filter=\"pending\"] .badge").text(pending)
    $(".tab[data-filter=\"accepted\"] .badge").text(accepted)
    $(".tab[data-filter=\"denied\"] .badge").text(denied)
}

// nastaveni dnesniho data jako vychozi v novem clanku
$(document).ready(function () {
    const today = new Date().toISOString().split("T")[0]
    $("#newDate").val(today)

    // ikony
    lucide.createIcons()
})

// ------------------------------------------------------- //
//  ----------- Zobrazit PDF - Vlozit iframe ------------- //
// ------------------------------------------------------- //
$(document).on("click", ".show-pdf-btn", function (e) {
    e.preventDefault() // zamezit vychozi akci odkazu

    const $btn = $(this)
    // najdeme ikonu vedle tlacitka
    const $icon = $btn.siblings("i[data-lucide]")
    const pdfUrl = $btn.data("pdf-url")
    const targetId = $btn.data("target-id")
    const $container = $(`#${targetId}`)

    // najdeme misto, kam vlozime iframe
    const $iframeWrapper = $container.find("div[style*=\"padding-top\"]")

    // pokud je viditelny, skryjeme ho
    if ($container.is(":visible")) {
        // skryt PDF
        $container.slideUp(300, function () {
            $(this).addClass("hidden")
            $iframeWrapper.empty().html(`
                <p class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                    Načítání PDF...
                </p>
            `) // vymazat iframe pro usporu pameti
        })
        $btn.text("Zobrazit PDF")
        $icon.attr("data-lucide", "file-text")
    } else {
        // zobrazit PDF
        // vytvorit iframe
        const $iframe = $(`
            <iframe src="${pdfUrl}" 
                    class="absolute top-0 left-0 w-full h-full border rounded-lg">
                Váš prohlížeč nepodporuje vkládání PDF. 
                <a href="${pdfUrl}" target="_blank">Zobrazit v nové záložce</a>.
            </iframe>
        `)

        // vlozit a zobrazit
        $iframeWrapper.empty().append($iframe)
        $container.removeClass("hidden").hide().slideDown(300)
        $btn.text("Skrýt PDF")
        $icon.attr("data-lucide", "chevron-up")
    }

    // ikony
    lucide.createIcons()
})