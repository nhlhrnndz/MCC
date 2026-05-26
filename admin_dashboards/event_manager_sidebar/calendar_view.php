<?php
// This file contains only the calendar content
$current_page = 'calendar';

// Status configuration
$statusColors = [
    'pending' => '#ffcc00',
    'approved' => '#00b8a9',
    'rejected' => '#dc3545',
    'confirmed' => '#00998c',
    'under_review' => '#17a2b8',
    'needs_changes' => '#fd7e14',
    'payment_pending_verification' => '#6f42c1',
    'balance_pending_verification' => '#e83e8c',
    'fully_paid' => '#20c997',
    'completed' => '#28a745'
];

$statusLabels = [
    'pending' => 'Pending',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'confirmed' => 'Confirmed',
    'under_review' => 'Under Review',
    'needs_changes' => 'Needs Changes',
    'payment_pending_verification' => 'Payment Pending',
    'balance_pending_verification' => 'Balance Pending',
    'fully_paid' => 'Fully Paid',
    'completed' => 'Completed'
];

// Get mode from URL or default to 'month'
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'month';

// Get current date from URL or use today
$currentDate = isset($_GET['date']) ? new DateTime($_GET['date']) : new DateTime();

// Navigation functions
function getPreviousDate($currentDate, $mode) {
    $newDate = clone $currentDate;
    if ($mode === 'month') {
        $newDate->modify('-1 month');
    } elseif ($mode === 'week') {
        $newDate->modify('-7 days');
    } else {
        $newDate->modify('-1 day');
    }
    return $newDate;
}

function getNextDate($currentDate, $mode) {
    $newDate = clone $currentDate;
    if ($mode === 'month') {
        $newDate->modify('+1 month');
    } elseif ($mode === 'week') {
        $newDate->modify('+7 days');
    } else {
        $newDate->modify('+1 day');
    }
    return $newDate;
}

function getToday() {
    return new DateTime();
}

// Navigation URLs
$prevUrl = "?page=calendar&mode=$mode&date=" . getPreviousDate($currentDate, $mode)->format('Y-m-d');
$nextUrl = "?page=calendar&mode=$mode&date=" . getNextDate($currentDate, $mode)->format('Y-m-d');
$todayUrl = "?page=calendar&mode=$mode&date=" . getToday()->format('Y-m-d');
$monthUrl = "?page=calendar&mode=month&date=" . $currentDate->format('Y-m-d');
$weekUrl = "?page=calendar&mode=week&date=" . $currentDate->format('Y-m-d');
$dayUrl = "?page=calendar&mode=day&date=" . $currentDate->format('Y-m-d');

// Get month name
$monthName = $currentDate->format('F Y');

// Helper function to check if date is today
function isToday($date) {
    return $date->format('Y-m-d') === (new DateTime())->format('Y-m-d');
}

// Database function to get events for a specific date
function getEventsForDate($date) {
    global $conn;
    $dateStr = $date->format('Y-m-d');
    
    // Query to get events based on event_date or arrival_date
    $query = "SELECT 
                id,
                proposal_id,
                event_title,
                event_type,
                event_date,
                arrival_date,
                arrival_time,
                venue_preference,
                full_name as client_name,
                contact_number,
                email,
                status,
                expected_guests
              FROM event_proposals 
              WHERE (event_date = ? OR arrival_date = ?) 
              AND status NOT IN ('rejected')
              ORDER BY arrival_time ASC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("ss", $dateStr, $dateStr);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    
    $stmt->close();
    return $events;
}

// Helper function to get status indicators for a date
function getStatusIndicators($date, $statusColors) {
    $eventsForDay = getEventsForDate($date);
    $statusCounts = [
        'pending' => 0,
        'approved' => 0,
        'confirmed' => 0,
        'under_review' => 0,
        'needs_changes' => 0,
        'payment_pending_verification' => 0,
        'balance_pending_verification' => 0,
        'fully_paid' => 0,
        'completed' => 0
    ];
    
    foreach ($eventsForDay as $event) {
        if (isset($statusCounts[$event['status']])) {
            $statusCounts[$event['status']]++;
        }
    }
    
    $indicators = [];
    foreach ($statusCounts as $status => $count) {
        if ($count > 0) {
            $indicators[] = [
                'status' => $status,
                'color' => $statusColors[$status],
                'count' => $count
            ];
        }
    }
    
    return $indicators;
}

