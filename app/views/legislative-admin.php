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
$referralItems = [];
$postSessionItems = [];
$workflowSteps = ['Receiving', 'Review', 'Agenda', 'Approval', 'Distribution', 'SP Session', 'Committee/Referral', 'Final Draft', 'Signatures', 'LCE Approval', 'Filing/Scan', 'Archive'];
$workflowActions = [
  'Receiving' => ['review' => 'Start Review'],
  'Review' => ['agenda' => 'Send to Agenda', 'revision' => 'Prepare Final Draft'],
  'Agenda' => ['approval' => 'Send for Approval', 'revision' => 'Prepare Final Draft'],
  'Approval' => ['distribution' => 'Approve for Distribution', 'revision' => 'Return for Revision'],
  'SP Session' => ['final_draft' => 'Complete Session Review'],
  'Committee/Referral' => ['final_draft' => 'Complete Final Draft', 'revision' => 'Request Revision'],
  'Final Draft' => ['signature' => 'Send for Signatures'],
  'LCE Approval' => ['revision' => 'Return for Revision'],
  'Filing/Scan' => ['archive' => 'Complete Filing and Archive'],
];
?>
<div class="page-section">
  <div class="section-heading">
    <h2>Legislative Records</h2>
    <p>Use this section to create or update legislative proposals and monitor their status.</p>
    <div class="section-actions" style="margin-top:12px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:flex-start;">
      <form method="get" action="index.php" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <input type="hidden" name="page" value="legislative-admin" />
        <input type="text" name="hash_search" placeholder="Search control number, title, or SHA-256" value="<?= esc($hashSearch ?? '') ?>" style="max-width:320px;min-width:220px;" />
        <button class="btn btn-secondary" type="submit">Search records</button>
        <?php if (($hashSearch ?? '') !== ''): ?>
          <a class="btn btn-ghost" href="?page=legislative-admin">Clear</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <?php if (isset($analytics)): ?>
    <div style="display:flex;gap:12px;margin-top:12px;margin-bottom:12px;align-items:center"> 
      <div style="padding:12px;border-radius:8px;background:#111827;color:#fff;flex:1"> 
        <div style="font-size:12px">Total legislative items</div>
        <div id="analytics-total" style="font-weight:700;font-size:20px"><?= esc($analytics['total'] ?? 0) ?></div>
      </div>
      <div style="padding:12px;border-radius:8px;background:#059669;color:#fff;flex:1"> 
        <div style="font-size:12px">Approved</div>
        <div id="analytics-approved" style="font-weight:700;font-size:20px"><?= esc($analytics['approved'] ?? 0) ?></div>
      </div>
      <div style="padding:12px;border-radius:8px;background:#3b82f6;color:#fff;flex:1"> 
        <div style="font-size:12px">% Approved</div>
        <div id="analytics-percent" style="font-weight:700;font-size:20px"><?= esc($analytics['percent_approved'] ?? 0) ?>%</div>
      </div>
      <button class="btn btn-secondary" type="button" style="margin-left:8px" onclick="refreshAnalytics()">🔄 Refresh analytics</button>
      <div style="display:flex;align-items:center;gap:8px;margin-left:12px">
        <label style="font-size:13px;display:flex;align-items:center;gap:6px"><input type="checkbox" id="analytics-autorefresh" /> Auto-refresh</label>
        <label style="font-size:13px;display:flex;align-items:center;gap:6px">Interval <input id="analytics-interval" type="number" min="5" value="30" style="width:68px;padding:4px;margin-left:6px" /> s</label>
        <button class="btn btn-ghost" type="button" id="analytics-apply">Apply</button>
      </div>
    </div>
    <?php if (!empty($attendanceStats)): ?>
      <div id="attendance-container" style="margin-bottom:12px;padding:10px;border-radius:8px;border:1px solid #e5e7eb;background:#ffffff">
        <strong>Attendance by session</strong>
        <div id="attendance-stats" style="margin-top:8px;font-size:13px">
          <?php foreach ($attendanceStats as $s): ?>
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed #f3f4f6">
              <div><?= esc($s['title'] ?? $s['date']) ?> - <?= esc($s['date']) ?></div>
              <div><?= esc($s['present']) ?> / <?= esc($s['council_total']) ?> (<?= esc($s['percent_attendance']) ?>%)</div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <div class="legislative-grid">
    <div class="legislative-list">
      <div class="list-header">
        <span>Title</span>
        <span>Type</span>
        <span>Status</span>
        <span>Owner</span>
        <span>Created</span>
        <span></span>
      </div>
      <?php if (empty($legislatives)): ?>
        <div class="empty-state">No legislative records yet. Create one using the form.</div>
      <?php else: ?>
        <?php foreach ($legislatives as $item): ?>
          <div class="list-row">
            <div><?= esc($item['title']) ?></div>
            <div><?= esc($item['type']) ?></div>
            <div>
              <span class="badge <?= badgeClass($item['status']) ?>"><?= esc(ucfirst(str_replace('_', ' ', $item['status']))) ?></span>
              <?php if (($item['status'] ?? '') === 'submitted_to_admin'): ?>
                <div class="field-note" style="margin-top:5px;color:#92400e">Council submission waiting for agenda preparation</div>
              <?php endif; ?>
            </div>
            <div><?= esc($item['createdByName'] ?? 'Unknown') ?></div>
            <div class="created-cell">
              <?= esc(fmtDateTime($item['createdAt'] ?? $item['updatedAt'] ?? '')) ?>
            </div>
            <div>
              <?php if (stripos((string)($item['type'] ?? ''), 'bill') === false): ?>
                <a class="link" href="?page=legislative-admin&edit=<?= esc($item['id']) ?>&type=legislative">Edit</a>
                <a class="link" href="#" onclick="openLegislativeSendPanel('<?= esc($item['id']) ?>', '<?= esc($item['title']) ?>');return false;">📨 Send</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($referralItems)): ?>
  <section class="card" style="margin-top:18px;" id="referral-master">
    <div class="card-head">
      <div>
        <div class="card-title">Master of Referrals</div>
        <div class="field-note">REFER TO APPROPRIATE COMMITTEES → COMMITTEE HEARING → COMMITTEE REPORT → FOR AGENDA</div>
      </div>
      <span class="badge badge-info"><?= count($referralItems) ?> referral<?= count($referralItems) === 1 ? '' : 's' ?></span>
    </div>
    <div class="card-body" style="display:grid;gap:12px;">
      <?php if (empty($referralItems)): ?>
        <div class="empty-state">No matters for referral yet. Create a legislative record with the stream type “Matters for Referral”.</div>
      <?php else: ?>
        <?php foreach ($referralItems as $referral): ?>
          <?php $referralStage = $referral['workflow_status'] ?? 'EDITING'; ?>
          <article style="border:1px solid #e5e7eb;border-radius:10px;padding:14px;background:#fff;">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
              <div>
                <strong><?= esc($referral['title'] ?? 'Untitled referral') ?></strong>
                <div class="field-note" style="margin-top:4px;">Committee: <?= esc($referral['assigned_committee'] ?? 'Not assigned') ?> · Referred: <?= esc($referral['date_referred'] ?? 'Not recorded') ?></div>
              </div>
              <span class="badge <?= badgeClass($referral['status'] ?? 'draft') ?>"><?= esc($referralStage) ?></span>
            </div>

            <?php if ($referralStage === 'EDITING'): ?>
              <form method="post" action="index.php?page=legislative-admin" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:8px;align-items:end;margin-top:12px;">
                <input type="hidden" name="action" value="save_committee_referral" />
                <input type="hidden" name="legislative_id" value="<?= esc($referral['id']) ?>" />
                <label class="fg"><span>Designated Committee</span><input type="text" name="assigned_committee" value="<?= esc($referral['assigned_committee'] ?? '') ?>" placeholder="e.g. Committee on Laws" required /></label>
                <label class="fg"><span>Referral Date</span><input type="date" name="date_referred" value="<?= esc($referral['date_referred'] ?? date('Y-m-d')) ?>" required /></label>
                <label class="fg"><span>Hearing Date</span><input type="date" name="hearing_date" value="<?= esc($referral['hearing_date'] ?? '') ?>" /></label>
                <button class="btn btn-primary" type="submit">Refer to Committee</button>
              </form>
            <?php else: ?>
              <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
                <?php if ($referralStage === 'REFER TO APPROPRIATE COMMITTEES'): ?>
                  <form method="post" action="index.php?page=legislative-admin"><input type="hidden" name="action" value="advance_legislative_workflow" /><input type="hidden" name="legislative_id" value="<?= esc($referral['id']) ?>" /><input type="hidden" name="workflow_action" value="hearing" /><button class="btn btn-secondary" type="submit">Start Committee Hearing</button></form>
                <?php elseif ($referralStage === 'COMMITTEE HEARING'): ?>
                  <form method="post" action="index.php?page=legislative-admin"><input type="hidden" name="action" value="advance_legislative_workflow" /><input type="hidden" name="legislative_id" value="<?= esc($referral['id']) ?>" /><input type="hidden" name="workflow_action" value="report" /><button class="btn btn-secondary" type="submit">Record Committee Report</button></form>
                <?php elseif ($referralStage === 'COMMITTEE REPORT'): ?>
                  <form method="post" action="index.php?page=legislative-admin"><input type="hidden" name="action" value="advance_legislative_workflow" /><input type="hidden" name="legislative_id" value="<?= esc($referral['id']) ?>" /><input type="hidden" name="workflow_action" value="agenda" /><button class="btn btn-primary" type="submit">Return to Agenda</button></form>
                <?php elseif ($referralStage === 'FOR AGENDA'): ?>
                  <span class="field-note" style="align-self:center;color:#047857;font-weight:600;">Ready for agenda preparation</span>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if (!empty($postSessionItems)): ?>
  <section class="card" style="margin-top:18px;" id="post-session-ordinances">
    <div class="card-head">
      <div>
        <div class="card-title">Approved Resolutions / Ordinances</div>
        <div class="field-note">EDITING → FINAL DRAFT → PRINT (6 COPIES) → SIGNATURE SEC/P.O. → LCE ACTION → ARCHIVE / OVERRIDE</div>
      </div>
      <span class="badge badge-info"><?= count($postSessionItems) ?> record<?= count($postSessionItems) === 1 ? '' : 's' ?></span>
    </div>
    <div class="card-body" style="display:grid;gap:12px;">
      <?php if (empty($postSessionItems)): ?>
        <div class="empty-state">No approved resolutions or ordinances are currently in this workflow.</div>
      <?php else: ?>
        <?php foreach ($postSessionItems as $postSessionItem): ?>
          <?php $postSessionStage = $postSessionItem['workflow_status'] ?? 'EDITING'; ?>
          <article style="border:1px solid #e5e7eb;border-radius:10px;padding:14px;background:#fff;">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
              <div><strong><?= esc($postSessionItem['title'] ?? 'Untitled resolution') ?></strong><div class="field-note" style="margin-top:4px;">LCE action: <?= esc($postSessionItem['lce_action'] ?? 'Pending') ?><?php if (!empty($postSessionItem['archive_status'])): ?> · Archive: <?= esc($postSessionItem['archive_status']) ?><?php endif; ?></div></div>
              <span class="badge <?= badgeClass($postSessionItem['status'] ?? 'draft') ?>"><?= esc($postSessionStage) ?></span>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
              <?php if ($postSessionStage === 'EDITING'): ?>
                <span class="field-note" style="align-self:center;">Awaiting LCE approval before Final Draft.</span>
              <?php elseif ($postSessionStage === 'FINAL DRAFT'): ?>
                <form method="post" action="index.php?page=legislative-admin"><input type="hidden" name="action" value="advance_legislative_workflow" /><input type="hidden" name="legislative_id" value="<?= esc($postSessionItem['id']) ?>" /><input type="hidden" name="workflow_action" value="print" /><button class="btn btn-secondary" type="submit">Print 6 Copies</button></form>
              <?php elseif ($postSessionStage === 'PRINTED (6 COPIES)'): ?>
                <form method="post" action="index.php?page=legislative-admin"><input type="hidden" name="action" value="advance_legislative_workflow" /><input type="hidden" name="legislative_id" value="<?= esc($postSessionItem['id']) ?>" /><input type="hidden" name="workflow_action" value="signature" /><button class="btn btn-secondary" type="submit">For Signature Sec / P.O.</button></form>
              <?php elseif ($postSessionStage === 'FOR SIGNATURE SEC/P.O.'): ?>
                <form method="post" action="index.php?page=legislative-admin"><input type="hidden" name="action" value="process_lce_action" /><input type="hidden" name="legislative_id" value="<?= esc($postSessionItem['id']) ?>" /><input type="hidden" name="lce_action" value="LCE Approved" /><button class="btn btn-success" type="submit">LCE Approved</button></form>
                <form method="post" action="index.php?page=legislative-admin"><input type="hidden" name="action" value="process_lce_action" /><input type="hidden" name="legislative_id" value="<?= esc($postSessionItem['id']) ?>" /><input type="hidden" name="lce_action" value="Vetoed" /><button class="btn btn-danger" type="submit">Vetoed</button></form>
              <?php elseif ($postSessionStage === 'VETOED'): ?>
                <form method="post" action="index.php?page=legislative-admin"><input type="hidden" name="action" value="process_lce_action" /><input type="hidden" name="legislative_id" value="<?= esc($postSessionItem['id']) ?>" /><input type="hidden" name="lce_action" value="Overridden by Council" /><button class="btn btn-primary" type="submit">Override by Council</button></form>
                <form method="post" action="index.php?page=legislative-admin"><input type="hidden" name="action" value="process_lce_action" /><input type="hidden" name="legislative_id" value="<?= esc($postSessionItem['id']) ?>" /><input type="hidden" name="lce_action" value="Archived" /><button class="btn btn-secondary" type="submit">Archive</button></form>
              <?php elseif ($postSessionStage === 'FURNISH COPIES'): ?>
                <form method="post" action="index.php?page=legislative-admin"><input type="hidden" name="action" value="advance_legislative_workflow" /><input type="hidden" name="legislative_id" value="<?= esc($postSessionItem['id']) ?>" /><input type="hidden" name="workflow_action" value="file_literal_copies" /><button class="btn btn-secondary" type="submit">File Literal Copies</button></form>
              <?php elseif ($postSessionStage === 'FILE LITERAL COPIES'): ?>
                <form method="post" action="index.php?page=legislative-admin"><input type="hidden" name="action" value="advance_legislative_workflow" /><input type="hidden" name="legislative_id" value="<?= esc($postSessionItem['id']) ?>" /><input type="hidden" name="workflow_action" value="scan" /><button class="btn btn-secondary" type="submit">Scan to PDF</button></form>
              <?php elseif ($postSessionStage === 'SCAN TO PDF'): ?>
                <form method="post" action="index.php?page=legislative-admin"><input type="hidden" name="action" value="advance_legislative_workflow" /><input type="hidden" name="legislative_id" value="<?= esc($postSessionItem['id']) ?>" /><input type="hidden" name="workflow_action" value="save_external_drive" /><button class="btn btn-primary" type="submit">Archive to External Drive</button></form>
              <?php else: ?>
                <span class="field-note" style="align-self:center;color:#047857;font-weight:600;">Stored stage: <?= esc($postSessionStage) ?></span>
              <?php endif; ?>
              <?php if (($postSessionItem['lce_action'] ?? '') !== 'LCE Approved' && ($postSessionItem['lce_action'] ?? '') !== 'Pending LCE Review' && ($postSessionItem['status'] ?? '') !== 'agenda_created'): ?>
                <form method="post" action="index.php?page=legislative-admin"><input type="hidden" name="action" value="send_legislative_to_lce" /><input type="hidden" name="legislative_id" value="<?= esc($postSessionItem['id']) ?>" /><button class="btn btn-primary btn-xs" type="submit">Send This Matter to LCE</button></form>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="card" style="margin-top:18px;" id="digital-workflow">
    <div class="card-head">
      <div>
        <div class="card-title">Document Workflow</div>
        <div class="field-note">Receiving → Review → Agenda → Approval → Distribution → SP Session → Committee/Referral → Final Draft → Signatures → LCE Approval → Filing/Scan → Archive</div>
      </div>
      <a class="btn btn-secondary btn-xs" href="?page=legislative-audit-trail">Open Legislative Audit Trail</a>
    </div>
    <div class="card-body" style="display:grid;gap:12px;">
      <?php if (empty($legislatives)): ?>
        <div class="empty-state">No legislative documents are registered yet.</div>
      <?php else: ?>
        <?php foreach ($legislatives as $document): ?>
          <?php
            $stage = $document['workflow_status'] ?? 'Receiving';
            $stepIndex = array_search($stage, $workflowSteps, true);
            $isLocked = !empty($document['locked_at']);
          ?>
          <article style="border:1px solid #d8dee7;border-radius:8px;padding:14px;background:#fff;">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
              <div>
                <strong><?= esc($document['control_number'] ?? 'Unregistered') ?> · <?= esc($document['title'] ?? 'Untitled document') ?></strong>
                <div class="field-note" style="margin-top:4px;">Received <?= esc(fmtDateTime($document['received_at'] ?? $document['createdAt'] ?? '')) ?> · Origin <?= esc($document['origin'] ?? 'Not recorded') ?> · Office <?= esc($document['originating_office'] ?? 'Not recorded') ?></div>
                <div class="field-note">Responsible: <?= esc($document['assigned_role'] ?? 'Secretariat') ?> · <?= esc($document['assigned_to_name'] ?? $document['createdByName'] ?? 'Unassigned') ?></div>
              </div>
              <span class="badge badge-info"><?= esc($stage) ?></span>
            </div>
            <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:12px;" aria-label="Document workflow progress">
              <?php foreach ($workflowSteps as $index => $step): ?>
                <span style="font-size:11px;padding:5px 7px;border-radius:4px;background:<?= $index === $stepIndex ? '#dbeafe' : ($stepIndex !== false && $index < $stepIndex ? '#dcfce7' : '#f1f5f9') ?>;color:#334155;"><?= esc($step) ?></span>
              <?php endforeach; ?>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:12px;">
              <a class="btn btn-ghost btn-xs" href="?page=legislative-user&edit=<?= esc($document['id']) ?>&type=legislative">Open record</a>
              <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                <form method="post" action="index.php?page=legislative-admin" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                  <input type="hidden" name="action" value="assign_legislative_document" />
                  <input type="hidden" name="legislative_id" value="<?= esc($document['id']) ?>" />
                  <select name="assigned_to_id" aria-label="Assign document to" required>
                    <?php foreach (($data['users'] ?? []) as $assignee): ?>
                      <?php if (($assignee['status'] ?? '') === 'active'): ?>
                        <option value="<?= esc($assignee['id']) ?>"<?= (string)($document['assigned_to_id'] ?? '') === (string)$assignee['id'] ? ' selected' : '' ?>><?= esc($assignee['name']) ?> · <?= esc($assignee['role']) ?></option>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-secondary btn-xs" type="submit">Assign</button>
                </form>
              <?php endif; ?>
              <?php if ($stage === 'Distribution' && !$isLocked): ?>
                <form method="post" action="index.php?page=legislative-admin" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                  <input type="hidden" name="action" value="approve_legislative" />
                  <input type="hidden" name="legislative_id" value="<?= esc($document['id']) ?>" />
                  <select name="session_id" aria-label="Choose session for agenda publication" required>
                    <option value="">Choose session</option>
                    <?php foreach (($sessions ?? []) as $session): ?><option value="<?= esc($session['id']) ?>"><?= esc($session['title']) ?> · <?= esc($session['date']) ?></option><?php endforeach; ?>
                  </select>
                  <button class="btn btn-primary btn-xs" type="submit">Publish Agenda & Notify Council</button>
                </form>
              <?php endif; ?>
              <?php if ($stage === 'SP Session' && !$isLocked): ?>
                <form method="post" action="index.php?page=legislative-admin" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                  <input type="hidden" name="action" value="save_committee_referral" />
                  <input type="hidden" name="legislative_id" value="<?= esc($document['id']) ?>" />
                  <input type="text" name="assigned_committee" placeholder="Designated committee" aria-label="Designated committee" required />
                  <input type="date" name="date_referred" value="<?= esc(date('Y-m-d')) ?>" aria-label="Referral date" required />
                  <button class="btn btn-secondary btn-xs" type="submit">Refer to Committee</button>
                </form>
              <?php endif; ?>
              <?php if ($stage === 'Filing/Scan' && !empty($document['scan_file_path'])): ?>
                <a class="btn btn-ghost btn-xs" href="download.php?file=<?= rawurlencode((string)$document['scan_file_path']) ?>" target="_blank" rel="noopener">Open Filing Scan</a>
              <?php elseif ($stage === 'Filing/Scan' && (($currentUser['role'] ?? '') === 'admin' || in_array(($currentUser['role'] ?? ''), ['lce', 'mayor'], true) || (string)($document['assigned_to_id'] ?? '') === (string)($currentUser['id'] ?? ''))): ?>
                <form method="post" action="index.php?page=legislative-admin" enctype="multipart/form-data" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                  <input type="hidden" name="action" value="complete_legislative_filing_scan" />
                  <input type="hidden" name="legislative_id" value="<?= esc($document['id']) ?>" />
                  <label class="field-note">Filing scan <input type="file" name="scan_file" accept=".pdf,.jpg,.jpeg,.png" required /></label>
                  <button class="btn btn-secondary btn-xs" type="submit">Record Filing / Scan</button>
                </form>
              <?php endif; ?>
              <?php if ((!$isLocked || ($stage === 'LCE Approval' && in_array(($currentUser['role'] ?? ''), ['admin', 'lce', 'mayor'], true)) || ($stage === 'Filing/Scan' && !empty($document['scan_file_path']) && ($currentUser['role'] ?? '') === 'admin')) && isset($workflowActions[$stage])): ?>
                <?php foreach ($workflowActions[$stage] as $workflowAction => $actionLabel): ?>
                  <form method="post" action="index.php?page=legislative-admin">
                    <input type="hidden" name="action" value="advance_legislative_workflow" />
                    <input type="hidden" name="legislative_id" value="<?= esc($document['id']) ?>" />
                    <input type="hidden" name="workflow_action" value="<?= esc($workflowAction) ?>" />
                    <button class="btn btn-primary btn-xs" type="submit"><?= esc($actionLabel) ?></button>
                  </form>
                <?php endforeach; ?>
              <?php elseif ($stage === 'Signatures' && !$isLocked && (string)($document['assigned_to_id'] ?? '') === (string)($currentUser['id'] ?? '') && in_array(($currentUser['role'] ?? ''), ['mayor', 'lce', 'secretary', 'presiding officer'], true)): ?>
                <form method="post" action="index.php?page=legislative-admin" onsubmit="return confirm('Sign and lock this finalized version?');" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                  <input type="hidden" name="action" value="sign_legislative_document" />
                  <input type="hidden" name="legislative_id" value="<?= esc($document['id']) ?>" />
                  <input type="password" name="key_passphrase" autocomplete="current-password" placeholder="Private key passphrase" aria-label="Private key passphrase" required />
                  <button class="btn btn-primary btn-xs" type="submit">PKI Sign & Verify</button>
                </form>
              <?php endif; ?>
              <?php if ($isLocked): ?><span class="field-note">Signed version locked · SHA-256 <?= esc($document['signed_hash'] ?? '') ?></span><?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <?php if (!empty($editItem) && !empty($legislativeVersions)): ?>
    <section class="card" style="margin-top:18px;">
      <div class="card-head"><div class="card-title">Version History · <?= esc($editItem['control_number'] ?? '') ?></div></div>
      <div class="card-body">
        <?php foreach ($legislativeVersions as $version): ?>
          <div style="padding:8px 0;border-bottom:1px solid #e5e7eb;">v<?= (int)$version['version_number'] ?> · <?= esc($version['status']) ?> · SHA-256 <?= esc($version['document_hash']) ?> · <?= esc($version['created_by_name'] ?? 'System') ?> · <?= esc(fmtDateTime($version['created_at'])) ?></div>
        <?php endforeach; ?>
        <?php foreach (($legislativeAudit ?? []) as $event): ?>
          <div class="field-note" style="padding-top:6px;"><?= esc($event['event_type']) ?> · <?= esc($event['user_name'] ?? 'System') ?> (<?= esc($event['user_role'] ?? '') ?>) · <?= esc(fmtDateTime($event['created_at'])) ?><?= !empty($event['details']) ? ' · ' . esc($event['details']) : '' ?></div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($editItem): ?>
    <section class="card legislative-review-card" id="review-content" style="margin-top:18px;">
      <div class="card-head">
        <div>
          <div class="card-title">Review Legislative Record</div>
          <div class="field-note">Read the submitted bill before approving it.</div>
        </div>
        <span class="badge <?= badgeClass($editItem['status'] ?? 'draft') ?>"><?= esc(ucfirst(str_replace('_', ' ', $editItem['status'] ?? 'draft'))) ?></span>
      </div>
      <div class="card-body">
        <div class="review-record-meta">
          <div><span>Title</span><strong><?= esc($editItem['title'] ?? '') ?></strong></div>
          <div><span>Type</span><strong><?= esc($editItem['type'] ?? '') ?></strong></div>
          <div><span>Submitted by</span><strong><?= esc($editItem['createdByName'] ?? 'Unknown') ?></strong></div>
          <div><span>Document hash</span><strong class="review-document-hash"><?= esc($editItem['documentHash'] ?? 'Not available') ?></strong></div>
        </div>
        <div class="review-content-label">Document contents</div>
        <div class="review-content"><?= nl2br(esc($editItem['content'] ?? 'No document content was submitted.')) ?></div>
        <?php if (!empty($editItem['ai_summary'])): ?>
          <div class="review-content-label" style="margin-top:16px;">AI summary</div>
          <div class="review-content"><?= nl2br(esc($editItem['ai_summary'])) ?></div>
        <?php endif; ?>
        <?php if (!empty($editItem['file_path'])): ?>
          <div class="review-attachment">
            <span>Attachment: <?= esc($editItem['file_name'] ?? 'Uploaded document') ?></span>
            <a class="btn btn-secondary btn-xs" href="download.php?file=<?= esc($editItem['file_path']) ?>&inline=1" target="_blank" rel="noopener">View attachment</a>
          </div>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>
