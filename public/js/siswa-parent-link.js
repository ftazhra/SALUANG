document.addEventListener('DOMContentLoaded', function() {
    // Memproses kode penghubung Orang Tua.
    const button = document.getElementById('generateParentCode');
    const result = document.getElementById('parentCodeResult');
    const value = document.getElementById('parentCodeValue');
    const error = document.getElementById('parentCodeError');
    const countdown = document.getElementById('parentCodeCountdown');

    if (!button || !result || !value || !error || !countdown) {
        return;
    }

    const assetsConfig = document.getElementById('sd-assets');
    if (!assetsConfig) {
        return;
    }
    const parentLinkUrl = JSON.parse(assetsConfig.textContent).parentLinkGenerate;

    let countdownInterval = null;

    button.addEventListener('click', async function() {
        button.disabled = true;
        error.textContent = '';
        result.hidden = true;

        if (countdownInterval) {
            clearInterval(countdownInterval);
        }

        try {
            const response = await fetch(
                parentLinkUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector(
                            'meta[name="csrf-token"]'
                        ).content,
                        'Accept': 'application/json'
                    }
                }
            );

            const data = await response.json();

            if (!response.ok) {
                throw new Error(
                    data.message || 'Gagal membuat kode penghubung.'
                );
            }

            value.textContent = data.code;
            result.hidden = false;

            let remaining = Number(data.expires_in) || 600;

            function updateCountdown() {
                if (remaining <= 0) {
                    clearInterval(countdownInterval);
                    value.textContent = 'Kedaluwarsa';
                    countdown.textContent =
                        'Silakan buat kode baru.';
                    return;
                }

                const minutes = Math.floor(remaining / 60);
                const seconds = remaining % 60;

                countdown.textContent =
                    'Berlaku selama ' +
                    minutes + ':' +
                    String(seconds).padStart(2, '0');

                remaining--;
            }

            updateCountdown();

            countdownInterval = setInterval(
                updateCountdown,
                1000
            );

        } catch (err) {
            error.textContent = err.message;
        } finally {
            button.disabled = false;
        }
    });
});
