<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/sheets.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../admin/_layout.php';

startSecureSession();
requireAdmin();

$filter = $_GET['status'] ?? 'all';
$all    = readSheet(SHEET_SUBMISSIONS);

$rows = ($filter === 'all')
    ? $all
    : array_values(array_filter($all, fn($r) => $r['status'] === $filter));

$catLabel = ['general'=>'一般大眾','streamer'=>'直播主','student'=>'在校學生'];

adminHeader('審核管理');
?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem">
    <h3>報名列表</h3>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
      <?php foreach (['all'=>'全部','pending'=>'待審','approved'=>'通過','rejected'=>'拒絕','hidden'=>'下架'] as $val=>$label): ?>
        <a href="?status=<?= $val ?>" class="btn <?= $filter===$val?'btn-green':'btn-gray' ?>"><?= $label ?></a>
      <?php endforeach ?>
    </div>
  </div>

  <?php if (empty($rows)): ?>
    <p style="color:#666">沒有符合的報名記錄。</p>
  <?php else: ?>
  <div style="overflow-x:auto">
  <table>
    <thead>
      <tr>
        <th>組名</th>
        <th>報名時間</th>
        <th>參賽身分</th>
        <th>影片</th>
        <th>狀態</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['team_name'], ENT_QUOTES) ?></td>
        <td><?= htmlspecialchars($r['submitted_at'], ENT_QUOTES) ?></td>
        <td><?= $catLabel[$r['category']] ?? $r['category'] ?></td>
        <td><a href="<?= htmlspecialchars($r['video_url'], ENT_QUOTES) ?>" target="_blank">YouTube ↗</a></td>
        <td><span class="badge badge-<?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
        <td><a href="/修法王/admin/review_detail.php?id=<?= urlencode($r['id']) ?>" class="btn btn-green">審核</a></td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
  </div>
  <?php endif ?>
</div>
<?php adminFooter() ?>
