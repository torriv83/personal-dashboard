<?php

declare(strict_types=1);

namespace App\Livewire\Tools;

use Livewire\Component;

class PortGenerator extends Component
{
    public int $port;

    public function mount(): void
    {
        $this->generatePort();
    }

    public function generatePort(): void
    {
        // Generer tilfeldig port mellom 49152-65535 (dynamiske/private porter som ikke kolliderer med andre programmer)
        $this->port = random_int(49152, 65535);
    }

    public function render()
    {
        return view('livewire.tools.port-generator');
    }
}
