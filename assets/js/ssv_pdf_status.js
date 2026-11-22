jQuery(document).ready(function ($) {
  if (!ssvPdfStatus.post_id) {
    console.log("Nothing");
    return;
  }

  let card = $("#ssv-pdf-card");

  function updateCard(status) {
    let icon = card.find(".ssv-icon");
    let text = card.find(".ssv-text");

    card.removeClass("completed failed");

    if (status === "pending") {
      icon.attr("class", "ri-loader-4-line ssv-icon spin");
      text.text("Processing PDF...");
    }

    if (status === "completed") {
      icon.attr("class", "ri-checkbox-circle-fill ssv-icon");
      text.html(
        `PDF Ready <span onclick="location.reload()" style="text-decoration: underline; cursor: pointer">Reload</span>`,
      );
      card.addClass("completed");
    }

    if (status === "failed") {
      icon.attr("class", "ri-error-warning-fill ssv-icon");
      text.html(
        `PDF Conversion Failed <span onclick="location.reload()" style="text-decoration: underline; cursor: pointer">Reload</span>`,
      );
      card.addClass("failed");
    }

    if (status !== "failed" && status !== "completed" && status !== "pending") {
      icon.attr("class", "ri-error-warning-fill ssv-icon");
      text.html(
        `${status} <span onclick="location.reload()" style="text-decoration: underline; cursor: pointer">Reload</span>`,
      );
      card.addClass("failed");
    }
  }

  function checkStatus() {
    $.post(
      ssvPdfStatus.ajax_url,
      {
        action: "ssv_check_pdf_status",
        post_id: ssvPdfStatus.post_id,
      },
      function (response) {
        if (!response.success) return;

        updateCard(response.data.status);

        if (response.data.status !== "pending") {
          clearInterval(polling);
        }
      },
    );
  }

  checkStatus();
  let polling = setInterval(checkStatus, 5000);
});
