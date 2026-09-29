<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @include('filament.components.evidence-attachments', ['attachments' => $getState()])
</x-dynamic-component>
