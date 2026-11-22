$ = jQuery;

function ssvFormatFileSize(bytes, decimals = 2) {
  if (!bytes || bytes === 0) return "0 Bytes";

  const k = 1024;
  const sizes = ["Bytes", "KB", "MB", "GB", "TB", "PB"];

  const i = Math.floor(Math.log(bytes) / Math.log(k));
  const value = bytes / Math.pow(k, i);

  return (
    new Intl.NumberFormat(undefined, {
      minimumFractionDigits: 0,
      maximumFractionDigits: decimals,
    }).format(value) +
    " " +
    sizes[i]
  );
}

function ssvToast(message, type = "success") {
  const container = $("#ssv-toast-container");

  const toast = $('<div class="ssv-toast ' + type + '">' + message + "</div>");

  container.append(toast);

  setTimeout(() => toast.addClass("show"), 50);

  setTimeout(() => {
    toast.removeClass("show");
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

jQuery(document).ready(function ($) {
  const dropzone = $("#ssv-dropzone");
  const input = $("#ssv-file-input");
  const previewBox = $("#ssv-preview-temp");

  if (!dropzone.length) return;

  dropzone.on("click", function () {
    input[0].click();
  });

  input.on("change", function () {
    const file = this.files[0];
    if (!file) return;

    const ext = file.name.split(".").pop().toLowerCase();
    $("#title").val(file.name.split(".")[0]).change();
    $("#title-prompt-text").addClass("screen-reader-text");
    previewBox.html("");

    if (ext === "pdf") {
      const reader = new FileReader();
      reader.onload = function (e) {
        previewBox.html(`<div class="ssv-file-preview">
                <i class="ri-file-pdf-2-fill"></i>
                <div class="ssv-file-info">
                    <p>${file.name}</p>
                    <span class="ssv-file-size">${ssvFormatFileSize(file.size)}</span>
                    <span>Will be saved to secure directory</span>
                </div>
            </div>`);
      };
      reader.readAsDataURL(file);
    } else if (ext === "ppt" || ext === "pptx") {
      previewBox.html(`<div class="ssv-file-preview">
                <i class="ri-file-ppt-2-fill"></i>
                <div class="ssv-file-info">
                    <p>${file.name}</p>
                    <span class="ssv-file-size">${ssvFormatFileSize(file.size)}</span>
                    <span>Will be converted to PDF on save</span>
                </div>
            </div>`);
    } else {
      previewBox.html(
        '<div class="ssv-file-preview error">' +
          '<i class="ri-error-warning-fill"></i>' +
          "<p>Invalid file type</p>" +
          "</div>",
      );

      input.val("");
    }
  });
});

$(document)
  .on("click", ".ssv-preview-btn", function () {
    $("#ssv-preview").fadeIn(100);
  })
  .on("click", ".ssv-preview-close", function () {
    $("#ssv-preview").hide();
  });
