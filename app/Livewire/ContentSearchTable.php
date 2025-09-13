<?php

namespace App\Livewire;

use App\Services\ContentSearchService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ContentSearchTable extends Component
{
    use WithPagination;

    private const DEFAULT_SORT  = 'final_score';
    private const DEFAULT_ORDER = 'desc';
    private const ALLOWED_SORT  = ['final_score','published_at','title','type'];
    private const ALLOWED_ORDER = ['asc','desc'];

    #[Url(except: '')]                   public string  $query = '';
    #[Url(except: null)]                 public ?string $type  = null;
    #[Url(except: self::DEFAULT_SORT)]   public string  $sort  = self::DEFAULT_SORT;
    #[Url(except: self::DEFAULT_ORDER)]  public string  $order = self::DEFAULT_ORDER;
    #[Url(except: 1)]                    public int     $page  = 1; // hide page=1 from URL

    // Reset page or sync paginator when Livewire state changes
    public function updated(string $name, $value): void
    {
        // Keep paginator in sync when page changes
        if ($name === 'page') {
            $this->gotoPage((int) $value);
            return;
        }

        if (in_array($name, ['query','type','order','sort'], true)) {
            $this->resetPage();
        }
    }

    public function render(ContentSearchService $search): View
    {
        $results = $search->search($this->params());
        return view('livewire.content-search-table', compact('results'));
    }

    // Build and sanitize search parameters
    private function params(): array
    {
        [$sort, $order] = $this->sanitizedSortOrder();

        return [
            'q'     => $this->query,
            'type'  => $this->type,
            'sort'  => $sort,
            'order' => $order,
            'page'  => $this->getPage(),
        ];
    }

    private function sanitizedSortOrder(): array
    {
        $sort  = in_array($this->sort, self::ALLOWED_SORT, true) ? $this->sort : self::DEFAULT_SORT;
        $order = strtolower($this->order);
        $order = in_array($order, self::ALLOWED_ORDER, true) ? $order : self::DEFAULT_ORDER;

        return [$sort, $order];
    }

    // Computed property: should "Clear filters" button be visible?
    public function getHasActiveFiltersProperty(): bool
    {
        return $this->query !== ''
            || $this->type !== null
            || $this->sort !== self::DEFAULT_SORT
            || $this->order !== self::DEFAULT_ORDER
            || $this->getPage() !== 1;
    }

    // Reset all filters to default
    public function clearFilters(): void
    {
        $this->query = '';
        $this->type  = null;
        $this->sort  = self::DEFAULT_SORT;
        $this->order = self::DEFAULT_ORDER;
        $this->resetPage();
    }

    // Handle sorting when a table header is clicked
    public function setSort(string $field): void
    {
        if (!in_array($field, self::ALLOWED_SORT, true)) {
            return;
        }

        $this->order = ($this->sort === $field)
            ? ($this->order === 'asc' ? 'desc' : 'asc')
            : 'desc';

        $this->sort = $field;

        $this->resetPage();
    }
}
