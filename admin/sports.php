<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/activity.php';
require_role('super_admin','sport_admin');
$pdo = db();
$canManageSports=is_super_admin();
$admin=current_admin();
$assignedSportIds=[];
if (!$canManageSports) {
    $stmt=$pdo->prepare('SELECT sport_id FROM admin_sports WHERE admin_id=?'); $stmt->execute([$admin['id']]);
    $assignedSportIds=array_map('intval',$stmt->fetchAll(PDO::FETCH_COLUMN));
    if (!$assignedSportIds && !empty($admin['sport_id'])) $assignedSportIds=[(int)$admin['sport_id']];
}
function can_manage_event_sport(int $sportId, bool $all, array $ids): bool { return $all || in_array($sportId,$ids,true); }
$editSportId=filter_input(INPUT_GET,'edit_sport',FILTER_VALIDATE_INT);
$editEventId=filter_input(INPUT_GET,'edit_event',FILTER_VALIDATE_INT);
$editingSport=null; $editingEvent=null;
if ($editSportId) { $stmt=$pdo->prepare('SELECT * FROM sports WHERE id=?'); $stmt->execute([$editSportId]); $editingSport=$stmt->fetch(); }
if ($editEventId) { $stmt=$pdo->prepare('SELECT * FROM events WHERE id=?'); $stmt->execute([$editEventId]); $editingEvent=$stmt->fetch(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    if (($action === 'sport' || $action === 'update_sport') && !$canManageSports) {
        flash('error','Only the Meet Administrator may change the official sports list.');
    } elseif ($action === 'sport' || $action === 'update_sport') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $coordinator = trim($_POST['coordinator'] ?? '');
        $icon = trim($_POST['icon'] ?? '🏅');
        if ($name === '') flash('error', 'Sport name is required.');
        else {
            $id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
            $check=$pdo->prepare('SELECT COUNT(*) FROM sports WHERE name=? AND id<>?'); $check->execute([$name, $id ?: 0]);
            if ($check->fetchColumn()) flash('error', 'That sport already exists.');
            elseif ($action === 'update_sport' && $id) { $pdo->prepare('UPDATE sports SET name=?,icon=?,description=?,coordinator=? WHERE id=?')->execute([$name,mb_substr($icon,0,10),$description,$coordinator,$id]); log_activity('updated_sport','sport',$id,'Updated sport details for '.$name.'.'); flash('success', 'Sport details updated.'); }
            else { $pdo->prepare('INSERT INTO sports(name,icon,description,coordinator) VALUES(?,?,?,?)')->execute([$name, mb_substr($icon,0,10), $description, $coordinator]); log_activity('created_sport','sport',(int)$pdo->lastInsertId(),'Added sport '.$name.'.'); flash('success', 'Sport added successfully.'); }
        }
    } elseif ($action === 'event' || $action === 'update_event') {
        $sportId=filter_input(INPUT_POST,'sport_id',FILTER_VALIDATE_INT);
        $name=trim($_POST['event_name'] ?? ''); $division=trim($_POST['division'] ?? ''); $venue=trim($_POST['venue'] ?? '');
        $scheduledAt=trim($_POST['scheduled_at'] ?? ''); $deadline=trim($_POST['registration_deadline'] ?? '');
        $category=$_POST['category'] ?? 'Open'; $gender=$_POST['gender'] ?? 'Open'; $eventType=$_POST['event_type'] ?? 'Individual'; $status=$_POST['status'] ?? 'draft';
        $maxEntries=filter_input(INPUT_POST,'max_entries_per_area',FILTER_VALIDATE_INT) ?: null; $maxPlayers=filter_input(INPUT_POST,'max_players_per_team',FILTER_VALIDATE_INT) ?: null;
        $scoring=$_POST['scoring_type'] ?? 'points';
        $id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
        if (!$sportId || $name === '') flash('error', 'Select a sport and enter an event name.');
        elseif (!can_manage_event_sport((int)$sportId,$canManageSports,$assignedSportIds)) flash('error','This event is outside your assigned sport desk.');
        elseif ($action === 'update_event' && $id) { $pdo->prepare('UPDATE events SET sport_id=?,name=?,division=?,category=?,gender=?,event_type=?,max_entries_per_area=?,max_players_per_team=?,venue=?,scheduled_at=?,registration_deadline=?,scoring_type=?,status=? WHERE id=?')->execute([$sportId,$name,$division,$category,$gender,$eventType,$maxEntries,$maxPlayers,$venue,$scheduledAt ?: null,$deadline ?: null,$scoring,$status,$id]); log_activity('updated_event','event',$id,'Updated event '.$name.'.'); flash('success', 'Event details updated.'); }
        else { $pdo->prepare("INSERT INTO events(sport_id,name,division,category,gender,event_type,max_entries_per_area,max_players_per_team,venue,scheduled_at,registration_deadline,scoring_type,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute([$sportId,$name,$division,$category,$gender,$eventType,$maxEntries,$maxPlayers,$venue,$scheduledAt ?: null,$deadline ?: null,$scoring,$status]); log_activity('created_event','event',(int)$pdo->lastInsertId(),'Added event '.$name.'.'); flash('success', 'Event added.'); }
    } elseif ($action === 'delete_sport' && !$canManageSports) {
        flash('error','Only the Meet Administrator may change the official sports list.');
    } elseif ($action === 'delete_sport') {
        $id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
        $check=$pdo->prepare('SELECT COUNT(*) FROM events WHERE sport_id=?'); $check->execute([$id]);
        if ($check->fetchColumn()) flash('error','Delete or reassign the sport’s events before deleting the sport.');
        else { $pdo->prepare('DELETE FROM sports WHERE id=?')->execute([$id]); log_activity('deleted_sport','sport',$id,'Deleted a sport without events.'); flash('success','Sport deleted.'); }
    } elseif ($action === 'delete_event') {
        $id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
        $check=$pdo->prepare('SELECT sport_id FROM events WHERE id=?'); $check->execute([$id]); $eventSport=(int)$check->fetchColumn();
        if (!$eventSport || !can_manage_event_sport($eventSport,$canManageSports,$assignedSportIds)) flash('error','This event is outside your assigned sport desk.');
        else { $pdo->prepare('UPDATE events SET archived_at=NOW(),status=\'archived\' WHERE id=?')->execute([$id]); log_activity('archived_event','event',$id,'Archived an event.'); flash('success','Event archived.'); }
    }
    header('Location: sports.php'); exit;
}
$sportCondition=$canManageSports?'':' WHERE s.id IN ('.implode(',', array_map('intval',$assignedSportIds ?: [0])).')';
$sports=$pdo->query('SELECT s.*,COUNT(e.id) AS event_count FROM sports s LEFT JOIN events e ON e.sport_id=s.id'.$sportCondition.' GROUP BY s.id ORDER BY s.name')->fetchAll();
$events=$pdo->query('SELECT e.*,s.name sport_name FROM events e JOIN sports s ON s.id=e.sport_id'.$sportCondition.' ORDER BY s.name,e.scheduled_at,e.name')->fetchAll();
$upcomingCount=count(array_filter($events, fn($event) => $event['status']==='upcoming'));
$venueTbaCount=count(array_filter($events, fn($event) => empty($event['venue'])));
require_once __DIR__ . '/../includes/layout.php'; page_head('Sports & Events');
?>
<div class="admin-hero"><div><span class="eyebrow"><?= $canManageSports?'Meet Administrator':'Sports Administrator' ?></span><h1>Sports & Events</h1><p class="admin-subtitle"><?= $canManageSports?'Set official sports, coordinators, venues, and the competition schedule.':'Create, update, schedule, and archive events in your assigned sport desk.' ?></p></div></div>
<?php admin_nav('sports'); ?>
<?php if($message=flash('error')): ?><div class="notice error"><?= htmlspecialchars($message) ?></div><?php endif; ?><?php if($message=flash('success')): ?><div class="notice success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<div class="sports-summary"><div><b><?= count($sports) ?></b><span>Official sports</span></div><div><b><?= count($events) ?></b><span>Scheduled events</span></div><div><b><?= $upcomingCount ?></b><span>Upcoming events</span></div><div><b><?= $venueTbaCount ?></b><span>Venues to confirm</span></div></div>
<?php if($canManageSports): ?><section class="sports-admin-section"><div class="directory-heading"><div><span class="form-kicker">Sport directory</span><h2>Official sports</h2></div><button class="btn" type="button" data-sport-modal="create">+ Add sport</button></div><div class="table-card admin-users-table sports-list"><table class="score-table"><thead><tr><th>Sport</th><th>Coordinator</th><th>Events</th><th>Actions</th></tr></thead><tbody><?php foreach($sports as $sport): ?><tr><td><span class="title-ic"><?= sport_icon($sport['name'],$sport['icon'],18) ?></span><strong><?= htmlspecialchars($sport['name']) ?></strong><br><small><?= htmlspecialchars($sport['description'] ?? '') ?></small></td><td><?= htmlspecialchars($sport['coordinator'] ?: 'Not assigned') ?></td><td><?= (int)$sport['event_count'] ?></td><td class="actions"><button class="btn secondary" type="button" data-sport-modal="edit" data-id="<?= $sport['id'] ?>" data-name="<?= htmlspecialchars($sport['name'],ENT_QUOTES) ?>" data-icon="<?= htmlspecialchars($sport['icon'],ENT_QUOTES) ?>" data-coordinator="<?= htmlspecialchars($sport['coordinator'] ?? '',ENT_QUOTES) ?>" data-description="<?= htmlspecialchars($sport['description'] ?? '',ENT_QUOTES) ?>">Edit</button><?php if(!(int)$sport['event_count']): ?><button class="btn danger" type="button" data-sport-delete="<?= $sport['id'] ?>" data-name="<?= htmlspecialchars($sport['name'],ENT_QUOTES) ?>">Delete</button><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>
<section class="sports-admin-section"><div class="directory-heading"><div><span class="form-kicker">Event schedule</span><h2>Competition events</h2></div><button class="btn" type="button" data-event-modal="create">+ Add event</button></div><div class="table-card admin-users-table sports-list"><table class="score-table"><thead><tr><th>Event</th><th>Sport</th><th>Venue / location</th><th>Schedule</th><th>Actions</th></tr></thead><tbody><?php foreach($events as $event): ?><tr><td><strong><?= htmlspecialchars($event['name']) ?></strong><br><small><?= htmlspecialchars($event['division']) ?></small></td><td><?= htmlspecialchars($event['sport_name']) ?></td><td><?= htmlspecialchars($event['venue'] ?: 'Venue TBA') ?></td><td><?= $event['scheduled_at'] ? date('M j, Y · g:i A',strtotime($event['scheduled_at'])) : 'TBA' ?></td><td class="actions"><button class="btn secondary" type="button" data-event-modal="edit" data-id="<?= $event['id'] ?>" data-sport="<?= $event['sport_id'] ?>" data-name="<?= htmlspecialchars($event['name'],ENT_QUOTES) ?>" data-division="<?= htmlspecialchars($event['division'],ENT_QUOTES) ?>" data-venue="<?= htmlspecialchars($event['venue'] ?? '',ENT_QUOTES) ?>" data-scheduled="<?= $event['scheduled_at'] ? date('Y-m-d\TH:i',strtotime($event['scheduled_at'])) : '' ?>">Edit</button><?php if($event['status'] !== 'final'): ?><button class="btn danger" type="button" data-event-delete="<?= $event['id'] ?>" data-name="<?= htmlspecialchars($event['name'],ENT_QUOTES) ?>">Delete</button><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<dialog class="admin-modal" id="sport-modal"><form class="modal-card" method="post"><input type="hidden" name="action" id="sport-action" value="sport"><input type="hidden" name="id" id="sport-id"><div class="modal-heading"><div><span class="form-kicker" id="sport-kicker">New sport</span><h2 id="sport-title">Add sport</h2></div><button class="modal-close" type="button" data-modal-close aria-label="Close">×</button></div><div class="field"><label>Sport name</label><input id="sport-name" name="name" required maxlength="100"></div><div class="field"><label>Icon</label><input id="sport-icon" name="icon" value="🏅" maxlength="10"></div><div class="field"><label>Sport coordinator</label><input id="sport-coordinator" name="coordinator" maxlength="150"></div><div class="field"><label>Description</label><textarea id="sport-description" name="description" maxlength="255"></textarea></div><div class="form-actions"><button class="btn" id="sport-submit">Add sport</button><button class="btn secondary" type="button" data-modal-close>Cancel</button></div></form></dialog>
<dialog class="admin-modal" id="event-modal"><form class="modal-card" method="post"><input type="hidden" name="action" id="event-action" value="event"><input type="hidden" name="id" id="event-id"><div class="modal-heading"><div><span class="form-kicker" id="event-kicker">New event</span><h2 id="event-title">Add event</h2></div><button class="modal-close" type="button" data-modal-close aria-label="Close">×</button></div><div class="field"><label>Sport</label><select id="event-sport" name="sport_id" required><option value="">Select a sport</option><?php foreach($sports as $sport): ?><option value="<?= $sport['id'] ?>"><?= htmlspecialchars($sport['name']) ?></option><?php endforeach; ?></select></div><div class="field"><label>Event name</label><input id="event-name" name="event_name" required maxlength="150" placeholder="e.g. 100m Dash"></div><div class="field"><label>Division / class</label><input id="event-division" name="division" maxlength="100" placeholder="e.g. Secondary Girls"></div><div class="modal-grid"><div class="field"><label>Category</label><select name="category"><option>Open</option><option>Elementary</option><option>Secondary</option></select></div><div class="field"><label>Gender</label><select name="gender"><option>Open</option><option>Boys</option><option>Girls</option><option>Mixed</option></select></div><div class="field"><label>Event type</label><select name="event_type"><option>Individual</option><option>Team</option><option>Relay</option></select></div><div class="field"><label>Scoring type</label><select name="scoring_type"><option value="points">Points</option><option value="time">Time</option><option value="distance">Distance</option><option value="bracket">Bracket / elimination</option><option value="judges_scores">Judges' scores</option></select></div></div><div class="modal-grid"><div class="field"><label>Max entries per area</label><input name="max_entries_per_area" type="number" min="1"></div><div class="field"><label>Max players per team</label><input name="max_players_per_team" type="number" min="1"></div></div><div class="field"><label>Venue / location</label><input id="event-venue" name="venue" maxlength="180"></div><div class="modal-grid"><div class="field"><label>Date and time</label><input id="event-scheduled" name="scheduled_at" type="datetime-local"></div><div class="field"><label>Registration deadline</label><input name="registration_deadline" type="datetime-local"></div></div><div class="field"><label>Event status</label><select name="status"><option value="draft">Draft</option><option value="open">Open for registration</option><option value="closed">Registration closed</option><option value="ongoing">Ongoing</option><option value="completed">Completed</option></select></div><div class="form-actions"><button class="btn" id="event-submit">Add event</button><button class="btn secondary" type="button" data-modal-close>Cancel</button></div></form></dialog>
<dialog class="admin-modal confirm-modal" id="delete-sport-event-modal"><form class="modal-card" method="post"><input type="hidden" name="action" id="delete-sport-event-action"><input type="hidden" name="id" id="delete-sport-event-id"><div class="modal-heading"><div><span class="form-kicker">Confirm deletion</span><h2 id="delete-sport-event-title">Delete item?</h2></div><button class="modal-close" type="button" data-modal-close aria-label="Close">×</button></div><p id="delete-sport-event-message"></p><div class="form-actions"><button class="btn danger">Delete</button><button class="btn secondary" type="button" data-modal-close>Cancel</button></div></form></dialog>
<?php page_foot(); ?>
