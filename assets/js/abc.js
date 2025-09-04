//Steps
/*
document.addEventListener("DOMContentLoaded", function () {
  let currentStep = page;
  const steps = document.querySelectorAll(".gf-step");
  const totalSteps = steps.length;

  const prevBtn = document.getElementById("gf-prev");
  const nextBtn = document.getElementById("gf-next");
  const submitBtn = document.getElementById("gf-submit");
  const form = document.querySelector(".custom-gf-form");

  function showStep(step) {
    steps.forEach((el, i) => (el.style.display = i + 1 === step ? "" : "none"));
    prevBtn.style.display = step > 1 ? "" : "none";
    nextBtn.style.display = step < totalSteps ? "" : "none";
    submitBtn.style.display = step === totalSteps ? "" : "none";
  }

  prevBtn.addEventListener("click", function () {
    if (currentStep > 1) {
      currentStep--;
      showStep(currentStep);
    }
  });

  nextBtn.addEventListener("click", function () {
    // Optional: validate required inputs on current step
    const inputs = steps[currentStep - 1].querySelectorAll("[required]");
    for (let input of inputs) {
      if (!input.value) {
        input.focus();
        return;
      }
    }
    if (currentStep < totalSteps) {
      currentStep++;
      showStep(currentStep);
    }
  });

  showStep(currentStep);
});
*/

document.addEventListener("DOMContentLoaded", function () {
  let currentStep = page; // `page` = starting step (should be defined globally)
  const steps = document.querySelectorAll(".gf-step");
  const totalSteps = steps.length;

  const prevBtn = document.getElementById("gf-prev");
  const nextBtn = document.getElementById("gf-next");
  const submitBtn = document.getElementById("gf-submit");

  /**
   * Show a specific step
   */
  function showStep(step) {
    steps.forEach((el, i) => (el.style.display = i + 1 === step ? "" : "none"));
    prevBtn.style.display = step > 1 ? "" : "none";
    nextBtn.style.display = step < totalSteps ? "" : "none";
    submitBtn.style.display = step === totalSteps ? "" : "none";
  }

  /**
   * Get parent container for grouped fields (like Name, Address, Checkboxes)
   * Gravity Forms usually wraps them in `.gfield`
   */
  function getFieldContainer(field) {
    return field.closest(".gfield") || field.parentElement;
  }

  /**
   * Add error message under the field's parent container
   */
  function showError(field, message) {
    const container = getFieldContainer(field);

    // Remove old message if it exists
    let oldMsg = container.querySelector(".gf-error-msg");
    if (oldMsg) oldMsg.remove();

    // Create new message
    const msg = document.createElement("div");
    msg.className = "gf-error-msg";
    msg.innerText = message;

    container.appendChild(msg);

    // Highlight the field with red border
    field.classList.add("gf-error");
  }

  /**
   * Clear error message for a field
   */
  function clearError(field) {
    const container = getFieldContainer(field);
    let oldMsg = container.querySelector(".gf-error-msg");
    if (oldMsg) oldMsg.remove();
    field.classList.remove("gf-error");
  }

  /**
   * Validate fields in the current step
   */
  function validateStepp(step) {
    let valid = true;
    const fields = steps[step - 1].querySelectorAll(
      "[required], [aria-required='true']"
    );

    fields.forEach((field) => {
      let isValid = true;

      // Reset errors before checking
      clearError(field);

      // --- Handle by field type ---
      if (field.type === "radio" || field.type === "checkbox") {
        // For radios/checkboxes, check at least one in the group
        const group = steps[step - 1].querySelectorAll(
          `[name="${field.name}"]`
        );
        isValid = [...group].some((input) => input.checked);
      } else if (field.type === "email") {
        // Basic email regex check
        isValid =
          field.value.trim() !== "" &&
          /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value);
      } else if (field.type === "number") {
        // Number field validation
        let val = field.value.trim();
        let num = Number(val);
        let min = field.min ? Number(field.min) : null;
        let max = field.max ? Number(field.max) : null;

        isValid = val !== "" && !isNaN(num);
        if (isValid && min !== null) isValid = num >= min;
        if (isValid && max !== null) isValid = num <= max;
      } else if (field.type === "tel") {
        // Phone validation (very loose, adjust per region)
        isValid =
          field.value.trim() !== "" && /^[0-9\-\+\s\(\)]+$/.test(field.value);
      } else if (field.tagName.toLowerCase() === "select") {
        // Select must have a value
        isValid = field.value.trim() !== "";
      } else {
        // Default text/textarea validation
        isValid = field.value.trim() !== "";
      }

      // --- If invalid, mark error ---
      if (!isValid) {
        valid = false;
        showError(field, "This field is required or invalid.");
      }
    });

    return valid;
  }
  function validateStep(step) {
    let valid = true;
    const stepFields = steps[step - 1].querySelectorAll(".gf-field");

    stepFields.forEach((container) => {
      const type = container.classList.contains("gf-type-radio")
        ? "radio"
        : container.classList.contains("gf-type-consent")
        ? "consent"
        : "default";

      let groupValid = true;

      if (type === "radio") {
        // --- Radio group validation ---
        const radios = container.querySelectorAll('input[type="radio"]');
        groupValid = [...radios].some((r) => r.checked);

        if (!groupValid) {
          radios.forEach((r) => r.classList.add("gf-error"));
        } else {
          radios.forEach((r) => r.classList.remove("gf-error"));
        }
      } else if (type === "consent") {
        // --- Consent checkbox validation ---
        const checkbox = container.querySelector('input[type="checkbox"]');
        groupValid = checkbox.checked;

        if (!groupValid) {
          checkbox.classList.add("gf-error");
        } else {
          checkbox.classList.remove("gf-error");
        }
      } else {
        // --- Default (text, select, textarea, name group, etc.) ---
        const requiredInputs = container.querySelectorAll(
          "[required], [aria-required='true']"
        );
        if (!requiredInputs.length) return; // skip non-required

        groupValid = true;
        requiredInputs.forEach((field) => {
          clearError(field); // reset

          let isValid = true;
          if (field.type === "email") {
            isValid =
              field.value.trim() !== "" &&
              /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value);
          } else if (field.type === "number") {
            let val = field.value.trim();
            let num = Number(val);
            let min = field.min ? Number(field.min) : null;
            let max = field.max ? Number(field.max) : null;
            isValid = val !== "" && !isNaN(num);
            if (isValid && min !== null) isValid = num >= min;
            if (isValid && max !== null) isValid = num <= max;
          } else if (field.type === "tel") {
            isValid =
              field.value.trim() !== "" &&
              /^[0-9\-\+\s\(\)]+$/.test(field.value);
          } else if (field.tagName.toLowerCase() === "select") {
            isValid = field.value.trim() !== "";
          } else {
            isValid = field.value.trim() !== "";
          }

          if (!isValid) {
            groupValid = false;
            field.classList.add("gf-error");
          } else {
            field.classList.remove("gf-error");
          }
        });
      }

      // --- Handle error message display for this group ---
      let oldMsg = container.querySelector(".gf-error-msg");
      if (oldMsg) oldMsg.remove();

      if (!groupValid) {
        valid = false;
        const msg = document.createElement("div");
        msg.className = "gf-error-msg";
        msg.innerText = "Please complete this required field.";
        container.appendChild(msg);
      }
    });

    return valid;
  }

  /**
   * Button events
   */
  prevBtn.addEventListener("click", function () {
    if (currentStep > 1) {
      currentStep--;
      showStep(currentStep);
    }
  });

  nextBtn.addEventListener("click", function () {
    if (validateStep(currentStep)) {
      if (currentStep < totalSteps) {
        currentStep++;
        showStep(currentStep);
      }
    }
  });

  showStep(currentStep);
});

