@props(['id'])
<div class="form-group" data-ticket-upload>
    <label for="{{ $id }}">Lampiran (opsional)</label>
    <input id="{{ $id }}" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx" aria-describedby="{{ $id }}-help">
    <p id="{{ $id }}-help" class="form-help">Foto JPG/PNG/WebP/GIF, PDF, Word, atau Excel. Maksimal 5 file, masing-masing 10 MB. Pilih ulang file jika formulir gagal dikirim.</p>
    <ul class="ticket-selected-files" data-ticket-selected hidden aria-live="polite"></ul>
</div>
