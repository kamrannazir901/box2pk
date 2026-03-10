/**
 * Shipbox User Header Widget - Optimized & Locked
 */
(function () {
  // Prevent this specific block from running more than once
  if (window.shipboxDropdownInitialized) return;
  window.shipboxDropdownInitialized = true;

  document.addEventListener("DOMContentLoaded", function () {
    const widget = document.querySelector(".shipbox-user-widget");
    const dropdown = document.querySelector(".shipbox-dropdown");

    if (!widget || !dropdown) return;

    // Use a single click listener on the widget
    widget.addEventListener("click", function (e) {
      // If clicking a link, let it navigate
      if (e.target.closest("a")) return;

      e.preventDefault();
      e.stopPropagation();

      const isOpen = dropdown.classList.contains("is-active");

      if (isOpen) {
        dropdown.classList.remove("is-active");
        console.log("Menu State: Closed");
      } else {
        dropdown.classList.add("is-active");
        console.log("Menu State: Open");
      }
    });

    // Close on outside click
    document.addEventListener("click", function (e) {
      if (!widget.contains(e.target)) {
        dropdown.classList.remove("is-active");
      }
    });
  });
})();

// Submit order confirmation form
(function ($) {
  "use strict";

  // Prevent multiple initializations
  if (window.shipboxInitialized) {
    return;
  }
  window.shipboxInitialized = true;

  $(function () {
    const $form = $("#shipbox-submission-form");
    const $whSelect = $("#warehouse-select");
    const $whDisplay = $("#warehouse-display");

    // Submission lock to prevent duplicate AJAX calls
    let isSubmitting = false;

    // ============================================
    // 1. Warehouse Address Preview (Data-Driven Only)
    // ============================================
    $whSelect.off("change.shipbox").on("change.shipbox", function (e) {
      e.preventDefault();
      e.stopImmediatePropagation();

      const key = $(this).val();
      if (key && shipbox_wh_data[key]) {
        const wh = shipbox_wh_data[key];

        let html = `
                    <div class="d-flex justify-content-between border-bottom py-1"><span>Line 1:</span> <span class="fw-bold text-dark">${wh.address_line1}</span></div>
                    <div class="d-flex justify-content-between border-bottom py-1"><span>Line 2:</span> <span class="text-dark">${wh.address_line2_prefix}</span></div>
                    <div class="d-flex justify-content-between border-bottom py-1"><span>City:</span> <span class="fw-bold text-dark">${wh.city}</span></div>
                    <div class="d-flex justify-content-between border-bottom py-1"><span>State:</span> <span class="fw-bold text-dark">${wh.state}</span></div>
                    <div class="d-flex justify-content-between border-bottom py-1"><span>Zip Code:</span> <span class="fw-bold text-dark">${wh.zip_code}</span></div>
                    <div class="d-flex justify-content-between py-1"><span>Phone:</span> <span class="fw-bold text-dark">${wh.phone}</span></div>
                `;

        if (wh.warehouse_notes) {
          html += `<div class="mt-2 text-danger small pt-2 border-top"><strong>Note:</strong> ${wh.warehouse_notes}</div>`;
        }

        $whDisplay.html(html).fadeIn();
      } else {
        $whDisplay.hide();
      }
    });

    // ============================================
    // 2. Repeater Logic - Add Merchant Row
    // ============================================
    $(document)
      .off("click.shipbox", "#add-merchant-row")
      .on("click.shipbox", "#add-merchant-row", function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        const $btn = $(this);
        if ($btn.data("processing")) {
          return false;
        }

        $btn.data("processing", true);

        const rowHtml = `
          <div class="row g-2 mb-2 merchant-order-row align-items-center">
              <div class="col-4">
                  <input type="text" name="merchants[]" class="form-control shipbox-input-white" placeholder="e.g: Amazon" required>
              </div>
              <div class="col-4">
                  <input type="text" name="order_numbers[]" class="form-control shipbox-input-white" placeholder="e.g: 001001002" required>
              </div>
              <div class="col-3">
                  <input type="text" name="tracking_numbers[]" class="form-control shipbox-input-white" placeholder="e.g: 940...890">
              </div>
              <div class="col-1">
                  <button type="button" class="btn btn-link remove-merchant-row p-0 text-danger" style="text-decoration: none;">
                      <span class="dashicons dashicons-trash"></span>
                  </button>
              </div>
          </div>`;

        $("#merchant-rows-wrapper").append(rowHtml);

        setTimeout(function () {
          $btn.data("processing", false);
        }, 300);

        return false;
      });

    // ============================================
    // 3. Repeater Logic - Remove Merchant Row
    // ============================================
    $(document)
      .off("click.shipbox", ".remove-merchant-row")
      .on("click.shipbox", ".remove-merchant-row", function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        $(this).closest(".merchant-order-row").remove();
        return false;
      });

    // ============================================
    // 4. Image Preview Logic
    // ============================================
    $("#screenshot_input")
      .off("change.shipbox")
      .on("change.shipbox", function (e) {
        e.stopImmediatePropagation();
        const [file] = this.files;
        if (file) {
          $("#image-preview").attr("src", URL.createObjectURL(file)).show();
          $("#placeholder-text").hide();
        } else {
          // Allow clearing/resetting preview if user removes file
          $("#image-preview").hide();
          $("#placeholder-text").show();
        }
      });

    // ============================================
    // 5. AJAX Form Submission
    // ============================================
    $form.off("submit.shipbox").on("submit.shipbox", function (e) {
      e.preventDefault();
      e.stopImmediatePropagation();

      if (isSubmitting) return false;

      const $msg = $("#shipbox-form-message");
      const $btn = $("#shipbox-submit-btn");

      // REMOVED: Screenshot validation. The form will now submit even if empty.

      isSubmitting = true;

      const formData = new FormData(this);
      formData.append("action", "shipbox_submit_shipment");
      formData.append(
        "security",
        $('input[name="shipbox_shipment_nonce"]').val(),
      );

      $btn.prop("disabled", true).text("Processing...");

      $.ajax({
        url: shipbox_params.ajax_url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            window.location.href =
              shipbox_params.thank_you_url +
              "?order_id=" +
              response.data.order_id;
            $msg.html(
              '<div class="alert alert-success">' +
                response.data.message +
                "</div>",
            );
            $form[0].reset();
            $("#image-preview").hide();
            $("#placeholder-text").show();
            $whDisplay.hide();
            $(".merchant-order-row:not(:first)").remove();
          } else {
            $msg.html(
              '<div class="alert alert-danger">' +
                response.data.message +
                "</div>",
            );
          }
        },
        error: function (xhr, status, error) {
          $msg.html(
            '<div class="alert alert-danger">Server error. Please try again.</div>',
          );
        },
        complete: function () {
          isSubmitting = false;
          $btn.prop("disabled", false).text("SUBMIT");
        },
      });

      return false;
    });
  });
})(jQuery);

