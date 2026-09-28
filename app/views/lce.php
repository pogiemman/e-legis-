<?php
$role = $currentUser['role'] ?? '';
$lceRecords = array_values(array_filter($legislatives ?? [], static function (array $item): bool {
    return in_array($item['routing_status'] ?? '', ['Sent to LCE', 'Archived for Local Download', 'Returned to Council - Vetoed'], true)
        || in_array($item['lce_action'] ?? '', ['Pending LCE Review', 'LCE Approved', 'Vetoed'], true);
}));
?>
<div class="page-section">
  <div class="section-heading">
    <h2>LCE Legislative Review</h2>
    <p>Review legislative records, approve signed versions for filing and scan, or record a veto.</p>
  </div>
  <section class="card">
    <div class="card-head">
      <div>
        <div class="card-title">Local Legislative Archive</div>
        <div class="field-note">Files are stored on this server and remain downloadable when internet access is unavailable.</div>
      </div>
      <span class="badge b-approved"><?= count($lceRecords) ?> record<?= count($lceRecords) === 1 ? '' : 's' ?></span>
    </div>
    <div class="card-body" style="display:grid;gap:12px;">
      <?php if (empty($lceRecords)): ?>
        <div class="empty-state">No legislative matters have been sent to the LCE.</div>
      <?php else: ?>
        <?php foreach ($lceRecords as $record): ?>
          <article style="border:1px solid #e5e7eb;border-radius:10px;padding:14px;background:#fff;">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
              <div>
                <strong><?= esc($record['title'] ?? 'Untitled legislative matter') ?></strong>
                <div class="field-note" style="margin-top:5px;">Status: <?= esc($record['lce_action'] ?? 'Pending LCE Review') ?> · <?= esc($record['routing_status'] ?? '') ?></div>
                <?php if (!empty($record['admin_decision_notes'])): ?><div class="field-note" style="margin-top:5px;">Admin note: <?= esc($record['admin_decision_notes']) ?></div><?php endif; ?>
              </div>
              <span class="badge <?= badgeClass($record['status'] ?? 'draft') ?>"><?= esc($record['status'] ?? 'draft') ?></span>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
              <?php if (!empty($record['file_path'])): ?>
                <a class="btn btn-secondary btn-xs" href="download.php?file=<?= esc(basename((string)$record['file_path'])) ?>" target="_blank" rel="noopener">Download Local File</a>
              <?php else: ?>
                <span class="field-note">No uploaded file is attached.</span>
              <?php endif; ?>
              <?php if (in_array($role, ['lce', 'mayor'], true) && ($record['lce_action'] ?? '') === 'Pending LCE Review'): ?>
                <form method="post" action="index.php?page=lce"><input type="hidden" name="action" value="process_lce_decision" /><input type="hidden" name="legislative_id" value="<?= esc($record['id']) ?>" /><input type="hidden" name="decision" value="approved" /><button class="btn btn-success btn-xs" type="submit"><?= ($record['workflow_status'] ?? '') === 'LCE Approval' ? 'Approve for Filing / Scan' : 'Approve & Store Archive' ?></button></form>
                <form method="post" action="index.php?page=lce" onsubmit="return confirm('Record an LCE veto for this legislative matter?')"><input type="hidden" name="action" value="process_lce_decision" /><input type="hidden" name="legislative_id" value="<?= esc($record['id']) ?>" /><input type="hidden" name="decision" value="vetoed" /><button class="btn btn-danger btn-xs" type="submit">Veto</button></form>
              <?php endif; ?>
              <?php if (($record['lce_action'] ?? '') === 'LCE Approved'): ?><span class="field-note" style="color:#047857;font-weight:600;"><?= ($record['workflow_status'] ?? '') === 'Filing/Scan' ? 'Approved; ready for filing and scan' : 'Stored locally for offline download' ?></span><?php elseif (($record['lce_action'] ?? '') === 'Vetoed'): ?><span class="field-note" style="color:#b91c1c;font-weight:600;">Submitter notified of LCE veto</span><?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>
</div>
