<?php

// Sample Livewire code to test baseline patterns against
// This file contains common Livewire patterns that should be covered by baselines

use Livewire\Component;

class SampleLivewireCode extends Component
{
    public string $name = '';
    public string $email = '';
    public array $items = [];
    
    protected $listeners = [
        'userUpdated' => 'refreshComponent',
        'itemAdded' => 'addItem',
    ];

    public function mount($initialName = '')
    {
        $this->name = $initialName;
    }

    public function render()
    {
        return view('livewire.sample-component');
    }

    public function updated($propertyName)
    {
        if ($propertyName === 'email') {
            $this->validate([
                'email' => 'email|required',
            ]);
        }
    }

    public function addItem($item)
    {
        $this->items[] = $item;
        $this->emit('itemCountUpdated', count($this->items));
    }

    public function refreshComponent()
    {
        // Refresh component state
        $this->reset(['name', 'email']);
    }

    public function boot()
    {
        // Component boot logic
    }

    public function hydrate()
    {
        // Component hydration logic
    }

    public function dehydrate()
    {
        // Component dehydration logic
    }
}