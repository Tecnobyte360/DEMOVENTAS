<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;

class GrabarVideo extends Component
{
    use WithFileUploads;

    public $video;

    protected $rules = [
        'video' => 'required|file|mimetypes:video/webm,video/mp4|max:512000',
    ];

    public function guardarVideo()
    {
        $this->validate();

        $path = $this->video->store('videos', 'public');

        session()->flash('message', '🎥 Video guardado correctamente.');
        $this->reset('video');
    }

    public function render()
    {
        return view('livewire.grabar-video');
    }
}
