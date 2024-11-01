const navigateToFormStep = (stepNumber) => {
	/**
	 * Hide all form steps.
	 */
	document.querySelectorAll(".form-step").forEach((formStepElement) => {
		formStepElement.classList.add("d-none");
	});
	/**
	 * Mark all form steps as unfinished.
	 */
	document.querySelectorAll(".form-stepper-list").forEach((formStepHeader) => {
		formStepHeader.classList.add("form-stepper-unfinished");
		formStepHeader.classList.remove("form-stepper-active", "form-stepper-completed");
	});
	/**
	 * Show the current form step (as passed to the function).
	 */
	document.querySelector("#step-" + stepNumber).classList.remove("d-none");
	/**
	 * Select the form step circle (progress bar).
	 */
	const formStepCircle = document.querySelector('li[step="' + stepNumber + '"]');
	/**
	 * Mark the current form step as active.
	 */
	formStepCircle.classList.remove("form-stepper-unfinished", "form-stepper-completed");
	formStepCircle.classList.add("form-stepper-active");
	/**
	 * Loop through each form step circles.
	 * This loop will continue up to the current step number.
	 * Example: If the current step is 3,
	 * then the loop will perform operations for step 1 and 2.
	 */
	for (let index = 0; index < stepNumber; index++) {
		/**
		 * Select the form step circle (progress bar).
		 */
		const formStepCircle = document.querySelector('li[step="' + index + '"]');
		/**
		 * Check if the element exist. If yes, then proceed.
		 */
		if (formStepCircle) {
			/**
			 * Mark the form step as completed.
			 */
			formStepCircle.classList.remove("form-stepper-unfinished", "form-stepper-active");
			formStepCircle.classList.add("form-stepper-completed");
		}
	}
};
/**
 * Select all form navigation buttons, and loop through them.
 */
document.querySelectorAll(".btn-navigate-form-step").forEach((formNavigationBtn) => {
	/**
	 * Add a click event listener to the button.
	 */
	formNavigationBtn.addEventListener("click", () => {
		/**
		 * Get the value of the step.
		 */

		const stepNumber = parseInt(formNavigationBtn.getAttribute("step_number"));
		/**
		 * Call the function to navigate to the target form step.
		 */
		navigateToFormStep(stepNumber);
	});
});


jQuery(document).ready(function () {
	jQuery('select.allowstates, select.allowcities, select.allowscountry').select2({
		width: '50%',
		containerCssClass: 'torod-select2'
	});
	jQuery(".allowcities").each(function () {
		var random_number = jQuery(this).attr("data-random_id");
		if (jQuery("#torod_enabled_cities_" + random_number).length) {
			jQuery("#torod_enabled_cities_" + random_number).val(jQuery(this).val());
		}
	});
	jQuery('.select-all').on('click', function () {
		var $select = jQuery(this).siblings('select[multiple]');
		$select.val($select.find('option').map(function () { return this.value; })).trigger('change');
	});

	jQuery('.clear').on('click', function () {
		var $select = jQuery(this).siblings('select[multiple]');
		$select.val(null).trigger('change');
	});

	jQuery('.baglantikes').on('click', function () {
		jQuery.ajax({
			type: "post",
			dataType: "json",
			url: torod.ajax_url,
			data: {
				action: 'torod_disconnect',
				nonce: torod.nonce,
			},
			success: function (response) {
				if (response.type == "success") {
					alert("Disconnected");
					location.reload();
				} else {
					alert("Something Wrong");
					location.reload();
				}
			}
		});
	});

	jQuery('.statusregister').on('click', function () {
		var data = jQuery('#select_status_order').val();
		var statusradio = jQuery("input[name='statusradio']:checked").val();
		var paymentgt = jQuery('#select_payment_method').val();
		jQuery.ajax({
			type: "post",
			dataType: "json",
			url: torod.ajax_url,
			data: {
				action: 'torod_status_reg',
				nonce: torod.nonce,
				data: data,
				paymentgt: paymentgt,
				radiobtn: statusradio,
			},
			beforeSend: function () {
				jQuery('.lodinggif').show();
			},
			success: function (response) {
				jQuery('.lodinggif').hide();
				if (response.type == "success") {
					alert("Updated");
				} else {
					alert("Something Wrong");
					location.reload();
				}
			}
		});
	});
	/* Torod Order Mapping Status Admin side*/ 
	jQuery('.webhook_o_status_save').on('click', function (e) {
		e.preventDefault();
		var torodordermapping = {};
		jQuery('select[name="torod_order_mapping_status[]"]').each(function(){
			torodordermapping[jQuery(this).attr('data-wp-status')] = jQuery(this).val();
		});
		jQuery.ajax({
			type: "post",
			dataType: "json",
			url: torod.ajax_url,
			data: {
				action: 'torod_OrderMappingStatus',
				nonce: torod.nonce,
				data: torodordermapping
			},
			beforeSend: function () {
				jQuery('.lodinggif').show();
			},
			success: function (response) {
				jQuery('.lodinggif').hide();
				if (response.type == "success") {
					alert("Updated");
				} else {
					alert("Something Wrong");
				}
			}
		});
	});
	jQuery.ajax({
		type: "post",
		dataType: "json",
		url: torod.ajax_url,
		data: {
			action: 'get_torod_status_reg'
		},
		success: function (data) {
			var deneme = [];
			jQuery.each(data, function (index, value) {
				deneme.push({
					id: value[0],
					text: value[1],
					selected: value[2]
				});
			});

			jQuery(".select_status_order").select2({
				width: '500px',
				height: '75px',
				data: deneme
			});
		},
		error: function (xhr, status, error) {
			console.log(xhr.responseText);
		}
	});

	jQuery.ajax({
		type: "post",
		dataType: "json",
		url: torod.ajax_url,
		data: {
			action: 'getPaymentMethod',
		},
		success: getPaymentMethod,
	});

	function getPaymentMethod(data) {
		var deneme = [];
		jQuery.each(data, function (index, value) {
			deneme.push({ id: value[0], text: value[1], selected: value[2] });
		});
		jQuery(".select_payment_method").select2({
			width: '500',
			height: '75px',
			data: deneme,
		});
	};

	jQuery('#_billing_country').on('change', function () {
		updateCities('billing');
	});

	jQuery('#_shipping_country').on('change', function () {
		updateCities('shipping');
	});

	updateCities('billing');
	updateCities('shipping');

	/* Settings ajaxa for update DB */
	jQuery('.updatedbadmin').on('click', function (e) {
		e.preventDefault();
		jQuery.ajax({
			type: "post",
			dataType: "json",
			url: torod.ajax_url,
			data: {
				action: 'updateDbFromsetting',
				nonce: torod.nonce,
			},
			beforeSend: function () {
				jQuery('.lodinggif').show();
			},
			success: function (response) {
				jQuery('.lodinggif').hide();
				jQuery('.resultajax').html('successfully Sync');
			}
		});
	});
}).on("change", "#_billing_state", function () {
	updateCities('billing');
}).on("change", "#_shipping_state", function () {
	updateCities('shipping');
}).on("change", ".allowcities", function () {
	var random_number = jQuery(this).attr("data-random_id");
	if (jQuery("#torod_enabled_cities_" + random_number).length) {
		jQuery("#torod_enabled_cities_" + random_number).val(jQuery(this).val());
	}
});

