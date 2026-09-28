<?php
$signingKeys = $signingKeys ?? [];
$activeSigningPurposes = [];
foreach ($signingKeys as $signingKey) {
    if (!empty($signingKey['is_active'])) {
    $activeSigningPurposes[] = $signingKey['key_purpose'] ?? 'legislative';
    }
}
?>
<section class="page-section">
  <header class="section-heading">
    <div>
      <h2>Signing Key Management</h2>
      <p>Manage separate PKI keys for legislative documents and agenda approvals.</p>
    </div>
  </header>

  <section class="card">
    <div class="card-head"><div class="card-title">Generate or Rotate Signing Key</div></div>
    <div class="card-body">
      <form method="post" action="index.php?page=signing-key-management" class="form-grid" id="signing-key-form" style="max-width:760px;">
        <input type="hidden" name="action" id="signing-key-action" value="generate_signing_key" />
        <div class="fg">
          <label for="signing-key-purpose">Signing key</label>
          <select id="signing-key-purpose" name="key_purpose" required>
            <option value="legislative">Legislative Signing Key</option>
            <option value="agenda">Agenda Signing Key</option>
          </select>
        </div>
        <div class="fg">
          <label for="signing-key-passphrase">Private key passphrase</label>
          <div class="password-field">
            <input id="signing-key-passphrase" type="password" name="key_passphrase" autocomplete="new-password" minlength="12" required />
            <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false" title="Show password">
              <svg class="password-toggle-icon password-toggle-show" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
              <svg class="password-toggle-icon password-toggle-hide" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18"></path><path d="M10.6 5.1A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a18.3 18.3 0 0 1-3.1 3.8M6.2 6.2C3.5 8 2 12 2 12s3.5 7 10 7a10 10 0 0 0 3-.5"></path><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path></svg>
            </button>
          </div>
        </div>
        <div class="fg">
          <label for="signing-key-passphrase-confirm">Confirm passphrase</label>
          <div class="password-field">
            <input id="signing-key-passphrase-confirm" type="password" name="key_passphrase_confirm" autocomplete="new-password" minlength="12" required />
            <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false" title="Show password">
              <svg class="password-toggle-icon password-toggle-show" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
              <svg class="password-toggle-icon password-toggle-hide" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18"></path><path d="M10.6 5.1A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a18.3 18.3 0 0 1-3.1 3.8M6.2 6.2C3.5 8 2 12 2 12s3.5 6 10 6a10 10 0 0 0 3-.5"></path><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path></svg>
            </button>
          </div>
        </div>
        <div class="form-actions">
          <button class="btn btn-primary" id="signing-key-submit" type="submit">Generate Key</button>
        </div>
      </form>
    </div>
  </section>

  <section class="card" style="margin-top:18px;">
    <div class="card-head"><div class="card-title">Public Key History</div></div>
    <div class="card-body" style="display:grid;gap:12px;">
      <?php if (!$signingKeys): ?>
        <div class="empty-state">No signing keys have been generated.</div>
      <?php endif; ?>
      <?php foreach ($signingKeys as $signingKey): ?>
        <article style="border:1px solid #d8dee7;border-radius:6px;padding:14px;background:#fff;">
          <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <strong><?= esc(ucfirst((string)($signingKey['key_purpose'] ?? 'legislative'))) ?> · <?= esc($signingKey['key_name']) ?></strong>
            <span class="badge <?= !empty($signingKey['is_active']) ? 'badge-success' : 'badge-info' ?>"><?= !empty($signingKey['is_active']) ? 'Active' : 'Retired' ?></span>
          </div>
          <div class="field-note" style="margin-top:6px;">RSA-SHA256 · Created by <?= esc($signingKey['created_by_name'] ?? 'Administrator') ?> · <?= esc(fmtDateTime($signingKey['created_at'])) ?></div>
          <?php if (!empty($signingKey['retired_at'])): ?><div class="field-note">Retired <?= esc(fmtDateTime($signingKey['retired_at'])) ?></div><?php endif; ?>
          <code class="ledger-document-hash" style="display:block;margin-top:8px;overflow-wrap:anywhere;">SHA-256 fingerprint: <?= esc($signingKey['fingerprint']) ?></code>
          <details style="margin-top:8px;">
            <summary>View public key</summary>
            <pre style="white-space:pre-wrap;overflow-wrap:anywhere;"><?= esc($signingKey['public_key_pem']) ?></pre>
          </details>
          <form method="post" action="index.php?page=signing-key-management" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap;margin-top:12px;">
            <input type="hidden" name="action" value="export_signing_key" />
            <input type="hidden" name="key_id" value="<?= esc($signingKey['id']) ?>" />
            <label>Key passphrase <div class="password-field"><input type="password" name="key_passphrase" autocomplete="current-password" required /><button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false" title="Show password"><svg class="password-toggle-icon password-toggle-show" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg><svg class="password-toggle-icon password-toggle-hide" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18"></path><path d="M10.6 5.1A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a18.3 18.3 0 0 1-3.1 3.8M6.2 6.2C3.5 8 2 12 2 12s3.5 6 10 6a10 10 0 0 0 3-.5"></path><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path></svg></button></div></label>
            <button class="btn btn-secondary btn-xs" type="submit">Export Protected TXT</button>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</section>
<script>
const activeSigningPurposes = <?= json_encode(array_values(array_unique($activeSigningPurposes))) ?>;
const signingPurposeSelect = document.getElementById('signing-key-purpose');
const signingActionInput = document.getElementById('signing-key-action');
const signingSubmitButton = document.getElementById('signing-key-submit');

function updateSigningKeyAction() {
  const hasActiveKey = activeSigningPurposes.includes(signingPurposeSelect.value);
  signingActionInput.value = hasActiveKey ? 'rotate_signing_key' : 'generate_signing_key';
  signingSubmitButton.textContent = hasActiveKey ? 'Rotate Key' : 'Generate Key';
}

signingPurposeSelect.addEventListener('change', updateSigningKeyAction);
updateSigningKeyAction();
</script>