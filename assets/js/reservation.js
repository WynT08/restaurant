// Reservation Management JavaScript

$(document).ready(function() {
    // Initialize date picker with minimum date as today
    $('input[name="reservation_date"]').attr('min', new Date().toISOString().split('T')[0]);
    
    // Check table availability when date/time changes
    $('input[name="reservation_date"], input[name="reservation_time"]').on('change', checkTableAvailability);
    
    // Guest number change
    $('input[name="number_of_guests"]').on('change', function() {
        filterTablesByCapacity($(this).val());
    });
});

function checkTableAvailability() {
    const date = $('input[name="reservation_date"]').val();
    const time = $('input[name="reservation_time"]').val();
    
    if (!date || !time) return;
    
    $. ajax({
        url: 'ajax_check_availability.php',
        method: 'GET',
        data: { date: date, time: time },
        dataType: 'json',
        success: function(response) {
            updateTableOptions(response.available_tables);
        }
    });
}

function updateTableOptions(availableTables) {
    const tableSelect = $('select[name="table_id"]');
    const currentValue = tableSelect.val();
    
    tableSelect.find('option').each(function() {
        if ($(this).val()) {
            const tableId = $(this).val();
            if (availableTables.includes(parseInt(tableId))) {
                $(this).prop('disabled', false);
                $(this).text($(this).text().replace(' (Đã đặt)', ''));
            } else {
                $(this).prop('disabled', true);
                if (! $(this).text().includes('(Đã đặt)')) {
                    $(this).text($(this).text() + ' (Đã đặt)');
                }
            }
        }
    });
    
    // Reset selection if current table is not available
    if (! availableTables.includes(parseInt(currentValue))) {
        tableSelect.val('');
    }
}

function filterTablesByCapacity(guests) {
    const tableSelect = $('select[name="table_id"]');
    
    tableSelect.find('option').each(function() {
        if ($(this).val()) {
            const optionText = $(this).text();
            const capacityMatch = optionText.match(/\((\d+) chỗ/);
            
            if (capacityMatch) {
                const capacity = parseInt(capacityMatch[1]);
                if (capacity >= guests) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            }
        }
    });
}

// Calendar view functions
function renderCalendar(year, month) {
    $.ajax({
        url: 'ajax_get_reservations.php',
        method: 'GET',
        data:  { year: year, month: month },
        dataType: 'json',
        success: function(data) {
            displayCalendar(data, year, month);
        }
    });
}

function displayCalendar(reservations, year, month) {
    // Calendar rendering logic here
    const calendarGrid = $('#calendar-grid');
    calendarGrid.empty();
    
    const firstDay = new Date(year, month - 1, 1).getDay();
    const daysInMonth = new Date(year, month, 0).getDate();
    
    // Add day headers
    const dayNames = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
    dayNames.forEach(day => {
        calendarGrid.append(`<div class="calendar-day-name">${day}</div>`);
    });
    
    // Add empty cells for days before month starts
    for (let i = 0; i < firstDay; i++) {
        calendarGrid.append('<div class="calendar-day empty"></div>');
    }
    
    // Add days
    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayReservations = reservations.filter(r => r.reservation_date === dateStr);
        
        let dayHtml = `
            <div class="calendar-day ${dateStr === new Date().toISOString().split('T')[0] ? 'today' : ''}">
                <div class="calendar-day-header">${day}</div>
        `;
        
        dayReservations.forEach(reservation => {
            dayHtml += `
                <div class="reservation-item" onclick="viewReservation(${reservation.reservation_id})">
                    ${reservation.reservation_time. substring(0, 5)} - ${reservation.customer_name}
                </div>
            `;
        });
        
        dayHtml += '</div>';
        calendarGrid. append(dayHtml);
    }
}

function viewReservation(reservationId) {
    window.open('view_reservation.php?id=' + reservationId, '_blank', 'width=600,height=800');
}