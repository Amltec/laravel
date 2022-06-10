@extends('templates.admin.index',[
    'dashboard'=>$dashboard??null
])

@if(isset($title))
    @section('title')
    {!! callstr($title??null) !!}
    @endsection
@endif

@if(isset($title_bar))
    @section('title_bar')
    {!! callstr($title_bar) !!}
    @endsection
@endif

@if(isset($description))
    @section('description')
    {!!callstr($description)!!}
    @endsection
@endif

@if(isset($toolbar))
    @section('toolbar-header')
    {!!callstr($toolbar)!!}
    @endsection
@endif


@if(isset($content))
    @section('content-view')
    {!!callstr($content)!!}
    @endsection
@endif


@if(isset($head))
    @push('head')
    {!!callstr($head)!!}
    @endpush
@endif

@if(isset($bottom))
    @push('bottom')
    {!!callstr($bottom)!!}
    @endpush
@endif