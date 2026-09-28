<?php
/**
 * @var string $page
 * @var array $currentUser
 * @var array $legislatives
 * @var array|null $editItem
 * @var string|null $search
 * @var array $flashes
 * @var array $data
 */
if (!function_exists('esc')) {
  function esc($value): string
  {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  }
}

$councilRecipients = array_values(array_filter($data['users'] ?? [], fn ($user) => ($user['role'] ?? '') === 'council members' && ($user['status'] ?? 'active') === 'active'));
$agendaSourceIds = array_flip(array_filter(array_column($data['agendas'] ?? [], 'source_legislative_id')));
$pendingBills = count(array_filter($data['legislatives'] ?? [], function (array $item) use ($agendaSourceIds): bool {
  $type = strtolower((string)($item['type'] ?? ''));
  return (str_contains($type, 'bill') || str_contains($type, 'resolution'))
    && in_array($item['status'] ?? '', ['submitted_to_admin', 'approved'], true)
    && (($item['status'] ?? '') !== 'approved' || !isset($agendaSourceIds[$item['id'] ?? '']));
}));
$pendingQueue = ($currentUser['role'] ?? '') === 'admin' && ($_GET['bill_status'] ?? '') === 'pending';
$isCouncilView = ($currentUser['role'] ?? '') === 'council members';
$sentAgendas = array_values(array_filter($agendas ?? [], fn (array $agenda): bool => ($agenda['approval_status'] ?? '') === 'published_to_council' || !empty($agenda['sent_to_council_at'])));
?>
<div class="page-section" style="padding:0;">
  <div class="section-actions" style="margin-bottom:12px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:flex-start;">
    <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
      <span class="badge b-published" style="padding:8px 10px;">Pending bills / resolutions: <?= esc($pendingBills) ?></span>
      <?php if ($pendingQueue): ?><a class="btn btn-ghost" href="?page=legislative-bills&bill_status=all">View all bills</a><?php endif; ?>
    <?php endif; ?>
    <form method="get" action="index.php" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
      <input type="hidden" name="page" value="legislative-bills" />
      <input type="text" name="hash_search" placeholder="Search control number or SHA-256" value="<?= esc($hashSearch ?? '') ?>" style="max-width:320px;min-width:220px;" />
      <button class="btn btn-secondary" type="submit">🔍 Search hash</button>
      <?php if (($hashSearch ?? '') !== ''): ?>
        <a class="btn btn-ghost" href="?page=legislative-bills">Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="legislative-grid" style="grid-template-columns:1fr;">
    <div class="legislative-list">
      <?php if (empty($legislatives)): ?>
        <div class="empty-state"><?= $pendingQueue ? 'No submitted bills or resolutions are waiting for review.' : 'No bill records yet. Create one using the form.' ?></div>
      <?php else: ?>
        <?php foreach ($legislatives as $item): ?>
          <div class="list-row">
            <div><?= esc($item['title']) ?></div>
            <div><?= esc($item['type']) ?></div>
            <div><span class="badge <?= badgeClass($item['status']) ?>"><?= esc(ucfirst(str_replace('_', ' ', $item['status']))) ?></span></div>
            <div><?= esc($item['createdByName'] ?? 'Unknown') ?></div>
            <div class="created-cell">
              <?= esc(fmtDateTime($item['createdAt'] ?? $item['updatedAt'] ?? '')) ?>
            </div>
            <div>
              <?php if ($pendingQueue): ?>
                <a class="link" href="?page=legislative-admin&edit=<?= esc($item['id']) ?>&type=legislative#review-content">Review</a>
              <?php else: ?>
                <a class="link" href="?page=legislative-bills&edit=<?= esc($item['id']) ?>&type=legislative">Edit</a>
              <?php endif; ?>
              <?php if (($currentUser['role'] ?? '') === 'council members' && ($item['createdById'] ?? '') === ($currentUser['id'] ?? '')): ?>
                <?php if (in_array(($item['status'] ?? ''), ['submitted_to_admin', 'agenda_created'], true)): ?>
                  <span class="link" style="color:#64748b">✓ Submitted to Admin</span>
                <?php else: ?>
                  <form method="post" style="display:inline;margin:0;padding:0" onsubmit="return confirm('Submit this bill to the admin for review?')">
                    <input type="hidden" name="action" value="submit_legislative_to_admin" />
                    <input type="hidden" name="legislative_id" value="<?= esc($item['id']) ?>" />
                    <button class="link" style="background:none;border:none;color:#2563eb;cursor:pointer;font-weight:600" type="submit">Submit to Admin</button>
                  </form>
                <?php endif; ?>
              <?php endif; ?>
              <?php if (($currentUser['role'] ?? '') === 'admin' && ($item['status'] ?? '') !== 'approved'): ?>
                <form method="post" enctype="multipart/form-data" style="display:inline-flex;gap:5px;align-items:center;margin:0;padding:0;flex-wrap:wrap" onsubmit="return confirm('Approve this bill or resolution and add it to the selected session agenda?')">
                  <input type="hidden" name="action" value="approve_legislative" />
                  <input type="hidden" name="legislative_id" value="<?= esc($item['id']) ?>" />
                  <select name="session_id" required style="max-width:145px;font-size:11px;padding:4px;">
                    <option value="">Select session</option>
                    <?php foreach (($data['sessions'] ?? []) as $session): ?>
                      <option value="<?= esc($session['id']) ?>"><?= esc($session['title']) ?> · <?= esc($session['date']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="file" name="agenda_file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp,.txt" style="max-width:170px;font-size:11px;" />
                  <button class="btn btn-success btn-xs" type="submit">✓ Approve & Add Agenda</button>
                </form>
                <form method="post" style="display:inline-flex;gap:5px;align-items:center;margin:6px 0 0 0;padding:0;flex-wrap:wrap" onsubmit="return confirm('Disapprove this submitted legislative matter and return it for revision?')">
                  <input type="hidden" name="action" value="disapprove_legislative" />
                  <input type="hidden" name="legislative_id" value="<?= esc($item['id']) ?>" />
                  <input type="text" name="disapproval_reason" required placeholder="Reason for return" style="max-width:190px;font-size:11px;padding:5px;" />
                  <button class="btn btn-danger btn-xs" type="submit">✕ Disapprove</button>
                </form>
              <?php else: ?>
                <span class="link link-success" style="color:#059669;font-weight:600">✓ Approved</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <?php if ($isCouncilView): ?>
      <div class="card" style="margin-bottom:18px;border:1px solid #dbeafe;">
        <div class="card-head"><span class="card-title">📎 Agendas Sent to Council</span></div>
        <div class="card-body" style="padding:0;">
          <?php if (empty($sentAgendas)): ?>
            <div class="empty-state" style="padding:24px;text-align:center;">No agendas have been sent to you yet.</div>
          <?php else: ?>
            <div style="display:grid;gap:0;">
              <?php foreach ($sentAgendas as $agenda): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-bottom:1px solid #eef2f7;flex-wrap:wrap;">
                  <div>
                    <strong><?= esc($agenda['title'] ?? 'Untitled agenda') ?></strong>
                    <div class="field-note"><?= esc($agenda['file_name'] ?? '') ?> · Sent <?= esc(fmtDate($agenda['sent_to_council_at'] ?? $agenda['uploaded_at'] ?? '')) ?></div>
                  </div>
                  <?php if (!empty($agenda['file_path'])): ?>
                    <a class="btn btn-secondary btn-xs" href="download.php?file=<?= esc($agenda['file_path']) ?>" target="_blank">⬇️ View Agenda</a>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!$pendingQueue && !$isCouncilView): ?>
    <div class="legislative-form-card" style="padding:14px;">
      <h3 style="margin-top:0;"><?= $editItem ? 'Edit Bill' : 'Create Bill' ?></h3>
      <div class="field-note" style="margin-bottom:10px;">Timestamp: <?= esc(fmtDateTime($editItem['createdAt'] ?? $editItem['updatedAt'] ?? date(DATE_ATOM))) ?></div>

      <form method="post" action="index.php?page=legislative-bills" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_legislative" />
        <input type="hidden" name="page" value="legislative-bills" />
        <input type="hidden" name="id" value="<?= esc($editItem['id'] ?? '') ?>" />
        <label>Title</label>
        <input type="text" name="title" value="<?= esc($editItem['title'] ?? '') ?>" placeholder="Leave blank to detect from the document" />
        <label>Author</label>
        <input type="text" name="author_name" value="<?= esc($editItem['author_name'] ?? '') ?>" placeholder="Optional author" />
        <label>Sponsor</label>
        <input type="text" name="sponsor_name" value="<?= esc($editItem['sponsor_name'] ?? '') ?>" placeholder="Optional sponsor" />
        <label>Type</label>
        <select name="type" data-legislative-type>
          <option value="">Auto detect from title and document</option>
          <option value="Bill"<?= ($editItem['type'] ?? '') === 'Bill' ? ' selected' : '' ?>>Bill</option>
          <option value="Resolution"<?= ($editItem['type'] ?? '') === 'Resolution' ? ' selected' : '' ?>>Resolution</option>
        </select>
        <label>Status</label>
        <select name="status">
          <option value="draft"<?= isset($editItem['status']) && $editItem['status'] === 'draft' ? ' selected' : '' ?>>Draft</option>
          <option value="review"<?= isset($editItem['status']) && $editItem['status'] === 'review' ? ' selected' : '' ?>>Review</option>
          <option value="approved"<?= isset($editItem['status']) && $editItem['status'] === 'approved' ? ' selected' : '' ?>>Approved</option>
          <option value="approved"<?= isset($editItem['status']) && $editItem['status'] === 'approved' ? ' selected' : '' ?>>Approved</option>
        </select>
        <label>Document Content</label>
        <div class="legislative-editor-toolbar">
          <button type="button" onclick="formatLegislative('bold')"><strong>B</strong></button>
          <button type="button" onclick="formatLegislative('italic')"><em>I</em></button>
          <button type="button" onclick="formatLegislative('underline')"><u>U</u></button>
          <button type="button" onclick="formatLegislative('insertUnorderedList')">•</button>
          <button type="button" onclick="formatLegislative('insertOrderedList')">1.</button>
        </div>
        <?php $editorContent = (string)($editItem['content'] ?? ''); $hasEditorMarkup = preg_match('/<\/?(p|strong|em|u|h[1-6]|br)[ >]/i', $editorContent); ?>
        <div id="legislative-editor" class="legislative-editor" contenteditable="true"><?= $hasEditorMarkup ? strip_tags($editorContent, '<p><strong><em><u><h1><h2><h3><h4><h5><h6><br>') : nl2br(esc($editorContent)) ?></div>
        <textarea id="legislative-content" name="content" hidden><?= esc($editItem['content'] ?? '') ?></textarea>
        <label>Optional Attachment</label>
        <input type="file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.rtf,.jpg,.jpeg,.png" />
        <button class="btn btn-ghost" type="button" onclick="importLegislativeText(this)">Import uploaded text into editor</button>
        <?php if (!empty($editItem['file_name'])): ?>
          <div class="field-note">Current attachment: <a class="link" href="download.php?file=<?= esc($editItem['file_path'] ?? '') ?>&inline=1" target="_blank" rel="noopener">View in system</a></div>
        <?php endif; ?>
        <div class="form-actions" style="display:flex;gap:10px;flex-wrap:wrap;">
          <button class="btn btn-primary" type="submit">💾 Save Bill</button>
          <a class="btn btn-ghost" href="?page=legislative-bills">Cancel</a>
        </div>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>