//Registration ID
jQuery(document).ready(function () {
  function formatDate() {
    let d = new Date(),
      str =
        Math.random().toString(20).substr(2, 5) +
        d.getTime().toString(36).substr(0, 9);
    return str.toUpperCase();
  }
  let original_val = jQuery(".gf_readonly input").data("value");
  if (original_val) {
    jQuery(".gf_readonly input").val(original_val);
  } else {
    //Apply only to inputs with class .gf_readonly
    //jQuery(".gf_readonly input").attr("readonly", "readonly");
    jQuery(".gf_readonly input").val(formatDate());
  }
});

document.addEventListener("DOMContentLoaded", function () {
  // Get DOM elements by container classes
  const participationContainer = document.querySelector(".participation");
  const currencyContainer = document.querySelector(".currency");

  // Find inputs within the containers
  const participationRadios = participationContainer.querySelectorAll(
    'input[type="radio"]'
  );
  const currencyRadios = currencyContainer.querySelectorAll(
    'input[type="radio"]'
  );

  const subtotalDisplay = document.getElementById("subtotal_display");
  const discountDisplay = document.getElementById("discount_display");
  const totalDisplay = document.getElementById("total_display");

  function updatePricing() {
    // Get selected values from containers
    const selectedParticipation = participationContainer.querySelector(
      'input[type="radio"]:checked'
    )?.value;
    const selectedCurrency = currencyContainer.querySelector(
      'input[type="radio"]:checked'
    )?.value;

    if (!selectedParticipation || !selectedCurrency) return;

    const priceData = window.pricingData[selectedParticipation];
    if (!priceData) {
      console.warn("No pricing data found for:", selectedParticipation);
      return;
    }

    // Use current prices from backend
    const currentPrice =
      selectedCurrency === "NGN"
        ? priceData.current_naira
        : priceData.current_dollar;

    const regularPrice =
      selectedCurrency === "NGN"
        ? priceData.regular_naira
        : priceData.regular_dollar;

    let discountValue = document.querySelector("#discount").value;

    const hasDiscount = discountValue > 0;

    // Format prices
    const formatPrice = (price, currency) => {
      if (currency === "NGN") {
        return `₦${price.toLocaleString("en-NG", {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        })}`;
      } else {
        return `$${price.toLocaleString("en-US", {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        })}`;
      }
    };

    // Update displays
    subtotalDisplay.textContent = formatPrice(currentPrice, selectedCurrency);
    discountDisplay.textContent = formatPrice(discountValue, selectedCurrency);
    totalDisplay.textContent = formatPrice(
      currentPrice - discountValue,
      selectedCurrency
    );

    // Visual styling
    if (hasDiscount) {
      discountDisplay.style.color = "#dc3545";
      discountDisplay.style.fontWeight = "bold";
      totalDisplay.innerHTML = `${formatPrice(
        currentPrice - discountValue,
        selectedCurrency
      )}`;
    } else {
      discountDisplay.style.color = "";
      discountDisplay.style.fontWeight = "";
      totalDisplay.innerHTML = formatPrice(currentPrice, selectedCurrency);
    }
  }

  // Event listeners
  participationRadios.forEach((radio) => {
    radio.addEventListener("change", updatePricing);
  });

  currencyRadios.forEach((radio) => {
    radio.addEventListener("change", updatePricing);
  });

  // Auto-select first participation option if none selected
  function initPricing() {
    const hasSelectedParticipation = participationContainer.querySelector(
      'input[type="radio"]:checked'
    );
    if (!hasSelectedParticipation && participationRadios.length > 0) {
      participationRadios[0].checked = true;
    }
    updatePricing();
  }

  // Initialize pricing
  initPricing();

  /*
  // Optional: Save preferences to localStorage
  function savePreferences() {
    const selectedCurrency = currencyContainer.querySelector(
      'input[type="radio"]:checked'
    )?.value;
    const selectedParticipation = participationContainer.querySelector(
      'input[type="radio"]:checked'
    )?.value;

    if (selectedCurrency)
      localStorage.setItem("selectedCurrency", selectedCurrency);
    if (selectedParticipation)
      localStorage.setItem("selectedParticipation", selectedParticipation);
  }


  function loadPreferences() {
    const savedCurrency = localStorage.getItem("selectedCurrency");
    const savedParticipation = localStorage.getItem("selectedParticipation");

    if (savedCurrency) {
      const currencyRadio = currencyContainer.querySelector(
        `input[type="radio"][value="${savedCurrency}"]`
      );
      if (currencyRadio) currencyRadio.checked = true;
    }

    if (savedParticipation) {
      const participationRadio = participationContainer.querySelector(
        `input[type="radio"][value="${savedParticipation}"]`
      );
      if (participationRadio) participationRadio.checked = true;
    }
  }


  // Load saved preferences
  loadPreferences();

  // Save preferences on change
  participationRadios.forEach((radio) => {
    radio.addEventListener("change", savePreferences);
  });
  currencyRadios.forEach((radio) => {
    radio.addEventListener("change", savePreferences);
  });*/

  //Coupon Handling
  // Select the container first
  const couponContainer = document.querySelector(".gf-coupon-input");
  if (!couponContainer) return;

  // Get the input inside that container
  const couponInput = couponContainer.querySelector("input");

  if (!couponInput) return;

  // Create the apply button
  const applyBtn = document.createElement("button");
  applyBtn.type = "button";
  applyBtn.textContent = "Apply Coupon";
  applyBtn.classList.add("apply-coupon-btn");

  // Append the button after the input
  couponInput.after(applyBtn);

  // Handle click
  applyBtn.addEventListener("click", async function () {
    const code = couponInput.value.trim();
    if (!code) {
      alert("Please enter a coupon code.");
      return;
    }

    let $option = participationContainer.querySelector(
      'input[type="radio"]:checked'
    );

    if (!$option) {
      alert("Please select a participation option.");
      return;
    }

    let $currency = currencyContainer.querySelector(
      'input[type="radio"]:checked'
    );
    if (!$currency) {
      alert("Please select a currency.");
      return;
    }

    const formData = new FormData();
    formData.append("action", "validate_coupon");
    formData.append("security", abcAjax.nonce);
    formData.append("participation_option", $option?.value);
    formData.append("currency", $currency?.value);
    formData.append("code", code);

    try {
      const response = await fetch(abcAjax.ajax_url, {
        method: "POST",
        body: formData, // WordPress understands this format
      });

      const data = await response.json();

      if (data.success) {
        const details = data.data; // shortcut to your payload

        alert(`Coupon applied: ${details.discount_value}% off`);

        // Optionally store discount in hidden field
        let discount = document.querySelector("#discount");
        if (discount) {
          discount.value = details.discount_amount; // or discount_value if you only need percent
          updatePricing(); // make sure pricing reacts immediately
        }
      } else {
        console.log(data, data?.data, data?.data?.message);
        alert(data.data?.message || "Invalid coupon");
      }
    } catch (error) {
      console.error("Error:", error);
      alert("An unexpected error occurred.");
    }
  });
});
