<?php
$legislatives = $legislatives ?? [];
$selectedLedgerItem = $selectedLedgerItem ?? null;
$legislativeSignatures = $legislativeSignatures ?? [];
$hashSearch = $hashSearch ?? '';
$legislativeVersions = $legislativeVersions ?? [];
$legislativeAudit = $legislativeAudit ?? [];
$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
?>
<section class="page-section">
  <header class="section-heading">
    <div>
      <h2>Legislative Audit Trail</h2>
      <p>Users, actions, approvals, versions, signatures, and timestamps for each legislative record.</p>
    </div>
    <form method="get" action="index.php" class="ledger-search-form">
      <input type="hidden" name="page" value="legislative-audit-trail" />
      <input type="search" name="hash_search" value="<?= $escape($hashSearch) ?>" placeholder="Control number, title, or SHA-256" />
      <button class="btn btn-primary" type="submit">Search records</button>
      <?php if ($hashSearch !== ''): ?><a class="btn btn-ghost" href="?page=legislative-audit-trail">Clear</a><?php endif; ?>
    </form>
  </header>

  <div class="ledger-layout">
    <aside class="ledger-side-panel">
      <div class="ledger-side-header"><span class="ledger-kicker"><?= count($legislatives) ?> registered records</span></div>
      <?php if (!$legislatives): ?>
        <div class="empty-state">No records match the current search.</div>
      <?php else: ?>
        <div class="ledger-record-list">
          <?php foreach ($legislatives as $item): ?>
            <a class="ledger-record<?= ($selectedLedgerItem['id'] ?? '') === ($item['id'] ?? null) ? ' active' : '' ?>" href="?page=legislative-audit-trail&ledger_id=<?= rawurlencode((string)$item['id']) ?>">
              <div class="record-main-row">
                <span class="record-dot"></span>
                <div class="record-copy">
                  <div class="record-topline"><strong><?= $escape($item['control_number'] ?? 'Unregistered') ?></strong><span class="badge badge-info"><?= $escape($item['workflow_status'] ?? 'Receiving') ?></span></div>
                  <div class="record-meta"><?= $escape($item['title'] ?? 'Untitled') ?> · <?= $escape($item['type'] ?? 'Document') ?></div>
                </div>
              </div>
              <div class="record-date"><?= $escape($item['assigned_role'] ?? 'Secretariat') ?> · <?= $escape($item['assigned_to_name'] ?? $item['createdByName'] ?? 'Unassigned') ?></div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </aside>

    <main class="ledger-main-panel">
      <?php if (!$selectedLedgerItem): ?>
        <div class="ledger-empty-state"><h3>Select a record</h3><p>Choose a registered document to inspect its versions, integrity hash, signature, and audit history.</p></div>
      <?php else: ?>
        <?php
          $verificationUrl = 'index.php?verify=' . rawurlencode((string)($selectedLedgerItem['control_number'] ?? ''));
        ?>
        <article class="ledger-detail-shell">
          <header class="detail-header">
            <div><span class="eyebrow"><?= $escape($selectedLedgerItem['control_number'] ?? '') ?></span><h3><?= $escape($selectedLedgerItem['title'] ?? '') ?></h3></div>
            <span class="badge badge-info"><?= $escape($selectedLedgerItem['workflow_status'] ?? 'Receiving') ?></span>
          </header>
          <div class="detail-metrics">
            <div class="metric-box"><span>Type</span><strong><?= $escape($selectedLedgerItem['type'] ?? '') ?></strong></div>
            <div class="metric-box"><span>Received</span><strong><?= $escape(fmtDateTime($selectedLedgerItem['received_at'] ?? $selectedLedgerItem['createdAt'] ?? '')) ?></strong></div>
            <div class="metric-box"><span>Origin / Office</span><strong><?= $escape($selectedLedgerItem['origin'] ?? 'Not recorded') ?> · <?= $escape($selectedLedgerItem['originating_office'] ?? 'Not recorded') ?></strong></div>
            <div class="metric-box"><span>Responsible</span><strong><?= $escape($selectedLedgerItem['assigned_role'] ?? 'Secretariat') ?> · <?= $escape($selectedLedgerItem['assigned_to_name'] ?? $selectedLedgerItem['createdByName'] ?? 'Unassigned') ?></strong></div>
            <div class="metric-box"><span>Current SHA-256</span><strong class="ledger-document-hash"><?= $escape($selectedLedgerItem['documentHash'] ?? 'Not computed') ?></strong></div>
            <div class="metric-box"><span>Signed SHA-256</span><strong class="ledger-document-hash"><?= $escape($selectedLedgerItem['signed_hash'] ?? 'Not signed') ?></strong></div>
            <div class="metric-box"><span>Signature</span><strong><?= !empty($selectedLedgerItem['signature_verified_at']) ? 'Verified · ' . $escape(fmtDateTime($selectedLedgerItem['signature_verified_at'])) : 'Not verified' ?></strong></div>
            <div class="metric-box"><span>Version lock</span><strong><?= !empty($selectedLedgerItem['locked_at']) ? 'Locked · ' . $escape(fmtDateTime($selectedLedgerItem['locked_at'])) : 'Editable' ?></strong></div>
            <?php if (!empty($selectedLedgerItem['scan_file_path'])): ?><div class="metric-box"><span>Filing scan</span><strong><a class="link" href="download.php?file=<?= rawurlencode((string)$selectedLedgerItem['scan_file_path']) ?>" target="_blank" rel="noopener">Open scanned filing</a></strong></div><?php endif; ?>
          </div>

          <section class="ledger-history-section">
            <h4>Signature History</h4>
            <?php if (!$legislativeSignatures): ?><p class="field-note">No signed versions are recorded.</p><?php endif; ?>
            <?php foreach ($legislativeSignatures as $signature): ?>
              <div class="ledger-history-entry"><strong>Version <?= (int)$signature['version_number'] ?> · <?= $escape($signature['signer_name']) ?> · <?= $escape($signature['signer_role']) ?></strong><span><?= $escape($signature['algorithm']) ?> · <?= $escape(fmtDateTime($signature['created_at'])) ?></span><code>Document SHA-256: <?= $escape($signature['document_hash']) ?></code><code>Key fingerprint: <?= $escape($signature['certificate_fingerprint']) ?></code><a class="btn btn-secondary btn-xs" href="index.php?verify=<?= rawurlencode((string)($selectedLedgerItem['control_number'] ?? '')) ?>&version=<?= (int)$signature['version_number'] ?>" target="_blank" rel="noopener">Verify signed version</a></div>
            <?php endforeach; ?>
          </section>

          <section class="ledger-history-section">
            <h4>Version History</h4>
            <?php if (!$legislativeVersions): ?><p class="field-note">No saved version snapshots.</p><?php endif; ?>
            <?php foreach ($legislativeVersions as $version): ?>
              <div class="ledger-history-entry"><strong>v<?= (int)$version['version_number'] ?> · <?= $escape($version['status']) ?></strong><span><?= $escape($version['created_by_name'] ?? 'System') ?> · <?= $escape(fmtDateTime($version['created_at'])) ?></span><code><?= $escape($version['document_hash']) ?></code><?php if (str_starts_with((string)$version['status'], 'Signed v')): ?><a class="link" href="index.php?verify=<?= rawurlencode((string)($selectedLedgerItem['control_number'] ?? '')) ?>&version=<?= (int)$version['version_number'] ?>" target="_blank" rel="noopener">Verify this signed version</a><?php endif; ?><?php if (!empty($version['file_path'])): ?><a class="link" href="download.php?file=<?= rawurlencode((string)$version['file_path']) ?>&inline=1" target="_blank" rel="noopener">Open retained attachment</a><?php endif; ?></div>
            <?php endforeach; ?>
          </section>

          <section class="ledger-history-section">
            <h4>Audit Trail</h4>
            <?php if (!$legislativeAudit): ?><p class="field-note">No audit events recorded.</p><?php endif; ?>
            <?php foreach ($legislativeAudit as $event): ?>
              <div class="ledger-history-entry"><strong><?= $escape(str_replace('_', ' ', $event['event_type'])) ?></strong><span><?= $escape($event['user_name'] ?? 'System') ?> · <?= $escape($event['user_role'] ?? '') ?> · <?= $escape(fmtDateTime($event['created_at'])) ?></span><?php if (!empty($event['details'])): ?><small><?= $escape($event['details']) ?></small><?php endif; ?></div>
            <?php endforeach; ?>
          </section>
        </article>
      <?php endif; ?>
    </main>
  </div>
</section>