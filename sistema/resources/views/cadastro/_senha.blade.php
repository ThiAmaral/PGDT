<div class="row g-3">
    <div class="col-md-6">
        <label for="password" class="form-label">Senha</label>
        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
        <div class="form-text">Mínimo de 8 caracteres, com letras e números.</div>
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label for="password-confirm" class="form-label">Confirme a senha</label>
        <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
    </div>
</div>