</div>

<div class="overlay" id="leg-send-overlay" onclick="closeLegislativeSendPanel()"></div>
<div class="slide-panel" id="leg-send-panel">
  <div class="panel-head">
    <div><div class="panel-title">Send Legislative Draft</div><div class="panel-subtitle" id="leg-send-subtitle"></div></div>
    <button class="panel-close" type="button" onclick="closeLegislativeSendPanel()">✕</button>
  </div>
  <form class="panel-body" method="post" action="index.php?page=legislative-admin" id="leg-send-form">
    <input type="hidden" name="action" value="send_legislative_to_recipients" />
    <input type="hidden" name="legislative_id" value="" id="leg-send-legislative-id" />
    <div class="fg">
      <label>Choose Council Members</label>
      <div style="display:grid;gap:8px;max-height:280px;overflow:auto;padding:12px;border:1px solid #e5e7eb;border-radius:10px;background:#fff">
        <?php if (empty($councilRecipients)): ?>
          <div class="empty-state">No active council members found.</div>
        <?php else: ?>
          <?php foreach ($councilRecipients as $recipient): ?>
            <label style="display:flex;align-items:center;gap:10px">
              <input type="checkbox" name="recipient_ids[]" value="<?= esc($recipient['id']) ?>" />
              <span><?= esc($recipient['name'] ?? 'Council Member') ?> <span style="opacity:.65">(<?= esc($recipient['email'] ?? 'no email') ?>)</span></span>
            </label>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </form>
  <div class="panel-foot">
    <button class="btn btn-secondary" type="button" onclick="closeLegislativeSendPanel()">Cancel</button>
    <button class="btn btn-primary" type="submit" form="leg-send-form" onclick="return validateLegislativeRecipients()">📨 Send to Selected Council Members</button>
  </div>
</div>

