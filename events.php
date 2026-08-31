<?php
require_once 'config.php';

$sql = "SELECT event_id, title, description, event_date, event_time, venue FROM events ORDER BY event_date ASC, event_time ASC";
$result = $conn->query($sql);

$events = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}

$today = date('Y-m-d');
$upcoming = [];
$past = [];

foreach ($events as $event) {
    if ($event['event_date'] >= $today) {
        $upcoming[] = $event;
    } else {
        $past[] = $event;
    }
}

$displayEvents = array_merge($upcoming, $past);

if (empty($displayEvents)) {
    echo '<div class="card empty-state">No events have been created yet.</div>';
    exit;
}

foreach ($displayEvents as $event) {
    $isPast = $event['event_date'] < $today;
    $badgeClass = $isPast ? 'past' : '';
    $badgeText = $isPast ? 'Past' : 'Upcoming';
    $dateText = date('d M Y', strtotime($event['event_date']));
    $timeText = !empty($event['event_time']) ? date('h:i A', strtotime($event['event_time'])) : 'Time not set';
    $venueText = !empty($event['venue']) ? htmlspecialchars($event['venue']) : 'Venue to be announced';
    $description = !empty($event['description']) ? htmlspecialchars($event['description']) : 'More details will be shared soon.';

    echo '<div class="card event-card">';
    echo '<span class="event-badge ' . $badgeClass . '">' . $badgeText . '</span>';
    echo '<h3>' . htmlspecialchars($event['title']) . '</h3>';
    echo '<div class="event-meta">';
    echo '<div><strong>Date:</strong> ' . $dateText . '</div>';
    echo '<div><strong>Time:</strong> ' . $timeText . '</div>';
    echo '<div><strong>Venue:</strong> ' . $venueText . '</div>';
    echo '</div>';
    echo '<p class="event-description">' . $description . '</p>';
    echo '</div>';
}
?>
