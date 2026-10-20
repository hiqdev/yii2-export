(function ($) {

  $.fn.exporter = function (methodOrOptions = {}) {
    const methods = {
      copy: function (text) {
        function fallbackCopy(text) {
          const listener = function (ev) {
            ev.preventDefault();
            ev.clipboardData.setData("text/plain", text);
          };
          document.addEventListener("copy", listener);
          document.execCommand("copy");
          document.removeEventListener("copy", listener);
        }
        if (navigator.clipboard) {
          window.navigator.permissions.query({ name: "clipboard-write" }).then((result) => {
            if (result.state == "granted" || result.state == "prompt") {
              navigator.clipboard.writeText(text).then(
                () => {
                  hipanel.notify.success("Clipped!");
                },
                (e) => {
                  hipanel.notify.error("Failed to copy to clipboard");
                  console.error(e);
                },
              );
            }
          });
        } else {
          fallbackCopy(text);
          hipanel.notify.success("Clipped!");
        }
      },
    };
    let settings = {};
    if (methods[methodOrOptions]) {
      return methods[methodOrOptions].apply(this, Array.prototype.slice.call(arguments, 1));
    } else if (typeof methodOrOptions === "object" || !methodOrOptions) {
      settings = $.extend({
        progressUrl: "progress-export",
        downloadUrl: "download-export",
        cancelUrl: "cancel-export",
        messages: {
          step0: "Initialization",
          step1: "Downloading",
          step2: "Wait until the report is downloaded",
        },
      }, methodOrOptions);
    } else {
      $.error("Method: " + methodOrOptions + " is not found in the Export plugin!");
    }

    this.each(function () {
      const bar = $("#export-progress-box");
      const progress = bar.find(".progress-bar").eq(0);
      const progressText = bar.find(".progress-text").eq(0);
      const progressNumberText = bar.find(".progress-number").eq(0);
      const progressDescriptionText = bar.find(".progress-description").eq(0);
      const progressCancelExportButton = bar.find("button").eq(0);
      const exportBtn = $("#export-btn");
      // The server gives up on a single HIAPI request after 120s (see AbstractExporter::EXPORT_REQUEST_TIMEOUT),
      // so a job that has not changed for longer than that is dead, and the user must be told about it.
      const STALL_TIMEOUT_MS = 180000;
      let stallTimer = null;
      let lastFrame = null;

      const resetExportUI = function () {
        clearTimeout(stallTimer);
        lastFrame = null;
        bar.hide(500, () => {
          progressText.text("");
          progressNumberText.text("");
          progressDescriptionText.text("");
          exportBtn.attr("disabled", false).removeClass("disabled");
          progress.css("width", 0);
          progressCancelExportButton.show();
        });
      };

      const beginExportUi = function (callback) {
        exportBtn.attr("disabled", true).addClass("disabled");
        progress.css("width", "100%");
        bar.show(500, () => callback());
      };

      const armStallTimer = (exportId, getEventSource) => {
        clearTimeout(stallTimer);
        stallTimer = setTimeout(() => {
          getEventSource()?.close();
          hipanel.runProcess(settings.cancelUrl, { id: exportId });
          hipanel.notify.error("The export did not make any progress for several minutes and was stopped. Please try again or narrow the filters.");
          resetExportUI();
        }, STALL_TIMEOUT_MS);
      };

      const startExport = (event) => {
        event.preventDefault();
        if (!window.EventSource) {
          return;
        }
        const startExportUrl = event.target.dataset.exportUrl;
        const cancelExportUrl = settings.cancelUrl;
        // A fresh 10-digit id per click: the id rendered into the page is shared by every click,
        // so a repeat export would hit the already finished job in cache.
        const exportId = String(Math.floor(1e9 + Math.random() * 9e9));
        let activeEventSource = null;
        beginExportUi(() => {
          armStallTimer(exportId, () => activeEventSource);
          hipanel.runProcess(startExportUrl, { export_id: exportId }, null, () => {
            const {
              onMessage,
              onError,
            } = hipanel.progress(`${settings.progressUrl}?id=${exportId}`, (es) => {
              activeEventSource = es;
              const onPageUnload = function (e) {
                e.preventDefault();
                e.returnValue = "";
                es.close();
                hipanel.runProcess(cancelExportUrl, { id: exportId });
              };
              window.addEventListener("beforeunload", onPageUnload);
              progressCancelExportButton.click(function () {
                hipanel.runProcess(cancelExportUrl, { id: exportId });
                es.close();
                window.removeEventListener("beforeunload", onPageUnload);
                resetExportUI();
              });
            });
            onMessage((event, es) => {
              if (event.data !== lastFrame) {
                lastFrame = event.data;
                armStallTimer(exportId, () => activeEventSource);
              }
              const data = JSON.parse(event.data);
              if ("errorMessage" in data && data.errorMessage) {
                es.close();
                hipanel.notify.error(`Status: ${data.status}\nMessage: ${data.errorMessage}`);
                resetExportUI();
              } else {
                if (data.status === "running") {
                  progressText.text(data.taskName || "Running");
                  const hasTotal = data.total > 0;
                  progress.css("width", hasTotal ? Math.floor((data.progress / data.total) * 100) + "%" : 0);
                  progressNumberText.text(hasTotal ? data.progress + " / " + data.total + " " + (data.unit || "") : "...");
                } else {
                  progress.css("width", "100%");
                  progressNumberText.text("");
                  clearTimeout(stallTimer);
                  progressCancelExportButton.hide(() => {
                    es.close();
                  });
                  if (data.status === "success") {
                    downloadWithProgress(exportId, new URL(startExportUrl).searchParams.get("format"));
                  } else {
                    hipanel.notify.error(`Status: ${data.status}\nMessage: ${data.errorMessage}`);
                    resetExportUI();
                  }
                }
              }
            });
            onError((event, es) => {
              console.error(event, es);
              es.close();
              hipanel.notify.error("Export progress connection was lost. Please try again.");
              resetExportUI();
            });
          });
        });
      };

      const downloadWithProgress = (id, ext) => {
        progressText.text(settings.messages.step1);

        if (ext === "md") {
          // Not a file download - read the body and copy it to clipboard instead.
          const xhr = $.ajaxSettings.xhr();
          xhr.onreadystatechange = function () {
            if (this.readyState === 4 && this.status === 200) {
              methods.copy(xhr.responseText);
              resetExportUI();
            }
          };
          xhr.responseType = "text";
          xhr.open("GET", settings.downloadUrl + "?id=" + id, true);
          xhr.send();
          return;
        }

        // A blob URL + programmatic <a download>.click() only downloads
        // reliably while the browser still considers this a user-initiated
        // gesture. By the time this runs - after start-export, the full
        // progress-export SSE wait, and an XHR round trip - that window has
        // often expired, and the browser can silently drop the download with
        // no dialog and no visible error (observed in the wild for larger,
        // slower exports). A same-origin iframe navigation isn't subject to
        // that gesture requirement, so it downloads reliably regardless of
        // how long the export took. The server already sets the correct
        // filename via Content-Disposition, so nothing needs to be computed
        // client-side.
        // Once the iframe request is underway, the browser's own download UI
        // (save dialog / download shelf) takes over - this app's progress bar
        // only needs to cover the export itself, not the file transfer, so it
        // doesn't need to know when the download actually finishes. iframe
        // 'load' isn't reliable for a Content-Disposition: attachment response
        // in every browser anyway (some never fire it for a navigation that
        // turns into a download rather than rendering a page), so waiting on
        // it - or on any long fallback timer - just leaves the bar stuck
        // showing "Downloading" long after the browser has already taken over.
        progressDescriptionText.text(settings.messages.step2);
        const iframe = document.createElement("iframe");
        iframe.style.display = "none";
        iframe.src = settings.downloadUrl + "?id=" + id;
        document.body.appendChild(iframe);
        setTimeout(() => {
          resetExportUI();
          iframe.remove();
        }, 1500);
      };

      $(this).on("click", startExport);
    });

    return this;
  };

}(jQuery));
