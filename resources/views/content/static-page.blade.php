@extends('layouts.app')

@section('title', $translation->title . ' — Asia Cosmetic')

@section('content')
    <div class="static-page mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8 lg:py-16">
        <header class="mb-8 border-b border-zinc-200 pb-7 sm:mb-10 sm:pb-9">
            <div class="mb-3 text-xs font-semibold uppercase tracking-[0.18em] text-[#071d5d]/60">
                Asia Cosmetic
            </div>
            <h1 class="max-w-4xl text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl lg:text-5xl">
                {{ $translation->title }}
            </h1>
            <div class="mt-5 h-1 w-14 rounded-full bg-[#071d5d]"></div>
        </header>

        <article class="static-page__content rounded-3xl bg-white px-5 py-7 shadow-sm ring-1 ring-black/5 sm:px-9 sm:py-10 lg:px-12 lg:py-12">
            {!! $translation->content !!}
        </article>
    </div>

    <style>
        .static-page__content {
            color: #3f3f46;
            font-size: 1rem;
            line-height: 1.9;
            overflow-wrap: anywhere;
        }
        .static-page__content > :first-child { margin-top: 0; }
        .static-page__content > :last-child { margin-bottom: 0; }
        .static-page__content p { margin: 0 0 1.15em; }
        .static-page__content p[style*="text-align: justify"] { text-align: left !important; }
        .static-page__content h1,
        .static-page__content h2,
        .static-page__content h3,
        .static-page__content h4 {
            color: #18181b;
            font-weight: 700;
            line-height: 1.3;
            letter-spacing: -0.02em;
            margin: 1.7em 0 .7em;
        }
        .static-page__content h1 { font-size: clamp(1.7rem, 3vw, 2.3rem); }
        .static-page__content h2 { font-size: clamp(1.4rem, 2.5vw, 1.9rem); }
        .static-page__content h3 { font-size: 1.25rem; }
        .static-page__content h4 { font-size: 1.1rem; }
        .static-page__content strong,
        .static-page__content b { color: #27272a; font-weight: 650; }
        .static-page__content a { color: #071d5d; text-decoration: underline; text-underline-offset: 3px; text-decoration-color: #a5b4fc; transition: color .2s; }
        .static-page__content a:hover { color: #1d4ed8; }
        .static-page__content ul { list-style: disc; padding-left: 1.6rem; margin: 1.2em 0; }
        .static-page__content ol { list-style: decimal; padding-left: 1.6rem; margin: 1.2em 0; }
        .static-page__content li { padding-left: .2rem; margin: .4em 0; }
        .static-page__content li::marker { color: #071d5d; }
        .static-page__content blockquote { border-left: 3px solid #071d5d; padding: .8rem 1.25rem; margin: 1.6rem 0; background: #f8fafc; border-radius: 0 .75rem .75rem 0; }
        .static-page__content img { max-width: 100%; height: auto; border-radius: .85rem; }
        .static-page__content p > img:only-child { display: inline-block; vertical-align: middle; }
        .static-page__content figure { margin: 1.8rem 0; }
        .static-page__content figure img { width: 100%; object-fit: cover; }
        .static-page__content figcaption { font-size: .85rem; color: #71717a; margin-top: .5rem; text-align: center; }
        .static-page__content table { width: 100%; border-collapse: collapse; margin: 1.6rem 0; font-size: .95rem; }
        .static-page__content th,
        .static-page__content td { padding: .85rem 1rem; border: 1px solid #e4e4e7; text-align: left; vertical-align: top; }
        .static-page__content th { background: #f4f4f5; color: #18181b; font-weight: 600; }
        .static-page__content hr { border: 0; border-top: 1px solid #e4e4e7; margin: 2rem 0; }
        .static-page__content iframe { max-width: 100%; }
        @media (max-width: 640px) {
            .static-page__content { font-size: .95rem; line-height: 1.8; }
            .static-page__content th,
            .static-page__content td { padding: .6rem; }
        }
    </style>
@endsection