/**
 * ShipBox Multi-Package Calculator JavaScript
 * Defensive Event Handling - Prevents Multiple Initializations
 */

/*
 * ShipBox Public JS
 * File: public/js/shipbox-public.js
 */

(function ($) {
  "use strict";

  if (window.shipboxCalculatorInitialized) return;
  window.shipboxCalculatorInitialized = true;

  $(document).ready(function () {
    let packageCount = 1;
    let isCalculating = false;

    // ─── 1. Unit Toggle ───────────────────────────────────────────────────────
    $('input[name="unit_system"]').on("change", function () {
      const isMetric = $(this).val() === "metric";
      $(".weight-unit").text(isMetric ? "kg" : "lbs");
      $(".dim-unit").text(isMetric ? "cm" : "inch");
    });

    // ─── 2. Add Package ───────────────────────────────────────────────────────
    $(document).on("click", "#addPackageBtn", function (e) {
      e.preventDefault();
      packageCount++;

      const isMetric =
        $('input[name="unit_system"]:checked').val() === "metric";
      const wUnit = isMetric ? "kg" : "lbs";
      const dUnit = isMetric ? "cm" : "inch";
      const idx = packageCount - 1;

      const packageHTML = `
        <div class="package-item" data-package-id="${idx}">
          <div class="package-header">
            <span class="package-label">Package ${packageCount}</span>
            <button type="button" class="btn-remove-package">✕ Remove</button>
          </div>
          <div class="package-body">
            <div class="package-field-group">
              <span>Weight (${wUnit}):</span>
              <input type="number" step="0.01" name="packages[${idx}][weight]"
                     class="pkg-input package-weight weight-input" placeholder="0.00">
            </div>
            <div class="package-field-group dims-group">
              <span>Dimensions (${dUnit}):</span>
              <input type="number" step="0.1" name="packages[${idx}][length]"
                     class="pkg-input package-length dim-input" placeholder="L">
              <span class="dim-sep">×</span>
              <input type="number" step="0.1" name="packages[${idx}][width]"
                     class="pkg-input package-width dim-input" placeholder="W">
              <span class="dim-sep">×</span>
              <input type="number" step="0.1" name="packages[${idx}][height]"
                     class="pkg-input package-height dim-input" placeholder="H">
            </div>
          </div>
        </div>`;

      $("#packagesContainer").append(packageHTML);
    });

    // ─── 3. Remove Package ────────────────────────────────────────────────────
    $(document).on("click", ".btn-remove-package", function () {
      $(this).closest(".package-item").remove();
      updatePackageLabels();
    });

    function updatePackageLabels() {
      $(".package-item").each(function (i) {
        $(this)
          .find(".package-label")
          .text("Package " + (i + 1));
      });
    }

    // ─── 4. Form Submission ───────────────────────────────────────────────────
    $("#shipboxCalculatorForm").on("submit", function (e) {
      e.preventDefault();
      if (isCalculating) return;

      // Client-side validation
      let isValid = true;
      $(".package-item").each(function (index) {
        const weight = parseFloat($(this).find(".package-weight").val());
        const l = parseFloat($(this).find(".package-length").val());
        const w = parseFloat($(this).find(".package-width").val());
        const h = parseFloat($(this).find(".package-height").val());

        const hasWeight = weight > 0;
        const hasDims = l > 0 && w > 0 && h > 0;

        if (!hasWeight && !hasDims) {
          alert(
            `Package ${index + 1}: Please enter at least a Weight OR all three Dimensions.`,
          );
          $(this).css({ border: "2px solid #cd0613", background: "#fff5f5" });
          isValid = false;
          return false;
        } else {
          $(this).css({ border: "", background: "" });
        }
      });

      if (!isValid) return;

      const $btn = $(this).find(".get-estimate-btn");
      $btn.prop("disabled", true).text("CALCULATING...");
      isCalculating = true;

      $.ajax({
        url: shipboxAjax.ajaxurl,
        type: "POST",
        data:
          $(this).serialize() +
          "&action=shipbox_calculate&nonce=" +
          shipboxAjax.nonce,
        success: function (response) {
          if (response.success) {
            displayResults(response.data);

            // 1. Hide form and show results
            $("#shipboxFormSection").slideUp(300);
            $("#resultsSection").slideDown(300, function () {
              // 2. SCROLL TO TOP OF RESULTS
              // We target the results section so the user sees the "SHIPPING COST" title immediately
              $("html, body").animate(
                {
                  scrollTop: $("#resultsSection").offset().top - 50, // -50 adds a little padding from the top
                },
                500,
              ); // 500ms duration
            });
          } else {
            alert(
              response.data && response.data.message
                ? response.data.message
                : "Calculation failed. Please try again.",
            );
          }
        },
        error: function () {
          alert("A network error occurred. Please try again.");
        },
        complete: function () {
          isCalculating = false;
          $btn.prop("disabled", false).text("GET ESTIMATE");
        },
      });
    });

    // ─── 5. Display Results ───────────────────────────────────────────────────
    function displayResults(data) {
      const service = data.services[0];
      const details = data.details || {};
      const isMetric =
        $('input[name="unit_system"]:checked').val() === "metric";
      const wUnit = isMetric ? "kg" : "lbs";
      const dUnit = isMetric ? "cm" : "inches";

      let displayWeight = parseFloat(
        details.total_display_weight || details.total_chargeable_weight,
      ).toFixed(2);

      const formattedPrice = parseFloat(service.total_usd).toFixed(2);

      // Dimensions display
      const pkgRows = $(".package-item");
      let dimDisplay = "N/A";

      if (pkgRows.length === 1) {
        const l = pkgRows.find(".package-length").val() || "0";
        const w = pkgRows.find(".package-width").val() || "0";
        const h = pkgRows.find(".package-height").val() || "0";
        if (parseFloat(l) > 0 && parseFloat(w) > 0 && parseFloat(h) > 0) {
          dimDisplay = `${l} x ${w} x ${h} ${dUnit}`;
        } else {
          dimDisplay = "—";
        }
      } else {
        dimDisplay = `${pkgRows.length} Packages`;
      }

      const html = `
        <div class="shipping-result-wrapper">

          <!-- Top bar: title + modify button -->
          <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
              <h2 style="font-size:28px; font-weight:900; margin:0;">SHIPPING COST</h2>
              <div style="font-size:15px; color:#666;">Dashboard &bull; Cost Calculator</div>
            </div>
            <button id="modifySelectionBtn" class="modify-btn">MODIFY SELECTIONS</button>
          </div>

          <!-- Summary row (5 columns: stacked on mobile, horizontal on desktop) -->
          <div class="row mb-3 pb-4 g-0" style="border-bottom:2px solid #eee;">
            <div class="col-12 col-md result-summary-item">
              <div class="result-header-label">SHIPMENT FROM</div>
              <div class="result-value">${escapeHtml(details.country || "—")}</div>
            </div>
            <div class="col-12 col-md result-summary-item">
              <div class="result-header-label">DESTINATION</div>
              <div class="result-value">${escapeHtml(details.city)}, Pakistan</div>
            </div>
            <div class="col-12 col-md result-summary-item">
              <div class="result-header-label">WEIGHT</div>
              <div class="result-value">${displayWeight} ${wUnit}</div>
            </div>
            <div class="col-12 col-md result-summary-item">
              <div class="result-header-label">DIMENSIONS</div>
              <div class="result-value">${dimDisplay}</div>
            </div>
            <div class="col-12 col-md result-summary-item">
              <div class="result-header-label">TOTAL PRICE</div>
              <div class="result-value">USD ${formattedPrice}</div>
            </div>
          </div>

          <!-- Big price -->
          <div class="text-center d-flex align-items-center justify-content-center mb-3">
            <span class="price-main">USD ${formattedPrice}</span>
            <span class="ms-3 days-badge">15 DAYS</span>
          </div>

          <!-- Term rows -->
          <div style="max-width:800px; margin:0 auto;">
            ${termRow(
              "Estimated delivery time",
              "15 days<br><span style=''>Delivery time may increase due to destination country&rsquo;s customs processing or remote area delivery terms, please see UPS/DHL shipping service terms.</span>",
            )}
            ${termRow("Maximum Weight", "68 kg (150 lb) for single item")}
            ${termRow("Total Weight", "No upper limit for multiple packages")}
            ${termRow(
              "Dimensional weight",
              "Applies<br>L &times; W &times; H / 139 (if inches) &ndash; L &times; W &times; H / 5000 (if cm)",
            )}
            ${termRow("Tracking", "Included")}
            ${termRow("Insurance", "Free insurance coverage up to $100", true)}
          </div>

        </div>`;

      $("#resultsContainer").html(html);
    }

    function termRow(label, value, isLast = false) {
      return `
        <div class="term-row" ${isLast ? 'style="border-bottom:none;"' : ""}>
          <div class="term-label">${label}</div>
          <div class="term-value">${value}</div>
        </div>`;
    }

    function escapeHtml(str) {
      if (!str) return "";
      return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
    }

    // ─── 6. Modify Button ─────────────────────────────────────────────────────
    $(document).on("click", "#modifySelectionBtn", function () {
      $("#resultsSection").fadeOut(250, function () {
        $("#shipboxFormSection").fadeIn(300);
      });
    });
  });
})(jQuery);
