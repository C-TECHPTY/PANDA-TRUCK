// assets/js/player.js - Versión actualizada

class PandaPlayer {
    constructor() {
        this.audio = new Audio();
        this.currentPlaylist = [];
        this.currentIndex = 0;
        this.isPlaying = false;
        this.volume = 0.7;
        this.isShuffle = false;
        this.isRepeat = false;
        
        this.init();
    }
    
    init() {
        this.audio.volume = this.volume;
        
        // Event listeners del audio
        this.audio.addEventListener('timeupdate', () => this.updateProgress());
        this.audio.addEventListener('ended', () => this.next());
        this.audio.addEventListener('play', () => this.updatePlayButton(true));
        this.audio.addEventListener('pause', () => this.updatePlayButton(false));
        
        // Evento para contar reproducción cuando comienza a sonar
        this.audio.addEventListener('play', () => this.countPlay());
        
        // Crear UI del reproductor
        this.createPlayerUI();
        
        // Cargar playlist
        this.loadPlaylist();
        
        // Cargar estadísticas
        this.loadStats();
        
        // Actualizar estadísticas cada 30 segundos
        setInterval(() => this.loadStats(), 30000);
    }
    
    countPlay() {
        const currentTrack = this.currentPlaylist[this.currentIndex];
        if (currentTrack && !this._playCounted) {
            this._playCounted = true;
            this.updateStats(currentTrack.id, 'play');
            
            // Actualizar en la interfaz
            const playSpan = document.getElementById(`play-${currentTrack.id}`);
            if (playSpan) {
                let currentPlays = parseInt(playSpan.textContent) || 0;
                playSpan.textContent = currentPlays + 1;
            }
        }
    }
    
