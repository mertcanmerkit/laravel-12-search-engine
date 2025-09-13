<div class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3"
         wire:loading.class="opacity-50 pointer-events-none">
        <input type="text" placeholder="Search title..."
               class="border rounded px-3 py-2 w-full"
               wire:model.live.debounce.500ms="query"/>

        <select class="border rounded px-3 py-2 w-full"
                wire:model.live="type">
            <option value="">All types</option>
            <option value="video">Video</option>
            <option value="article">Article</option>
        </select>

        <select class="border rounded px-3 py-2 w-full"
                wire:model.live="sort">
            <option value="final_score">Sort by score</option>
            <option value="published_at">Sort by date</option>
            <option value="title">Sort by title</option>
            <option value="type">Sort by type</option>
        </select>

        <select class="border rounded px-3 py-2 w-full"
                wire:model.live="order">
            <option value="desc">Desc</option>
            <option value="asc">Asc</option>
        </select>
    </div>

    {{-- Clear filters button --}}
    <div class="flex justify-end"
         wire:loading.class="opacity-50 pointer-events-none"
         wire:target="query,type,sort,order,perPage,page,setSort,clearFilters">
        <button type="button"
                class="inline-flex items-center gap-2 border rounded px-3 py-2 text-sm hover:bg-gray-50"
                wire:click="clearFilters"
                wire:loading.attr="disabled"
                wire:target="clearFilters"
            @disabled(!$this->hasActiveFilters)>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2">
                <path d="M3 5h18M8 5v14l4-3 4 3V5" />
            </svg>
            Clear filters
        </button>
    </div>

    <div class="relative overflow-x-auto border rounded min-h-[320px]"
         wire:loading.class="opacity-50"
         wire:target="query,type,sort,order,perPage,page,setSort,clearFilters">
        <div wire:loading.delay.shortest
             wire:target="query,type,sort,order,perPage,page,setSort,clearFilters"
             class="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 z-20">
            <div class="flex items-center gap-2 text-sm text-gray-700">
                <svg class="animate-spin h-6 w-6" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="4" fill="none"/>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8v4A4 4 0 004 12z"/>
                </svg>
            </div>
        </div>

        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
            <tr>
                <th class="p-3 text-left">
                    <button wire:click="setSort('title')" class="underline">Title</button>
                </th>
                <th class="p-3 text-left">
                    <button wire:click="setSort('type')" class="underline">Type</button>
                </th>
                <th class="p-3 text-left">
                    <button wire:click="setSort('final_score')" class="underline">Score</button>
                </th>
                <th class="p-3 text-left">
                    <button wire:click="setSort('published_at')" class="underline">Published</button>
                </th>
            </tr>
            </thead>
            <tbody>
            @forelse($results as $row)
                <tr class="border-t">
                    <td class="p-3">{{ $row->title }}</td>
                    <td class="p-3 capitalize">{{ $row->type }}</td>
                    <td class="p-3">{{ number_format((float)$row->final_score, 2) }}</td>
                    <td class="p-3">{{ optional($row->published_at)->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr><td class="p-3" colspan="4">No results</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-2"
         wire:loading.class="opacity-50 pointer-events-none"
         wire:target="query,type,sort,order,perPage,page,setSort,clearFilters">
        {{ $results->withQueryString()->links() }}
    </div>
</div>
