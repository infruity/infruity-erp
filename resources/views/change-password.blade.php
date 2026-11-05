@extends('layouts.erp')
@section('title', 'Perbarui Kata Sandi')
@section('content')
<section class="erp-password-page" aria-label="Perbarui kata sandi">
    <form method="POST" action="{{ url('change-password') }}" id="password-change-form" class="erp-password-form">
        @csrf
        <div class="erp-password-card">
            <header class="erp-password-intro">
                <h1>Perbarui Kata Sandi</h1>
                <p id="password-help">Gunakan minimal 12 karakter, termasuk huruf besar, huruf kecil, angka, dan karakter khusus (!@#$%^&amp;*()_+-).</p>
                @if (session('error'))
                    <p class="erp-password-server-error" role="alert">{{ session('error') }}</p>
                @endif
            </header>

            <div class="erp-password-fields">
                <div class="erp-password-field">
                    <label for="old-password" class="erp-password-label">Kata Sandi Saat Ini</label>
                    <div class="erp-password-control">
                        <input id="old-password" type="password" name="old_password" autocomplete="current-password" class="erp-password-input" placeholder="Ketik kata sandi saat ini..." required>
                        <button type="button" class="erp-password-toggle" data-password-toggle="old-password" aria-label="Tampilkan kata sandi saat ini" aria-controls="old-password" aria-pressed="false"><i class="ph ph-eye" aria-hidden="true"></i></button>
                    </div>
                    @error('old_password')
                        <p class="erp-password-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="erp-password-field">
                    <label for="new-password" class="erp-password-label">Kata Sandi Baru</label>
                    <div class="erp-password-control">
                        <input id="new-password" type="password" name="password" autocomplete="new-password" minlength="12" class="erp-password-input" placeholder="Minimal 12 karakter..." aria-describedby="password-help" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" required>
                        <button type="button" class="erp-password-toggle" data-password-toggle="new-password" aria-label="Tampilkan kata sandi baru" aria-controls="new-password" aria-pressed="false"><i class="ph ph-eye" aria-hidden="true"></i></button>
                    </div>
                    @error('password')
                        <p class="erp-password-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="erp-password-field">
                    <label for="confirm-password" class="erp-password-label">Konfirmasi Kata Sandi Baru</label>
                    <div class="erp-password-control">
                        <input id="confirm-password" type="password" name="password_confirmation" autocomplete="new-password" class="erp-password-input" placeholder="Ulangi kata sandi baru..." aria-describedby="password-confirmation-error" required>
                        <button type="button" class="erp-password-toggle" data-password-toggle="confirm-password" aria-label="Tampilkan konfirmasi kata sandi" aria-controls="confirm-password" aria-pressed="false"><i class="ph ph-eye" aria-hidden="true"></i></button>
                    </div>
                    <p id="password-confirmation-error" class="erp-password-error" data-password-confirmation-error role="alert" hidden>Kata sandi baru belum sama.</p>
                </div>
            </div>
        </div>

        <div class="erp-password-actions">
            <button type="submit" class="erp-password-submit" data-password-submit disabled>Perbarui Kata Sandi</button>
        </div>
    </form>
</section>
@endsection
@section('script')
<script>
(() => {
    const form = document.getElementById('password-change-form');
    if (!form) return;

    const oldPassword = form.querySelector('[name="old_password"]');
    const password = form.querySelector('[name="password"]');
    const confirmation = form.querySelector('[name="password_confirmation"]');
    const submit = form.querySelector('[data-password-submit]');
    const confirmationError = form.querySelector('[data-password-confirmation-error]');
    const rules = value => [
        value.length >= 12,
        /[A-Z]/.test(value),
        /[a-z]/.test(value),
        /[0-9]/.test(value),
        /[!@#$%^&*()_+-]/.test(value),
    ].every(Boolean);

    const updateFormState = () => {
        const matches = confirmation.value.length > 0 && confirmation.value === password.value;
        const mismatch = confirmation.value.length > 0 && !matches;
        confirmation.setCustomValidity(mismatch ? 'Kata sandi baru belum sama.' : '');
        confirmationError.hidden = !mismatch;
        submit.disabled = !oldPassword.value || !rules(password.value) || !matches;
    };

    form.addEventListener('input', updateFormState);
    form.addEventListener('submit', event => {
        updateFormState();
        if (submit.disabled || !form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
        }
    });

    form.querySelectorAll('[data-password-toggle]').forEach(toggle => {
        const input = document.getElementById(toggle.dataset.passwordToggle);
        const icon = toggle.querySelector('i');
        const fieldName = toggle.getAttribute('aria-label').replace(/^Tampilkan /, '');
        toggle.addEventListener('click', () => {
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', String(visible));
            toggle.setAttribute('aria-label', `${visible ? 'Sembunyikan' : 'Tampilkan'} ${fieldName}`);
            icon.className = visible ? 'ph ph-eye-slash' : 'ph ph-eye';
        });
    });

    updateFormState();
})();
</script>
@endsection
