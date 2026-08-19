/**
 * NoteNest AI - Speech Recognition & Lecture Recording Engine
 */

class SpeechRecorder {
  constructor(options = {}) {
    this.onTranscriptChange = options.onTranscriptChange || (() => {});
    this.onStatusChange = options.onStatusChange || (() => {});
    this.onTimerTick = options.onTimerTick || (() => {});

    this.recognition = null;
    this.isRecording = false;
    this.transcript = '';
    this.interimTranscript = '';
    this.timerInterval = null;
    this.secondsElapsed = 0;

    this.initRecognition();
  }

  initRecognition() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
      console.warn('Web Speech API is not supported in this browser.');
      return;
    }

    this.recognition = new SpeechRecognition();
    this.recognition.continuous = true;
    this.recognition.interimResults = true;
    this.recognition.lang = 'en-US';

    this.recognition.onresult = (event) => {
      let final = '';
      let interim = '';

      for (let i = event.resultIndex; i < event.results.length; i++) {
        const text = event.results[i][0].transcript;
        if (event.results[i].isFinal) {
          final += text + ' ';
        } else {
          interim += text;
        }
      }

      if (final) {
        this.transcript += final;
      }
      this.interimTranscript = interim;

      this.onTranscriptChange(this.transcript, this.interimTranscript);
    };

    this.recognition.onerror = (event) => {
      console.error('Speech recognition error:', event.error);
      if (event.error === 'no-speech' && this.isRecording) {
        try { this.recognition.start(); } catch (e) {}
      }
    };

    this.recognition.onend = () => {
      if (this.isRecording) {
        try { this.recognition.start(); } catch (e) {}
      }
    };
  }

  start() {
    if (!this.recognition) {
      alert('Speech recognition is not supported in your browser. Please use Google Chrome or Microsoft Edge.');
      return false;
    }

    this.transcript = '';
    this.interimTranscript = '';
    this.secondsElapsed = 0;
    this.isRecording = true;

    try {
      this.recognition.start();
    } catch (e) {
      console.warn(e);
    }

    this.timerInterval = setInterval(() => {
      this.secondsElapsed++;
      this.onTimerTick(this.secondsElapsed);
    }, 1000);

    this.onStatusChange('RECORDING');
    return true;
  }

  stop() {
    this.isRecording = false;
    if (this.timerInterval) clearInterval(this.timerInterval);

    if (this.recognition) {
      try { this.recognition.stop(); } catch (e) {}
    }

    this.interimTranscript = '';
    this.onTranscriptChange(this.transcript, '');
    this.onStatusChange('REVIEW');
  }

  getTranscript() {
    return this.transcript.trim();
  }

  getFormattedTime() {
    const mins = Math.floor(this.secondsElapsed / 60);
    const secs = this.secondsElapsed % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  }
}

window.SpeechRecorder = SpeechRecorder;
