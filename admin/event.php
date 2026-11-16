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
if($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();
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
            if (!empty($_FILES[$medal . '_photo']['tmp_name'])) {
                $extension = strtolower(pathinfo($_FILES[$medal . '_photo']['name'], PATHINFO_EXTENSION));
                if (!in_array($extension, ['jpg','jpeg','png','webp'], true)) throw new RuntimeException('Photos must be JPG, PNG, or WebP.');
                $folder = __DIR__ . '/../assets/uploads/athletes';
                if (!is_dir($folder)) mkdir($folder, 0755, true);
                $filename = $medal . '-' . $id . '-' . time() . '.' . $extension;
                move_uploaded_file($_FILES[$medal . '_photo']['tmp_name'], $folder . '/' . $filename);
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
<div class="auth-wrap" style="max-width:650px">
<form class="form-card" method="post" enctype="multipart/form-data">
<span class="eyebrow">
<span class="title-ic"><?= sport_icon($event['sport_name'],$event['icon'],18) ?></span><?= htmlspecialchars($event['sport_name']) ?></span>
<h1>
<?= htmlspecialchars($event['name']) ?></h1>
<p>
<?= htmlspecialchars($event['division']) ?> · Publish only after the result is verified. The official tally counts Final events only.</p>
<?php if($m=flash('error')): ?><div class="notice error">
<?= htmlspecialchars($m) ?></div>
<?php endif;
?><div class="field">
<label>Event status</label>
<select name="status">
<option value="upcoming"
<?= $event['status']==='upcoming'?'selected':'' ?>>Upcoming</option>
<option value="ongoing"
<?= $event['status']==='ongoing'?'selected':'' ?>>Ongoing</option>
<option value="final"
<?= $event['status']==='final'?'selected':'' ?>>Final / Verified</option>
</select>
</div>
<?php foreach (['gold'=>'Gold', 'silver'=>'Silver', 'bronze'=>'Bronze'] as $medal=>$label): ?>
<div class="field"><label class="lbl-medal"><?= medal_icon($medal,20) ?> <?= $label ?> winner / delegation</label><select required name="<?= $medal ?>_team"><option value="">Select an Area</option><?php foreach($delegations as $delegation): ?><option value="<?= htmlspecialchars($delegation['name']) ?>" <?= ($event[$medal.'_team']??'')===$delegation['name']?'selected':'' ?>><?= htmlspecialchars($delegation['name']) ?></option><?php endforeach; ?></select></div>
<div class="field"><label><?= $label ?> player name (optional)</label><input name="<?= $medal ?>_player" value="<?= htmlspecialchars($event[$medal.'_player']??'') ?>"></div><div class="field"><label><?= $label ?> player photo</label><input type="file" name="<?= $medal ?>_photo" accept="image/png,image/jpeg,image/webp"></div>
<?php endforeach; ?>
<button class="btn">Publish medal winners</button>
<a class="btn secondary" href="<?= app_url('admin/') ?>">Cancel</a>
</form>
</div>
<?php page_foot();
?>
