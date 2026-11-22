$ = jQuery;

let delaySave;

function savePage(container) {
  clearTimeout(delaySave);

  delaySave = setTimeout(function () {
    $.ajax({
      url: ssv_data.ajax,
      type: "POST",
      data: {
        action: "ssv_save_page",
        page_num: container.find(".ssv-page-input").val(),
        user_id: container.data("user-id"),
        post_id: container.data("post_id"),
        id: container.data("id"),
      },
      success: function (res) {
        if (res.success) {
          container.attr("data-page", res.data.page_num);
        }
      },
      error: function (err) {
        console.error(err);
      },
    });
  }, 2000);
}

jQuery(document).ready(function ($) {
  $(".ssv-pdf-viewer").each(function () {
    const container = $(this);
    const url = container.data("url");
    const userEmail = container.data("user-email") || "";
    const userId = container.data("user-id") || "";
    let zoomLevel = 1;
    let isMobile = window.matchMedia("(max-width: 768px)").matches;

    let pdfInstance = null;
    let currentPage = 1;
    let totalPages = 0;
    let renderPage = function () {};
    let lastRenderZoom = 1;

    // ✅ NEW STATE
    let panX = 0;
    let panY = 0;
    let isPanning = false;
    let startX = 0;
    let startY = 0;
    let ctrlPressed = false;

    const wrapper = $('<div class="ssv-wrapper" tabindex="0"></div>');

    const toolbar = $(`
      <div class="ssv-toolbar">
        <button class="ssv-toggle">
            <i class="ri-layout-left-line"></i>
        </button>

        <div class="ssv-page-control">
            <button class="ssv-prev-top"><i class="ri-arrow-left-line"></i></button>
            <input type="number" class="ssv-page-input" value="1" min="1" />
            <span class="ssv-total">/ 1</span>
            <button class="ssv-next-top"><i class="ri-arrow-right-line"></i></button>
        </div>

        <button class="ssv-fullscreen">
            <i class="ri-fullscreen-line"></i>
        </button>
      </div>
    `);

    const viewerArea = $('<div class="ssv-viewer-area"></div>');
    const sidebar = $('<div class="ssv-sidebar"></div>');
    const main = $('<div class="ssv-main"></div>');

    viewerArea.append(sidebar).append(main);
    wrapper.append(toolbar).append(viewerArea);
    container.append(wrapper);

    const pageInput = wrapper.find(".ssv-page-input");
    const totalDisplay = wrapper.find(".ssv-total");

    let isActive = false;

    wrapper.on("click", function () {
      isActive = true;
      wrapper.focus();
    });

    $(document).on("click", function (e) {
      if (!wrapper.is(e.target) && wrapper.has(e.target).length === 0) {
        isActive = false;
      }
    });

    function applyTransform() {
      const canvas = main.find("canvas");

      if (!canvas.length) return;

      if (zoomLevel === 1) {
        panX = 0;
        panY = 0;

        canvas.css({
          transform: `translate(0px, 0px) scale(1)`,
          transformOrigin: "center top",
        });

        return;
      }

      canvas.css({
        transform: `translate(${panX}px, ${panY}px) scale(${zoomLevel})`,
        transformOrigin: "0 0",
      });
    }

    pdfjsLib.getDocument(url).promise.then(function (pdf) {
      pdfInstance = pdf;
      totalPages = pdf.numPages;

      totalDisplay.text("/ " + totalPages);
      pageInput.attr("max", totalPages);

      renderPage = function (pageNum, quick = false) {
        if (pageNum < 1) pageNum = 1;
        if (pageNum > totalPages) pageNum = totalPages;

        pdfInstance.getPage(pageNum).then(function (page) {
          const isFullscreen = wrapper.hasClass("ssv-fullscreen-active");

          const screenWidth = window.innerWidth;
          const screenHeight = window.innerHeight;

          const viewport = page.getViewport({ scale: 1 });

          /* SCREEN FACTOR */
          let screenFactor = 1;

          if (screenWidth > 1600) screenFactor = 2.2;
          else if (screenWidth > 1200) screenFactor = 1.9;
          else if (screenWidth > 900) screenFactor = 1.6;
          else if (screenWidth > 600) screenFactor = 1.3;
          else screenFactor = 1.1;

          /* FULLSCREEN BOOST */
          let fullscreenFactor = isFullscreen ? 1.4 : 1;

          /* ZOOM FACTOR */
          let zoomFactor = Math.max(1, zoomLevel);

          /* DEVICE DPI */
          let deviceFactor = window.devicePixelRatio || 1;

          /* FINAL RENDER RESOLUTION */
          let renderResolution =
            screenFactor * fullscreenFactor * zoomFactor * deviceFactor;

          /* QUICK RENDER MODE */
          if (quick) {
            renderResolution = Math.max(1, renderResolution * 0.35);
          }

          /* prevent GPU overload */
          renderResolution = Math.min(renderResolution, 5);

          const deviceScale = renderResolution;

          // fit logic
          let scale;

          if (viewport.width > viewport.height) {
            // horizontal slide
            scale = screenWidth / viewport.width;
          } else {
            // vertical slide
            scale = (screenHeight * 0.9) / viewport.height;
          }

          const renderScale = scale * deviceScale;
          const scaledViewport = page.getViewport({ scale: renderScale });

          const canvas = document.createElement("canvas");
          const ctx = canvas.getContext("2d");

          canvas.width = scaledViewport.width;
          canvas.height = scaledViewport.height;

          canvas.style.width = scaledViewport.width / deviceScale + "px";
          canvas.style.height = scaledViewport.height / deviceScale + "px";

          main.html("").append(canvas);
          if (quick) {
            $(canvas).css("filter", "blur(0.6px)");
          }

          page
            .render({
              canvasContext: ctx,
              viewport: scaledViewport,
            })
            .promise.then(function () {
              if (userEmail || userId) {
                const watermarkText = userEmail;

                ctx.save();
                ctx.globalAlpha = 0.05;

                const watermarkFontSize = 20 * deviceScale;
                ctx.font = "bold " + watermarkFontSize + "px Arial";
                ctx.fillStyle = "#000000";

                const spacing = 100 * deviceScale;

                for (
                  let x = -canvas.width;
                  x < canvas.width * 2;
                  x += spacing
                ) {
                  for (
                    let y = -canvas.height;
                    y < canvas.height * 2;
                    y += spacing
                  ) {
                    ctx.save();
                    ctx.translate(x, y);
                    ctx.rotate(-Math.PI / 6);
                    ctx.fillText(watermarkText, 0, 0);
                    ctx.restore();
                  }
                }

                ctx.restore();
              }
            });

          $(canvas).css("filter", "none");
          const canvasRatio = canvas.width / canvas.height;

          const areaWidth = viewerArea.width();
          const areaHeight = window.innerHeight * 0.9;

          let finalWidth, finalHeight;

          // horizontal slide
          if (canvasRatio > 1) {
            finalWidth = areaWidth;
            finalHeight = areaWidth / canvasRatio;
          }
          // vertical slide
          else {
            finalHeight = areaHeight;
            finalWidth = areaHeight * canvasRatio;
          }

          viewerArea.css({
            height: finalHeight + "px",
          });

          $(canvas).css({
            width: finalWidth + "px",
            height: finalHeight + "px",
          });

          // ✅ RESET PAN
          panX = 0;
          panY = 0;
          applyTransform();

          currentPage = pageNum;
          pageInput.val(currentPage);
          lastRenderZoom = zoomLevel;

          sidebar.find("canvas").removeClass("active");
          sidebar.find(`canvas[data-page="${pageNum}"]`).addClass("active");
          /* PROGRESSIVE SHARPEN */
          if (!quick) return;

          setTimeout(() => {
            renderPage(pageNum, false);
          }, 120);
        });
      };

      for (let i = 1; i <= totalPages; i++) {
        pdfInstance.getPage(i).then(function (page) {
          const viewport = page.getViewport({ scale: 0.15 });

          const canvas = document.createElement("canvas");
          const ctx = canvas.getContext("2d");

          canvas.width = viewport.width;
          canvas.height = viewport.height;
          canvas.dataset.page = page.pageNumber;

          page.render({ canvasContext: ctx, viewport: viewport });

          $(canvas).on("click", function () {
            renderPage(page.pageNumber, true);
          });

          sidebar.append(canvas);
        });
      }

      renderPage(1, true);
    });

    // ✅ MOBILE PINCH + PAN
    if ("ontouchstart" in window) {
      let startDistance = 0;
      let startZoom = 1;
      let lastTouchX = 0;
      let lastTouchY = 0;

      main.on("touchstart", function (e) {
        if (e.touches.length === 2) {
          const dx = e.touches[0].pageX - e.touches[1].pageX;
          const dy = e.touches[0].pageY - e.touches[1].pageY;
          startDistance = Math.sqrt(dx * dx + dy * dy);
          startZoom = zoomLevel;
        }

        if (e.touches.length === 1 && zoomLevel > 1) {
          isPanning = true;
          lastTouchX = e.touches[0].pageX;
          lastTouchY = e.touches[0].pageY;
        }
      });

      main.on("touchmove", function (e) {
        if (e.touches.length === 2) {
          e.preventDefault();

          const dx = e.touches[0].pageX - e.touches[1].pageX;
          const dy = e.touches[0].pageY - e.touches[1].pageY;
          const newDistance = Math.sqrt(dx * dx + dy * dy);

          let scale = newDistance / startDistance;
          zoomLevel = Math.min(Math.max(startZoom * scale, 1), 3);
          if (Math.abs(zoomLevel - lastRenderZoom) > 0.4) {
            lastRenderZoom = zoomLevel;
            renderPage(currentPage);
            return;
          }

          if (zoomLevel === 1) {
            panX = 0;
            panY = 0;
          }

          applyTransform();
        }

        if (e.touches.length === 1 && isPanning) {
          const dx = e.touches[0].pageX - lastTouchX;
          const dy = e.touches[0].pageY - lastTouchY;

          panX += dx;
          panY += dy;

          lastTouchX = e.touches[0].pageX;
          lastTouchY = e.touches[0].pageY;

          applyTransform();
        }
      });

      main.on("touchend", function () {
        isPanning = false;
      });
    }

    // ✅ DESKTOP ZOOM
    wrapper.on("wheel", function (e) {
      if (!e.ctrlKey) return;

      e.preventDefault();

      const rect = main[0].getBoundingClientRect();

      const mouseX = e.originalEvent.clientX - rect.left;
      const mouseY = e.originalEvent.clientY - rect.top;

      const prevZoom = zoomLevel;

      const delta = e.originalEvent.deltaY > 0 ? -0.1 : 0.1;
      zoomLevel = Math.min(Math.max(zoomLevel + delta, 1), 3);

      // re-render if zoom increased significantly
      if (Math.abs(zoomLevel - lastRenderZoom) > 0.35) {
        lastRenderZoom = zoomLevel;
        renderPage(currentPage);
        return;
      }

      const scaleFactor = zoomLevel / prevZoom;

      panX = mouseX - (mouseX - panX) * scaleFactor;
      panY = mouseY - (mouseY - panY) * scaleFactor;

      if (zoomLevel === 1) {
        panX = 0;
        panY = 0;
      }

      applyTransform();
    });

    // ✅ CONTROL KEY
    $(document).on("keydown", function (e) {
      if (e.ctrlKey) {
        ctrlPressed = true;
      }
    });

    $(document).on("keyup", function (e) {
      if (!e.ctrlKey) {
        ctrlPressed = false;
        isPanning = false;
      }
    });

    $(document).on("keydown", function (e) {
      if (e.ctrlKey) {
        ctrlPressed = true;
        main.addClass("ctrl-active");
      }
    });

    $(document).on("keyup", function (e) {
      if (!e.ctrlKey) {
        ctrlPressed = false;
        isPanning = false;
        main.removeClass("ctrl-active");
      }
    });

    // ✅ MOUSE PAN
    main.on("mousedown", function (e) {
      if (!ctrlPressed || zoomLevel <= 1) return;

      isPanning = true;
      startX = e.pageX - panX;
      startY = e.pageY - panY;
    });

    $(document).on("mousemove", function (e) {
      if (!isPanning) return;

      panX = e.pageX - startX;
      panY = e.pageY - startY;

      applyTransform();
    });

    $(document).on("mouseup", function () {
      isPanning = false;
    });

    // (rest of your original code unchanged)
    wrapper.on("keydown", function (e) {
      if (!pdfInstance || !isActive) return;

      if (e.key === "ArrowRight" && currentPage < totalPages) {
        renderPage(currentPage + 1, true);
        savePage(container);
      }
      if (e.key === "ArrowLeft" && currentPage > 1) {
        renderPage(currentPage - 1, true);
        savePage(container);
      }
    });

    pageInput.on("keydown", function (e) {
      if (e.key === "Enter") {
        let val = parseInt($(this).val());
        if (isNaN(val)) val = 1;
        if (val < 1) val = 1;
        if (val > totalPages) val = totalPages;
        renderPage(val);
        savePage(container);
      }
      e.stopPropagation();
    });

    pageInput.on("change", function () {
      let val = parseInt($(this).val());
      if (isNaN(val)) val = 1;
      if (val < 1) val = 1;
      if (val > totalPages) val = totalPages;
      renderPage(val);
      savePage(container);
    });

    const navArrows = $(`
      <div class="ssv-nav">
        <button class="ssv-prev">
          <i class="ri-arrow-left-s-line"></i>
        </button>
        <button class="ssv-next">
          <i class="ri-arrow-right-s-line"></i>
        </button>
      </div>
    `);

    wrapper.append(navArrows);

    wrapper.on("click", ".ssv-next", function () {
      if (currentPage < totalPages) renderPage(currentPage + 1, true);
      savePage(container);
    });

    wrapper.on("click", ".ssv-prev", function () {
      if (currentPage > 1) renderPage(currentPage - 1, true);
      savePage(container);
    });

    wrapper.on("click", ".ssv-next-top", function () {
      if (currentPage < totalPages) renderPage(currentPage + 1, true);
      savePage(container);
    });

    wrapper.on("click", ".ssv-prev-top", function () {
      if (currentPage > 1) renderPage(currentPage - 1, true);
      savePage(container);
    });

    // Sidebar toggle
    wrapper.on("click", ".ssv-toggle", function (e) {
      e.stopPropagation();
      wrapper.toggleClass("ssv-collapsed");
    });

    // Fullscreen
    wrapper.on("click", ".ssv-fullscreen", function () {
      const el = wrapper[0];

      if (!document.fullscreenElement) {
        if (el.requestFullscreen) el.requestFullscreen();
      } else {
        if (document.exitFullscreen) document.exitFullscreen();
      }
    });

    $(document).on("click", ".ssv-startOver", function () {
      zoomLevel = 1;
      panX = 0;
      panY = 0;
      renderPage(1, true);
    });

    $(document).on("click", ".ssv-resume", function () {
      zoomLevel = 1;
      panX = 0;
      panY = 0;
      const item = $(`#${$(this).data("viewer")}`);
      item.find(".ssv-page-input").val(item.attr("data-page")).change();
    });

    $(document).on("click", ".ssv-complete", function () {
      zoomLevel = 1;
      panX = 0;
      panY = 0;
      const item = $(`#${$(this).data("viewer")}`);
      item.find(".ssv-sidebar canvas").last().click();
      item.find(".ssv-page-input").change();
    });

    // Fullscreen class
    $(document).on(
      "fullscreenchange webkitfullscreenchange mozfullscreenchange MSFullscreenChange",
      function () {
        if (document.fullscreenElement) {
          wrapper.addClass("ssv-fullscreen-active");
        } else {
          wrapper.removeClass("ssv-fullscreen-active");
        }

        // re-render current page with new resolution
        if (pdfInstance) {
          renderPage(currentPage);
        }
      },
    );

    container.on("contextmenu", function (e) {
      e.preventDefault();
    });
  });
});
