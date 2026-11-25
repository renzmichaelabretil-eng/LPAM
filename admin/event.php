<?php require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/medals.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$admin=current_admin();
$pdo=db();
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
$stmt=$pdo->prepare('SELECT e.*,s.name sport_name,s.icon,r.gold_team,r.silver_team,r.bronze_team,r.gold_player,r.silver_player,r.bronze_player,r.gold_photo,r.silver_photo,r.bronze_photo FROM events e JOIN sports s ON s.id=e.sport_id LEFT JOIN results r ON r.event_id=e.id WHERE e.id=?');
$stmt->execute([$id]);
$event=$stmt->fetch();
if(!$event || ($admin['role']==='sport_admin' && (int)$event['sport_id']!==(int)$admin['sport_id'])) {
    http_response_code(403);
    exit('You do not have permission to edit this event.');
}
$delegations=delegations();
$delegationNames=array_column($delegations, 'name');
$isVerified = $event['status'] === 'final' && !empty($event['gold_team']);
$correctionMode = $isVerified && ($_GET['correct'] ?? '') === '1';
if($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();
    if ($isVerified && ($_POST['confirm_verified_correction'] ?? '') !== '1') {
        flash('error', 'Verified results are locked. Choose “Correct verified result” before saving a correction.');
        header('Location: event.php?id='.$id);
        exit;
    }
    $gold=trim($_POST['gold_team']??'');
    $silver=trim($_POST['silver_team']??'');
    $bronze=trim($_POST['bronze_team']??'');
    $goldPlayer=trim($_POST['gold_player']??'');
    $silverPlayer=trim($_POST['silver_player']??'');
    $bronzePlayer=trim($_POST['bronze_player']??'');
    $status=in_array($_POST['status']??'', ['upcoming','ongoing','final'],true)?$_POST['status']:'upcoming';
    if($gold===''||$silver===''||$bronze==='') {
        flash('error','Gold, silver, and bronze winners are all required before publishing.');
        header('Location: event.php?id='.$id);
        exit;
    }
    if ($status !== 'final') {
        flash('error','Set the event to Final / Verified before its medals can affect the official tally.');
        header('Location: event.php?id='.$id);
        exit;
    }
    if (!in_array($gold, $delegationNames, true) || !in_array($silver, $delegationNames, true) || !in_array($bronze, $delegationNames, true)) {
        flash('error','Choose winners from the official Area delegation list.');
        header('Location: event.php?id='.$id);
        exit;
    }
    if (count(array_unique([$gold, $silver, $bronze])) !== 3) {
        flash('error','A delegation can receive only one medal for an event.');
        header('Location: event.php?id='.$id);
        exit;
    }
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE events SET status=? WHERE id=?')->execute([$status,$id]);
        $photos=[];
        foreach (['gold','silver','bronze'] as $medal) {
            $photos[$medal] = $event[$medal . '_photo'] ?? null;
            if (!empty($_POST[$medal . '_remove_photo'])) {
                $photos[$medal] = null;
            }
            $upload = null;
            foreach (['camera', 'photo'] as $source) {
                if (!empty($_FILES[$medal . '_' . $source]['tmp_name'])) {
                    $upload = $_FILES[$medal . '_' . $source];
                    break;
                }
            }
            if ($upload) {
                $extension = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
                if (!in_array($extension, ['jpg','jpeg','png','webp'], true)) throw new RuntimeException('Photos must be JPG, PNG, or WebP.');
                $folder = __DIR__ . '/../assets/uploads/athletes';
                if (!is_dir($folder)) mkdir($folder, 0755, true);
                $filename = $medal . '-' . $id . '-' . time() . '.' . $extension;
                if (!move_uploaded_file($upload['tmp_name'], $folder . '/' . $filename)) throw new RuntimeException('Could not save the uploaded photo. Please try again.');
                $photos[$medal] = 'assets/uploads/athletes/' . $filename;
            }
        }
        $previous = $event['gold_team'] ? ['gold_team'=>$event['gold_team'], 'silver_team'=>$event['silver_team'], 'bronze_team'=>$event['bronze_team'], 'gold_player'=>$event['gold_player'], 'silver_player'=>$event['silver_player'], 'bronze_player'=>$event['bronze_player']] : null;
        $current = ['gold_team'=>$gold, 'silver_team'=>$silver, 'bronze_team'=>$bronze, 'gold_player'=>$goldPlayer, 'silver_player'=>$silverPlayer, 'bronze_player'=>$bronzePlayer];
        $pdo->prepare('INSERT INTO results(event_id,gold_team,silver_team,bronze_team,gold_player,silver_player,bronze_player,gold_photo,silver_photo,bronze_photo,submitted_by) VALUES(?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE gold_team=VALUES(gold_team),silver_team=VALUES(silver_team),bronze_team=VALUES(bronze_team),gold_player=VALUES(gold_player),silver_player=VALUES(silver_player),bronze_player=VALUES(bronze_player),gold_photo=VALUES(gold_photo),silver_photo=VALUES(silver_photo),bronze_photo=VALUES(bronze_photo),submitted_by=VALUES(submitted_by)')->execute([$id,$gold,$silver,$bronze,$goldPlayer,$silverPlayer,$bronzePlayer,$photos['gold'],$photos['silver'],$photos['bronze'],$admin['id']]);
        $pdo->prepare('INSERT INTO result_audits(event_id,admin_id,action,previous_result,new_result) VALUES(?,?,?,?,?)')->execute([$id,$admin['id'],$previous ? 'corrected' : 'published',$previous ? json_encode($previous) : null,json_encode($current)]);
        $pdo->commit();
        flash('success','Medal winners published to the public tracker.');
        header('Location: index.php');
        exit;
    }
    catch(Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
require_once __DIR__ . '/../includes/layout.php';
page_head('Enter Winners');
?>
<div class="admin-result-wrap">
<?php if ($isVerified && !$correctionMode): ?>
<section class="form-card verified-result-lock"><span class="eyebrow"><span class="title-ic"><?= sport_icon($event['sport_name'],$event['icon'],18) ?></span><?= htmlspecialchars($event['sport_name']) ?></span><h1><?= htmlspecialchars($event['name']) ?></h1><p>This result is <strong>Final / Verified</strong> and is locked to prevent accidental changes. It is already included in the public medal tally.</p><?php if($m=flash('error')): ?><div class="notice error"><?= htmlspecialchars($m) ?></div><?php endif; ?><div class="verified-medals"><div class="gold"><?= medal_icon('gold',21) ?><b>Gold</b><span><?= htmlspecialchars($event['gold_team']) ?><?= $event['gold_player'] ? ' · '.htmlspecialchars($event['gold_player']) : '' ?></span></div><div class="silver"><?= medal_icon('silver',21) ?><b>Silver</b><span><?= htmlspecialchars($event['silver_team']) ?><?= $event['silver_player'] ? ' · '.htmlspecialchars($event['silver_player']) : '' ?></span></div><div class="bronze"><?= medal_icon('bronze',21) ?><b>Bronze</b><span><?= htmlspecialchars($event['bronze_team']) ?><?= $event['bronze_player'] ? ' · '.htmlspecialchars($event['bronze_player']) : '' ?></span></div></div><div class="form-actions"><a class="btn" data-confirm="Correct this verified result? Use this only to fix a mistake such as a misspelled winner or incorrect photo." href="<?= app_url('admin/event.php') ?>?id=<?= $id ?>&correct=1">Correct verified result</a><a class="btn secondary" href="<?= app_url('admin/') ?>">Back to results desk</a></div></section>
<?php else: ?>
<form class="form-card result-entry-form" method="post" enctype="multipart/form-data">
<span class="eyebrow">
<span class="title-ic"><?= sport_icon($event['sport_name'],$event['icon'],18) ?></span><?= htmlspecialchars($event['sport_name']) ?></span>
<h1>
<?= htmlspecialchars($event['name']) ?></h1>
<p>
<?= htmlspecialchars($event['division']) ?> · <?= $event['gold_team'] ? 'Edit the saved result or replace a photo, then save the correction.' : 'Publish only after the result is verified. The official tally counts Final events only.' ?></p>
<?php if($m=flash('error')): ?><div class="notice error">
<?= htmlspecialchars($m) ?></div>
<?php endif;
?><?php if ($isVerified): ?><input type="hidden" name="confirm_verified_correction" value="1"><input type="hidden" name="status" value="final"><div class="verified-correction-note"><b>Correction mode</b><span>This result stays Final / Verified. Review the medal panels carefully, then save the correction.</span></div><?php else: ?><div class="result-status field">
<label>Event status</label>
<select name="status">
<option value="upcoming"
<?= $event['status']==='upcoming'?'selected':'' ?>>Upcoming</option>
<option value="ongoing"
<?= $event['status']==='ongoing'?'selected':'' ?>>Ongoing</option>
<option value="final"
<?= $event['status']==='final'?'selected':'' ?>>Final / Verified</option>
</select>
</div><?php endif; ?>
<div class="medal-entry-grid"><?php foreach (['gold'=>'Gold', 'silver'=>'Silver', 'bronze'=>'Bronze'] as $medal=>$label): ?>
<section class="medal-entry-card <?= $medal ?>"><header><span><?= medal_icon($medal,28) ?></span><div><b><?= $label ?> medal</b><small>Winner details</small></div></header><div class="field"><label><?= $label ?> winner / delegation</label><select required name="<?= $medal ?>_team"><option value="">Select an Area</option><?php foreach($delegations as $delegation): ?><option value="<?= htmlspecialchars($delegation['name']) ?>" <?= ($event[$medal.'_team']??'')===$delegation['name']?'selected':'' ?>><?= htmlspecialchars($delegation['name']) ?></option><?php endforeach; ?></select></div><div class="field"><label>Player name <small>(optional)</small></label><input name="<?= $medal ?>_player" value="<?= htmlspecialchars($event[$medal.'_player']??'') ?>" placeholder="Athlete name"></div><div class="photo-capture"><label>Winner photo</label><?php if ($event[$medal.'_photo']): ?><div class="current-winner-photo"><img src="<?= app_url($event[$medal.'_photo']) ?>" alt="Current <?= strtolower($label) ?> winner photo"><span>Current photo</span></div><?php endif; ?><input class="visually-hidden" id="<?= $medal ?>-camera" type="file" name="<?= $medal ?>_camera" accept="image/png,image/jpeg,image/webp" capture="environment"><input class="visually-hidden" id="<?= $medal ?>-photo" type="file" name="<?= $medal ?>_photo" accept="image/png,image/jpeg,image/webp"><div class="photo-actions"><label class="btn" for="<?= $medal ?>-camera">📷 Camera</label><label class="btn secondary" for="<?= $medal ?>-photo">Choose photo</label></div><small>Use Camera on a phone, or select an existing image.</small><?php if ($event[$medal.'_photo']): ?><label class="remove-photo"><input type="checkbox" name="<?= $medal ?>_remove_photo" value="1"> Remove current photo</label><?php endif; ?></div></section>
<?php endforeach; ?></div>
<div class="form-actions result-actions"><button class="btn"><?= $event['gold_team'] ? 'Save result changes' : 'Publish medal winners' ?></button><a class="btn secondary" href="<?= app_url('admin/') ?>">Back to results desk</a></div>
</form>
<?php endif; ?>
</div>
<?php page_foot();
?>
