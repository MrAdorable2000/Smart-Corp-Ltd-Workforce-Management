/* =====================================================
   SMART ATTENDANCE - MAIN APPLICATION JS
   ===================================================== */

$(document).ready(function() {

    // Mobile sidebar toggle
    $('#mobileMenuBtn').on('click', function() {
        $('.sidebar').addClass('show');
    });
    $('#sidebarToggle').on('click', function() {
        $('.sidebar').removeClass('show');
    });

    // Password show/hide toggle
    $('.toggle-pwd').on('click', function() {
        const target = $(this).data('target');
        const input = $('#' + target);
        const icon = $(this).find('i');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });

    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        $('.alert-dismissible').fadeTo(500, 0).slideUp(500, function() {
            $(this).remove();
        });
    }, 5000);

    // AJAX setup with CSRF
    $.ajaxSetup({
        beforeSend: function(xhr) {
            const token = $('meta[name="csrf-token"]').attr('content');
            if (token) {
                xhr.setRequestHeader('X-CSRF-TOKEN', token);
            }
        }
    });

    // Confirm delete
    $(document).on('click', '.confirm-delete', function(e) {
        if (!confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
            e.preventDefault();
        }
    });

    // Data table search
    $('#tableSearch').on('keyup', function() {
        const value = $(this).val().toLowerCase();
        $('table.data-table tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Date range filter for reports
    $('.report-filter').on('change', function() {
        $(this).closest('form').submit();
    });

    // Initialize tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(el => new bootstrap.Tooltip(el));
});

/**
 * Show toast notification
 */
function showToast(message, type = 'success') {
    const icons = {
        success: 'bi-check-circle-fill',
        error: 'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill'
    };
    const toast = $(`
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
            <div class="toast show align-items-center text-white bg-${type === 'error' ? 'danger' : type}" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${icons[type]} me-2"></i> ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>
    `);
    $('body').append(toast);
    setTimeout(() => toast.remove(), 4000);
}

/**
 * AJAX helper
 */
function ajaxRequest(url, method, data, callback, errorCallback) {
    $.ajax({
        url: BASE_URL + url,
        method: method,
        data: data,
        dataType: 'json',
        success: function(response) {
            if (callback) callback(response);
        },
        error: function(xhr) {
            let msg = 'An error occurred';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            showToast(msg, 'error');
            if (errorCallback) errorCallback(xhr);
        }
    });
}

/**
 * Format date as YYYY-MM-DD
 */
function formatDate(date) {
    const d = new Date(date);
    const month = '' + (d.getMonth() + 1);
    const day = '' + d.getDate();
    const year = d.getFullYear();
    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;
    return [year, month, day].join('-');
}

const BASE_URL = window.location.origin + '/smart-attendance/public';
