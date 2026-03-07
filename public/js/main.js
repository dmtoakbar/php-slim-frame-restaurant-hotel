// Mobile-first JavaScript
$(document).ready(function () {
    // Initialize all components
    initMobileMenu();
    initDatePickers();
    initRoomAvailability();
    initBookingForms();
    initGallery();
    initSmoothScroll();
    initFormValidation();
    initAlerts();
});

// Mobile Menu Handling
function initMobileMenu() {
    const navbarToggler = $('.navbar-toggler');
    const navbarCollapse = $('.navbar-collapse');

    navbarToggler.click(function () {
        $('body').toggleClass('menu-open');
    });

    // Close menu when clicking outside
    $(document).click(function (e) {
        if (!navbarCollapse.is(e.target) && navbarCollapse.has(e.target).length === 0 && !navbarToggler.is(e.target)) {
            if (navbarCollapse.hasClass('show')) {
                navbarToggler.click();
            }
        }
    });

    // Close menu on nav item click (mobile)
    $('.nav-link').click(function () {
        if ($(window).width() < 992) {
            if (navbarCollapse.hasClass('show')) {
                navbarToggler.click();
            }
        }
    });
}

// Date Pickers with Mobile Support
function initDatePickers() {
    if ($('#check_in').length) {
        const today = new Date().toISOString().split('T')[0];
        $('#check_in').attr('min', today);

        // For mobile devices, use native date picker
        if ('ontouchstart' in window) {
            $('#check_in, #check_out').attr('type', 'date');
        }

        $('#check_in').change(function () {
            const checkIn = $(this).val();
            $('#check_out').attr('min', checkIn);

            if ($('#check_out').val() && $('#check_out').val() < checkIn) {
                $('#check_out').val('');
            }
        });
    }
}

// Open Booking Modal
function openBookingModal(roomId, roomName, roomPrice) {
    const checkIn = $('#check_in').val();
    const checkOut = $('#check_out').val();
    const adults = $('#adults').val();
    const children = $('#children').val();

    const nights = calculateNights(checkIn, checkOut);
    const totalPrice = roomPrice * nights;

    const modalHtml = `
        <div class="modal fade" id="bookingModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Book ${capitalizeFirst(roomName)} Room</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="roomBookingForm" class="needs-validation" novalidate>
                            <input type="hidden" name="room_id" value="${roomId}">
                            <input type="hidden" name="check_in" value="${checkIn}">
                            <input type="hidden" name="check_out" value="${checkOut}">
                            <input type="hidden" name="adults" value="${adults}">
                            <input type="hidden" name="children" value="${children}">
                            
                            <div class="form-group mb-3">
                                <label for="name">Full Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="invalid-feedback">Please enter your name</div>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="email">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback">Please enter a valid email</div>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="phone">Phone Number *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                                <div class="invalid-feedback">Please enter your phone number</div>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="special_requests">Special Requests</label>
                                <textarea class="form-control" id="special_requests" name="special_requests" rows="3"></textarea>
                            </div>
                            
                            <div class="booking-summary p-3 bg-light rounded mb-3">
                                <h6>Booking Summary</h6>
                                <p class="mb-1">Nights: ${nights}</p>
                                <p class="mb-1">Guests: ${adults} Adults, ${children} Children</p>
                                <p class="mb-0 fw-bold">Total: ₹${totalPrice.toFixed(2)}</p>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="roomBookingForm" class="btn btn-primary">Confirm Booking</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('body').append(modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('bookingModal'));
    modal.show();

    // Handle form submission
    $('#roomBookingForm').off('submit').on('submit', function (e) {
        e.preventDefault();
        submitBookingForm($(this));
    });

    // Remove modal from DOM when hidden
    $('#bookingModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
}

// Submit Booking Form
function submitBookingForm(form) {
    if (!form[0].checkValidity()) {
        form.addClass('was-validated');
        return;
    }

    const submitBtn = $('button[form="roomBookingForm"]');
    const originalText = submitBtn.text();

    submitBtn.html('<span class="spinner-border spinner-border-sm"></span> Processing...')
        .prop('disabled', true);


    $.ajax({
        url: '/reservation/room',
        method: 'POST',
        data: form.serialize(),
        timeout: 30000,
        success: function (response) {
            if (response.success) {
                $('#bookingModal').modal('hide');
                showAlert('Booking created successfully! Booking ID: ' + response.booking_id, 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showAlert('Error: ' + response.message, 'error');
            }
        },
        error: function () {
            showAlert('An error occurred. Please try again.', 'error');
        },
        complete: function () {
            submitBtn.text(originalText).prop('disabled', false);
        }
    });
}

// Restaurant Reservation Form
function initBookingForms() {
    $('#restaurantReservationForm').submit(function (e) {
        e.preventDefault();

        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.text();

        submitBtn.html('<span class="spinner-border spinner-border-sm" role="status"></span> Processing...').prop('disabled', true);

        $.ajax({
            url: '/reservation/restaurant',
            method: 'POST',
            data: form.serialize(),
            success: function (response) {
                if (response.success) {
                    showAlert('Reservation created successfully!', 'success');
                    form[0].reset();
                } else {
                    showAlert('Error: ' + response.message, 'error');
                }
            },
            error: function () {
                showAlert('An error occurred. Please try again.', 'error');
            },
            complete: function () {
                submitBtn.text(originalText).prop('disabled', false);
            }
        });
    });
}

// Gallery Lightbox
function initGallery() {
    $('.gallery-item').click(function () {
        const imgSrc = $(this).find('img').attr('src');
        const title = $(this).find('.gallery-overlay h5').text();
        const description = $(this).find('.gallery-overlay p').text();

        const modalHtml = `
            <div class="modal fade" id="galleryModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-0">
                            <img src="${imgSrc}" class="img-fluid w-100" alt="${title}">
                            <div class="p-3">
                                <p class="mb-0">${description}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('galleryModal'));
        modal.show();

        $('#galleryModal').on('hidden.bs.modal', function () {
            $(this).remove();
        });
    });
}

