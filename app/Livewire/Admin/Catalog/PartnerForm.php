<?php

namespace App\Livewire\Admin\Catalog;

use App\Domain\Catalog\Models\Partner;
use App\Enums\PartnerType;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class PartnerForm extends Component
{
    public ?int $partnerId = null;

    /** @var array<string,string> */
    public array $name = ['en' => '', 'id' => '', 'ar' => ''];

    public string $type = '';

    public string $city = '';

    public string $contact = '';

    public bool $is_active = true;

    #[On('create-partner')]
    public function openForCreate(): void
    {
        $this->authorize('viewAny', Partner::class);
        $this->reset(['partnerId', 'name', 'type', 'city', 'contact']);
        $this->name = ['en' => '', 'id' => '', 'ar' => ''];
        $this->is_active = true;
        $this->dispatch('open-modal', 'partner-form');
    }

    #[On('edit-partner')]
    public function openForEdit(int $partnerId): void
    {
        $partner = Partner::findOrFail($partnerId);
        $this->authorize('update', $partner);

        $this->partnerId = $partner->id;
        $this->name = [
            'en' => $partner->getTranslation('name', 'en', false) ?? '',
            'id' => $partner->getTranslation('name', 'id', false) ?? '',
            'ar' => $partner->getTranslation('name', 'ar', false) ?? '',
        ];
        $this->type = $partner->type->value;
        $this->city = $partner->city ?? '';
        $this->contact = $partner->contact ?? '';
        $this->is_active = $partner->is_active;

        $this->dispatch('open-modal', 'partner-form');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name.en' => ['required', 'string', 'max:255'],
            'name.id' => ['required', 'string', 'max:255'],
            'name.ar' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.implode(',', array_column(PartnerType::cases(), 'value'))],
            'city' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        if ($this->partnerId) {
            $partner = Partner::findOrFail($this->partnerId);
            $this->authorize('update', $partner);
        } else {
            $this->authorize('viewAny', Partner::class);
            $partner = new Partner;
        }

        $partner->fill([
            'type' => $validated['type'],
            'city' => $validated['city'],
            'contact' => $validated['contact'],
            'is_active' => $validated['is_active'],
        ]);

        foreach ($validated['name'] as $locale => $value) {
            $partner->setTranslation('name', $locale, $value);
        }

        $partner->save();

        $this->dispatch('partner-saved');
        $this->dispatch('close-modal', 'partner-form');
    }

    public function render(): View
    {
        return view('livewire.admin.catalog.partner-form', [
            'types' => PartnerType::cases(),
        ]);
    }
}
