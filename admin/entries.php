<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/activity.php';
require_role('area_coordinator');
$pdo=db(); $admin=current_admin(); $areaId=(int)($admin['area_id']??0);
if (!$areaId) exit('Your account has no assigned area. Ask the Meet Administrator to assign one.');

function registration_event(PDO $pdo, int $eventId): array {
    $stmt=$pdo->prepare("SELECT e.*,s.name sport_name FROM events e JOIN sports s ON s.id=e.sport_id WHERE e.id=? AND e.status='open' AND e.archived_at IS NULL"); $stmt->execute([$eventId]);
    $event=$stmt->fetch(); if (!$event) throw new RuntimeException('That event is not open for registration.');
    if ($event['registration_deadline'] && strtotime($event['registration_deadline']) < time()) throw new RuntimeException('Registration for this event has closed.');
    return $event;
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();
    try {
        $action=$_POST['action']??'';
        if ($action==='create_entry') {
            $eventId=filter_input(INPUT_POST,'event_id',FILTER_VALIDATE_INT); $event=registration_event($pdo,(int)$eventId);
            $lrn=trim($_POST['lrn']??''); $name=trim($_POST['full_name']??''); $sex=$_POST['sex']??''; $birthdate=$_POST['birthdate']??''; $grade=trim($_POST['grade_level']??''); $school=trim($_POST['school']??'');
            if ($lrn===''||$name===''||!in_array($sex,['Male','Female'],true)||!$birthdate||$school==='') throw new RuntimeException('Complete the athlete profile before saving the entry.');
            if ($event['minimum_birthdate'] && $birthdate < $event['minimum_birthdate']) throw new RuntimeException('This athlete is above the permitted age for the event.');
            if ($event['maximum_birthdate'] && $birthdate > $event['maximum_birthdate']) throw new RuntimeException('This athlete is below the permitted age for the event.');
            $limit=(int)($event['max_entries_per_area']??0);
            if ($limit) { $check=$pdo->prepare("SELECT COUNT(*) FROM event_entries WHERE event_id=? AND area_id=? AND status<>'disqualified'"); $check->execute([$eventId,$areaId]); if ((int)$check->fetchColumn()>=$limit) throw new RuntimeException('This area has reached the maximum entries for this event.'); }
            $stmt=$pdo->prepare('SELECT * FROM athletes WHERE lrn=?'); $stmt->execute([$lrn]); $athlete=$stmt->fetch();
            if ($athlete && (int)$athlete['area_id']!==$areaId) throw new RuntimeException('This LRN already belongs to another area and cannot be entered here.');
            if ($athlete) { $athleteId=(int)$athlete['id']; $pdo->prepare('UPDATE athletes SET full_name=?,sex=?,birthdate=?,grade_level=?,school=? WHERE id=?')->execute([$name,$sex,$birthdate,$grade?:null,$school,$athleteId]); }
            else { $pdo->prepare('INSERT INTO athletes(lrn,full_name,sex,birthdate,grade_level,school,area_id) VALUES(?,?,?,?,?,?,?)')->execute([$lrn,$name,$sex,$birthdate,$grade?:null,$school,$areaId]); $athleteId=(int)$pdo->lastInsertId(); }
            $pdo->prepare('INSERT INTO event_entries(event_id,athlete_id,area_id,coach_name,coach_school,coach_position,coach_contact,created_by) VALUES(?,?,?,?,?,?,?,?)')->execute([$eventId,$athleteId,$areaId,trim($_POST['coach_name']??'')?:null,trim($_POST['coach_school']??'')?:null,trim($_POST['coach_position']??'')?:null,trim($_POST['coach_contact']??'')?:null,$admin['id']]);
            log_activity('created_event_entry','event_entry',(int)$pdo->lastInsertId(),'Created draft entry for '.$name.'.'); flash('success','Draft entry saved. Review it, then submit it before the deadline.');
        } elseif ($action==='submit_entry') {
            $id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT); $stmt=$pdo->prepare('SELECT ee.*,e.registration_deadline,e.status event_status FROM event_entries ee JOIN events e ON e.id=ee.event_id WHERE ee.id=? AND ee.area_id=?'); $stmt->execute([$id,$areaId]); $entry=$stmt->fetch();
            if (!$entry||$entry['status']!=='draft'||$entry['event_status']!=='open'||($entry['registration_deadline']&&strtotime($entry['registration_deadline'])<time())) throw new RuntimeException('This draft can no longer be submitted.');
            $pdo->prepare("UPDATE event_entries SET status='submitted',submitted_at=NOW() WHERE id=?")->execute([$id]); log_activity('submitted_event_entry','event_entry',$id,'Submitted an event entry.'); flash('success','Entry submitted for Sports Administrator review.');
        }
    } catch(Throwable $e) { flash('error',$e->getMessage()); }
    header('Location: entries.php'); exit;
}
$open=$pdo->prepare("SELECT e.*,s.name sport_name FROM events e JOIN sports s ON s.id=e.sport_id WHERE e.status='open' AND e.archived_at IS NULL AND (e.registration_deadline IS NULL OR e.registration_deadline>=NOW()) ORDER BY e.registration_deadline,e.scheduled_at,e.name"); $open->execute(); $openEvents=$open->fetchAll();
$entries=$pdo->prepare('SELECT ee.*,a.full_name,a.lrn,e.name event_name,e.registration_deadline,s.name sport_name FROM event_entries ee JOIN athletes a ON a.id=ee.athlete_id JOIN events e ON e.id=ee.event_id JOIN sports s ON s.id=e.sport_id WHERE ee.area_id=? ORDER BY ee.updated_at DESC'); $entries->execute([$areaId]); $entries=$entries->fetchAll();
require_once __DIR__ . '/../includes/layout.php'; page_head('My Event Entries');
?>
<div class="admin-hero"><div><span class="eyebrow">Area Coordinator desk</span><h1>My Event Entries</h1><p class="admin-subtitle">Register athletes per event, save drafts, and submit complete entries before the deadline.</p></div></div>
<?php if($m=flash('error')):?><div class="notice error"><?= htmlspecialchars($m) ?></div><?php endif; ?><?php if($m=flash('success')):?><div class="notice success"><?= htmlspecialchars($m) ?></div><?php endif; ?>
<section class="account-directory"><div class="directory-heading"><div><span class="form-kicker">Registration</span><h2>Open events</h2></div></div><div class="table-card admin-users-table"><table class="score-table"><thead><tr><th>Sport / event</th><th>Category</th><th>Venue</th><th>Deadline</th><th></th></tr></thead><tbody><?php foreach($openEvents as $event): ?><tr><td><strong><?= htmlspecialchars($event['sport_name']) ?></strong><br><small><?= htmlspecialchars($event['name']) ?></small></td><td><?= htmlspecialchars($event['category'].' · '.$event['gender'].' · '.$event['event_type']) ?></td><td><?= htmlspecialchars($event['venue']?:'Venue TBA') ?></td><td><?= $event['registration_deadline']?date('M j, Y · g:i A',strtotime($event['registration_deadline'])):'Not set' ?></td><td><button class="btn" type="button" data-entry-modal data-event="<?= $event['id'] ?>" data-label="<?= htmlspecialchars($event['sport_name'].' · '.$event['name'],ENT_QUOTES) ?>">Register athlete</button></td></tr><?php endforeach; ?><?php if(!$openEvents): ?><tr><td colspan="5" class="empty">No events are currently open for registration.</td></tr><?php endif; ?></tbody></table></div></section>
<section class="account-directory"><div class="directory-heading"><div><span class="form-kicker">Your delegation</span><h2>Saved entries</h2></div></div><div class="table-card admin-users-table"><table class="score-table"><thead><tr><th>Athlete</th><th>Event</th><th>Status</th><th>Remarks</th><th>Action</th></tr></thead><tbody><?php foreach($entries as $entry): ?><tr><td><strong><?= htmlspecialchars($entry['full_name']) ?></strong><br><small><?= htmlspecialchars($entry['lrn']) ?></small></td><td><?= htmlspecialchars($entry['sport_name']) ?><br><small><?= htmlspecialchars($entry['event_name']) ?></small></td><td><span class="status <?= $entry['status']==='approved'?'final':($entry['status']==='submitted'?'ongoing':'upcoming') ?>"><?= htmlspecialchars($entry['status']) ?></span></td><td><?= htmlspecialchars($entry['remarks']?:'—') ?></td><td><?php if($entry['status']==='draft'): ?><form method="post"><input type="hidden" name="action" value="submit_entry"><input type="hidden" name="id" value="<?= $entry['id'] ?>"><button class="btn secondary">Submit</button></form><?php else: ?>Locked<?php endif; ?></td></tr><?php endforeach; ?><?php if(!$entries): ?><tr><td colspan="5" class="empty">No entries saved yet.</td></tr><?php endif; ?></tbody></table></div></section>
<dialog class="admin-modal" id="entry-modal"><form class="modal-card" method="post"><input type="hidden" name="action" value="create_entry"><input type="hidden" name="event_id" id="entry-event-id"><div class="modal-heading"><div><span class="form-kicker">New draft entry</span><h2 id="entry-event-label">Register athlete</h2></div><button class="modal-close" type="button" data-modal-close>×</button></div><div class="modal-grid"><div class="field"><label>LRN</label><input name="lrn" required maxlength="30"></div><div class="field"><label>Full name</label><input name="full_name" required maxlength="150"></div><div class="field"><label>Sex</label><select name="sex" required><option value="">Select</option><option>Male</option><option>Female</option></select></div><div class="field"><label>Birthdate</label><input type="date" name="birthdate" required></div><div class="field"><label>Grade level</label><input name="grade_level" maxlength="50"></div><div class="field"><label>School</label><input name="school" required maxlength="180"></div></div><div class="modal-grid"><div class="field"><label>Coach name</label><input name="coach_name"></div><div class="field"><label>Coach contact</label><input name="coach_contact"></div><div class="field"><label>Coach school</label><input name="coach_school"></div><div class="field"><label>Coach position</label><input name="coach_position"></div></div><div class="form-actions"><button class="btn">Save draft</button><button class="btn secondary" type="button" data-modal-close>Cancel</button></div></form></dialog>
<?php page_foot(); ?>
