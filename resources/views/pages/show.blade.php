<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->title }}</title>
    @if($page->css)
        <style>{!! $page->css !!}</style>
    @endif
    {!! seo()->for($page) !!}
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    {!! $page->html !!}
</body>
</html>
