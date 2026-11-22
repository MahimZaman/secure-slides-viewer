jQuery(document).ready(function ($) {
  let panel = $("#ssv-note-panel");
  let toggle = $("#ssv-note-toggle");
  let closeBtn = $(".ssv-note-close");
  let textarea = $("#ssv-note-content");
  let status = $("#ssv-note-status");
  let saveBtn = $("#ssv-note-save");

  let timer = null;
  let lastSaved = "";

  function openPanel() {
    panel.addClass("ssv-open");
    loadNote();
  }

  function closePanel() {
    panel.removeClass("ssv-open");
  }

  toggle.on("click", openPanel);
  closeBtn.on("click", closePanel);

  function loadNote() {
    $.post(
      SSVNotesData.ajax_url,
      {
        action: "ssv_get_note",
        nonce: SSVNotesData.nonce,
        lesson_id: SSVNotesData.lesson_id,
      },
      function (res) {
        if (res.success) {
          textarea.val(res.data.content);
          lastSaved = res.data.content;
        }
      },
    );
  }

  function saveNote(manual = false) {
    let content = textarea.val();

    if (content === lastSaved && !manual) return;

    status.text("Saving...");

    $.post(
      SSVNotesData.ajax_url,
      {
        action: "ssv_save_note",
        nonce: SSVNotesData.nonce,
        lesson_id: SSVNotesData.lesson_id,
        course_id: SSVNotesData.course_id,
        content: content,
      },
      function (res) {
        if (res.success) {
          lastSaved = content;
          status.text("Saved ✓");
        }
      },
    );
  }

  textarea.on("input", function () {
    clearTimeout(timer);
    timer = setTimeout(function () {
      saveNote();
    }, 2000);
  });

  saveBtn.on("click", function () {
    saveNote(true);
  });
});
