<div
    x-data="cameraRecorder()"
    x-init="init()"
    class="max-w-3xl mx-auto space-y-4"
>
    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
        Grabación desde cámara
    </h2>

    {{-- Mensaje de estado --}}
    @if (session('message'))
        <div class="px-4 py-2 rounded-xl bg-emerald-100 text-emerald-800 text-sm">
            {{ session('message') }}
        </div>
    @endif

    {{-- Vista previa en vivo --}}
    <div class="relative bg-black rounded-2xl overflow-hidden shadow-lg aspect-video">
        <video
            x-ref="preview"
            autoplay
            playsinline
            muted
            class="w-full h-full object-cover"
        ></video>

        <div class="absolute bottom-3 left-3 flex items-center gap-2 text-xs px-3 py-1
                    bg-black/60 text-white rounded-full">
            <span x-show="recording" class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span>
            <span x-text="recording ? 'Grabando...' : (cameraOn ? 'Cámara encendida' : 'Cámara apagada')"></span>
        </div>
    </div>

    {{-- Controles --}}
    <div class="flex flex-wrap gap-3">
        <button
            type="button"
            @click="startCamera()"
            :disabled="cameraOn"
            class="px-4 py-2 rounded-xl text-sm font-medium
                   bg-indigo-600 text-white disabled:opacity-50 disabled:cursor-not-allowed
                   hover:bg-indigo-700 transition"
        >
            Encender cámara
        </button>

        <button
            type="button"
            @click="startRecording()"
            :disabled="!cameraOn || recording"
            class="px-4 py-2 rounded-xl text-sm font-medium
                   bg-emerald-600 text-white disabled:opacity-50 disabled:cursor-not-allowed
                   hover:bg-emerald-700 transition"
        >
            Iniciar grabación
        </button>

        <button
            type="button"
            @click="stopRecording()"
            :disabled="!recording"
            class="px-4 py-2 rounded-xl text-sm font-medium
                   bg-red-600 text-white disabled:opacity-50 disabled:cursor-not-allowed
                   hover:bg-red-700 transition"
        >
            Detener grabación
        </button>
    </div>

    {{-- Preview del video grabado --}}
    <template x-if="recordedUrl">
        <div class="space-y-2">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                Vista previa del video grabado
            </h3>
            <video
                x-bind:src="recordedUrl"
                controls
                class="w-full rounded-2xl shadow"
            ></video>
        </div>
    </template>

    @error('video')
        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
    @enderror

    <script>
        function cameraRecorder() {
            return {
                cameraOn: false,
                recording: false,
                mediaStream: null,
                mediaRecorder: null,
                chunks: [],
                recordedUrl: null,

                async init() {
                    // nada por ahora
                },

                async startCamera() {
                    try {
                        this.mediaStream = await navigator.mediaDevices.getUserMedia({
                            video: {
                                width: { ideal: 1280 },
                                height: { ideal: 720 },
                            },
                            audio: true,
                        });

                        const video = this.$refs.preview;
                        video.srcObject = this.mediaStream;
                        await video.play();

                        this.cameraOn = true;
                    } catch (error) {
                        console.error('Error al acceder a la cámara: ', error);
                        alert('No se pudo acceder a la cámara. Revisa permisos y HTTPS.');
                    }
                },

                startRecording() {
                    if (!this.mediaStream) {
                        alert('Primero enciende la cámara.');
                        return;
                    }

                    this.chunks = [];

                    try {
                        this.mediaRecorder = new MediaRecorder(this.mediaStream, {
                            mimeType: 'video/webm;codecs=vp9'
                        });

                        this.mediaRecorder.ondataavailable = (event) => {
                            if (event.data && event.data.size > 0) {
                                this.chunks.push(event.data);
                            }
                        };

                        this.mediaRecorder.onstop = () => {
                            const blob = new Blob(this.chunks, { type: 'video/webm' });

                            // Preview local
                            if (this.recordedUrl) {
                                URL.revokeObjectURL(this.recordedUrl);
                            }
                            this.recordedUrl = URL.createObjectURL(blob);

                            // ⬇️ Aquí usamos Livewire directamente desde Alpine
                            const file = new File(
                                [blob],
                                `grabacion-${Date.now()}.webm`,
                                { type: 'video/webm' }
                            );

                            const lw = this.$wire;
                            lw.upload(
                                'video',
                                file,
                                () => {
                                    lw.call('guardarVideo');
                                },
                                (error) => {
                                    console.error('Error al subir el video a Livewire', error);
                                }
                            );
                        };

                        this.mediaRecorder.start();
                        this.recording = true;
                    } catch (error) {
                        console.error('Error al iniciar la grabación: ', error);
                        alert('No se pudo iniciar la grabación.');
                    }
                },

                stopRecording() {
                    if (this.mediaRecorder && this.recording) {
                        this.mediaRecorder.stop();
                        this.recording = false;
                    }
                },
            };
        }
    </script>
</div>
