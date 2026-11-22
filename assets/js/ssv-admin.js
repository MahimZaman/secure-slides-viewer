jQuery(document).ready(function ($) {
  $(".ssv-upload-btn").on("click", function () {
    let fileInput = $('<input type="file" accept=".pdf,.ppt,.pptx">');
    fileInput.trigger("click");

    fileInput.on("change", function () {
      let file = this.files[0];
      let formData = new FormData();

      formData.append("action", "ssv_process_slide");
      formData.append("nonce", ssvData.nonce);
      formData.append("post_id", $("#post_ID").val());
      formData.append("file", file);

      $(".ssv-loader").show();

      $.ajax({
        url: ssvData.ajax,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (res) {
          $(".ssv-loader").hide();

          if (res.success) {
            $(".ssv-preview").html(
              '<iframe src="' +
                res.data.url +
                '" width="100%" height="500"></iframe>',
            );
          } else {
            alert(res.data);
          }
        },
      });
    });
  });

  $(".cs-toggle-btn").on("click", function () {
    const input = $("#cs_converter_api_key");
    const icon = $(this).find("i");

    if (input.attr("type") === "password") {
      input.attr("type", "text");
      icon.removeClass("ri-eye-line").addClass("ri-eye-off-line");
    } else {
      input.attr("type", "password");
      icon.removeClass("ri-eye-off-line").addClass("ri-eye-line");
    }
  });

  $(document).on("click", ".ssv-copy-btn", function () {
    const code = $(this).data("code");

    navigator.clipboard.writeText(code).then(function () {
      const toast = $('<div class="ssv-toast">Shortcode copied</div>');
      $("body").append(toast);

      setTimeout(function () {
        toast.addClass("show");
      }, 10);

      setTimeout(function () {
        toast.removeClass("show");
        setTimeout(function () {
          toast.remove();
        }, 300);
      }, 2000);
    });
  });

  $(".cs-color-field").wpColorPicker();
});