// Render month view - FIXED
function renderMonthView($currentDate, $statusColors) {
    $year = $currentDate->format('Y');
    $month = $currentDate->format('n');
    $firstDay = new DateTime("$year-$month-01");
    $lastDay = new DateTime($firstDay->format('Y-m-t'));
    $startingDayOfWeek = (int)$firstDay->format('w');
    $daysInMonth = (int)$lastDay->format('j');
    
    $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    
    $html = '<div class="calendar-month-view">';
    $html .= '<div class="calendar-header-row">';
    
    foreach ($dayNames as $name) {
        $html .= '<div class="calendar-day-header">' . $name . '</div>';
    }
    
    $html .= '</div>';
    $html .= '<div class="calendar-days-grid">';
    
    // Add empty cells for days before month starts
    for ($i = 0; $i < $startingDayOfWeek; $i++) {
        $html .= '<div class="calendar-day-empty"></div>';
    }
    
    // Add days of the month
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = new DateTime("$year-$month-$day");
        $eventsForDay = getEventsForDate($date);
        $statusIndicators = getStatusIndicators($date, $statusColors);
        $isToday = isToday($date);
        
        $todayClass = $isToday ? "calendar-day-today" : "";
        
        $html .= '<div class="calendar-day ' . $todayClass . '">';
        
        // Day number with status indicators
        $html .= '<div class="calendar-day-header-inner">';
        $html .= '<div class="calendar-day-number">' . $day . '</div>';
        
        // Status indicators
        if (!empty($statusIndicators)) {
            $html .= '<div class="calendar-status-indicators">';
            foreach ($statusIndicators as $indicator) {
                $html .= '<div class="status-indicator-dot" style="background-color: ' . $indicator['color'] . '" 
                          title="' . ucfirst($indicator['status']) . ': ' . $indicator['count'] . ' event(s)"></div>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        
        // Events list - FIXED: Added safe status color access
        $html .= '<div class="calendar-events-list">';
        foreach (array_slice($eventsForDay, 0, 3) as $event) {
            $time = !empty($event['arrival_time']) ? date('g:i A', strtotime($event['arrival_time'])) : 'All Day';
            $statusColor = $statusColors[$event['status']] ?? '#6c757d'; // Default gray
            
            $html .= '<div 
                class="calendar-event-item"
                style="border-left: 4px solid ' . $statusColor . '">
                <div class="event-name">' . htmlspecialchars($event['event_title']) . '</div>
                <div class="event-time">' . htmlspecialchars($time) . '</div>
            </div>';
        }
        
        // Show "+ more" if there are more than 3 events
        if (count($eventsForDay) > 3) {
            $html .= '<div class="text-center">
                <small class="text-muted">+ ' . (count($eventsForDay) - 3) . ' more</small>
            </div>';
        }
        $html .= '</div>';
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

// Render week view - FIXED
function renderWeekView($currentDate, $statusColors) {
    $startOfWeek = clone $currentDate;
    $startOfWeek->modify('-' . $currentDate->format('w') . ' days');
    
    $weekDays = [];
    for ($i = 0; $i < 7; $i++) {
        $day = clone $startOfWeek;
        $day->modify("+$i days");
        $weekDays[] = $day;
    }
    
    $html = '<div class="calendar-week-view">';
    $html .= '<div class="calendar-week-header">';
    
    foreach ($weekDays as $day) {
        $statusIndicators = getStatusIndicators($day, $statusColors);
        $isToday = isToday($day);
        
        $html .= '<div class="calendar-week-day-header">';
        $html .= '<div class="week-day-name">' . $day->format('D') . '</div>';
        $html .= '<div class="week-day-number' . ($isToday ? ' today' : '') . '">' . $day->format('j') . '</div>';
        
        // Status indicators
        if (!empty($statusIndicators)) {
            $html .= '<div class="calendar-status-indicators">';
            foreach ($statusIndicators as $indicator) {
                $html .= '<div class="status-indicator-dot" style="background-color: ' . $indicator['color'] . '" 
                          title="' . ucfirst($indicator['status']) . ': ' . $indicator['count'] . ' event(s)"></div>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '<div class="calendar-week-days">';
    
    foreach ($weekDays as $day) {
        $eventsForDay = getEventsForDate($day);
        
        $html .= '<div class="calendar-week-day">';
        $html .= '<div class="week-day-events">';
        
        foreach ($eventsForDay as $event) {
            $time = !empty($event['arrival_time']) ? date('g:i A', strtotime($event['arrival_time'])) : 'All Day';
            $statusColor = $statusColors[$event['status']] ?? '#6c757d'; // Default gray
            
            $html .= '<div 
                class="calendar-event-item week-view"
                style="border-left: 4px solid ' . $statusColor . '">
                <div class="event-time">' . htmlspecialchars($time) . '</div>
                <div class="event-name">' . htmlspecialchars($event['event_title']) . '</div>
                <div class="event-venue">' . htmlspecialchars($event['venue_preference']) . '</div>
            </div>';
        }
        
        if (empty($eventsForDay)) {
            $html .= '<div class="text-center text-muted py-3">
                <small>No events scheduled</small>
            </div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

// Render day view - FIXED
function renderDayView($currentDate, $statusColors) {
    $eventsForDay = getEventsForDate($currentDate);
    $statusIndicators = getStatusIndicators($currentDate, $statusColors);
    
    // Sort events by time
    usort($eventsForDay, function($a, $b) {
        $timeA = strtotime($a['arrival_time']);
        $timeB = strtotime($b['arrival_time']);
        return $timeA - $timeB;
    });
    
    $hours = range(0, 23);
    
    $html = '<div class="calendar-day-view">';
    $html .= '<div class="calendar-day-header">';
    $html .= '<div class="day-header-inner">';
    $html .= '<h3 class="day-title">' . $currentDate->format('l, F j, Y') . '</h3>';
    
    // Status indicators
    if (!empty($statusIndicators)) {
        $html .= '<div class="day-status-indicators">';
        foreach ($statusIndicators as $indicator) {
            $html .= '<div class="day-status-item">';
            $html .= '<div class="status-indicator-dot" style="background-color: ' . $indicator['color'] . '"></div>';
            $html .= '<span>' . ucfirst($indicator['status']) . ' (' . $indicator['count'] . ')</span>';
            $html .= '</div>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="calendar-day-timeline">';
    
    foreach ($hours as $hour) {
        $hourEvents = array_filter($eventsForDay, function($event) use ($hour) {
            $eventHour = (int)date('G', strtotime($event['arrival_time']));
            return $eventHour === $hour;
        });
        
        $html .= '<div class="calendar-hour-row">';
        $html .= '<div class="calendar-hour-label">';
        $html .= $hour === 0 ? '12 AM' : ($hour < 12 ? "$hour AM" : ($hour === 12 ? '12 PM' : ($hour - 12) . ' PM'));
        $html .= '</div>';
        $html .= '<div class="calendar-hour-content">';
        $html .= '<div class="hour-events">';
        
        foreach ($hourEvents as $event) {
            $time = !empty($event['arrival_time']) ? date('g:i A', strtotime($event['arrival_time'])) : 'All Day';
            $statusColor = $statusColors[$event['status']] ?? '#6c757d'; // Default gray
            
            $html .= '<div 
                class="calendar-event-item day-view"
                style="border-left: 4px solid ' . $statusColor . '">
                <div class="event-name">' . htmlspecialchars($event['event_title']) . '</div>
                <div class="event-time">' . htmlspecialchars($time) . '</div>
                <div class="event-details">' . htmlspecialchars($event['venue_preference']) . ' - ' . htmlspecialchars($event['client_name']) . '</div>
            </div>';
        }
        
        if (empty($hourEvents)) {
            $html .= '<div class="text-center text-muted py-2">
                <small>No events</small>
            </div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}
?>

<!-- Header -->
<div class="header-card">
    <div class="row align-items-center">
        <div class="col">
            <h1><i class="fas fa-calendar-alt me-3"></i>Calendar View</h1>
            <p class="mb-0">Manage your event schedule and appointments</p>
        </div>
        <div class="col-auto">
            <span class="badge bg-light text-dark fs-6"><?php echo $monthName; ?></span>
        </div>
    </div>
</div>

<!-- Calendar Controls -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-3">
                    <h4 class="mb-0 fw-bold text-primary"><?php echo $monthName; ?></h4>
                    <div class="d-flex align-items-center gap-2">
                        <a href="<?php echo $prevUrl; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <a href="<?php echo $todayUrl; ?>" class="btn btn-primary btn-sm">Today</a>
                        <a href="<?php echo $nextUrl; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <div class="d-flex align-items-center justify-content-end gap-3">
                    <!-- Status Legend -->
                    <div class="calendar-status-legend">
                        <?php foreach ($statusLabels as $status => $label): ?>
                            <?php if (in_array($status, ['pending', 'approved', 'confirmed', 'completed'])): ?>
                                <div class="status-legend-item">
                                    <div class="status-color" style="background-color: <?php echo $statusColors[$status]; ?>"></div>
                                    <span><?php echo $label; ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- View Mode Buttons -->
                    <div class="btn-group">
                        <a href="<?php echo $monthUrl; ?>" 
                           class="btn <?php echo $mode === 'month' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            Month
                        </a>
                        <a href="<?php echo $weekUrl; ?>" 
                           class="btn <?php echo $mode === 'week' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            Week
                        </a>
                        <a href="<?php echo $dayUrl; ?>" 
                           class="btn <?php echo $mode === 'day' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            Day
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Calendar View -->
<?php
if ($mode === 'month') {
    echo renderMonthView($currentDate, $statusColors);
} elseif ($mode === 'week') {
    echo renderWeekView($currentDate, $statusColors);
} else {
    echo renderDayView($currentDate, $statusColors);
}
?>

<style>
:root {
    --primary: #00b8a9;
    --primary-dark: #00998c;
    --primary-light: #e3f8f6;
    --white: #ffffff;
    --form-bg: #f9fbfc;
}

/* Header Card */
.header-card {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: var(--white);
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0, 184, 169, 0.3);
}

.header-card h1 {
    margin: 0;
    font-weight: 600;
}

.header-card p {
    opacity: 0.9;
    margin: 0.5rem 0 0 0;
}

/* Calendar Controls Card */
.card {
    border: 1px solid rgba(0, 184, 169, 0.1);
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    margin-bottom: 1.5rem;
}

.card-body {
    padding: 1.5rem;
}

/* Buttons */
.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-dark) 0%, #00857a 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 184, 169, 0.3);
}

.btn-outline-primary {
    color: var(--primary);
    border-color: var(--primary);
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-outline-primary:hover {
    background: var(--primary);
    border-color: var(--primary);
    transform: translateY(-1px);
}

/* Calendar Layout */
.calendar-month-view,
.calendar-week-view,
.calendar-day-view {
    background: var(--white);
    border-radius: 12px;
    border: 1px solid rgba(0, 184, 169, 0.1);
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

.calendar-header-row {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    border-bottom: 1px solid rgba(0, 184, 169, 0.1);
    background: var(--primary-light);
}

.calendar-day-header {
    padding: 0.75rem;
    text-align: center;
    font-weight: 600;
    color: var(--primary-dark);
}

.calendar-days-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
}

/* Day Cells */
.calendar-day {
    min-height: 8rem;
    border: 1px solid rgba(0, 184, 169, 0.1);
    padding: 0.5rem;
    background: var(--white);
    transition: all 0.3s ease;
}

.calendar-day:hover {
    background: var(--primary-light);
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 184, 169, 0.1);
}

.calendar-day-empty {
    min-height: 8rem;
    border: 1px solid rgba(0, 184, 169, 0.1);
    background: var(--primary-light);
    opacity: 0.5;
}

.calendar-day-today {
    background: var(--primary-light) !important;
    border-color: var(--primary);
}

.calendar-day-header-inner {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.25rem;
}

.calendar-day-number {
    font-size: 0.875rem;
    font-weight: 600;
    color: #2c3e50;
}

.calendar-day-today .calendar-day-number {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    border-radius: 50%;
    width: 1.75rem;
    height: 1.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

/* Status Indicators */
.calendar-status-indicators {
    display: flex;
    gap: 0.25rem;
}

.status-indicator-dot {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

/* Events */
.calendar-events-list {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.calendar-event-item {
    width: 100%;
    text-align: left;
    padding: 0.375rem;
    border-radius: 6px;
    background: var(--form-bg);
    font-size: 0.75rem;
    border-left: 4px solid;
    transition: all 0.3s ease;
    cursor: pointer;
}

.calendar-event-item:hover {
    transform: translateX(2px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.event-name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-weight: 500;
    color: #2c3e50;
}

.event-time {
    font-size: 0.7rem;
    opacity: 0.9;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #6c757d;
}

.event-venue {
    font-size: 0.7rem;
    opacity: 0.9;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-top: 0.125rem;
    color: #6c757d;
}

.event-details {
    font-size: 0.7rem;
    opacity: 0.9;
    margin-top: 0.125rem;
    color: #6c757d;
}

/* Week View */
.calendar-week-header {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    border-bottom: 1px solid rgba(0, 184, 169, 0.1);
    background: var(--primary-light);
}

.calendar-week-day-header {
    padding: 0.75rem;
    text-align: center;
}

.week-day-name {
    font-size: 0.875rem;
    color: var(--primary-dark);
    font-weight: 500;
}

.week-day-number {
    font-size: 1.125rem;
    margin-top: 0.25rem;
    font-weight: 600;
    color: #2c3e50;
}

.week-day-number.today {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    border-radius: 50%;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0.25rem auto 0;
    font-weight: 600;
}

.calendar-week-days {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    border-left: 1px solid rgba(0, 184, 169, 0.1);
}

.calendar-week-day {
    min-height: 24rem;
    padding: 0.5rem;
    border-right: 1px solid rgba(0, 184, 169, 0.1);
    border-bottom: 1px solid rgba(0, 184, 169, 0.1);
    transition: all 0.3s ease;
}

.calendar-week-day:hover {
    background: var(--primary-light);
}

.week-day-events {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.calendar-event-item.week-view {
    padding: 0.5rem;
    font-size: 0.75rem;
    border-radius: 6px;
}

/* Day View */
.calendar-day-header {
    padding: 1rem;
    border-bottom: 1px solid rgba(0, 184, 169, 0.1);
    background: var(--primary-light);
}

.day-header-inner {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.day-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
    color: var(--primary-dark);
}

.day-status-indicators {
    display: flex;
    gap: 1rem;
}

.day-status-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #2c3e50;
}

.calendar-day-timeline {
    max-height: 600px;
    overflow-y: auto;
}

.calendar-hour-row {
    display: flex;
    border-bottom: 1px solid rgba(0, 184, 169, 0.1);
    transition: all 0.3s ease;
}

.calendar-hour-row:hover {
    background: var(--primary-light);
}

.calendar-hour-label {
    width: 5rem;
    padding: 0.75rem;
    font-size: 0.875rem;
    color: var(--primary-dark);
    background: var(--white);
    border-right: 1px solid rgba(0, 184, 169, 0.1);
    font-weight: 500;
}

.calendar-hour-content {
    flex: 1;
    padding: 0.5rem;
    min-height: 4rem;
}

.hour-events {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.calendar-event-item.day-view {
    padding: 0.75rem;
    font-size: 0.875rem;
    border-radius: 8px;
}

/* Status Legend */
.calendar-status-legend {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
}

.status-legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.5rem;
    background: var(--white);
    border-radius: 6px;
    border: 1px solid rgba(0, 184, 169, 0.1);
}

.status-color {
    width: 0.75rem;
    height: 0.75rem;
    border-radius: 0.125rem;
    border: 1px solid rgba(0,0,0,0.1);
}

/* Badge */
.badge {
    background: var(--white);
    color: var(--primary-dark);
    font-weight: 500;
    padding: 0.5rem 0.75rem;
    border-radius: 20px;
    border: 1px solid rgba(0, 184, 169, 0.2);
}

/* Responsive */
@media (max-width: 768px) {
    .calendar-status-legend {
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .status-legend-item span {
        font-size: 0.7rem;
    }
    
    .btn-group .btn {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    .day-header-inner {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .day-status-indicators {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .calendar-day {
        min-height: 6rem;
        padding: 0.25rem;
    }
    
    .calendar-event-item {
        padding: 0.25rem;
        font-size: 0.7rem;
    }
    
    .calendar-week-day {
        min-height: 20rem;
    }
    
    .calendar-hour-label {
        width: 4rem;
        padding: 0.5rem;
    }
    
    .header-card {
        padding: 1rem;
        text-align: center;
    }
    
    .header-card h1 {
        font-size: 1.5rem;
    }
}

/* Animation for better UX */
.calendar-month-view,
.calendar-week-view,
.calendar-day-view {
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Scrollbar styling */
.calendar-day-timeline::-webkit-scrollbar {
    width: 6px;
}

.calendar-day-timeline::-webkit-scrollbar-track {
    background: var(--primary-light);
}

.calendar-day-timeline::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 3px;
}

.calendar-day-timeline::-webkit-scrollbar-thumb:hover {
    background: var(--primary-dark);
}
</style>