// Document ready function
$(document).ready(function() {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Password visibility toggle
    $('.toggle-password').click(function() {
        const input = $(this).siblings('input');
        const icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Form validation
    $('form.needs-validation').on('submit', function(e) {
        if (this.checkValidity() === false) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });
    
    // Remember me functionality check
    if (localStorage.getItem('remember') === 'true') {
        $('#remember').prop('checked', true);
    }
    
    $('#remember').change(function() {
        localStorage.setItem('remember', $(this).is(':checked'));
    });
    
    // Auto-focus first invalid input in forms
    $('.is-invalid').first().focus();
});

// AJAX functions for user authentication
function checkEmailAvailability(email) {
    return $.ajax({
        url: '../ajax/check_email.php',
        type: 'POST',
        data: { email: email },
        dataType: 'json'
    });
}

// Real-time email availability check
$('#email').on('blur', function() {
    const email = $(this).val();
    const emailField = $(this);
    
    if (email.length > 3 && email.includes('@')) {
        checkEmailAvailability(email).done(function(response) {
            if (response.available) {
                emailField.removeClass('is-invalid').addClass('is-valid');
                $('#email-feedback').text('Email is available').removeClass('invalid-feedback').addClass('valid-feedback').show();
            } else {
                emailField.removeClass('is-valid').addClass('is-invalid');
                $('#email-feedback').text('Email is already registered').removeClass('valid-feedback').addClass('invalid-feedback').show();
            }
        });
    }
});

// Password strength meter
$('#password').on('keyup', function() {
    const password = $(this).val();
    const strengthMeter = $('#password-strength');
    
    if (password.length === 0) {
        strengthMeter.html('').removeClass('text-danger text-warning text-success');
        return;
    }
    
    // Calculate strength
    let strength = 0;
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    // Update meter
    let text, color;
    if (strength <= 2) {
        text = 'Weak';
        color = 'text-danger';
    } else if (strength <= 4) {
        text = 'Medium';
        color = 'text-warning';
    } else {
        text = 'Strong';
        color = 'text-success';
    }
    
    strengthMeter.html(text).removeClass('text-danger text-warning text-success').addClass(color);
});

// Search suggestions
$('input[name="q"]').on('input', function() {
    const query = $(this).val();
    const searchForm = $(this).closest('form');
    const suggestionsContainer = $('#search-suggestions');
    
    if (query.length < 2) {
        suggestionsContainer.hide().empty();
        return;
    }
    
    $.ajax({
        url: SITE_URL + '/ajax/search_suggestions.php',
        type: 'GET',
        data: { q: query },
        dataType: 'json',
        success: function(response) {
            if (response.length === 0) {
                suggestionsContainer.hide().empty();
                return;
            }
            
            let html = '<div class="list-group">';
            
            response.forEach(item => {
                if (item.type === 'book') {
                    html += `
                        <a href="${item.url}" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${item.title}</h6>
                                <small>Book</small>
                            </div>
                            <p class="mb-1 small text-muted">by ${item.author}</p>
                        </a>
                    `;
                } else if (item.type === 'author') {
                    html += `
                        <a href="${item.url}" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${item.name}</h6>
                                <small>Author</small>
                            </div>
                        </a>
                    `;
                } else if (item.type === 'category') {
                    html += `
                        <a href="${item.url}" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${item.name}</h6>
                                <small>Category</small>
                            </div>
                        </a>
                    `;
                }
            });
            
            html += '</div>';
            suggestionsContainer.html(html).show();
        }
    });
});

// Hide suggestions when clicking outside
$(document).click(function(e) {
    if (!$(e.target).closest('.input-group').length) {
        $('#search-suggestions').hide();
    }
});