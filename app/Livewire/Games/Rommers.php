<?php

namespace App\Livewire\Games;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Rommers extends Component
{
    /**
     * Nivåkrav for hvert nivå i Rommers.
     *
     * @var array<int, string>
     */
    public array $levels = [
        1 => '2x tress',
        2 => '2x firs',
        3 => 'Rømmers på 4 + 1 firs',
        4 => '2 rømmers',
        5 => '1 rømmers + 2 tress',
        6 => '3 tress',
        7 => 'Rømmers på 7 + 1 tress',
        8 => '4 tress',
        9 => '3 firs',
        10 => 'Rømmers på 9 + 1 tress',
        11 => 'Rømmers på 12',
    ];

    public function render()
    {
        return view('livewire.games.rommers');
    }
}
