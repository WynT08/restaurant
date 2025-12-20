// Main JavaScript for Restaurant Management System

$(document).ready(function() {
    var $sidebar = $('#sidebar');
    var $body = $('body');
    
    // Sidebar toggle for desktop and mobile
    $('#sidebarToggle').on('click', function() {
        if ($(window).width() < 768) {
            $sidebar.toggleClass('show');
        } else {
            $body.toggleClass('sidebar-collapsed');
        }
    });
    
    // Close sidebar when clicking outside on mobile
    $(document).on('click', function(e) {
        if ($(window).width() < 768) {
            if (! $(e.target).closest('#sidebar, #sidebarToggle').length) {
                $sidebar.removeClass('show');
            }
        }
    });
    
    // Reset sidebar state on resize
    $(window).on('resize', function() {
        if ($(this).width() >= 768) {
            $sidebar.removeClass('show');
        } else {
            $body.removeClass('sidebar-collapsed');
        }
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Confirm delete actions
    $('. btn-delete').on('click', function(e) {
        if (! confirm('Bạn có chắc muốn xóa? ')) {
            e.preventDefault();
        }
    });
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap. Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice. call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Number formatting
    $('. format-money').each(function() {
        var value = parseFloat($(this).text());
        $(this).text(formatMoney(value));
    });
    
    // Date picker enhancement
    $('input[type="date"]').each(function() {
        if (! $(this).val() && $(this).attr('min') === undefined) {
            $(this).attr('min', new Date().toISOString().split('T')[0]);
        }
    });
    
    // Form validation
    $('form').on('submit', function(e) {
        var form = $(this)[0];
        if (form.checkValidity() === false) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });
    
    // Auto-logout warning
    var idleTime = 0;
    var idleInterval = setInterval(timerIncrement, 60000); // 1 minute
    
    $(document).on('mousemove keypress', function() {
        idleTime = 0;
    });
    
    function timerIncrement() {
        idleTime++;
        if (idleTime > 30) { // 30 minutes
            if (confirm('Bạn đã không hoạt động trong 30 phút. Bạn có muốn tiếp tục? ')) {
                idleTime = 0;
            } else {
                window.location.href = '/modules/auth/logout.php';
            }
        }
    }
});

// Utility Functions
function formatMoney(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

function showToast(message, type = 'success') {
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    if ($('#toast-container').length === 0) {
        $('body').append('<div id="toast-container" class="position-fixed top-0 end-0 p-3" style="z-index: 11"></div>');
    }
    
    $('#toast-container').append(toastHtml);
    const toastElement = $('#toast-container .toast').last()[0];
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    setTimeout(function() {
        $(toastElement).remove();
    }, 5000);
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

function loadingSpinner(show = true) {
    if (show) {
        if ($('#loading-spinner').length === 0) {
            $('body').append(`
                <div id="loading-spinner" style="position: fixed; top: 0; left: 0; width:  100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">
                    <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);
        }
    } else {
        $('#loading-spinner').remove();
    }
}

// AJAX Error Handler
$(document).ajaxError(function(event, jqxhr, settings, thrownError) {
    console.error('AJAX Error:', thrownError);
    showToast('Có lỗi xảy ra.  Vui lòng thử lại! ', 'danger');
    loadingSpinner(false);
});

// Print function
function printElement(elementId) {
    var content = document.getElementById(elementId).innerHTML;
    var printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Print</title>');
    printWindow.document.write('<link rel="stylesheet" href="/assets/css/style.css">');
    printWindow.document.write('</head><body>');
    printWindow.document. write(content);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

// Export to CSV
function exportTableToCSV(tableId, filename) {
    var csv = [];
    var rows = document.querySelectorAll('#' + tableId + ' tr');
    
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (var j = 0; j < cols.length; j++) {
            row.push(cols[j].innerText);
        }
        
        csv.push(row.join(','));
    }
    
    downloadCSV(csv. join('\n'), filename);
}

function downloadCSV(csv, filename) {
    var csvFile;
    var downloadLink;
    
    csvFile = new Blob([csv], {type: 'text/csv'});
    downloadLink = document.createElement('a');
    downloadLink.download = filename;
    downloadLink. href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
}