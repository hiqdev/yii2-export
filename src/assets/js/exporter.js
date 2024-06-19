(function ($) {

  $.fn.exporter = function (options = {}) {
    const settings = $.extend({
      progressUrl: "progress-export",
      downloadUrl: "download-export",
      cancelUrl: "cancel-export",
      messages: {
        step0: "Initialization",
        step1: "Downloading",
        step2: "Wait until the report is downloaded",
      },
    }, options);

    this.each(function () {
      const bar = $("#export-progress-box");
      const progress = bar.find(".progress-bar").eq(0);
      const progressText = bar.find(".progress-text").eq(0);
      const progressNumberText = bar.find(".progress-number").eq(0);
      const progressDescriptionText = bar.find(".progress-description").eq(0);
      const progressCanceExportButton = bar.find("button").eq(0);
      const exportBtn = $("#export-btn");

      const resetExportUI = function () {
        bar.hide(500, () => {
          progressText.text("");
          progressNumberText.text("");
          progressDescriptionText.text("");
          exportBtn.attr("disabled", false).removeClass("disabled");
          progress.css("width", 0);
          progressCanceExportButton.show();
        });
      };

      const beginExportUi = function (callback) {
        exportBtn.attr("disabled", true).addClass("disabled");
        progress.css("width", "100%");
        bar.show(500, () => callback());
      };

      const startExport = (event) => {
        event.preventDefault();
        if (!window.EventSource) {
          return;
        }
        const startExportUrl = event.target.dataset.exportUrl;
        const cancelExportUrl = settings.cancelUrl;
        const id = event.target.dataset.id;
        beginExportUi(() => {
          hipanel.runProcess(startExportUrl, { id: id }, null, () => {
            hipanel.progress(`${settings.progressUrl}?id=${id}`, (es) => {
              const onPageUnload = function (e) {
                e.preventDefault();
                e.returnValue = "";
                es.close();
                hipanel.runProcess(cancelExportUrl, { id });
              };
              window.addEventListener("beforeunload", onPageUnload);
              progressCanceExportButton.click(function () {
                hipanel.runProcess(cancelExportUrl, { id });
                es.close();
                window.removeEventListener("beforeunload", onPageUnload);
                resetExportUI();
              });
            }).onMessage((event, es) => {
              const data = JSON.parse(event.data);
              if (data.status === "running") {
                progressText.text(data.taskName || "Running");
                const percentComplete = Math.floor((data.progress / data.total) * 100) + "%";
                progress.css("width", percentComplete);
                progressNumberText.text(data.progress > 0 ? data.progress + " / " + data.total + " " + (data.unit || "") : "...");
              } else {
                progress.css("width", "100%");
                progressNumberText.text("");
                progressCanceExportButton.hide(() => {
                  es.close();
                });
                if (data.status === "success") {
                  downloadWithProggress(id, new URL(startExportUrl).searchParams.get("format"));
                } else {
                  hipanel.notify.error(`Status: ${data.status}\nMessage: ${data.errorMessage}`);
                  resetExportUI();
                }
              }
            });
          });
        });
      };

      const downloadWithProggress = (id, ext) => {
        progressText.text(settings.messages.step1);
        const xhr = $.ajaxSettings.xhr();
        xhr.onreadystatechange = function () {
          if (this.readyState === 4 && this.status === 200) {
            const filename = "report_" + id + "." + ext;
            if (ext === "md") {
              function copyText(text) {
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
                    navigator.clipboard.writeText(xhr.responseText).then(
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
                copyText(xhr.responseText);
                hipanel.notify.success("Clipped!");
              }
            } else {
              if (typeof window.chrome !== "undefined") {
                // Chrome version
                const link = document.createElement("a");
                link.href = window.URL.createObjectURL(xhr.response);
                link.download = filename;
                link.click();
              } else if (typeof window.navigator.msSaveBlob !== "undefined") {
                // IE version
                var blob = new Blob([xhr.response], { type: "application/force-download" });
                window.navigator.msSaveBlob(blob, filename);
              } else if (/(Version)\/(\d+)\.(\d+)(?:\.(\d+))?.*Safari\//.test(navigator.userAgent)) {
                const link = document.createElement("a");
                link.href = window.URL.createObjectURL(xhr.response);
                link.download = filename;
                link.click();
              } else {
                // Firefox version
                var file = new File([xhr.response], filename, { type: "application/force-download" });
                window.open(URL.createObjectURL(file));
              }
            }
          }
        };
        xhr.onprogress = function (event) {
          if (event.lengthComputable) {
            const percentComplete = Math.floor((event.loaded / event.total) * 100) + "%";
            progress.css("width", percentComplete);
            progressNumberText.text(percentComplete);
            progressDescriptionText.text("Wait until the report is downloaded");
            if (percentComplete === "100%") {
              resetExportUI();
            }
          }
        };
        xhr.responseType = ext === "md" ? "text" : "blob";
        xhr.open("GET", settings.downloadUrl + "?id=" + id, true);
        xhr.send();
      };

      $(this).on("click", startExport);
    });

    return this;
  };

}(jQuery));
