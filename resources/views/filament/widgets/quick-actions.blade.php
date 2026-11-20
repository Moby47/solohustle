<x-filament-widgets::widget>
    <x-filament::section heading="Quick add">
        <div class="flex flex-wrap gap-3">
            <x-filament::button tag="a" :href="$newJobUrl" icon="heroicon-o-briefcase">New job</x-filament::button>
            <x-filament::button tag="a" :href="$newArticleUrl" icon="heroicon-o-pencil-square" color="gray">New article</x-filament::button>
            <x-filament::button tag="a" :href="$siteUrl" target="_blank" icon="heroicon-o-arrow-top-right-on-square" color="gray" outlined>View site</x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
