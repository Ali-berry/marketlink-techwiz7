// chat form ke do buttons: paperclip (image chuno, preview chip) aur mic (daba ke record, WhatsApp jaisa - chhodo to attach aur send)

// thread khulte hi sab se naye message pe jao
document.querySelectorAll('[data-chat-scroll-container]').forEach((scrollContainer) => {
    scrollContainer.scrollTop = scrollContainer.scrollHeight;
});

document.querySelectorAll('[data-image-attach-input]').forEach((imageInput) => {
    const form = imageInput.closest('form');
    const preview = form.querySelector('[data-image-attach-preview]');
    if (! preview) return;

    imageInput.addEventListener('change', () => {
        const chosenFile = imageInput.files[0];
        if (! chosenFile) return;

        preview.querySelector('[data-image-attach-name]').textContent = chosenFile.name;
        preview.hidden = false;
    });

    preview.querySelector('[data-image-attach-remove]')?.addEventListener('click', () => {
        imageInput.value = '';
        preview.hidden = true;
    });
});

// webm duration ke liye player ko seek karne wali trick mat lagana - wo Range request bhejti hai jo
// php artisan serve support nahi karta aur playback toot jata hai. preload="auto" kaafi hai, aur
// dikhne wali duration voice_note_duration_seconds column se aati hai (Message::voiceNoteDurationText())

document.querySelectorAll('[data-voice-record-button]').forEach((micButton) => {
    const form = micButton.closest('form');
    const fileInput = form.querySelector('[data-voice-record-input]');
    const durationInput = form.querySelector('[data-voice-record-duration]');
    const indicator = form.querySelector('[data-voice-record-indicator]');
    const timerLabel = indicator?.querySelector('[data-voice-record-timer]');

    if (! navigator.mediaDevices?.getUserMedia || typeof MediaRecorder === 'undefined') {
        micButton.disabled = true;
        micButton.title = "Your browser can't record audio";
        return;
    }

    let mediaRecorder = null;
    let recordedChunks = [];
    let recordingStartedAt = null;
    let timerIntervalId = null;

    // Chrome / Firefox webm record karte hain, Safari sirf mp4 - jo browser support kare
    const mimeType = MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : 'audio/mp4';
    const fileExtension = mimeType === 'audio/webm' ? 'webm' : 'm4a';

    async function startRecording() {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        recordedChunks = [];
        mediaRecorder = new MediaRecorder(stream, { mimeType });

        mediaRecorder.addEventListener('dataavailable', (event) => {
            if (event.data.size > 0) recordedChunks.push(event.data);
        });

        mediaRecorder.addEventListener('stop', () => {
            stream.getTracks().forEach((track) => track.stop());

            const elapsedMs = Date.now() - recordingStartedAt;

            // 1 second se kam aksar galti se tap hota hai
            if (elapsedMs < 800) return;

            // yahan time karte hain, file ki apni duration pe bharosa nahi
            if (durationInput) durationInput.value = Math.round(elapsedMs / 1000);

            const voiceNoteFile = new File(recordedChunks, `voice-note.${fileExtension}`, { type: mimeType });

            // file input ko seedha set nahi kar sakte - DataTransfer se recorded Blob input mein dalte hain
            const transfer = new DataTransfer();
            transfer.items.add(voiceNoteFile);
            fileInput.files = transfer.files;

            form.requestSubmit();
        });

        mediaRecorder.start();
        recordingStartedAt = Date.now();
        micButton.classList.add('bg-tomato-500', 'text-white', 'scale-110');
        if (indicator) indicator.hidden = false;

        timerIntervalId = setInterval(() => {
            const secondsElapsed = Math.floor((Date.now() - recordingStartedAt) / 1000);
            if (timerLabel) timerLabel.textContent = `0:${String(secondsElapsed).padStart(2, '0')}`;
        }, 200);
    }

    function stopRecording() {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop();
        clearInterval(timerIntervalId);
        micButton.classList.remove('bg-tomato-500', 'text-white', 'scale-110');
        if (indicator) indicator.hidden = true;
    }

    micButton.addEventListener('mousedown', startRecording);
    micButton.addEventListener('mouseup', stopRecording);
    micButton.addEventListener('mouseleave', stopRecording);
    micButton.addEventListener('touchstart', (event) => {
        event.preventDefault();
        startRecording();
    });
    micButton.addEventListener('touchend', stopRecording);
});
