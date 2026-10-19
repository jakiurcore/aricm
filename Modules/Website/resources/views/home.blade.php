@extends('core::layouts.public')

@section('title', setting('institute_name_'.app()->getLocale(), config('app.name')))

@section('content')
    @foreach ($sections as $section)
        @include($section->view, $section->data)
    @endforeach
@endsection
