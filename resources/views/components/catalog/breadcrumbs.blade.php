@props(['items' => []])

@if(count($items))
    <nav aria-label="Хлебные крошки" {{ $attributes->class(['mb-7']) }}>
        <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500">
            @foreach($items as $item)
                <li class="flex min-w-0 items-center gap-2">
                    @unless($loop->first)
                        <span aria-hidden="true" class="select-none text-slate-300">/</span>
                    @endunless
                    @if(!empty($item['url']) && ! $loop->last)
                        <a href="{{ $item['url'] }}" class="transition hover:text-[#071d5d] hover:underline">
                            {{ $item['title'] }}
                        </a>
                    @else
                        <span @if($loop->last) aria-current="page" @endif class="font-medium text-slate-900 break-words">
                            {{ $item['title'] }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