// Smooth Scroll
function initSmoothScroll() {
    $('a[href^="#"]').click(function (e) {
        e.preventDefault();
        const target = $(this.hash);
        if (target.length) {
            const offset = $(window).width() < 768 ? 56 : 70;
            $('html, body').animate({
                scrollTop: target.offset().top - offset
            }, 800);
        }
    });
}

// Form Validation
function initFormValidation() {
    // Phone number formatting
    $('input[type="tel"]').on('input', function () {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length > 10) value = value.slice(0, 10);
        if (value.length > 6) value = value.slice(0, 6) + '-' + value.slice(6);
        if (value.length > 3) value = '(' + value.slice(0, 3) + ') ' + value.slice(3);
        $(this).val(value);
    });

    // Real-time validation
    $('.needs-validation input, .needs-validation textarea').on('blur', function () {
        validateField($(this));
    });
}

function validateField(field) {
    const value = field.val();
    const type = field.attr('type');
    let isValid = true;

    if (field.prop('required') && !value) {
        isValid = false;
    } else if (type === 'email' && value && !validateEmail(value)) {
        isValid = false;
    } else if (type === 'tel' && value && !validatePhone(value)) {
        isValid = false;
    }

    if (!isValid) {
        field.addClass('is-invalid');
    } else {
        field.removeClass('is-invalid').addClass('is-valid');
    }

    return isValid;
}

// Alert System
function initAlerts() {
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        $('.alert').fadeOut('slow', function () {
            $(this).remove();
        });
    }, 5000);
}

function showAlert(message, type = 'info') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999; max-width: 90%; width: 400px;" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    $('body').append(alertHtml);

    setTimeout(() => {
        $('.alert').fadeOut('slow', function () {
            $(this).remove();
        });
    }, 5000);
}

// Helper Functions
function calculateNights(checkIn, checkOut) {
    const start = new Date(checkIn);
    const end = new Date(checkOut);
    const diffTime = Math.abs(end - start);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
}

function capitalizeFirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function truncateText(text, length) {
    if (!text) return '';
    if (text.length <= length) return text;
    return text.substr(0, length) + '...';
}

function getAmenitiesHtml(amenities) {
    if (!amenities) return '';
    const items = amenities.split(',').slice(0, 3);
    return items.map(item => `<span><i class="fas fa-check-circle text-success"></i> ${item.trim()}</span>`).join('');
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^[\d\s\+\-\(\)]{10,}$/;
    return re.test(phone);
}

// Admin Functions
function updateBookingStatus(bookingId, status) {
    if (confirm('Are you sure you want to update this booking status?')) {
        $.ajax({
            url: '/admin/bookings/' + bookingId + '/status',
            method: 'PUT',
            data: { status: status },
            success: function (response) {
                if (response.success) {
                    showAlert('Booking status updated successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                }
            }
        });
    }
}

function deleteItem(url, message) {
    if (confirm(message || 'Are you sure you want to delete this item?')) {
        window.location.href = url;
    }
}

// Handle Window Resize
$(window).resize(function () {
    if ($(window).width() >= 992) {
        $('.navbar-collapse').removeClass('show');
        $('body').removeClass('menu-open');
    }
});

// Handle Orientation Change
$(window).on('orientationchange', function () {
    setTimeout(() => {
        $(window).trigger('resize');
    }, 200);
});