    updateStats(itemId, action) {
        fetch('api/update_stats.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: itemId,
                action: action
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (action === 'play') {
                    const playSpan = document.getElementById(`play-${itemId}`);
                    if (playSpan) playSpan.textContent = data.plays;
                } else if (action === 'download') {
                    const downloadSpan = document.getElementById(`dl-${itemId}`);
                    if (downloadSpan) downloadSpan.textContent = data.downloads;
                }
            }
        })
        .catch(err => console.error('Error:', err));
    }
    
    loadStats() {
        fetch('api/get_stats.php')
            .then(res => res.json())
            .then(data => {
                // Actualizar totales en la página
                if (document.getElementById('total-mixes')) {
                    document.getElementById('total-mixes').textContent = data.total.total_mixes;
                    document.getElementById('total-djs').textContent = data.total.total_djs;
                    document.getElementById('total-downloads').textContent = data.total.total_downloads.toLocaleString();
                    document.getElementById('total-plays').textContent = data.total.total_plays.toLocaleString();
                }
                
                // Actualizar cada mix individualmente
                for (const [id, stats] of Object.entries(data.mixes)) {
                    const playSpan = document.getElementById(`play-${id}`);
                    const downloadSpan = document.getElementById(`dl-${id}`);
                    if (playSpan) playSpan.textContent = stats.plays;
                    if (downloadSpan) downloadSpan.textContent = stats.downloads;
                }
            })
            .catch(err => console.error('Error cargando stats:', err));
    }
    
    createPlayerUI() {
        const playerHTML = `
            <div class="fixed bottom-0 left-0 right-0 bg-neutral-900 border-t border-neutral-800 p-4 z-50">
                <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center gap-4">
                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <img id="player-cover" class="w-12 h-12 rounded-lg object-cover" src="assets/img/default-cover.jpg">
                        <div class="min-w-0">
                            <div id="player-title" class="font-semibold truncate">Nada reproduciéndose</div>
                            <div id="player-artist" class="text-sm text-neutral-400 truncate">-</div>
                        </div>
                    </div>
                    
                    <div class="flex flex-col items-center flex-1">
                        <div class="flex gap-4 mb-2">
                            <button id="player-shuffle" class="text-neutral-400 hover:text-white transition">🔀</button>
                            <button id="player-prev" class="text-2xl hover:text-primary transition">⏮</button>
                            <button id="player-play" class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-xl hover:bg-primary-hover transition">▶</button>
                            <button id="player-next" class="text-2xl hover:text-primary transition">⏭</button>
                            <button id="player-repeat" class="text-neutral-400 hover:text-white transition">🔁</button>
                        </div>
                        
                        <div class="w-full flex items-center gap-3">
                            <span id="player-current-time" class="text-xs text-neutral-400">0:00</span>
                            <div class="flex-1 h-1 bg-neutral-700 rounded-full cursor-pointer" id="player-progress-bar">
                                <div id="player-progress" class="h-full bg-primary rounded-full" style="width: 0%"></div>
                            </div>
                            <span id="player-duration" class="text-xs text-neutral-400">0:00</span>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <button id="player-volume" class="text-xl">🔊</button>
                        <input type="range" id="player-volume-slider" min="0" max="100" value="70" class="w-24">
                        <button id="player-playlist-toggle" class="text-neutral-400 hover:text-white transition">📋</button>
                    </div>
                </div>
            </div>
            
            <div id="playlist-panel" class="fixed bottom-24 right-4 w-80 bg-neutral-900 border border-neutral-800 rounded-xl shadow-xl hidden z-50">
                <div class="p-4 border-b border-neutral-800">
                    <h4 class="font-semibold">Lista de Reproducción</h4>
                </div>
                <div id="playlist-items" class="max-h-96 overflow-y-auto"></div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', playerHTML);
        
        // Bindear eventos
        document.getElementById('player-play').addEventListener('click', () => this.togglePlay());
        document.getElementById('player-prev').addEventListener('click', () => this.prev());
        document.getElementById('player-next').addEventListener('click', () => this.next());
        document.getElementById('player-shuffle').addEventListener('click', () => this.toggleShuffle());
        document.getElementById('player-repeat').addEventListener('click', () => this.toggleRepeat());
        document.getElementById('player-volume-slider').addEventListener('input', (e) => this.setVolume(e.target.value / 100));
        document.getElementById('player-playlist-toggle').addEventListener('click', () => this.togglePlaylist());
        
        const progressBar = document.getElementById('player-progress-bar');
        progressBar.addEventListener('click', (e) => this.seek(e));
    }
    
    async loadPlaylist() {
        try {
            const response = await fetch('api/playlist.php');
            const data = await response.json();
            this.currentPlaylist = data;
            this.renderPlaylist();
            if (this.currentPlaylist.length > 0) {
                this.loadTrack(0);
            }
        } catch (error) {
            console.error('Error cargando playlist:', error);
        }
    }
    
    loadTrack(index) {
        if (index < 0 || index >= this.currentPlaylist.length) return;
        
        this.currentIndex = index;
        const track = this.currentPlaylist[index];
        
        this.audio.src = track.url;
        this.audio.load();
        this._playCounted = false; // Reset para contar nueva reproducción
        
        document.getElementById('player-title').textContent = track.title;
        document.getElementById('player-artist').textContent = `Por ${track.dj}`;
        document.getElementById('player-cover').src = track.cover || 'assets/img/default-cover.jpg';
        
        if (this.isPlaying) {
            this.audio.play().catch(e => console.log('Autoplay bloqueado:', e));
        }
        
        this.updateActiveTrackInPlaylist();
    }
    
    togglePlay() {
        if (this.audio.paused) {
            this.audio.play();
            this.isPlaying = true;
        } else {
            this.audio.pause();
            this.isPlaying = false;
        }
    }
    
    next() {
        let nextIndex = this.currentIndex + 1;
        if (nextIndex >= this.currentPlaylist.length) {
            nextIndex = 0;
        }
        this.loadTrack(nextIndex);
    }
    
    prev() {
        let prevIndex = this.currentIndex - 1;
        if (prevIndex < 0) {
            prevIndex = this.currentPlaylist.length - 1;
        }
        this.loadTrack(prevIndex);
    }
    
    updateProgress() {
        const currentTime = this.audio.currentTime;
        const duration = this.audio.duration;
        
        if (!isNaN(duration)) {
            const progress = (currentTime / duration) * 100;
            document.getElementById('player-progress').style.width = `${progress}%`;
            document.getElementById('player-current-time').textContent = this.formatTime(currentTime);
            document.getElementById('player-duration').textContent = this.formatTime(duration);
        }
    }
    
    seek(e) {
        const bar = e.currentTarget;
        const rect = bar.getBoundingClientRect();
        const percent = (e.clientX - rect.left) / rect.width;
        this.audio.currentTime = percent * this.audio.duration;
    }
    
    setVolume(value) {
        this.volume = value;
        this.audio.volume = value;
        document.getElementById('player-volume-slider').value = value * 100;
        document.getElementById('player-volume').textContent = value > 0 ? '🔊' : '🔇';
    }
    
    toggleShuffle() {
        this.isShuffle = !this.isShuffle;
        const btn = document.getElementById('player-shuffle');
        btn.style.color = this.isShuffle ? '#e1261d' : '';
    }
    
    toggleRepeat() {
        this.isRepeat = !this.isRepeat;
        const btn = document.getElementById('player-repeat');
        btn.style.color = this.isRepeat ? '#e1261d' : '';
        this.audio.loop = this.isRepeat;
    }
    
    togglePlaylist() {
        const panel = document.getElementById('playlist-panel');
        panel.classList.toggle('hidden');
    }
    
    renderPlaylist() {
        const container = document.getElementById('playlist-items');
        container.innerHTML = this.currentPlaylist.map((track, idx) => `
            <div class="playlist-item p-3 hover:bg-neutral-800 cursor-pointer transition ${idx === this.currentIndex ? 'bg-neutral-800' : ''}" data-index="${idx}">
                <div class="flex gap-3">
                    <img src="${track.cover || 'assets/img/default-cover.jpg'}" class="w-10 h-10 rounded object-cover">
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold truncate">${this.escapeHtml(track.title)}</div>
                        <div class="text-sm text-neutral-400 truncate">${this.escapeHtml(track.dj)}</div>
                    </div>
                    <div class="text-xs text-neutral-400">${track.duration || '00:00'}</div>
                </div>
            </div>
        `).join('');
        
        // Agregar evento de click a los items de playlist
        document.querySelectorAll('.playlist-item').forEach(item => {
            item.addEventListener('click', () => {
                const index = parseInt(item.dataset.index);
                this.loadTrack(index);
                this.togglePlaylist();
            });
        });
    }
    
    updateActiveTrackInPlaylist() {
        document.querySelectorAll('.playlist-item').forEach((item, idx) => {
            if (idx === this.currentIndex) {
                item.classList.add('bg-neutral-800');
            } else {
                item.classList.remove('bg-neutral-800');
            }
        });
    }
    
    formatTime(seconds) {
        if (isNaN(seconds)) return '0:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }
    
    updatePlayButton(playing) {
        const btn = document.getElementById('player-play');
        btn.textContent = playing ? '⏸' : '▶';
    }
    
    escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
}

// Inicializar reproductor cuando la página cargue
document.addEventListener('DOMContentLoaded', () => {
    window.pandaPlayer = new PandaPlayer();
});