function updateCities(type = 'billing') {
	var countryElement = type === 'shipping' ? '#_shipping_country' : '#_billing_country';
	var stateElement = type === 'shipping' ? '#_shipping_state' : '#_billing_state';
	var cityElement = type === 'shipping' ? '#_shipping_city' : '#_billing_city';
	var ulke = jQuery(countryElement).val();
	if (ulke == 'SA' || ulke == 'KW') {
		var defaultlang = torod.dlang;
		var sehir = jQuery(cityElement).val();
		var bolge = jQuery(stateElement).val();
		var cities_json = torod.cities.replace(/&quot;/g, '"');
		var cities = jQuery.parseJSON(cities_json);
		var liste = cities[ulke][bolge];
		if (typeof liste != "undefined") {
			if (liste.length > 0) {
				jQuery(`${cityElement}`).each(function () {
					let citySelect = jQuery(this);
					if (!citySelect.is("select")) {
						citySelect = jQuery("<select></select>")
							.attr("id", citySelect.attr("id"))
							.attr("name", citySelect.attr("name"))
							.addClass("wc-enhanced-select")
							.replaceAll(citySelect);
					}
					citySelect.empty();
					liste.forEach(function (city) {
						var isSelected = (sehir === city.ar || sehir === city.en);
						let option;
						if (defaultlang == 'ar') {
							option = jQuery("<option data-ar=" + city.ar + " data-en=" + city.en + "></option>").text(city.ar).val(city.ar);
						} else {
							option = jQuery("<option data-ar=" + city.ar + " data-en=" + city.en + "></option>").text(city.en).val(city.en);
						}
						if (option) {
							if (isSelected) {
								option.prop("selected", true);
							}
							citySelect.append(option);
						}
					});
					citySelect.select2({
						matcher: function (params, data) {
							if (jQuery.trim(params.term) === '') {
								return data;
							}
							if (!data || !data.element) {
								return null;
							}
							var term = params.term.toLowerCase();
							var dataAr = jQuery(data.element).attr('data-ar');
							var dataEn = jQuery(data.element).attr('data-en');
							if (data.text.toLowerCase().indexOf(term) > -1 ||
								(dataAr && dataAr.toLowerCase().indexOf(term) > -1) ||
								(dataEn && dataEn.toLowerCase().indexOf(term) > -1)) {
								return data;
							}
							return null;
						}
					}).trigger("change");
				});
			} else {
				jQuery(`${cityElement}`).each(function () {
					let citySelect = jQuery(this);
					if (citySelect.is("input")) {
						citySelect.prop('disabled', false);
						return;
					}
					var input_name = citySelect.attr('name');
					var input_id = citySelect.attr('id');
					citySelect.parent().find('.select2-container').remove();
					citySelect.replaceWith('<input type="text" class="short" name="' + input_name + '" id="' + input_id + '" value="" placeholder="">');
				});
			}
		} else {
			jQuery(`${cityElement}`).each(function () {
				let citySelect = jQuery(this);
				if (citySelect.is("input")) {
					citySelect.prop('disabled', false);
					return;
				}
				var input_name = citySelect.attr('name');
				var input_id = citySelect.attr('id');
				citySelect.parent().find('.select2-container').remove();
				citySelect.replaceWith('<input type="text" class="short" name="' + input_name + '" id="' + input_id + '" value="" placeholder="">');
			});
		}
	}
}