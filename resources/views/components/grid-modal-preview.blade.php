@php
    $mountedFormComponentActionsData = $getLivewire()->mountedFormComponentActionsData;

    $data = $mountedFormComponentActionsData[array_key_last($mountedFormComponentActionsData)];
@endphp

<div class="rounded-lg bg-gray-100 p-4 dark:bg-gray-950">
    <div
        class="grid gap-4"
        style="grid-template-columns: repeat({{ $data['columns'] }}, minmax(0, 1fr))"
    >
        @if ($data['asymmetric'])
            <div
                class="rounded-lg border border-dashed border-white bg-gray-300 p-0.5 text-center dark:border-gray-600 dark:bg-gray-800"
                style="grid-column: span {{ $data['asymmetric_left'] }};"
            >
                <p>1</p>
            </div>
            <div
                class="rounded-lg border border-dashed border-white bg-gray-300 p-0.5 text-center dark:border-gray-600 dark:bg-gray-800"
                style="grid-column: span {{ $data['asymmetric_right'] }};"
            >
                <p>1</p>
            </div>
        @else
            @foreach (range(1, $data['columns']) as $column)
                <div
                    class="rounded-lg border border-dashed border-white bg-gray-300 p-0.5 text-center dark:border-gray-600 dark:bg-gray-800">
                    <p>{{ $column }}</p>
                </div>
            @endforeach
        @endif
    </div>
</div>
