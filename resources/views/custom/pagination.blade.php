@if ($paginator->hasPages())
    <div style="text-align: center; margin: 20px 0;">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span style="display: inline-block; padding: 8px 12px; margin: 0 2px; border: 1px solid #ddd; background: #f8f9fa; color: #6c757d; border-radius: 4px; font-size: 14px;">« Попередня</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" style="display: inline-block; padding: 8px 12px; margin: 0 2px; text-decoration: none; border: 1px solid #ddd; background: white; color: #333; border-radius: 4px; font-size: 14px;">« Попередня</a>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <span style="display: inline-block; padding: 8px 12px; margin: 0 2px; border: 1px solid #ddd; background: #f8f9fa; color: #6c757d; border-radius: 4px; font-size: 14px;">{{ $element }}</span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span style="display: inline-block; padding: 8px 12px; margin: 0 2px; border: 1px solid #007bff; background: #007bff; color: white; border-radius: 4px; font-size: 14px;">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" style="display: inline-block; padding: 8px 12px; margin: 0 2px; text-decoration: none; border: 1px solid #ddd; background: white; color: #333; border-radius: 4px; font-size: 14px;">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" style="display: inline-block; padding: 8px 12px; margin: 0 2px; text-decoration: none; border: 1px solid #ddd; background: white; color: #333; border-radius: 4px; font-size: 14px;">Наступна »</a>
        @else
            <span style="display: inline-block; padding: 8px 12px; margin: 0 2px; border: 1px solid #ddd; background: #f8f9fa; color: #6c757d; border-radius: 4px; font-size: 14px;">Наступна »</span>
        @endif
    </div>

    <div style="text-align: center; margin-top: 10px; font-size: 14px; color: #6c757d;">
        Показано {{ $paginator->firstItem() }} до {{ $paginator->lastItem() }} з {{ $paginator->total() }} результатів
    </div>
@endif
