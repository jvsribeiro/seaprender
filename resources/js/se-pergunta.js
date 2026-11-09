document.addEventListener('alpine:init', () => {
    window.Alpine.data('sePergunta', () => ({
        fullscreen: false,
        fullscreenError: false,
        soundOn: false,
        audioError: false,
        mutedByChoice: false,
        remainingMs: 0,
        clockKey: '',
        deadline: 0,
        refreshedAtDeadline: false,
        observer: null,
        ticker: null,
        soundtrack: null,

        init() {
            this.$nextTick(() => {
                this.soundtrack = this.$refs.soundtrack;
                if (this.soundtrack) {
                    this.soundtrack.volume = 0.35;
                }
            });

            this.observer = new MutationObserver(() => this.syncClock());
            this.observer.observe(this.$el, {
                attributeFilter: ['data-game-phase', 'data-game-deadline', 'data-game-clock', 'data-game-active'],
            });
            this.syncClock();
        },

        destroy() {
            this.observer?.disconnect();
            window.clearInterval(this.ticker);
            this.soundtrack?.pause();
        },

        syncClock() {
            const { gamePhase, gameDeadline, gameClock, gameActive } = this.$el.dataset;
            const timed = Number(gameDeadline) > 0 && gameActive === '1';

            if (gamePhase === 'finished' || gameActive !== '1') {
                this.soundtrack?.pause();
            }

            if (!timed) {
                window.clearInterval(this.ticker);
                this.ticker = null;
                this.clockKey = '';
                this.remainingMs = 0;
                return;
            }

            const key = gamePhase + ':' + gameDeadline;
            const estimate = performance.now() + Math.max(0, Number(gameDeadline) - Number(gameClock));
            if (key === this.clockKey) {
                this.deadline = Math.min(this.deadline, estimate);
            } else {
                this.clockKey = key;
                this.deadline = estimate;
                this.refreshedAtDeadline = false;
            }

            this.tick();
            this.ticker ??= window.setInterval(() => this.tick(), 100);
        },

        tick() {
            this.remainingMs = Math.max(0, this.deadline - performance.now());

            if (this.remainingMs === 0 && !this.refreshedAtDeadline) {
                this.refreshedAtDeadline = true;
                this.$wire.$refresh().catch(() => {});
            }
        },

        get secondsLeft() {
            return Math.ceil(this.remainingMs / 1000);
        },

        startMusic() {
            if (!this.mutedByChoice && this.soundtrack?.paused) {
                this.playMusic();
            }
        },

        toggleMusic() {
            this.audioError = false;

            if (!this.soundtrack) {
                return;
            }

            this.mutedByChoice = !this.soundtrack.paused;
            if (this.mutedByChoice) {
                this.soundtrack.pause();
            } else {
                this.playMusic();
            }
        },

        playMusic() {
            this.audioError = false;
            this.soundtrack.play().catch((error) => {
                this.audioError = error.name !== 'AbortError';
            });
        },

        async toggleFullscreen() {
            this.fullscreenError = false;

            try {
                if (document.fullscreenElement) {
                    await document.exitFullscreen();
                } else {
                    await this.$refs.stage.requestFullscreen();
                }
            } catch {
                this.fullscreenError = true;
            }
        },
    }));
});
