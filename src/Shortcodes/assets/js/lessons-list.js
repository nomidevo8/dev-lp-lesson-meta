jQuery(document).ready(function ($) {
    const steps = $('.accordion-item');
    const selections = {};

    // 🔒 Initially disable all steps except the first
    steps.each(function (i) {
        if (i > 0) {
            $(this)
                .addClass('disabled-step')
                .find('.accordion-button')
                .prop('disabled', true)
                .addClass('opacity-50');
        }
    });

    // Step click logic
    $(document).on('click', '.lesson-row .btn-primary', function (e) {
        e.preventDefault(); // prevent default link behavior if any
        const $btn = $(this);
        const $row = $btn.closest('.lesson-row');

        // ❌ If lesson is full, do nothing
        if ($row.hasClass('lesson-full') || parseInt($row.data('slots') || 0) <= 0) {
            toastr.warning('This lesson is full.', 'Unavailable', { timeOut: 3000 });
            return;
        }

        const stepIndex = $row.closest('.accordion-item').index();
        const dateStr = $row.data('date');
        const lessonDate = new Date(dateStr);
        const lessonId = $row.data('lesson-id');
        const lessonTitle = $row.data('title');
        const price = parseFloat($row.data('price') || 0);

        // Store selection
        selections[stepIndex] = {
            id: lessonId,
            title: lessonTitle,
            price: price,
            date: lessonDate
        };

        // Highlight selection
        $row.closest('tbody').find('.lesson-row').removeClass('table-success');
        $row.addClass('table-success');

        console.log(`Selected Step ${stepIndex + 1}:`, selections[stepIndex]);

        // ✅ Enable the next step when a selection is made
        const $nextStep = steps.eq(stepIndex + 1);
        if ($nextStep.length) {
            $nextStep
                .removeClass('disabled-step')
                .find('.accordion-button')
                .prop('disabled', false)
                .removeClass('opacity-50');

            const selectedDate = new Date(dateStr);
            const $nextRows = $nextStep.find('.lesson-row');
            let hasValidLessons = false;

            // Filter lessons in the next step
            $nextRows.each(function () {
                const rowDate = new Date($(this).data('date'));
                if (rowDate >= selectedDate) {
                    $(this).show();
                    hasValidLessons = true;
                } else {
                    $(this).hide();
                }
            });

            console.log(`Step ${stepIndex + 2} lessons filtered based on selection.`);

            // ❌ If no valid lessons found → disable this and all later steps
            if (!hasValidLessons) {
                console.log(`No valid lessons found for Step ${stepIndex + 2}. Disabling step.`);

                $nextStep
                    .addClass('disabled-step')
                    .find('.accordion-button')
                    .prop('disabled', true)
                    .addClass('opacity-50');

                // 🔒 Disable all steps after this one as well
                for (let i = stepIndex + 2; i < steps.length; i++) {
                    steps.eq(i)
                        .addClass('disabled-step')
                        .find('.accordion-button')
                        .prop('disabled', true)
                        .addClass('opacity-50');
                }

                toastr.warning('No lessons available for the selected date.', 'No Lessons', { timeOut: 4000 });
                return;
            }

            // ✅ Expand next accordion step
            const $collapse = $nextStep.find('.accordion-collapse');
            const bsCollapse = new bootstrap.Collapse($collapse, { toggle: true });

            // Smooth scroll to opened step
            $('html, body').animate(
                {
                    scrollTop: $nextStep.offset().top - 200
                },
                600
            );
        }

        // If all steps selected → show summary
        if (Object.keys(selections).length === steps.length) {
            showSummary(selections);
        }
    });



    // 🧱 Prevent clicking locked steps manually
    $(document).on('click', '.disabled-step .accordion-button', function (e) {
        e.preventDefault();
        e.stopPropagation();
        alert('Please complete the previous step first.');
    });

    // Display booking summary
    function showSummary(selections) {
        $('#lesson-summary').remove();

        let total = 0;
        let html = '<div id="lesson-summary" class="card mt-4 shadow-sm p-3 p-md-4" style="width:100%;max-width:100%;">';
        html += '<h4 class="mb-3">Booking Summary</h4>';
        html += '<ul class="list-group mb-3">';

        $.each(selections, function (index, lesson) {
            total += lesson.price;

            // Format date and time
            const options = {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            const formattedDate = lesson.date.toLocaleString('en-US', options);

            html += `<li class="list-group-item d-flex justify-content-between align-items-start flex-column flex-sm-row">
                        <div class="mb-2 mb-sm-0">
                            <strong>${lesson.title}</strong><br>
                            <small class="text-muted">${formattedDate}</small>
                        </div>
                        <span class="fw-bold">${lesson.price.toFixed(2)}</span>
                    </li>`;
        });

        html += `</ul>
                <div class="d-flex justify-content-between align-items-center flex-column flex-sm-row">
                    <h5 class="mb-3 mb-sm-0">Total: <strong>${total.toFixed(2)}</strong></h5>
                    <button id="goToCheckout" class="btn btn-success">Go to Checkout</button>
                </div>
                </div>`;

        $('#lpCoursesAccordion').after(html);

        console.log('Selected Lessons Summary:', selections);

        // Go to checkout button click
        $('#goToCheckout').on('click', function () {
            const ids = Object.values(selections).map(sel => sel.id);

            $.ajax({
                url: devLesson.ajax_url,
                method: 'POST',
                data: {
                    action: 'dev_lp_create_lesson_cart',
                    lesson_ids: ids,
                    nonce: devLesson.nonce
                },
                beforeSend: function () {
                    $('#goToCheckout').text('Processing...').prop('disabled', true);
                },
                success: function (response) {
                    if (response.success) {
                        window.location.href = response.data.checkout_url;
                    } else {
                        alert(response.data || 'Something went wrong.');
                        $('#goToCheckout').text('Go to Checkout').prop('disabled', false);
                    }
                },
                error: function () {
                    alert('Server error, please try again.');
                    $('#goToCheckout').text('Go to Checkout').prop('disabled', false);
                }
            });
        });
    }
});